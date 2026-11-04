<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AdminImportTest extends TestCase
{
    use RefreshDatabase;

    private function createExcel(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($rows, null, 'A1');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return new UploadedFile($tempPath, 'test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin Test',
            'username' => 'admin_' . uniqid(),
            'password' => 'Password123!',
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_download_teacher_template(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.teachers.template'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=template-data-guru.xlsx');
    }

    public function test_admin_can_download_student_template(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.students.template'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename=template-data-siswa.xlsx');
    }

    public function test_admin_can_import_teachers(): void
    {
        $admin = $this->createAdmin();

        $file = $this->createExcel([
            ['nama', 'username', 'password', 'email'],
            ['Guru Satu Auto', '', '', ''],
            ['Guru Dua Custom', 'guru_custom_test', 'SecretPass123!', 'guru_dua@test.com'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.teachers.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.teachers.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('import_credentials');

        $this->assertDatabaseHas('users', [
            'name' => 'Guru Satu Auto',
            'role' => 'guru',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Guru Dua Custom',
            'username' => 'guru_custom_test',
            'email' => 'guru_dua@test.com',
            'role' => 'guru',
        ]);
    }

    public function test_admin_can_import_students(): void
    {
        $admin = $this->createAdmin();

        $file = $this->createExcel([
            ['nomor_induk', 'nama', 'kelas', 'username', 'password'],
            ['TEST_S_001', 'Siswa Satu Auto', 'XII IPA 1', '', ''],
            ['TEST_S_002', 'Siswa Dua Custom', 'XII IPA 2', 'siswa_custom_test', 'SiswaPass123!'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.students.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.students.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('import_credentials');

        $this->assertDatabaseHas('users', [
            'name' => 'Siswa Satu Auto',
            'student_number' => 'TEST_S_001',
            'class_name' => 'XII IPA 1',
            'role' => 'siswa',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Siswa Dua Custom',
            'student_number' => 'TEST_S_002',
            'username' => 'siswa_custom_test',
            'role' => 'siswa',
        ]);
    }

    public function test_import_ignores_catatan_note_row_safely(): void
    {
        $admin = $this->createAdmin();

        $file = $this->createExcel([
            ['nomor_induk', 'nama', 'kelas', 'username', 'password'],
            ['2026111', 'Siswa Nyata', 'XI RPL 1', '', ''],
            ['', '', '', '', ''], // empty row
            ['Catatan: Kolom username dan password bersifat opsional...', '', '', '', ''], // note row at line 5
        ]);

        $response = $this->actingAs($admin)->post(route('admin.students.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('admin.students.index'));
        $response->assertSessionHas('success');
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Siswa Nyata',
            'student_number' => '2026111',
            'role' => 'siswa',
        ]);
    }

    public function test_print_card_displays_actual_student_password(): void
    {
        $admin = $this->createAdmin();

        $student = User::create([
            'name'           => 'Siswa Print Test',
            'username'       => 'siswa_print_01',
            'student_number' => 'PRINT001',
            'class_name'     => 'X RPL 1',
            'password'       => 'HashedSecretPass123',
            'plain_password' => 'SecretPlain999',
            'role'           => 'siswa',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.students.print_card', $student));
        $response->assertStatus(200);
        $response->assertSee('SecretPlain999');
    }

    public function test_import_menjodohkan_question_with_long_answer_key(): void
    {
        $admin = $this->createAdmin();

        $exam = \App\Models\Exam::create([
            'title'            => 'Ujian Menjodohkan',
            'subject'          => 'Database',
            'starts_at'        => now(),
            'ends_at'          => now()->addHours(2),
            'duration_minutes' => 60,
            'token'            => 'TESTOK',
            'created_by'       => $admin->id,
            'is_published'     => true,
        ]);

        $file = $this->createExcel([
            [
                'nomor', 'tipe_soal', 'pertanyaan',
                'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e',
                'kunci_jawaban', 'bobot',
                'pasangan_1', 'pasangan_2', 'pasangan_3', 'pasangan_4',
                'pasangan_5', 'pasangan_6', 'pasangan_7', 'pasangan_8',
            ],
            [
                1, 'MENJODOHKAN', 'Jodohkan tahapan normalisasi basis data:',
                '1NF', '2NF', '3NF', 'BCNF', '',
                'A:2,B:4,C:1,D:3', 1,
                'Menghilangkan ketergantungan transitif',
                'Menghilangkan repeating group',
                'Setiap penentu wajib candidate key',
                'Menghilangkan ketergantungan parsial',
                '', '', '', '',
            ],
        ]);

        $response = $this->actingAs($admin)->post(route('questions.import', $exam), [
            'file' => $file,
            'mode' => 'append',
        ]);

        $response->assertRedirect(route('questions.list', $exam));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('questions', [
            'exam_id'    => $exam->id,
            'type'       => 'MENJODOHKAN',
            'answer_key' => 'A:2,B:4,C:1,D:3',
        ]);
    }

    public function test_import_questions_with_duplicate_numbers_in_excel_renumbers_safely(): void
    {
        $admin = $this->createAdmin();

        $exam = \App\Models\Exam::create([
            'title'            => 'Ujian Duplikat Nomor',
            'subject'          => 'Database',
            'starts_at'        => now(),
            'ends_at'          => now()->addHours(2),
            'duration_minutes' => 60,
            'token'            => 'TESTOK2',
            'created_by'       => $admin->id,
            'is_published'     => true,
        ]);

        $file = $this->createExcel([
            [
                'nomor', 'tipe_soal', 'pertanyaan',
                'opsi_a', 'opsi_b', 'opsi_c', 'opsi_d', 'opsi_e',
                'kunci_jawaban', 'bobot',
            ],
            // Dua soal dengan nomor sama: 9 dan 9
            [
                9, 'PG', 'Pertanyaan A',
                'Opsi A', 'Opsi B', '', '', '',
                'A', 1,
            ],
            [
                9, 'PG', 'Pertanyaan B',
                'Opsi A', 'Opsi B', '', '', '',
                'B', 1,
            ],
        ]);

        $response = $this->actingAs($admin)->post(route('questions.import', $exam), [
            'file' => $file,
            'mode' => 'append',
        ]);

        $response->assertRedirect(route('questions.list', $exam));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('questions', [
            'exam_id'  => $exam->id,
            'position' => 1,
            'question_text' => 'Pertanyaan A',
        ]);

        $this->assertDatabaseHas('questions', [
            'exam_id'  => $exam->id,
            'position' => 2,
            'question_text' => 'Pertanyaan B',
        ]);
    }
}
