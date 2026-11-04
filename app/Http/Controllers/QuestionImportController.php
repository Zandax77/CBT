<?php
namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class QuestionImportController extends Controller
{
    public function form(Exam $exam)
    {
        return view('questions.import', compact('exam'));
    }

    public function template()
    {
        $s  = new Spreadsheet();
        $sh = $s->getActiveSheet();
        $sh->setTitle('Soal');

        // Kolom: nomor | tipe_soal | pertanyaan | opsi_a | opsi_b | opsi_c | opsi_d | opsi_e | kunci_jawaban | bobot
        // Untuk MENJODOHKAN, kolom ditambah: pasangan_1 ... pasangan_8 (baris pasangan kanan)
        $headers = [
            'nomor','tipe_soal','pertanyaan',
            'opsi_a','opsi_b','opsi_c','opsi_d','opsi_e',
            'kunci_jawaban','bobot',
            'pasangan_1','pasangan_2','pasangan_3','pasangan_4',
            'pasangan_5','pasangan_6','pasangan_7','pasangan_8',
        ];

        $sh->fromArray([$headers], null, 'A1');

        // Contoh PG
        $sh->fromArray([[
            1, 'PG', 'Contoh: 2 + 2 = ?',
            '3','4','5','6','',
            'B', 1,
            '','','','','','','','',
        ]], null, 'A2');

        // Contoh BENAR_SALAH
        $sh->fromArray([[
            2, 'BENAR_SALAH', 'Laravel adalah framework PHP.',
            'Benar','Salah','','','',
            'A', 1,
            '','','','','','','','',
        ]], null, 'A3');

        // Contoh MENJODOHKAN
        // opsi_a..opsi_e = kolom kiri (premise A–E)
        // pasangan_1..pasangan_N = kolom kanan (jawaban yang diacak saat tampil)
        // kunci_jawaban = urutan jawaban benar: "A:1,B:3,C:2" (premise A cocok pasangan_1, dll)
        $sh->fromArray([[
            3, 'MENJODOHKAN', 'Cocokkan ibukota negara berikut!',
            'Indonesia','Malaysia','Thailand','Vietnam','',
            'A:1,B:2,C:3,D:4', 1,
            'Jakarta','Kuala Lumpur','Bangkok','Hanoi','','','','',
        ]], null, 'A4');

        // Style header
        foreach (range('A', 'R') as $c) {
            $sh->getColumnDimension($c)->setAutoSize(true);
        }
        $sh->getStyle('A1:R1')->getFont()->setBold(true);

        // Keterangan tipe di sheet kedua
        $sh2 = $s->createSheet();
        $sh2->setTitle('Panduan');
        $sh2->fromArray([
            ['Tipe Soal', 'Keterangan'],
            ['PG', 'Pilihan ganda. opsi_a–opsi_e untuk pilihan, kunci_jawaban = A/B/C/D/E.'],
            ['BENAR_SALAH', 'opsi_a=Benar, opsi_b=Salah. kunci_jawaban = A (benar) atau B (salah).'],
            ['MENJODOHKAN', 'opsi_a–opsi_e = kolom kiri (premise). pasangan_1–pasangan_8 = kolom kanan. kunci_jawaban = A:1,B:2,C:3 (premise:no.pasangan).'],
        ], null, 'A1');
        $sh2->getStyle('A1:B1')->getFont()->setBold(true);
        foreach (['A', 'B'] as $c) {
            $sh2->getColumnDimension($c)->setAutoSize(true);
        }

        $s->setActiveSheetIndex(0);
        $writer = new Xlsx($s);
        $name   = 'template-soal-cbt.xlsx';

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $name,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function import(Request $r, Exam $exam)
    {
        abort_unless($exam->created_by === $r->user()->id || $r->user()->role === 'admin', 403);
        $r->validate([
            'file' => 'required|file|mimes:xlsx|max:5120',
            'mode' => 'nullable|in:append,replace',
        ]);
        $mode = $r->input('mode', 'append'); // default: tambahkan

        $sheet  = IOFactory::load($r->file('file')->getRealPath())->getActiveSheet();
        $rows   = $sheet->toArray(null, true, true, false);

        if (count($rows) < 2) {
            return back()->withErrors(['file' => 'Template tidak berisi soal.']);
        }

        $headers  = array_map(fn ($x) => strtolower(trim((string) $x)), $rows[0]);
        $required = [
            'nomor','tipe_soal','pertanyaan',
            'opsi_a','opsi_b','opsi_c','opsi_d','opsi_e',
            'kunci_jawaban','bobot',
        ];

        if (array_slice($headers, 0, 10) !== $required) {
            return back()->withErrors(['file' => 'Header harus sesuai template: ' . implode(', ', $required)]);
        }

        // Cek apakah ada kolom pasangan (opsional di template lama)
        $hasPasangan = count($headers) >= 18;

        $valid  = [];
        $errors = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $line = $i + 2;

            // Ambil 10 kolom wajib
            $v = [];
            foreach ($required as $k => $h) {
                $v[$h] = trim((string) ($row[$k] ?? ''));
            }

            // Kolom pasangan (indeks 10–17)
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
            $key  = $v['kunci_jawaban'];
            $opts = [
                'A' => $v['opsi_a'],
                'B' => $v['opsi_b'],
                'C' => $v['opsi_c'],
                'D' => $v['opsi_d'],
                'E' => $v['opsi_e'],
            ];

            if (!in_array($type, ['PG', 'BENAR_SALAH', 'MENJODOHKAN'], true)) {
                $errors[] = "Baris $line: tipe_soal harus PG, BENAR_SALAH, atau MENJODOHKAN.";
            }
            if ($v['pertanyaan'] === '') {
                $errors[] = "Baris $line: pertanyaan kosong.";
            }
            if (!is_numeric($v['bobot']) || (float) $v['bobot'] <= 0) {
                $errors[] = "Baris $line: bobot harus angka lebih dari 0.";
            }

            if ($type === 'PG') {
                if ($opts['A'] === '' || $opts['B'] === '') {
                    $errors[] = "Baris $line: PG wajib memiliki opsi A dan B.";
                }
                $keyUp = strtoupper($key);
                if (!in_array($keyUp, ['A', 'B', 'C', 'D', 'E'], true) || ($opts[$keyUp] ?? '') === '') {
                    $errors[] = "Baris $line: kunci PG tidak valid atau opsi kunci kosong.";
                }
                $key = $keyUp;
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
                // Validasi premise (opsi kiri)
                $premises = array_filter($opts, fn ($x) => $x !== '');
                if (count($premises) < 2) {
                    $errors[] = "Baris $line: MENJODOHKAN wajib memiliki minimal 2 premise (opsi_a, opsi_b, ...).";
                }
                if (count($pasangan) < 2) {
                    $errors[] = "Baris $line: MENJODOHKAN wajib memiliki minimal 2 pasangan di kolom pasangan_1, pasangan_2, ...";
                }

                // Validasi format kunci: A:1,B:2,C:3
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

                // Simpan struktur khusus untuk menjodohkan
                $opts = $premises;   // hanya premise yang diisi
            }

            if (!in_array($type, ['PG', 'BENAR_SALAH', 'MENJODOHKAN'], true)
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

            // Untuk menjodohkan, simpan kolom kanan di options dengan key khusus
            if ($type === 'MENJODOHKAN' && count($pasangan) > 0) {
                $entry['options']['__pairs'] = $pasangan;
            }

            $valid[] = $entry;
        }

        if ($errors) {
            $bag = new \Illuminate\Support\MessageBag();
            foreach ($errors as $err) {
                $bag->add('file', $err);
            }
            return back()->withErrors($bag)->withInput();
        }
        if (!$valid) {
            return back()->withErrors(['file' => 'Tidak ada baris soal valid.']);
        }

        \DB::transaction(function () use ($exam, $valid, $mode) {
            if ($mode === 'replace') {
                // Hapus gambar soal lama dari storage
                foreach ($exam->questions as $q) {
                    if ($q->image_path) {
                        \Storage::disk('public')->delete($q->image_path);
                    }
                }
                $exam->questions()->delete();
                $lastPos = 0;
            } else {
                // Append: nomor urut dilanjutkan dari posisi terakhir
                $lastPos = (int) ($exam->questions()->max('position') ?? 0);
            }

            // Renumber posisi secara berurutan agar dijamin unik & urut tanpa konflik
            foreach ($valid as $idx => &$q) {
                $q['position'] = $lastPos + $idx + 1;
            }
            unset($q);

            foreach ($valid as $q) {
                $exam->questions()->create($q);
            }
        });

        $action = $mode === 'replace' ? 'diganti dengan' : 'ditambahkan';
        return redirect()->route('questions.list', $exam)
            ->with('success', count($valid) . " soal berhasil {$action} via impor Excel.");
    }
}
