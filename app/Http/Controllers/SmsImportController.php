<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use App\Support\SmsTableHelper;
use App\Support\ExcelChunkReadFilter;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

use Carbon\Carbon;
use Throwable;

class SmsImportController extends Controller
{
    private int $chunkSize = 2000;
    private int $insertSize = 1000;

    /*
    |--------------------------------------------------------------------------
    | IMPORT PAGE
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return view('sms.import');
    }

    /*
    |--------------------------------------------------------------------------
    | MAIN IMPORT
    |--------------------------------------------------------------------------
    */

    public function import(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'excel_file' => 'required|file|extensions:xlsx,xls,csv|max:512000',
        ]);

        $file = $request->file('excel_file');

        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only XLSX, XLS and CSV files are allowed.',
            ], 422);
        }

        $filePath = $file->store('sms-imports', 'local');

        $fullPath = Storage::disk('local')->path($filePath);

        $stats = [
            'total_rows' => 0,
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'failed' => 0,
        ];

        $createdTables = [];

        try {

            if ($extension === 'csv') {

                $this->importCsv(
                    $fullPath,
                    $stats,
                    $createdTables
                );

            } else {

                $this->importExcel(
                    $fullPath,
                    $stats,
                    $createdTables
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'SMS file imported successfully.',
                'total_rows' => $stats['total_rows'],
                'processed' => $stats['processed'],
                'inserted' => $stats['inserted'],
                'updated' => $stats['updated'],
                'failed' => $stats['failed'],
            ]);

        } catch (Throwable $e) {

            Log::error('SMS Import Error', [
                'message' => $e->getMessage(),
                'file' => $fullPath,
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SMS import failed: ' . $e->getMessage(),
            ], 500);

        } finally {

            Storage::disk('local')->delete($filePath);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CSV IMPORT
    |--------------------------------------------------------------------------
    */

    private function importCsv(
        string $path,
        array &$stats,
        array &$createdTables
    ): void {

        $handle = fopen($path, 'r');

        if (!$handle) {
            throw new \RuntimeException('Unable to open CSV file.');
        }

        try {

            $headers = fgetcsv($handle, 0, ',', '"', '\\');

            if (!$headers) {
                throw new \RuntimeException('CSV file is empty.');
            }

            $headerMap = $this->makeHeaderMap($headers);

            $this->validateHeaders($headerMap);

            $batch = [];

            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {

                if ($row === [null] || $row === []) {
                    continue;
                }

                $stats['total_rows']++;

                $record = $this->makeRecordFromRow(
                    $row,
                    $headerMap,
                    false
                );

                if (!$record) {
                    $stats['failed']++;
                    continue;
                }

                $stats['processed']++;

                $table = SmsTableHelper::getTableName(
                    $record['sent_time']
                );

                if (!isset($createdTables[$table])) {

                    SmsTableHelper::createMonthlyTable($table);

                    $createdTables[$table] = true;
                }

                $batch[$table][] = $record;

                if (count($batch[$table]) >= $this->insertSize) {

                    $this->insertBatch(
                        $table,
                        $batch[$table],
                        $stats
                    );

                    $batch[$table] = [];
                }
            }

            // Insert remaining records

            foreach ($batch as $table => $records) {

                if (!empty($records)) {

                    $this->insertBatch(
                        $table,
                        $records,
                        $stats
                    );
                }
            }

        } finally {

            fclose($handle);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EXCEL IMPORT
    |--------------------------------------------------------------------------
    */

    private function importExcel(
        string $path,
        array &$stats,
        array &$createdTables
    ): void {

        $reader = IOFactory::createReaderForFile($path);

        $reader->setReadDataOnly(true);

        $sheetNames = $reader->listWorksheetNames($path);

        if (empty($sheetNames)) {
            throw new \RuntimeException('No Excel worksheet found.');
        }

        $sheetName = $sheetNames[0];

        $reader->setLoadSheetsOnly($sheetName);

        $sheetInfo = $reader->listWorksheetInfo($path);

        if (empty($sheetInfo)) {
            throw new \RuntimeException('Unable to read Excel information.');
        }

        $totalRows = (int) $sheetInfo[0]['totalRows'];
        $totalColumns = (int) $sheetInfo[0]['totalColumns'];

        if ($totalRows < 2) {
            throw new \RuntimeException('Excel file has no data rows.');
        }

        $filter = new ExcelChunkReadFilter();

        $reader->setReadFilter($filter);

        // Read header row

        $filter->setRows(2, $this->chunkSize);

        $spreadsheet = $reader->load($path);

        $sheet = $spreadsheet->getSheetByName($sheetName);

        $headers = [];

        for ($col = 1; $col <= $totalColumns; $col++) {

            $headers[] = $sheet->getCell([$col, 1])->getValue();
        }

        $headerMap = $this->makeHeaderMap($headers);

        $this->validateHeaders($headerMap);

        $spreadsheet->disconnectWorksheets();

        unset($spreadsheet, $sheet);

        gc_collect_cycles();

        // Read Excel in chunks

        for (
            $startRow = 2;
            $startRow <= $totalRows;
            $startRow += $this->chunkSize
        ) {

            $filter->setRows($startRow, $this->chunkSize);

            $spreadsheet = $reader->load($path);

            $sheet = $spreadsheet->getSheetByName($sheetName);

            $endRow = min(
                $startRow + $this->chunkSize - 1,
                $totalRows
            );

            $batch = [];

            for (
                $rowNumber = $startRow;
                $rowNumber <= $endRow;
                $rowNumber++
            ) {

                $row = [];

                foreach ($headerMap as $column) {

                    $row[$column - 1] = $sheet
                        ->getCell([$column, $rowNumber])
                        ->getValue();
                }

                $stats['total_rows']++;

                $record = $this->makeRecordFromRow(
                    $row,
                    $headerMap,
                    true
                );

                if (!$record) {
                    $stats['failed']++;
                    continue;
                }

                $stats['processed']++;

                $table = SmsTableHelper::getTableName(
                    $record['sent_time']
                );

                if (!isset($createdTables[$table])) {

                    SmsTableHelper::createMonthlyTable($table);

                    $createdTables[$table] = true;
                }

                $batch[$table][] = $record;

                if (count($batch[$table]) >= $this->insertSize) {

                    $this->insertBatch(
                        $table,
                        $batch[$table],
                        $stats
                    );

                    $batch[$table] = [];
                }
            }

            // Insert remaining rows in this chunk

            foreach ($batch as $table => $records) {

                if (!empty($records)) {

                    $this->insertBatch(
                        $table,
                        $records,
                        $stats
                    );
                }
            }

            $spreadsheet->disconnectWorksheets();

            unset($spreadsheet, $sheet, $batch);

            gc_collect_cycles();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HEADER MAPPING
    |--------------------------------------------------------------------------
    */

    private function makeHeaderMap(array $headers): array
    {
        $map = [];

        foreach ($headers as $index => $header) {

            $key = $this->normalizeHeader($header);

            if ($key !== '') {
                $map[$key] = $index + 1;
            }
        }

        return $map;
    }

    private function normalizeHeader($header): string
    {
        return strtolower(
            preg_replace(
                '/[^a-zA-Z0-9]/',
                '',
                trim((string) $header)
            )
        );
    }

    private function validateHeaders(array $headers): void
    {
        foreach (['senttime', 'trackid'] as $required) {

            if (!isset($headers[$required])) {

                throw new \RuntimeException(
                    'Required Excel/CSV column missing: ' . $required
                );
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET ROW VALUE
    |--------------------------------------------------------------------------
    */

    private function makeRecordFromRow(
        array $row,
        array $headers,
        bool $isExcel
    ): ?array {

        $get = function (array $aliases) use ($row, $headers) {

            foreach ($aliases as $alias) {

                $key = $this->normalizeHeader($alias);

                if (isset($headers[$key])) {

                    $index = $headers[$key] - 1;

                    return $row[$index] ?? null;
                }
            }

            return null;
        };

        $trackId = trim(
            (string) ($get(['TrackID', 'Track ID']) ?? '')
        );

        $rawSentTime = $get(['Sent Time']);

        if ($trackId === '' || $rawSentTime === null || $rawSentTime === '') {
            return null;
        }

        $sentTime = $this->parseDate($rawSentTime, $isExcel);

        if (!$sentTime) {
            return null;
        }

        return [
            'status' => $get(['Status']),
            'description' => $get(['Description']),
            'credit_deducted' => (int) ($get(['Credit Deducted']) ?? 0),
            'sent_time' => $sentTime->format('Y-m-d H:i:s'),
            'sender_id' => $get(['Sender ID', 'SenderID']),
            'track_id' => $trackId,
            'credit_type' => $get(['Credit Type']),
            'circle' => $get(['Circle']),
            'message' => $get(['Message']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DATE CONVERSION
    |--------------------------------------------------------------------------
    */

    private function parseDate($value, bool $isExcel): ?Carbon
    {
        try {

            if ($value instanceof \DateTimeInterface) {

                return Carbon::instance($value);
            }

            if ($isExcel && is_numeric($value)) {

                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject((float) $value)
                );
            }

            $value = trim((string) $value);

            $formats = [
                'd-M-Y H:i:s',
                'd-M-Y H:i',
                'Y-m-d H:i:s',
                'Y-m-d H:i',
                'd/m/Y H:i:s',
                'd/m/Y H:i',
                'm/d/Y H:i:s',
            ];

            foreach ($formats as $format) {

                try {

                    $date = Carbon::createFromFormat(
                        '!' . $format,
                        $value
                    );

                    if ($date !== false) {
                        return $date;
                    }

                } catch (Throwable $e) {
                    // Try the next date format
                }
            }

            return Carbon::parse($value);

        } catch (Throwable $e) {

            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT OR UPDATE USING TRACK ID + SENT TIME
    |--------------------------------------------------------------------------
    */

    private function insertBatch(
        string $table,
        array $records,
        array &$stats
    ): void {

        if (empty($records)) {
            return;
        }

        /*
         * Keep only the last occurrence of the same
         * TrackID + Sent Time within this batch.
         */

        $uniqueRecords = [];

        foreach ($records as $record) {

            $key = json_encode([
                $record['track_id'],
                $record['sent_time'],
            ]);

            $uniqueRecords[$key] = $record;
        }

        $records = array_values($uniqueRecords);

        /*
         * Check which records already exist.
         */

        $trackIds = array_values(
            array_unique(
                array_column($records, 'track_id')
            )
        );

        $sentTimes = array_values(
            array_unique(
                array_column($records, 'sent_time')
            )
        );

        $existingRows = DB::table($table)
            ->whereIn('track_id', $trackIds)
            ->whereIn('sent_time', $sentTimes)
            ->get([
                'track_id',
                'sent_time',
            ]);

        $existingKeys = [];

        foreach ($existingRows as $existing) {

            $key = json_encode([
                $existing->track_id,
                $existing->sent_time,
            ]);

            $existingKeys[$key] = true;
        }

        $insertCount = 0;
        $updateCount = 0;

        foreach ($records as $record) {

            $key = json_encode([
                $record['track_id'],
                $record['sent_time'],
            ]);

            if (isset($existingKeys[$key])) {
                $updateCount++;
            } else {
                $insertCount++;
            }
        }

        /*
         * INSERT new records.
         * UPDATE existing records.
         */

        DB::table($table)->upsert(
            $records,
            [
                'track_id',
                'sent_time',
            ],
            [
                'status',
                'description',
                'credit_deducted',
                'sender_id',
                'credit_type',
                'circle',
                'message',
                'updated_at',
            ]
        );

        $stats['inserted'] += $insertCount;
        $stats['updated'] += $updateCount;
    }
}
