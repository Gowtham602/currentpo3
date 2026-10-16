<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsMonthlyRecord;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;
use Throwable;

class SmsExcelImportController extends Controller
{
   

public function index(Request $request)
{
    $request->validate([
        'month' => 'nullable|integer|min:1|max:12',
        'year'  => 'nullable|integer|min:2020|max:2100',
    ]);

    $records = null;
    $table = null;
    $selectedMonth = $request->input('month');
    $selectedYear = $request->input('year');
    $tableExists = false;
    $summary = collect();

    if ($selectedMonth && $selectedYear) {
        $table = strtolower(
            Carbon::createFromDate(
                (int) $selectedYear,
                (int) $selectedMonth,
                1
            )->format('MY')
        );

        $tableExists = Schema::hasTable($table);

        if ($tableExists) {
            SmsMonthlyRecord::ensureMonthlyTable($table);

            $records = DB::table($table)
                ->orderBy('id', 'asc')
                ->paginate(25)
                ->withQueryString();

            $summary = DB::table($table)
                ->select('branch')
                ->selectRaw("
                    COUNT(CASE
                        WHEN message_type = 'points_added'
                        THEN 1
                    END) AS points_added_count
                ")
                ->selectRaw("
                    COALESCE(SUM(points_added), 0)
                    AS points_added_total
                ")
                ->selectRaw("
                    COUNT(CASE
                        WHEN message_type = 'points_redeemed'
                        THEN 1
                    END) AS points_redeemed_count
                ")
                ->selectRaw("
                    COALESCE(SUM(points_redeemed), 0)
                    AS points_redeemed_total
                ")
                ->selectRaw("
                    COUNT(CASE
                        WHEN message_type = 'redeem_otp'
                        THEN 1
                    END) AS otp_count
                ")
                ->selectRaw("
                    COUNT(CASE
                        WHEN message_type = 'other'
                        THEN 1
                    END) AS other_count
                ")
                ->selectRaw('COUNT(*) AS total_sms')
                ->groupBy('branch')
                ->orderBy('branch', 'asc')
                ->get();
        }
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





    /**
     * Parse Excel Sent Time into a Carbon date.
     */
    private function parseSentTime($rawValue): ?Carbon
    {
        if ($rawValue === null || trim((string) $rawValue) === '') {
            return null;
        }

        // Excel date serial numbers are usually numeric values.
        if (is_numeric($rawValue)) {
            $serial = (float) $rawValue;

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

        
        $value = trim((string) $rawValue);

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
                $date = Carbon::createFromFormat(
                    '!' . $format,
                    $value
                );

                $errors = Carbon::getLastErrors();

                if (
                    $date !== false &&
                    (
                        $errors === false ||
                        (
                            $errors['warning_count'] === 0 &&
                            $errors['error_count'] === 0
                        )
                    )
                ) {
                    return $date;
                }
            } catch (Throwable $e) {
                // Try the next supported format.
            }
        }

        return null;
    }

    public function upload(Request $request)
    {
        $request->validate([
            'excel_file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:20480',
            ],
        ]);

        $processed = 0;
        $inserted = 0;
        $updated = 0;
        $invalid = 0;
        $failed = 0;
        $errors = [];

        $requiredHeaders = [
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

        try {
            $spreadsheet = IOFactory::load(
                $request->file('excel_file')->getRealPath()
            );

            $sheet = $spreadsheet->getActiveSheet();      

            // Read Excel rows. Row 1 contains the headings.
            $rows = $sheet->toArray(null, true, true, false);

            if (empty($rows)) {
                return back()->with(
                    'error',
                    'The Excel file is empty.'
                );
            }

            $headers = array_map(
                fn ($value) => strtolower(trim((string) $value)),
                $rows[0]
            );

            $expectedHeaders = array_map(
                fn ($value) => strtolower($value),
                $requiredHeaders
            );

            foreach ($expectedHeaders as $header) {
                if (!in_array($header, $headers, true)) {
                    $spreadsheet->disconnectWorksheets();

                    return back()->with(
                        'error',
                        'Missing Excel column: ' . $header
                    );
                }
            }

            // Map each required heading to its Excel column index.
            $columnIndexes = [];

            foreach ($requiredHeaders as $header) {
                $columnIndexes[$header] = array_search(
                    strtolower($header),
                    $headers,
                    true
                );
            }

            // Cache monthly table names already checked/created.
            $preparedTables = [];

            foreach (array_slice($rows, 1) as $index => $row) {
                $excelRowNumber = $index + 2;

                // Skip completely empty rows.
                if (
                    count(array_filter(
                        $row,
                        fn ($value) =>
                            $value !== null &&
                            trim((string) $value) !== ''
                    )) === 0
                ) {
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

                /*
                 * Parse Sent Time.
                 * The original Excel value is used for parsing,
                 * including Excel numeric date serials.
                 */
                $rawSentTime = $row[
                    $columnIndexes['Sent Time']
                ] ?? null;

                $parsedDate = $this->parseSentTime($rawSentTime);

                if (!$parsedDate) {
                    $invalid++;

                    $errors[] =
                        "Row {$excelRowNumber}: Invalid or missing Sent Time.";

                    continue;
                }

                $trackId = $getValue('TrackID');

                if ($trackId === null) {
                    $invalid++;

                    $errors[] =
                        "Row {$excelRowNumber}: Missing TrackID.";

                    continue;
                }

                /*
                 * Normalize Sent Time so the same timestamp
                 * is stored consistently:
                 * 27-Sep-2026 09:07:42
                 */
                $sentTime = $parsedDate->format('d-M-Y H:i:s');

                $date = $parsedDate->format('Y-m-d');

                // Example: 2026-09-27 => sep2026.
                $table = SmsMonthlyRecord::monthlyTableName($date);

                try {
                    // Create each monthly table only once per upload.
                    if (!isset($preparedTables[$table])) {
                        SmsMonthlyRecord::ensureMonthlyTable($table);

                        $preparedTables[$table] = true;
                    }

                    

$message = $getValue('Message');

$parsedMessage = SmsMonthlyRecord::parseMessage($message);

$data = [
    'recipient'       => $getValue('Recipient'),
    'status'          => $getValue('Status'),
    'description'     => $getValue('Description'),
    'credit_deducted' => $getValue('Credit Deducted'),
    'sent_time'       => $sentTime,
    'sender_id'       => $getValue('Sender ID'),
    'track_id'        => $trackId,
    'credit_type'     => $getValue('Credit Type'),
    'circle'          => $getValue('Circle'),
    'message'         => $message,
    'message_date'    => $date,

    // Extracted message information.
    'message_type'    => $parsedMessage['message_type'],
    'card_number'     => $parsedMessage['card_number'],
    'points_added'    => $parsedMessage['points_added'],
    'points_redeemed' => $parsedMessage['points_redeemed'],
    'total_points'    => $parsedMessage['total_points'],
    'branch'          => $parsedMessage['branch'],
    'sms_date'        => $parsedMessage['sms_date'],
];

$action = SmsMonthlyRecord::insertMonthlyRow(
    $table,
    $data
);

if ($action === 'updated') {
    $updated++;
} else {
    $inserted++;
}


                } catch (Throwable $e) {
                    $failed++;

                    $errors[] =
                        "Row {$excelRowNumber}: Database save failed.";

                    report($e);
                }
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return back()->with('import_result', [
                'processed' => $processed,
                'inserted'  => $inserted,
                'updated'   => $updated,
                'invalid'   => $invalid,
                'failed'    => $failed,
                'errors'    => $errors,
            ]);

        } catch (Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Unable to read the Excel file. Check the file format and try again.'
            );
        }
    }
}
