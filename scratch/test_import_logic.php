<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$filePath = 'c:/Users/pc/Documents/cbt-laravel/public/soal_siap_unggah.xlsx';
$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray(null, true, true, false);

echo "Total rows in sheet: " . count($rows) . "\n";

$headers  = array_map(fn ($x) => strtolower(trim((string) $x)), $rows[0]);
$required = [
    'nomor','tipe_soal','pertanyaan',
    'opsi_a','opsi_b','opsi_c','opsi_d','opsi_e',
    'kunci_jawaban','bobot',
];

if (array_slice($headers, 0, 10) !== $required) {
    echo "Header mismatch!\n";
    exit(1);
}

$hasPasangan = count($headers) >= 18;
$valid  = [];
$errors = [];

foreach (array_slice($rows, 1) as $i => $row) {
    $line = $i + 2;

    $v = [];
    foreach ($required as $k => $h) {
        $v[$h] = trim((string) ($row[$k] ?? ''));
    }

    $pasangan = [];
    if ($hasPasangan) {
        for ($pi = 10; $pi <= 17; $pi++) {
            $val = trim((string) ($row[$pi] ?? ''));
            if ($val !== '') {
                $pasangan[] = $val;
            }
        }
    }

    if (implode('', $v) === '') continue;

    $type = strtoupper($v['tipe_soal']);
    if ($type === 'PG_KOMPLEKS') {
        $type = 'PG_MULTI';
    }
    $key  = $v['kunci_jawaban'];
    $opts = [
        'A' => $v['opsi_a'],
        'B' => $v['opsi_b'],
        'C' => $v['opsi_c'],
        'D' => $v['opsi_d'],
        'E' => $v['opsi_e'],
    ];

    if (!in_array($type, ['PG', 'PG_MULTI', 'BENAR_SALAH', 'MENJODOHKAN'], true)) {
        $errors[] = "Baris $line: tipe_soal harus PG, PG_MULTI (atau PG_KOMPLEKS), BENAR_SALAH, atau MENJODOHKAN.";
    }
    if ($v['pertanyaan'] === '') {
        $errors[] = "Baris $line: pertanyaan kosong.";
    }
    if (!is_numeric($v['bobot']) || (float) $v['bobot'] <= 0) {
        $errors[] = "Baris $line: bobot harus angka lebih dari 0.";
    }

    if ($type === 'PG' || $type === 'PG_MULTI') {
        if ($opts['A'] === '' || $opts['B'] === '') {
            $errors[] = "Baris $line: PG/PG_MULTI wajib memiliki opsi A dan B.";
        }
        $keys = array_map('trim', explode(',', $key));
        $keys = array_filter($keys, fn($k) => $k !== '');
        $upperKeys = array_map('strtoupper', $keys);
        foreach ($upperKeys as $k) {
            if (!in_array($k, ['A','B','C','D','E'], true) || ($opts[$k] ?? '') === '') {
                $errors[] = "Baris $line: kunci PG/PG_MULTI '$k' tidak valid atau opsi kosong.";
            }
        }
        if ($type === 'PG_MULTI') {
            $key = $upperKeys;
        } else {
            $key = implode(',', $upperKeys);
        }
    }

    if ($type === 'BENAR_SALAH') {
        $keyUp = strtoupper($key);
        if (!in_array($keyUp, ['A', 'B'], true)) {
            $errors[] = "Baris $line: kunci benar/salah harus A (Benar) atau B (Salah).";
        }
        $opts = ['A' => $opts['A'] ?: 'Benar', 'B' => $opts['B'] ?: 'Salah'];
        $key  = $keyUp;
    }

    if ($type === 'MENJODOHKAN') {
        $premises = array_filter($opts, fn ($x) => $x !== '');
        if (count($premises) < 2) {
            $errors[] = "Baris $line: MENJODOHKAN wajib memiliki minimal 2 premise (opsi_a, opsi_b, ...).";
        }
        if (count($pasangan) < 2) {
            $errors[] = "Baris $line: MENJODOHKAN wajib memiliki minimal 2 pasangan di kolom pasangan_1, pasangan_2, ...";
        }

        $keyParts   = explode(',', $key);
        $validKey   = true;
        $premiseKeys = array_keys($premises);
        foreach ($keyParts as $part) {
            $part = trim($part);
            if (!preg_match('/^([A-E]):(\d+)$/', $part, $m)) {
                $validKey = false;
                break;
            }
            if (!in_array($m[1], $premiseKeys, true)) {
                $validKey = false;
                break;
            }
            if ((int) $m[2] < 1 || (int) $m[2] > count($pasangan)) {
                $validKey = false;
                break;
            }
        }
        if (!$validKey) {
            $errors[] = "Baris $line: kunci menjodohkan format salah. Contoh: A:1,B:3,C:2 (premise:nomor_pasangan).";
        }

        $opts = $premises;
    }

    if (!in_array($type, ['PG', 'PG_MULTI', 'BENAR_SALAH', 'MENJODOHKAN'], true)
        || $v['pertanyaan'] === ''
        || !is_numeric($v['bobot'])
        || (float) $v['bobot'] <= 0) {
        continue;
    }

    $entry = [
        'position'      => (int) ($v['nomor'] ?: count($valid) + 1),
        'type'          => $type,
        'question_text' => $v['pertanyaan'],
        'options'       => array_filter($opts, fn ($x) => $x !== ''),
        'answer_key'    => $key,
        'weight'        => (float) $v['bobot'],
    ];

    if ($type === 'MENJODOHKAN' && count($pasangan) > 0) {
        $entry['options']['__pairs'] = $pasangan;
    }

    $valid[] = $entry;
}

echo "Validation completed!\n";
echo "Errors count: " . count($errors) . "\n";
if ($errors) {
    print_r($errors);
} else {
    echo "SUCCESS: All " . count($valid) . " questions are 100% valid and ready to import!\n";
}
