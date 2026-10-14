<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsMonthlyRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;
use Throwable;

class SmsExcelImportController extends Controller
{
    public function index()
    {
        return view('smsexcel.index');
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

            // Read all rows. First row contains column headings.
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
                    return back()->with(
                        'error',
                        'Missing Excel column: ' . $header
                    );
                }
            }

            // Map column headings to their indexes.
            $columnIndexes = [];

            foreach ($requiredHeaders as $header) {
                $columnIndexes[$header] = array_search(
                    strtolower($header),
                    $headers,
                    true
                );
            }

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

                    return $value === null
                        ? null
                        : trim((string) $value);
                };

                /*
                 * IMPORTANT:
                 * The date is taken ONLY from Sent Time.
                 * Message is stored unchanged and is never parsed.
                 */
                $sentTime = $getValue('Sent Time');
                $date = null;

                if (
                    $sentTime !== null &&
                    $sentTime !== ''
                ) {
                    try {
                        /*
                         * Handle Excel date/time serial numbers.
                         * This conversion is used when the cell
                         * contains a numeric Excel date value.
                         */
                        $sentTimeIndex = $columnIndexes['Sent Time'];
                        $rawSentTime = $row[$sentTimeIndex] ?? null;

                        if (
                            is_numeric($rawSentTime) &&
                            is_int($rawSentTime) === false &&
                            is_float($rawSentTime)
                        ) {
                            $parsedDate = Carbon::instance(
                                ExcelDate::excelToDateTimeObject(
                                    (float) $rawSentTime
                                )
                            );

                            $date = $parsedDate->format('Y-m-d');
                        } else {
                           
$formats = [
    '!d-M-Y H:i:s', // 27-Sep-2026 09:07:42
    '!d-M-Y H:i',
    '!d-m-Y H:i:s',
    '!d-m-Y H:i',
    '!d/m/Y H:i:s',
    '!d/m/Y H:i',
    '!Y-m-d H:i:s',
    '!Y-m-d H:i',
    '!Y-m-d',
    '!d-M-Y',
    '!d-m-Y',
    '!d/m/Y',
];


                            foreach ($formats as $format) {
                                $parsedDate = Carbon::createFromFormat(
                                    '!' . $format,
                                    $sentTime
                                );

                                $dateErrors = Carbon::getLastErrors();

                                if (
                                    $parsedDate &&
                                    (
                                        $dateErrors === false ||
                                        (
                                            $dateErrors['warning_count'] === 0 &&
                                            $dateErrors['error_count'] === 0
                                        )
                                    )
                                ) {
                                    $date = $parsedDate->format('Y-m-d');
                                    break;
                                }
                            }
                        }
                    } catch (Throwable $e) {
                        // The row will be reported as invalid below.
                        $date = null;
                    }
                }

                // No valid Sent Time means the row cannot be routed.
                if (!$date) {
                    $invalid++;
                    $errors[] =
                        "Row {$excelRowNumber}: Invalid or missing Sent Time.";
                    continue;
                }

                // Example: 2026-09-27 => sep2026.
                $table = SmsMonthlyRecord::monthlyTableName($date);
try {
    // First, create the table if it doesn't exist.
    SmsMonthlyRecord::ensureMonthlyTable($table);

    // Next, insert the Excel row.
    SmsMonthlyRecord::insertMonthlyRow($table, [
        'recipient'       => $getValue('Recipient'),
        'status'          => $getValue('Status'),
        'description'     => $getValue('Description'),
        'credit_deducted' => $getValue('Credit Deducted'),
        'sent_time'       => $getValue('Sent Time'),
        'sender_id'       => $getValue('Sender ID'),
        'track_id'        => $getValue('TrackID'),
        'credit_type'     => $getValue('Credit Type'),
        'circle'          => $getValue('Circle'),
        'message'         => $getValue('Message'),
        'message_date'    => $date,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $inserted++;

} catch (\Throwable $e) {
    $failed++;

    $errors[] = "Row {$excelRowNumber}: Database insert failed.";

    report($e);
}
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return back()->with('import_result', [
                'processed' => $processed,
                'inserted'  => $inserted,
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
