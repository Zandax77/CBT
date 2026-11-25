<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
$path = 'C:/Users/pc/.gemini/antigravity/brain/32a50239-d084-4192-8c70-4224bc2e2800/.user_uploaded/media_1794369873905_079fad05.xlsx';
$spreadsheet = IOFactory::load($path);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray(null, true, true, false);
foreach ($rows as $i => $row) {
    echo "Row $i: ".json_encode($row)."\n";
}
?>