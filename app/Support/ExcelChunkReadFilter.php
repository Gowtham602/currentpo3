<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ExcelChunkReadFilter implements IReadFilter
{
    private $startRow = 2;
    private $endRow = 2001;

    /**
     * Set Excel chunk rows.
     */
    public function setRows(int $startRow, int $chunkSize): void
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize - 1;
    }

    /**
     * Read only selected Excel rows.
     */
    public function readCell(
        $columnAddress,
        $row,
        $worksheetName = ''
    ) {
        // Always read header
        if ($row == 1) {
            return true;
        }

        // Read selected chunk only
        return $row >= $this->startRow
            && $row <= $this->endRow;
    }
}
