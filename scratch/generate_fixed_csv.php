<?php
require 'vendor/autoload.php';

$csvFile = 'c:/Users/pc/Documents/cbt-laravel/scratch/test_input.csv';
$lines = [];
$f = fopen($csvFile, 'r');
while (($row = fgetcsv($f)) !== false) {
    $lines[] = $row;
}
fclose($f);

$lines[34][7] = 'Arrow syntax (=>)'; // Fix opsi_e
$soalRows = array_slice($lines, 0, 36); // Header + 35 soal

$outCsv = 'c:/Users/pc/Documents/cbt-laravel/public/soal_siap_unggah.csv';
$fOut = fopen($outCsv, 'w');
foreach ($soalRows as $row) {
    fputcsv($fOut, $row);
}
fclose($fOut);

echo "Saved CSV: $outCsv\n";
