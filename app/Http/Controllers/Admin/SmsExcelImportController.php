<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsMonthlyRecord;
use App\Support\ExcelChunkReadFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Throwable;

class SmsExcelImportController extends Controller
{
    private const CHUNK_SIZE = 2000;
    private const BATCH_SIZE = 500;

    private array $requiredHeaders = [
        'Recipient',
        'Status',
        'Description',
        'Credit Deducted',
        'Sent Time',
        'Sender ID',
        'TrackID',
        'Credit Type',
        'Circle',
        'Message',
    ];

    public function index(Request $request)
    {
        $request->validate([
            'month_year' => [
                'nullable',
                'date_format:Y-m',
                'after_or_equal:2026-01',
                'before_or_equal:2030-12',
            ],
        ]);

        $monthYear = $request->input(
            'month_year',
            now()->format('Y-m')
        );

        $date = Carbon::createFromFormat('!Y-m', $monthYear);

        $selectedMonth = $date->month;
        $selectedYear = $date->year;
        $table = strtolower($date->format('MY'));

        $tableExists = Schema::hasTable($table);
        $records = collect();
        $summary = collect();

        if ($tableExists) {
            $records = DB::table($table)
                ->orderBy('id')
                ->paginate(25)
                ->withQueryString();

            $summary = DB::table($table)
                ->select('branch')
                ->selectRaw('COUNT(*) AS total_sms')
                ->selectRaw("
                    SUM(CASE WHEN message_type = 'points_added'
                    THEN 1 ELSE 0 END) AS points_added_count
                ")
                ->selectRaw("
                    COALESCE(SUM(CASE
                        WHEN message_type = 'points_added'
                        THEN points_added ELSE 0
                    END), 0) AS points_added_total
                ")
                ->selectRaw("
                    SUM(CASE WHEN message_type = 'points_redeemed'
                    THEN 1 ELSE 0 END) AS points_redeemed_count
                ")
                ->selectRaw("
                    COALESCE(SUM(CASE
                        WHEN message_type = 'points_redeemed'
                        THEN points_redeemed ELSE 0
                    END), 0) AS points_redeemed_total
                ")
                ->selectRaw("
                    SUM(CASE WHEN message_type IN (
                        'points_added', 'points_redeemed'
                    ) THEN 1 ELSE 0 END) AS total_points_sms_count
                ")
                ->selectRaw("
                    SUM(CASE WHEN message_type = 'redeem_otp'
                    THEN 1 ELSE 0 END) AS otp_redeem_count
                ")
                ->selectRaw("
                    SUM(CASE WHEN message_type = 'otp_profile_updated'
                    THEN 1 ELSE 0 END) AS otp_profile_updated_count
                ")
                ->selectRaw("
                    SUM(CASE WHEN message_type IN (
                        'redeem_otp', 'otp_profile_updated'
                    ) THEN 1 ELSE 0 END) AS total_otp_count
                ")
                ->selectRaw("
                    SUM(CASE WHEN message_type IN (
                        'points_added',
                        'points_redeemed',
                        'redeem_otp',
                        'otp_profile_updated'
                    ) THEN 1 ELSE 0 END) AS total_activity_count
                ")
                ->selectRaw("
                    SUM(CASE WHEN message_type NOT IN (
                        'points_added',
                        'points_redeemed',
                        'redeem_otp',
                        'otp_profile_updated'
                    ) OR message_type IS NULL
                    THEN 1 ELSE 0 END) AS other_count
                ")
                ->groupBy('branch')
                ->orderBy('branch')
                ->get();
        }

        return view('smsexcel.index', compact(
            'records',
            'table',
            'selectedMonth',
            'selectedYear',
            'tableExists',
            'summary'
        ));
    }

    private function parseSentTime($value): ?Carbon
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            $serial = (float) $value;

            if ($serial >= 20000 && $serial <= 80000) {
                try {
                    return Carbon::instance(
                        ExcelDate::excelToDateTimeObject($serial)
                    );
                } catch (Throwable $e) {
                    return null;
                }
            }
        }

        $value = trim((string) $value);

        $formats = [
            'd-M-Y H:i:s',
            'd-M-Y H:i',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
            'd-M-Y',
            'd-m-Y',
            'd/m/Y',
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat(
                    '!' . $format,
                    $value
                );

                $errors = Carbon::getLastErrors();

                if (
                    $parsed !== false &&
                    (
                        $errors === false ||
                        (
                            $errors['warning_count'] === 0 &&
                            $errors['error_count'] === 0
                        )
                    )
                ) {
                    return $parsed;
                }
            } catch (Throwable $e) {
                // Try the next format.
            }
        }

        return null;
    }

    public function upload(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'excel_file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:20480',
            ],
        ]);

        $processed = 0;
        $inserted = 0;
        $updated = 0;
        $invalid = 0;
        $failed = 0;
        $errors = [];

        $filePath = $request->file('excel_file')->getRealPath();

        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);

            $filter = new ExcelChunkReadFilter();
            $reader->setReadFilter($filter);

            $sheetInfo = $reader->listWorksheetInfo($filePath);

            if (empty($sheetInfo)) {
                return back()->with('error', 'The Excel file is empty.');
            }

            $highestRow = (int) $sheetInfo[0]['totalRows'];
            $lastColumn = $sheetInfo[0]['lastColumnLetter'];

            if ($highestRow < 2) {
                return back()->with(
                    'error',
                    'The Excel file contains no data rows.'
                );
            }

            // Read only the heading row first.
            $filter->setRows(1, 1);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            $headerRow = $sheet->rangeToArray(
                'A1:' . $lastColumn . '1',
                null,
                true,
                true,
                false
            )[0] ?? [];

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet);

            $headers = array_map(
                fn ($value) => strtolower(trim((string) $value)),
                $headerRow
            );

            $columnIndexes = [];

            foreach ($this->requiredHeaders as $header) {
                $index = array_search(
                    strtolower($header),
                    $headers,
                    true
                );

                if ($index === false) {
                    return back()->with(
                        'error',
                        'Missing Excel column: ' . $header
                    );
                }

                $columnIndexes[$header] = $index;
            }

            $preparedTables = [];

            for (
                $startRow = 2;
                $startRow <= $highestRow;
                $startRow += self::CHUNK_SIZE
            ) {
                $endRow = min(
                    $startRow + self::CHUNK_SIZE - 1,
                    $highestRow
                );

                $filter->setRows($startRow, self::CHUNK_SIZE);

                $spreadsheet = $reader->load($filePath);
                $sheet = $spreadsheet->getActiveSheet();

                $rows = $sheet->rangeToArray(
                    'A' . $startRow . ':' .
                    $lastColumn . $endRow,
                    null,
                    true,
                    true,
                    false
                );

                $batches = [];

                foreach ($rows as $offset => $row) {
                    $excelRowNumber = $startRow + $offset;

                    $nonEmpty = false;

                    foreach ($row as $value) {
                        if (
                            $value !== null &&
                            trim((string) $value) !== ''
                        ) {
                            $nonEmpty = true;
                            break;
                        }
                    }

                    if (!$nonEmpty) {
                        continue;
                    }

                    $processed++;

                    $getValue = function (string $header) use (
                        $row,
                        $columnIndexes
                    ) {
                        $value = $row[$columnIndexes[$header]] ?? null;

                        if ($value === null) {
                            return null;
                        }

                        $value = trim((string) $value);

                        return $value === '' ? null : $value;
                    };

                    $rawSentTime =
                        $row[$columnIndexes['Sent Time']] ?? null;

                    $parsedDate = $this->parseSentTime($rawSentTime);
                    $trackId = $getValue('TrackID');

                    if (!$parsedDate) {
                        $invalid++;
                        $this->addError(
                            $errors,
                            "Row {$excelRowNumber}: Invalid Sent Time."
                        );
                        continue;
                    }

                    if ($trackId === null) {
                        $invalid++;
                        $this->addError(
                            $errors,
                            "Row {$excelRowNumber}: Missing TrackID."
                        );
                        continue;
                    }

                    $dateString = $parsedDate->format('Y-m-d');
                    $table = SmsMonthlyRecord::monthlyTableName(
                        $dateString
                    );

                    if (!isset($preparedTables[$table])) {
                        SmsMonthlyRecord::ensureMonthlyTable($table);
                        $preparedTables[$table] = true;
                    }

                    $message = $getValue('Message');

                    // Keep your existing message parser unchanged.
                    $parsedMessage = SmsMonthlyRecord::parseMessage(
                        $message
                    );


                    $data = [
                        'recipient' => $getValue('Recipient'),
                        'status' => $getValue('Status'),
                        'description' => $getValue('Description'),
                        'credit_deducted' =>
                            $getValue('Credit Deducted'),
                        'sent_time' =>
                            $parsedDate->format('d-M-Y H:i:s'),
                        'sender_id' => $getValue('Sender ID'),
                        'track_id' => $trackId,
                        'credit_type' => $getValue('Credit Type'),
                        'circle' => $getValue('Circle'),
                        'message' => $message,
                        'message_date' => $dateString,
                        'message_type' =>
                            $parsedMessage['message_type'],
                        'card_number' =>
                            $parsedMessage['card_number'],
                        'points_added' =>
                            $parsedMessage['points_added'],
                        'points_redeemed' =>
                            $parsedMessage['points_redeemed'],
                        'total_points' =>
                            $parsedMessage['total_points'],
                        'branch' => $parsedMessage['branch'],
                        'sms_date' => $parsedMessage['sms_date'],
                    ];

                    $batches[$table][] = $data;

                    // Flush full batches to the database.
                    if (
                        count($batches[$table]) >= self::BATCH_SIZE
                    ) {
                        $result = $this->saveBatch(
                            $table,
                            $batches[$table]
                        );

                        $inserted += $result['inserted'];
                        $updated += $result['updated'];
                        $failed += $result['failed'];

                        foreach ($result['errors'] as $error) {
                            $this->addError($errors, $error);
                        }

                        $batches[$table] = [];
                    }
                }

                // Save all remaining rows from this Excel chunk.
                foreach ($batches as $table => $batch) {
                    if (empty($batch)) {
                        continue;
                    }

                    $result = $this->saveBatch($table, $batch);

                    $inserted += $result['inserted'];
                    $updated += $result['updated'];
                    $failed += $result['failed'];

                    foreach ($result['errors'] as $error) {
                        $this->addError($errors, $error);
                    }
                }

                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $sheet, $rows, $batches);
            }

            return back()->with('import_result', [
                'processed' => $processed,
                'inserted' => $inserted,
                'updated' => $updated,
                'invalid' => $invalid,
                'failed' => $failed,
                'errors' => $errors,
                'errors_truncated' => max(
                    0,
                    $invalid + $failed - count($errors)
                ),
            ]);
        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Unable to import Excel file. Check the file format and server logs.'
            );
        }
    }

    /**
     * Save a batch without performing a SELECT for every row.
     *
     * Existing matching rows are updated individually.
     * New rows are inserted in bulk.
     *
     * Duplicate key: sent_time + track_id.
     */
    private function saveBatch(string $table, array $batch): array
    {
        $result = [
            'inserted' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        if (empty($batch)) {
            return $result;
        }

        // Keep the last occurrence of each key in this batch.
        $uniqueBatch = [];

        foreach ($batch as $data) {
            $key = hash('sha256',
                $data['sent_time'] . "\0" . $data['track_id']
            );

            $uniqueBatch[$key] = $data;
        }

        $batch = array_values($uniqueBatch);

        $trackIds = array_values(array_unique(array_column(
            $batch,
            'track_id'
        )));

        // One lookup for this batch rather than one lookup per row.
        $existingRows = DB::table($table)
            ->whereIn('track_id', $trackIds)
            ->get(['id', 'sent_time', 'track_id']);

        $existingMap = [];

        foreach ($existingRows as $existing) {
            $key = hash('sha256',
                $existing->sent_time . "\0" . $existing->track_id
            );

            // If historical duplicates exist, choose the first ID.
            $existingMap[$key] ??= $existing->id;
        }

        $newRows = [];

        foreach ($batch as $data) {
            $key = hash('sha256',
                $data['sent_time'] . "\0" . $data['track_id']
            );

            if (isset($existingMap[$key])) {
                try {
                    DB::table($table)
                        ->where('id', $existingMap[$key])
                        ->update(array_merge($data, [
                            'updated_at' => now(),
                        ]));

                    $result['updated']++;
                } catch (Throwable $e) {
                    report($e);
                    $result['failed']++;
                    $result['errors'][] =
                        "A matching record could not be updated.";
                }
            } else {
                $newRows[] = array_merge($data, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (!empty($newRows)) {
            try {
                // A single insert query for all new rows in this batch.
                DB::table($table)->insert($newRows);
                $result['inserted'] += count($newRows);
            } catch (Throwable $e) {
                report($e);

                // Fall back to individual inserts to identify failures.
                foreach ($newRows as $row) {
                    try {
                        DB::table($table)->insert($row);
                        $result['inserted']++;
                    } catch (Throwable $rowError) {
                        report($rowError);
                        $result['failed']++;
                        $result['errors'][] =
                            'A new record could not be inserted.';
                    }
                }
            }
        }

        return $result;
    }

    private function addError(array &$errors, string $message): void
    {
        // Avoid holding thousands of error messages in PHP memory.
        if (count($errors) < 100) {
            $errors[] = $message;
        }
    }

    private function getExportData(Request $request): array
    {
        $request->validate([
            'month_year' => [
                'required',
                'date_format:Y-m',
                'after_or_equal:2026-01',
                'before_or_equal:2030-12',
            ],
        ]);

        $date = Carbon::createFromFormat(
            '!Y-m',
            $request->input('month_year')
        );

        $table = strtolower($date->format('MY'));

        abort_unless(
            Schema::hasTable($table),
            404,
            'Monthly table not found.'
        );

        $summary = DB::table($table)
            ->select('branch')
            ->selectRaw('COUNT(*) AS total_sms')
            ->selectRaw("
                SUM(CASE WHEN message_type = 'points_added'
                THEN 1 ELSE 0 END) AS points_added_count
            ")
            ->selectRaw("
                SUM(CASE WHEN message_type = 'points_redeemed'
                THEN 1 ELSE 0 END) AS points_redeemed_count
            ")
            ->selectRaw("
                SUM(CASE WHEN message_type IN (
                    'points_added', 'points_redeemed'
                ) THEN 1 ELSE 0 END) AS total_points_sms_count
            ")
            ->selectRaw("
                SUM(CASE WHEN message_type = 'redeem_otp'
                THEN 1 ELSE 0 END) AS otp_redeem_count
            ")
            ->selectRaw("
                SUM(CASE WHEN message_type = 'otp_profile_updated'
                THEN 1 ELSE 0 END) AS otp_profile_updated_count
            ")
            ->selectRaw("
                SUM(CASE WHEN message_type IN (
                    'redeem_otp', 'otp_profile_updated'
                ) THEN 1 ELSE 0 END) AS total_otp_count
            ")
            ->selectRaw("
                SUM(CASE WHEN message_type IN (
                    'points_added',
                    'points_redeemed',
                    'redeem_otp',
                    'otp_profile_updated'
                ) THEN 1 ELSE 0 END) AS total_activity_count
            ")
            ->groupBy('branch')
            ->orderBy('branch')
            ->get();

        return [
            'table' => $table,
            'monthYear' => $date->format('F Y'),
            'summary' => $summary,
        ];
    }

    public function exportExcel(Request $request)
    {
        $data = $this->getExportData($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Monthly SMS Summary');

        $sheet->fromArray([[
            'Branch',
            'Points Added Count',
            'Points Redeemed Count',
            'Total Points SMS Count',
            'OTP Redeem Count',
            'OTP Profile Updated Count',
            'Total OTP Count',
            'Total Activity Count',
        ]], null, 'A1');

        $row = 2;

        foreach ($data['summary'] as $item) {
            $sheet->fromArray([[
                $item->branch,
                $item->points_added_count,
                $item->points_redeemed_count,
                $item->total_points_sms_count,
                $item->otp_redeem_count,
                $item->otp_profile_updated_count,
                $item->total_otp_count,
                $item->total_activity_count,
            ]], null, "A{$row}");

            $row++;
        }

        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'sms-summary-' . $data['table'] . '.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            $filename,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    public function exportPdf(Request $request)
    {
        $data = $this->getExportData($request);

        $pdf = Pdf::loadView(
            'smsexcel.summary-pdf',
            $data
        )->setPaper('a4', 'landscape');

        return $pdf->download(
            'sms-summary-' . $data['table'] . '.pdf'
        );
    }
}
