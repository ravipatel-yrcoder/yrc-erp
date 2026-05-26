<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class Service_Export {

    /**
     * Stream a file download to the browser.
     *
     * @param array  $rows     stdClass[]|array[] — rows from DB
     * @param string $format   'csv' | 'xlsx'
     * @param string $filename Base name (date appended automatically)
     * @param array  $columns  [['label' => string, 'key' => string, 'formatter' => callable|null], ...]
     */
    public static function stream(array $rows, string $format, string $filename, array $columns): void
    {
        //$filename = $filename . '-' . date('Y-m-d');

        match ($format) {
            'xlsx'  => static::streamXlsx($rows, $filename, $columns),
            default => static::streamCsv($rows, $filename, $columns),
        };
    }


    private static function streamCsv(array $rows, string $filename, array $columns): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

        fputcsv($out, array_column($columns, 'label'));

        foreach ($rows as $row) {
            fputcsv($out, static::mapRow($row, $columns));
        }

        fclose($out);
        exit;
    }


    private static function streamXlsx(array $rows, string $filename, array $columns): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        // Header row
        foreach ($columns as $i => $col) {
            $cell = Coordinate::stringFromColumnIndex($i + 1) . '1';
            $sheet->setCellValue($cell, $col['label']);
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        // Data rows
        foreach ($rows as $r => $row) {
            foreach (static::mapRow($row, $columns) as $i => $value) {
                $cell = Coordinate::stringFromColumnIndex($i + 1) . ($r + 2);
                $sheet->setCellValue($cell, $value);
            }
        }

        // Auto-size columns
        foreach (range(1, count($columns)) as $i) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        // Discard any buffered output (whitespace, notices) that would corrupt the binary stream
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }


    private static function mapRow(mixed $row, array $columns): array
    {
        $arr    = is_object($row) ? (array) $row : $row;
        $result = [];

        foreach ($columns as $col) {
            $value = $arr[$col['key']] ?? '';
            if (isset($col['formatter']) && is_callable($col['formatter'])) {
                $value = ($col['formatter'])($value, $arr);
            }
            $result[] = $value ?? '';
        }

        return $result;
    }
}
