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
use App\Support\SmsTableHelper;
use App\Support\SmsMessageParser;

class SmsExcelImportController extends Controller
{
    private const CHUNK_SIZE = 10000;
    private const BATCH_SIZE = 1000;


private array $requiredHeaders = [
    'Status',
    'Description',
    'Sent Time',
    'TrackID',
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
    

    // Get monthly table name
    $table = SmsTableHelper::getTableName(
        $date->format('Y-m-d')
    );

    $tableExists = Schema::hasTable($table);

    $records = collect();
    $summary = collect();
    $grandTotal = [];

    if ($tableExists) {

        // Monthly records
        $records = DB::table($table)
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        // Branch-wise summary
        $summary = DB::table($table)
            ->select('branch')

            // Points Added
            ->selectRaw("
                COUNT(CASE
                    WHEN message_type = 'points_added'
                    THEN 1 END
                ) AS points_added_count
            ")

            // Points Redeemed
            ->selectRaw("
                COUNT(CASE
                    WHEN message_type = 'points_redeemed'
                    THEN 1 END
                ) AS points_redeemed_count
            ")

            // Points Returned
            ->selectRaw("
                COUNT(CASE
                    WHEN message_type = 'points_returned'
                    THEN 1 END
                ) AS points_returned_count
            ")

            // Total Points Count
            ->selectRaw("
                COUNT(CASE
                    WHEN message_type IN (
                        'points_added',
                        'points_redeemed',
                        'points_returned'
                    )
                    THEN 1 END
                ) AS total_points_count
            ")

            // OTP Redeem
            ->selectRaw("
                COUNT(CASE
                    WHEN message_type = 'redeem_otp'
                    THEN 1 END
                ) AS otp_redeem_count
            ")

            // OTP Profile Updated
            ->selectRaw("
                COUNT(CASE
                    WHEN message_type = 'otp_profile_updated'
                    THEN 1 END
                ) AS otp_profile_updated_count
            ")

            // Total OTP Count
            ->selectRaw("
                COUNT(CASE
                    WHEN message_type IN (
                        'redeem_otp',
                        'otp_profile_updated'
                    )
                    THEN 1 END
                ) AS total_otp_count
            ")

            // Overall Count
            ->selectRaw("
                COUNT(CASE
                    WHEN message_type IN (
                        'points_added',
                        'points_redeemed',
                        'points_returned',
                        'redeem_otp',
                        'otp_profile_updated'
                    )
                    THEN 1 END
                ) AS overall_count
            ")

            ->groupBy('branch')
            ->orderBy('branch')
            ->get();
    }
    // Grand Total - All Branches
$grandTotal = [
    'points_added_count' => $summary->sum('points_added_count'),

    'points_redeemed_count' => $summary->sum('points_redeemed_count'),

    'points_returned_count' => $summary->sum('points_returned_count'),

    'total_points_count' => $summary->sum('total_points_count'),

    'otp_redeem_count' => $summary->sum('otp_redeem_count'),

    'otp_profile_updated_count' => $summary->sum('otp_profile_updated_count'),

    'total_otp_count' => $summary->sum('total_otp_count'),

    'overall_count' => $summary->sum('overall_count'),
];

    return view('smsexcel.index', compact(
        'records',
        'table',
        'selectedMonth',
        'selectedYear',
        'tableExists',
        'summary',
        'grandTotal'
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
                'max:204800',
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

    


$rawSentTime = $getValue('Sent Time');
$trackId = $getValue('TrackID');

$parsedDate = $this->parseSentTime($rawSentTime);

if (!$parsedDate) {
    $invalid++;

    $this->addError(
        $errors,
        "Row {$excelRowNumber}: Invalid Sent Time."
    );

    continue;
}

if (!$trackId) {
    $invalid++;

    $this->addError(
        $errors,
        "Row {$excelRowNumber}: Missing TrackID."
    );

    continue;
}

$message = $getValue('Message');

$parsedMessage = SmsMessageParser::parse($message);

if (!$parsedMessage['message_type']) {
    $invalid++;

    $this->addError(
        $errors,
        "Row {$excelRowNumber}: Unknown SMS message format."
    );

    continue;
}

if (!$parsedMessage['branch']) {
    $invalid++;

    $this->addError(
        $errors,
        "Row {$excelRowNumber}: Branch not found."
    );

    continue;
}

// Store Sent Time in MySQL DATETIME format.
$sentTime = $parsedDate->format('Y-m-d H:i:s');

// Create monthly table based on Sent Time.
$table = SmsTableHelper::getTableName($sentTime);

if (!isset($preparedTables[$table])) {

    SmsTableHelper::createMonthlyTable($table);

    $preparedTables[$table] = true;
}

// Save only the required columns.
$data = [
    'status' => $getValue('Status'),

    'description' => $getValue('Description'),

    'sent_time' => $sentTime,

    'track_id' => $trackId,

    'circle' => $getValue('Circle'),

    'message' => $message,

    'message_type' => $parsedMessage['message_type'],

    'branch' => $parsedMessage['branch'],
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
    // private function saveBatch(string $table, array $batch): array
    // {
    //     $result = [
    //         'inserted' => 0,
    //         'updated' => 0,
    //         'failed' => 0,
    //         'errors' => [],
    //     ];

    //     if (empty($batch)) {
    //         return $result;
    //     }

    //     // Keep the last occurrence of each key in this batch.
    //     $uniqueBatch = [];

    //     foreach ($batch as $data) {
    //         $key = hash('sha256',
    //             $data['sent_time'] . "\0" . $data['track_id']
    //         );

    //         $uniqueBatch[$key] = $data;
    //     }

    //     $batch = array_values($uniqueBatch);

    //     $trackIds = array_values(array_unique(array_column(
    //         $batch,
    //         'track_id'
    //     )));

    //     // One lookup for this batch rather than one lookup per row.
    //     $existingRows = DB::table($table)
    //         ->whereIn('track_id', $trackIds)
    //         ->get(['id', 'sent_time', 'track_id']);

    //     $existingMap = [];

    //     foreach ($existingRows as $existing) {
    //         $key = hash('sha256',
    //             $existing->sent_time . "\0" . $existing->track_id
    //         );

    //         // If historical duplicates exist, choose the first ID.
    //         $existingMap[$key] ??= $existing->id;
    //     }

    //     $newRows = [];

    //     foreach ($batch as $data) {
    //         $key = hash('sha256',
    //             $data['sent_time'] . "\0" . $data['track_id']
    //         );

    //         if (isset($existingMap[$key])) {
    //             try {
    //                 DB::table($table)
    //                     ->where('id', $existingMap[$key])
    //                     ->update(array_merge($data, [
    //                         'updated_at' => now(),
    //                     ]));

    //                 $result['updated']++;
    //             } catch (Throwable $e) {
    //                 report($e);
    //                 $result['failed']++;
    //                 $result['errors'][] =
    //                     "A matching record could not be updated.";
    //             }
    //         } else {
    //             $newRows[] = array_merge($data, [
    //                 'created_at' => now(),
    //                 'updated_at' => now(),
    //             ]);
    //         }
    //     }

    //     if (!empty($newRows)) {
    //         try {
    //             // A single insert query for all new rows in this batch.
    //             DB::table($table)->insert($newRows);
    //             $result['inserted'] += count($newRows);
    //         } catch (Throwable $e) {
    //             report($e);

    //             // Fall back to individual inserts to identify failures.
    //             foreach ($newRows as $row) {
    //                 try {
    //                     DB::table($table)->insert($row);
    //                     $result['inserted']++;
    //                 } catch (Throwable $rowError) {
    //                     report($rowError);
    //                     $result['failed']++;
    //                     $result['errors'][] =
    //                         'A new record could not be inserted.';
    //                 }
    //             }
    //         }
    //     }

    //     return $result;
    // }




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

    try {
        // 1. Remove duplicate TrackID + Sent Time combinations
        // within this batch. Keep the last occurrence.
        $uniqueBatch = [];

        foreach ($batch as $row) {
            $key = json_encode([
                $row['track_id'],
                $row['sent_time'],
            ]);

            $uniqueBatch[$key] = $row;
        }

        $batch = array_values($uniqueBatch);

        // 2. Get unique TrackIDs.
        $trackIds = array_values(array_unique(
            array_column($batch, 'track_id')
        ));

        // 3. Fetch existing records in one query.
        $existingRows = DB::table($table)
            ->whereIn('track_id', $trackIds)
            ->get(['track_id', 'sent_time']);

        // 4. Build a map of existing composite keys.
        $existingMap = [];

        foreach ($existingRows as $existing) {
            $key = json_encode([
                $existing->track_id,
                $existing->sent_time,
            ]);

            $existingMap[$key] = true;
        }

        // 5. Prepare timestamps and calculate counts.
        $now = now();
        $inserted = 0;
        $updated = 0;

        foreach ($batch as &$row) {
            $key = json_encode([
                $row['track_id'],
                $row['sent_time'],
            ]);

            if (isset($existingMap[$key])) {
                $updated++;
            } else {
                $inserted++;
            }

            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        unset($row);

        // 6. Insert new rows and update existing rows.
        DB::table($table)->upsert(
            $batch,
            ['track_id', 'sent_time'],
            [
                'status',
                'description',
                'circle',
                'message',
                'message_type',
                'branch',
                'updated_at',
            ]
        );

        $result['inserted'] = $inserted;
        $result['updated'] = $updated;

    } catch (Throwable $e) {
        report($e);

        $result['failed'] = count($batch);

        $result['errors'][] =
            'Batch database operation failed. Check Laravel logs.';
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

    $table = SmsTableHelper::getTableName(
        $date->format('Y-m-d')
    );

    abort_unless(
        Schema::hasTable($table),
        404,
        'Monthly table not found.'
    );

    $summary = DB::table($table)
        ->select('branch')

        ->selectRaw("
            SUM(CASE WHEN message_type = 'points_added'
            THEN 1 ELSE 0 END) AS points_added_count
        ")

        ->selectRaw("
            SUM(CASE WHEN message_type = 'points_redeemed'
            THEN 1 ELSE 0 END) AS points_redeemed_count
        ")

        ->selectRaw("
            SUM(CASE WHEN message_type = 'points_returned'
            THEN 1 ELSE 0 END) AS points_returned_count
        ")

        ->selectRaw("
            SUM(CASE WHEN message_type IN (
                'points_added',
                'points_redeemed',
                'points_returned'
            ) THEN 1 ELSE 0 END) AS total_points_count
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
                'redeem_otp',
                'otp_profile_updated'
            ) THEN 1 ELSE 0 END) AS total_otp_count
        ")

        ->selectRaw("
            SUM(CASE WHEN message_type IN (
                'points_added',
                'points_redeemed',
                'points_returned',
                'redeem_otp',
                'otp_profile_updated'
            ) THEN 1 ELSE 0 END) AS overall_count
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

    // Excel headings
    $sheet->fromArray([[
        'Branch',
        'Points Added Count',
        'Points Redeemed Count',
        'Points Returned Count',
        'Total Points Count',
        'OTP Redeem Count',
        'OTP Profile Updated Count',
        'Total OTP Count',
        'Overall Count',
    ]], null, 'A1');

    // Insert branch-wise records
    $row = 2;

    foreach ($data['summary'] as $item) {

        $sheet->fromArray([[
    $item->branch,
    (int) $item->points_added_count,
    (int) $item->points_redeemed_count,
    (int) $item->points_returned_count,
    (int) $item->total_points_count,
    (int) $item->otp_redeem_count,
    (int) $item->otp_profile_updated_count,
    (int) $item->total_otp_count,
    (int) $item->overall_count,
]], null, "A{$row}", true);

        $row++;
    }

    // Header formatting
    $sheet->getStyle('A1:I1')->getFont()->setBold(true);

    $sheet->getStyle('A1:I1')->getAlignment()
        ->setHorizontal(
            \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
        );

    // Highlight calculated total columns
    if ($row > 2) {
        $sheet->getStyle("E2:E" . ($row - 1))
            ->getFont()->setBold(true);

        $sheet->getStyle("H2:H" . ($row - 1))
            ->getFont()->setBold(true);

        $sheet->getStyle("I2:I" . ($row - 1))
            ->getFont()->setBold(true);
    }

    // Auto column width
    foreach (range('A', 'I') as $column) {
        $sheet->getColumnDimension($column)
            ->setAutoSize(true);
    }

    // Freeze header
    $sheet->freezePane('A2');

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
