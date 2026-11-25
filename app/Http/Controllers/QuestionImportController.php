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
        abort_unless($exam->created_by == auth()->id() || auth()->user()->role === 'admin', 403, 'Anda tidak punya akses ke ujian ini.');
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

        // Contoh 1: PG (Pilihan Ganda Biasa)
        $sh->fromArray([[
            1, 'PG', 'Tujuan utama dilakukannya normalisasi pada basis data adalah ...',
            'Menambah jumlah data pada tabel',
            'Mengurangi redundansi dan anomali data',
            'Memperbesar ukuran penyimpanan',
            'Menghapus seluruh primary key',
            'Mempercepat koneksi jaringan',
            'B', 1,
            '','','','','','','','',
        ]], null, 'A2');

        // Contoh 2: PG_MULTI / Pilihan Ganda Kompleks (Bisa lebih dari 1 jawaban benar)
        $sh->fromArray([[
            2, 'PG_MULTI', 'Pilih semua pernyataan yang benar tentang primary key!',
            'Nilainya harus unik',
            'Boleh bernilai NULL',
            'Tidak boleh bernilai NULL',
            'Boleh bernilai duplikat',
            'Mengidentifikasi setiap baris secara unik',
            'A,C,E', 2,
            '','','','','','','','',
        ]], null, 'A3');

        // Contoh 3: BENAR_SALAH
        $sh->fromArray([[
            3, 'BENAR_SALAH', 'Tabel yang telah memenuhi 3NF pasti telah memenuhi 2NF dan 1NF.',
            'Benar','Salah','','','',
            'A', 1,
            '','','','','','','','',
        ]], null, 'A4');

        // Contoh 4: MENJODOHKAN
        // opsi_a..opsi_e = kolom kiri (premise A–E)
        // pasangan_1..pasangan_N = kolom kanan (jawaban yang diacak saat tampil)
        // kunci_jawaban = urutan jawaban benar: "A:2,B:3,C:1" (premise:nomor_pasangan)
        $sh->fromArray([[
            4, 'MENJODOHKAN', 'Jodohkan jenis anomali berikut dengan penjelasannya!',
            'Anomali penyisipan','Anomali pembaruan','Anomali penghapusan','','',
            'A:2,B:3,C:1', 2,
            'Data lain ikut hilang saat sebuah baris dihapus',
            'Data baru tidak dapat dimasukkan tanpa data lain',
            'Perubahan harus dilakukan pada banyak baris sekaligus',
            'Data tersimpan dalam bentuk terenkripsi',
            '','','','',
        ]], null, 'A5');

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
            ['PG', 'Pilihan ganda biasa (1 jawaban benar). opsi_a–opsi_e untuk opsi pilihan, kunci_jawaban = A / B / C / D / E.'],
            ['PG_MULTI', 'Pilihan ganda kompleks (dapat memilih lebih dari 1 jawaban benar). opsi_a–opsi_e untuk pilihan, kunci_jawaban = huruf jawaban dipisahkan koma, contoh: A,C,E (atau alias PG_KOMPLEKS).'],
            ['BENAR_SALAH', 'Pernyataan benar atau salah. opsi_a=Benar, opsi_b=Salah. kunci_jawaban = A (Benar) atau B (Salah).'],
            ['MENJODOHKAN', 'Menjodohkan dua sisi. opsi_a–opsi_e = kolom kiri (premise A-E). pasangan_1–pasangan_8 = kolom kanan (pilihan pasangan). kunci_jawaban = format premise:no_pasangan, contoh: A:2,B:3,C:1.'],
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
        abort_unless($exam->created_by == $r->user()->id || $r->user()->role === 'admin', 403, 'Anda tidak punya akses ke ujian ini.');
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
            // Alias: treat legacy PG_KOMPLEKS as PG_MULTI
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
                // Validate required options A & B
                if ($opts['A'] === '' || $opts['B'] === '') {
                    $errors[] = "Baris $line: PG/PG_MULTI wajib memiliki opsi A dan B.";
                }
                // Normalize answer key(s)
                $keys = array_map('trim', explode(',', $key));
                $keys = array_filter($keys, fn($k) => $k !== '');
                $upperKeys = array_map('strtoupper', $keys);
                // Each key must be a valid option and the option must have a value
                foreach ($upperKeys as $k) {
                    if (!in_array($k, ['A','B','C','D','E'], true) || ($opts[$k] ?? '') === '') {
                        $errors[] = "Baris $line: kunci PG/PG_MULTI '$k' tidak valid atau opsi kosong.";
                    }
                }
                // Store as array for PG_MULTI, otherwise as comma‑separated string
                if ($type === 'PG_MULTI') {
                    $key = $upperKeys; // array – will be JSON‑encoded by model casts
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
