<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;

class TeacherController extends Controller
{
    /**
     * Generate username unik untuk guru dari nama.
     * Format: 'guru_' + huruf kecil nama (max 15 karakter) + 3-4 digit angka acak.
     */
    private function generateUsername(string $name, array $takenUsernames = []): string
    {
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        if ($base === '') {
            $base = 'guru';
        }
        $base = 'guru_' . substr($base, 0, 15);

        do {
            $username = $base . rand(100, 999);
        } while (
            User::where('username', $username)->exists() ||
            in_array($username, $takenUsernames, true)
        );

        return $username;
    }

    /**
     * Generate password acak 8 karakter (kombinasi huruf + angka).
     */
    private function generatePassword(): string
    {
        return Str::random(4) . rand(1000, 9999);
    }
    public function index(Request $request)
    {
        $query = User::where('role', 'guru');

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('username', 'like', "%{$keyword}%");
            });
        }

        $teachers = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('teachers.index', compact('teachers'));
    }

    public function create()
    {
        return view('teachers.create', ['teacher' => new User()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username', 'regex:/^[a-z0-9_]+$/'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $data['role'] = 'guru';
        $data['plain_password'] = $data['password'];
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Akun guru berhasil ditambahkan.');
    }

    public function edit(User $guru)
    {
        abort_unless($guru->role === 'guru', 404);
        return view('teachers.edit', ['teacher' => $guru]);
    }

    public function update(Request $request, User $guru)
    {
        abort_unless($guru->role === 'guru', 404);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($guru->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['plain_password'] = $data['password'];
            $data['password'] = Hash::make($data['password']);
        }

        $guru->update($data);

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy(User $guru)
    {
        abort_unless($guru->role === 'guru', 404);

        DB::transaction(function () use ($guru) {
            foreach ($guru->exams as $exam) {
                foreach ($exam->attempts as $attempt) {
                    $attempt->answers()->delete();
                    $attempt->delete();
                }
                $exam->questions()->delete();
                $exam->delete();
            }
            $guru->delete();
        });

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Akun guru "' . $guru->name . '" berhasil dihapus beserta ujian yang dibuatnya.');
    }

    /**
     * Hapus massal beberapa guru sekaligus.
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'teacher_ids'   => ['required', 'array'],
            'teacher_ids.*' => ['exists:users,id'],
        ]);

        $teachers = User::where('role', 'guru')
            ->whereIn('id', $request->teacher_ids)
            ->get();

        $count = $teachers->count();

        DB::transaction(function () use ($teachers) {
            foreach ($teachers as $teacher) {
                foreach ($teacher->exams as $exam) {
                    foreach ($exam->attempts as $attempt) {
                        $attempt->answers()->delete();
                        $attempt->delete();
                    }
                    $exam->questions()->delete();
                    $exam->delete();
                }
                $teacher->delete();
            }
        });

        return redirect()->route('admin.teachers.index')
            ->with('success', "Sebanyak {$count} akun guru berhasil dihapus.");
    }

    /**
     * Download template Excel untuk data guru.
     * Kolom: nama, username, password, email
     */
    public function template()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Guru');

        $sheet->fromArray([
            ['nama', 'username', 'password', 'email'],
            ['Budi Santoso, S.Pd', 'guru_budi', 'Password123!', 'budi@sekolah.sch.id'],
            ['Siti Aminah, M.Pd', '', '', ''],
        ], null, 'A1');

        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        // Tambahkan sheet petunjuk terpisah agar tidak mengganggu baris data
        $helpSheet = $spreadsheet->createSheet();
        $helpSheet->setTitle('Petunjuk Pengisian');
        $helpSheet->setCellValue('A1', 'Petunjuk Pengisian Template Data Guru:');
        $helpSheet->setCellValue('A2', '1. Kolom "nama" wajib diisi.');
        $helpSheet->setCellValue('A3', '2. Kolom "username", "password", dan "email" bersifat opsional.');
        $helpSheet->setCellValue('A4', '3. Jika username dan password dikosongkan, sistem akan otomatis membuatnya.');
        $helpSheet->setCellValue('A5', '4. Anda dapat menghapus atau menimpa baris contoh.');
        $helpSheet->getStyle('A1')->getFont()->setBold(true);
        $helpSheet->getColumnDimension('A')->setAutoSize(true);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            'template-data-guru.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Impor data guru dari Excel.
     * Kolom: nama (wajib), username (opsional), password (opsional), email (opsional).
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

        // Cek header minimal: kolom pertama harus 'nama'
        $firstHeader = strtolower(trim((string) ($rows[0][0] ?? '')));
        if ($firstHeader !== 'nama') {
            return back()->withErrors([
                'file' => 'Header Excel tidak sesuai. Kolom pertama harus "nama" (kolom opsional: username, password, email).',
            ]);
        }

        $headers = array_map(fn ($v) => strtolower(trim((string) $v)), $rows[0]);
        $hasUsername = in_array('username', $headers, true);
        $usernameIdx = array_search('username', $headers, true);
        $hasPassword = in_array('password', $headers, true);
        $passwordIdx = array_search('password', $headers, true);
        $hasEmail    = in_array('email', $headers, true);
        $emailIdx    = array_search('email', $headers, true);

        $valid          = [];
        $credentials    = [];
        $errors         = [];
        $takenUsernames = [];
        $takenEmails    = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $line = $index + 2;

            $nama = trim((string) ($row[0] ?? ''));

            // Lewati baris kosong
            $allValues = implode('', array_map(fn ($val) => trim((string) $val), $row));
            if ($allValues === '') {
                continue;
            }

            // Lewati baris catatan / petunjuk template
            if (preg_match('/^(catatan|note|petunjuk|keterangan)/i', $allValues) ||
                preg_match('/^(catatan|note|petunjuk|keterangan)/i', $nama)) {
                continue;
            }

            if ($nama === '') {
                $errors[] = "Baris {$line}: kolom nama guru wajib diisi.";
                continue;
            }

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
                if (strlen($rawPassword) < 8) {
                    $errors[] = "Baris {$line}: password untuk '{$nama}' minimal 8 karakter.";
                    continue;
                }
                $plainPassword = $rawPassword;
            } else {
                $plainPassword = $this->generatePassword();
            }

            // Email
            $rawEmail = ($hasEmail && $emailIdx !== false) ? trim((string) ($row[$emailIdx] ?? '')) : '';
            $email = null;
            if ($rawEmail !== '') {
                if (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Baris {$line}: format email '{$rawEmail}' tidak valid.";
                    continue;
                }
                if (User::where('email', $rawEmail)->exists() || in_array($rawEmail, $takenEmails, true)) {
                    $errors[] = "Baris {$line}: email '{$rawEmail}' sudah digunakan.";
                    continue;
                }
                $email = $rawEmail;
                $takenEmails[] = $email;
            }

            $valid[] = [
                'name'           => $nama,
                'username'       => $username,
                'email'          => $email,
                'plain_password' => $plainPassword,
                'password'       => Hash::make($plainPassword),
                'role'           => 'guru',
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            $credentials[] = [
                'nama'     => $nama,
                'username' => $username,
                'password' => $plainPassword,
                'email'    => $email ?: '-',
            ];
        }

        if ($errors) {
            return back()->withErrors([
                'file' => implode("\n", $errors),
            ]);
        }

        if (!$valid) {
            return back()->withErrors([
                'file' => 'Tidak ada data guru valid untuk diimpor.',
            ]);
        }

        DB::transaction(function () use ($valid) {
            foreach ($valid as $teacherData) {
                User::create($teacherData);
            }
        });

        session()->flash('import_credentials', $credentials);

        return redirect()->route('admin.teachers.index')
            ->with('success', count($valid) . ' akun guru berhasil diimpor. Simpan username & password berikut!');
    }
}
