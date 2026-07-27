<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds a clean, professionally-styled .xlsx report (title, meta line, dark
 * header, zebra rows, right-aligned numerics, number formats, frozen header)
 * and streams it as a download. No decorative characters — plain and print-ready.
 */
class SpreadsheetReport
{
    /**
     * @param array<string, string> $meta       label => value shown under the title
     * @param array<int, string>    $headers    column headings
     * @param array<int, string>    $aligns     'left' | 'right' per column
     * @param array<int, array<int, mixed>> $rows scalar cell values
     * @param array<int, string>    $numberFormats column index => Excel format code
     */
    public static function download(
        string $title,
        array $meta,
        array $headers,
        array $aligns,
        array $rows,
        string $filename,
        array $numberFormats = []
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Report');

        $colCount = count($headers);
        $lastCol = Coordinate::stringFromColumnIndex($colCount);

        // Title + meta.
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $metaLine = implode('     ', array_map(
            static fn ($k, $v) => "{$k}: {$v}",
            array_keys($meta),
            array_values($meta)
        ));
        $sheet->setCellValue('A2', $metaLine);
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->getFont()->setSize(9)->getColor()->setRGB('5F6368');

        // Header row (row 4).
        $headerRow = 4;
        foreach ($headers as $i => $heading) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $headerRow, $heading);
        }
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '202124']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(20);

        // Data rows.
        $r = $headerRow + 1;
        foreach ($rows as $row) {
            foreach ($row as $i => $value) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $r, $value);
            }
            $r++;
        }
        $lastRow = $r - 1;

        // Alignment + number formats per column.
        foreach ($headers as $i => $heading) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            if (($aligns[$i] ?? 'left') === 'right') {
                $sheet->getStyle("{$col}5:{$col}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
            if (isset($numberFormats[$i]) && $lastRow >= 5) {
                $sheet->getStyle("{$col}5:{$col}{$lastRow}")->getNumberFormat()->setFormatCode($numberFormats[$i]);
            }
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Light borders on the table + freeze the header.
        if ($lastRow >= $headerRow) {
            $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")->getBorders()
                ->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E8EAED');
        }
        $sheet->freezePane('A5');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            static fn () => $writer->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
