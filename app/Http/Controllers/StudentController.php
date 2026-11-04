<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SchoolSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentController extends Controller
{
    /**
     * Generate username unik dari nama siswa.
     * Format: huruf kecil tanpa spasi + angka 4 digit acak (diulang jika sudah ada).
     */
    private function generateUsername(string $name, array $takenUsernames = []): string
    {
        // Ambil bagian nama (huruf + angka), lowercase
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        if ($base === '') {
            $base = 'siswa';
        }
        // Batasi maksimal 15 karakter dari nama
        $base = substr($base, 0, 15);

        do {
            $username = $base . rand(1000, 9999);
        } while (
            User::where('username', $username)->exists() ||
            in_array($username, $takenUsernames, true)
        );

        return $username;
    }

    /**
     * Generate password acak 8 karakter (huruf + angka).
     */
    private function generatePassword(): string
    {
        return Str::random(4) . rand(1000, 9999);
    }

    public function index(Request $request)
    {
        $query = User::where('role', 'siswa');

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('username', 'like', "%{$keyword}%")
                  ->orWhere('student_number', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('kelas')) {
            $query->where('class_name', $request->kelas);
        }

        $students = $query->orderBy('class_name')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $classes = User::where('role', 'siswa')
            ->whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        return view('students.index', compact('students', 'classes'));
    }

    public function create()
    {
        return view('students.create', ['student' => new User()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'username'       => ['required', 'string', 'max:50', 'unique:users,username', 'regex:/^[a-z0-9_]+$/'],
            'student_number' => ['required', 'string', 'max:50', 'unique:users,student_number'],
            'class_name'     => ['required', 'string', 'max:100'],
            'grade_level'    => ['nullable', 'string', 'max:20'],
            'major'          => ['nullable', 'string', 'max:60'],
            'password'       => ['required', 'string', 'min:8'],
        ]);

        $data['role'] = 'siswa';
        $data['plain_password'] = $data['password'];
        $data['password'] = Hash::make($data['password']);

        // Auto-detect tingkat & jurusan dari kelas jika belum diisi
        if (empty($data['grade_level']) && !empty($data['class_name'])) {
            $parts = preg_split('/\s+/', trim($data['class_name']));
            $data['grade_level'] = $parts[0] ?? null;
        }
        if (empty($data['major']) && !empty($data['class_name'])) {
            $parts = preg_split('/\s+/', trim($data['class_name']));
            $data['major'] = $parts[1] ?? null;
        }

        User::create($data);

        return redirect()->route('admin.students.index')
            ->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function edit(User $siswa)
    {
        abort_unless($siswa->role === 'siswa', 404);

        return view('students.edit', ['student' => $siswa]);
    }

    public function update(Request $request, User $siswa)
    {
        abort_unless($siswa->role === 'siswa', 404);

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'username'       => [
                'required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($siswa->id),
            ],
            'student_number' => [
                'required', 'string', 'max:50',
                Rule::unique('users', 'student_number')->ignore($siswa->id),
            ],
            'class_name'  => ['required', 'string', 'max:100'],
            'grade_level' => ['nullable', 'string', 'max:20'],
            'major'       => ['nullable', 'string', 'max:60'],
            'password'    => ['nullable', 'string', 'min:8'],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['plain_password'] = $data['password'];
            $data['password'] = Hash::make($data['password']);
        }

        if (empty($data['grade_level']) && !empty($data['class_name'])) {
            $parts = preg_split('/\s+/', trim($data['class_name']));
            $data['grade_level'] = $parts[0] ?? null;
        }
        if (empty($data['major']) && !empty($data['class_name'])) {
            $parts = preg_split('/\s+/', trim($data['class_name']));
            $data['major'] = $parts[1] ?? null;
        }

        $siswa->update($data);

        return redirect()->route('admin.students.index')
            ->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(User $siswa)
    {
        abort_unless($siswa->role === 'siswa', 404);

        DB::transaction(function () use ($siswa) {
            foreach ($siswa->attempts as $attempt) {
                $attempt->answers()->delete();
                $attempt->delete();
            }
            $siswa->delete();
        });

        return redirect()->route('admin.students.index')
            ->with('success', 'Data siswa "' . $siswa->name . '" berhasil dihapus beserta seluruh riwayat pengerjaannya.');
    }

    /**
     * Hapus massal beberapa siswa sekaligus.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'student_ids'   => ['required', 'array'],
            'student_ids.*' => ['exists:users,id'],
        ]);

        $students = User::where('role', 'siswa')
            ->whereIn('id', $request->student_ids)
            ->get();

        $count = $students->count();

        DB::transaction(function () use ($students) {
            foreach ($students as $student) {
                foreach ($student->attempts as $attempt) {
                    $attempt->answers()->delete();
                    $attempt->delete();
                }
                $student->delete();
            }
        });

        return redirect()->route('admin.students.index')
            ->with('success', "Sebanyak {$count} data siswa berhasil dihapus.");
    }

    /**
     * Download template Excel untuk data siswa.
     * Kolom wajib: nomor_induk, nama, kelas.
     * Kolom opsional: username, password (dibuat otomatis jika dikosongkan).
     */
    public function template()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');

        $sheet->fromArray([
            ['nomor_induk', 'nama', 'kelas', 'username', 'password'],
            ['2026001', 'Contoh Siswa Satu', 'XI RPL 1', '', ''],
            ['2026002', 'Contoh Siswa Dua', 'XI RPL 2', 'siswa_dua', 'Siswa123!'],
        ], null, 'A1');

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);

        // Tambahkan sheet petunjuk terpisah agar tidak mengganggu baris data
        $helpSheet = $spreadsheet->createSheet();
        $helpSheet->setTitle('Petunjuk Pengisian');
        $helpSheet->setCellValue('A1', 'Petunjuk Pengisian Template Data Siswa:');
        $helpSheet->setCellValue('A2', '1. Kolom "nomor_induk", "nama", dan "kelas" wajib diisi.');
        $helpSheet->setCellValue('A3', '2. Kolom "username" dan "password" bersifat opsional.');
        $helpSheet->setCellValue('A4', '3. Jika username dan password dikosongkan, sistem akan otomatis membuatnya.');
        $helpSheet->setCellValue('A5', '4. Anda dapat menghapus atau menimpa baris contoh.');
        $helpSheet->getStyle('A1')->getFont()->setBold(true);
        $helpSheet->getColumnDimension('A')->setAutoSize(true);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            'template-data-siswa.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Import siswa dari Excel.
     * Kolom wajib: nomor_induk, nama, kelas.
     * Kolom opsional: username, password.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $sheet = IOFactory::load($request->file('file')->getRealPath())
            ->getActiveSheet();

        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows) || empty($rows[0])) {
            return back()->withErrors([
                'file' => 'File Excel kosong atau tidak terbaca.',
            ]);
        }

        $rawHeaders = array_map(fn ($v) => strtolower(trim((string) $v)), $rows[0]);
        $requiredHeaders = ['nomor_induk', 'nama', 'kelas'];

        if (array_slice($rawHeaders, 0, 3) !== $requiredHeaders) {
            return back()->withErrors([
                'file' => 'Header Excel tidak sesuai template. Tiga kolom pertama harus: nomor_induk, nama, kelas (kolom opsional: username, password).',
            ]);
        }

        $hasUsername = in_array('username', $rawHeaders, true);
        $usernameIdx = array_search('username', $rawHeaders, true);
        $hasPassword = in_array('password', $rawHeaders, true);
        $passwordIdx = array_search('password', $rawHeaders, true);

        $valid        = [];
        $credentials  = [];   // untuk ditampilkan ke admin
        $errors       = [];
        $takenUsernames = [];
        $seenNumbers  = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $line = $index + 2;

            $nomorInduk = trim((string) ($row[0] ?? ''));
            $nama       = trim((string) ($row[1] ?? ''));
            $kelas      = trim((string) ($row[2] ?? ''));

            // Lewati baris kosong
            $allValues = implode('', array_map(fn ($val) => trim((string) $val), $row));
            if ($allValues === '') {
                continue;
            }

            // Lewati baris catatan / petunjuk template
            if (preg_match('/^(catatan|note|petunjuk|keterangan)/i', $allValues) ||
                preg_match('/^(catatan|note|petunjuk|keterangan)/i', $nomorInduk)) {
                continue;
            }

            if ($nomorInduk === '' || $nama === '' || $kelas === '') {
                $errors[] = "Baris {$line}: kolom nomor_induk, nama, dan kelas wajib diisi.";
                continue;
            }

            if (User::where('student_number', $nomorInduk)->exists() ||
                in_array($nomorInduk, $seenNumbers, true)) {
                $errors[] = "Baris {$line}: nomor induk '{$nomorInduk}' sudah digunakan atau duplikat.";
                continue;
            }

            $seenNumbers[] = $nomorInduk;

            // Username
            $rawUsername = ($hasUsername && $usernameIdx !== false) ? strtolower(trim((string) ($row[$usernameIdx] ?? ''))) : '';
            if ($rawUsername !== '') {
                if (!preg_match('/^[a-z0-9_]+$/', $rawUsername)) {
                    $errors[] = "Baris {$line}: username '{$rawUsername}' hanya boleh menggunakan huruf kecil, angka, dan garis bawah (_).";
                    continue;
                }
                if (strlen($rawUsername) > 50) {
                    $errors[] = "Baris {$line}: username '{$rawUsername}' maksimal 50 karakter.";
                    continue;
                }
                if (User::where('username', $rawUsername)->exists() || in_array($rawUsername, $takenUsernames, true)) {
                    $errors[] = "Baris {$line}: username '{$rawUsername}' sudah digunakan.";
                    continue;
                }
                $username = $rawUsername;
            } else {
                $username = $this->generateUsername($nama, $takenUsernames);
            }
            $takenUsernames[] = $username;

            // Password
            $rawPassword = ($hasPassword && $passwordIdx !== false) ? trim((string) ($row[$passwordIdx] ?? '')) : '';
            if ($rawPassword !== '') {
                if (strlen($rawPassword) < 6) {
                    $errors[] = "Baris {$line}: password untuk '{$nama}' minimal 6 karakter.";
                    continue;
                }
                $plainPassword = $rawPassword;
            } else {
                $plainPassword = $this->generatePassword();
            }

            $parts = preg_split('/\s+/', $kelas);
            $gradeLevel = $parts[0] ?? null;
            $major = $parts[1] ?? null;

            $valid[] = [
                'name'           => $nama,
                'username'       => $username,
                'student_number' => $nomorInduk,
                'class_name'     => $kelas,
                'grade_level'    => $gradeLevel,
                'major'          => $major,
                'plain_password' => $plainPassword,
                'password'       => Hash::make($plainPassword),
                'role'           => 'siswa',
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            $credentials[] = [
                'nomor_induk' => $nomorInduk,
                'nama'        => $nama,
                'kelas'       => $kelas,
                'username'    => $username,
                'password'    => $plainPassword,
            ];
        }

        if ($errors) {
            return back()->withErrors([
                'file' => implode("\n", $errors),
            ]);
        }

        if (!$valid) {
            return back()->withErrors([
                'file' => 'Tidak ada data siswa valid untuk diimpor.',
            ]);
        }

        DB::transaction(function () use ($valid) {
            foreach ($valid as $student) {
                User::create($student);
            }
        });

        // Simpan kredensial ke session agar bisa ditampilkan ke admin
        session()->flash('import_credentials', $credentials);

        return redirect()->route('admin.students.index')
            ->with('success', count($valid) . ' data siswa berhasil diimpor. Simpan username & password berikut!');
    }

    /**
     * Cetak kartu identitas satu siswa.
     */
    public function printCard(User $siswa)
    {
        abort_unless($siswa->role === 'siswa', 404);

        $classes = User::where('role', 'siswa')
            ->whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        return view('students.card-print', [
            'students' => collect([$siswa]),
            'classes'  => $classes,
            'single'   => true,
            'setting'  => SchoolSetting::instance(),
        ]);
    }

    /**
     * Cetak kartu identitas massal (semua / per kelas).
     */
    public function printCards(Request $request)
    {
        $query = User::where('role', 'siswa');

        if ($request->filled('kelas')) {
            $query->where('class_name', $request->kelas);
        }

        $students = $query->orderBy('class_name')->orderBy('name')->get();

        $classes = User::where('role', 'siswa')
            ->whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        return view('students.card-print', [
            'students' => $students,
            'classes'  => $classes,
            'single'   => false,
            'setting'  => SchoolSetting::instance(),
        ]);
    }
}