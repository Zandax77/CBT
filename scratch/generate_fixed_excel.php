<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$csvFile = 'c:/Users/pc/Documents/cbt-laravel/scratch/test_input.csv';
$lines = [];
$f = fopen($csvFile, 'r');
while (($row = fgetcsv($f)) !== false) {
    $lines[] = $row;
}
fclose($f);

// 1. Fix Soal 34:
$lines[34][7] = 'Arrow syntax (=>)'; // opsi_e

// 2. Separate questions and guide
$soalRows = array_slice($lines, 0, 36);
$panduanRows = array_slice($lines, 36);

$spreadsheet = new Spreadsheet();

// --- SHEET 1: Soal ---
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Soal');

foreach ($soalRows as $rIndex => $row) {
    $rowNum = $rIndex + 1;
    foreach ($row as $cIndex => $val) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIndex + 1);
        $sheet1->setCellValueExplicit("{$colLetter}{$rowNum}", (string) $val, DataType::TYPE_STRING);
    }
}

// Styling Sheet 1
$lastCol = 'R';
$lastRow = count($soalRows);

// Header style
$sheet1->getStyle("A1:{$lastCol}1")->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11,
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '1E40AF'], // Dark Blue
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
]);
$sheet1->getRowDimension(1)->setRowHeight(28);

// Auto-size or set widths
foreach (range('A', $lastCol) as $col) {
    $sheet1->getColumnDimension($col)->setAutoSize(true);
}
// Set alignment for specific columns
$sheet1->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet1->getStyle("B2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet1->getStyle("I2:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet1->getStyle("J2:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Freeze header row
$sheet1->freezePane('A2');

// Gridlines
$sheet1->setShowGridLines(true);

// --- SHEET 2: Panduan ---
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Panduan');

foreach ($panduanRows as $rIndex => $row) {
    $rowNum = $rIndex + 1;
    foreach ($row as $cIndex => $val) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIndex + 1);
        $sheet2->setCellValueExplicit("{$colLetter}{$rowNum}", (string) $val, DataType::TYPE_STRING);
    }
}

// Header styling for sheet 2
$sheet2->getStyle('A1:B1')->applyFromArray([
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11,
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '059669'], // Emerald Green
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER,
    ],
]);
$sheet2->getRowDimension(1)->setRowHeight(25);
$sheet2->getColumnDimension('A')->setWidth(20);
$sheet2->getColumnDimension('B')->setWidth(100);
$sheet2->getStyle('B2:B6')->getAlignment()->setWrapText(true);
$sheet2->setShowGridLines(true);

// Active sheet = Soal
$spreadsheet->setActiveSheetIndex(0);

// Save to workspace root and public folder
$paths = [
    'c:/Users/pc/Documents/cbt-laravel/soal_siap_unggah.xlsx',
    'c:/Users/pc/Documents/cbt-laravel/public/soal_siap_unggah.xlsx'
];

$writer = new Xlsx($spreadsheet);
foreach ($paths as $p) {
    $writer->save($p);
    echo "Saved: $p\n";
}
