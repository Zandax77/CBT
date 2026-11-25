<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use App\Models\Attempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamPaginationAndShuffleTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'username' => 'admin_test',
        ]);
    }

    private function createTeacher(): User
    {
        return User::factory()->create([
            'role' => 'guru',
            'username' => 'guru_test',
        ]);
    }

    private function createStudent(string $className = 'X RPL 1'): User
    {
        return User::factory()->create([
            'role' => 'siswa',
            'username' => 'siswa_test',
            'class_name' => $className,
        ]);
    }

    private function createExamWithMultipleTypes(User $creator): Exam
    {
        $exam = Exam::create([
            'title' => 'Ujian Komprehensif',
            'subject' => 'Teknologi Informasi',
            'target_type' => 'umum',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(2),
            'duration_minutes' => 90,
            'token' => 'TESTOK',
            'is_published' => true,
            'created_by' => $creator->id,
        ]);

        // Tambah 3 PG
        for ($i = 1; $i <= 3; $i++) {
            Question::create([
                'exam_id' => $exam->id,
                'position' => $i,
                'type' => 'PG',
                'question_text' => "Soal PG {$i}",
                'options' => ['A' => 'Opsi A', 'B' => 'Opsi B', 'C' => 'Opsi C', 'D' => 'Opsi D'],
                'answer_key' => 'A',
                'weight' => 1.0,
            ]);
        }

        // Tambah 3 Benar/Salah
        for ($i = 4; $i <= 6; $i++) {
            Question::create([
                'exam_id' => $exam->id,
                'position' => $i,
                'type' => 'BENAR_SALAH',
                'question_text' => "Soal BS {$i}",
                'options' => ['A' => 'Benar', 'B' => 'Salah'],
                'answer_key' => 'A',
                'weight' => 1.0,
            ]);
        }

        // Tambah 2 Menjodohkan
        for ($i = 7; $i <= 8; $i++) {
            Question::create([
                'exam_id' => $exam->id,
                'position' => $i,
                'type' => 'MENJODOHKAN',
                'question_text' => "Soal Menjodohkan {$i}",
                'options' => [
                    'A' => 'Premis 1',
                    'B' => 'Premis 2',
                    '__pairs' => ['Pasangan 1', 'Pasangan 2']
                ],
                'answer_key' => 'A:1,B:2',
                'weight' => 2.0,
            ]);
        }

        return $exam;
    }

    public function test_student_exam_screen_renders_per_page_controls_and_palette(): void
    {
        $admin = $this->createAdmin();
        $exam = $this->createExamWithMultipleTypes($admin);
        $student = $this->createStudent();

        $attempt = Attempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'started_at' => now(),
            'token_verified' => true,
        ]);

        $response = $this->actingAs($student)->get(route('attempts.show', $attempt));

        $response->assertStatus(200);
        // Memastikan elemen navigasi per halaman ada
        $response->assertSee('qpage_0', false);
        $response->assertSee('qpage_1', false);
        $response->assertSee('btnPrev');
        $response->assertSee('btnNext');
        $response->assertSee('btnDoubt');
        $response->assertSee('paletteModal');
        $response->assertSee('Daftar Nomor Soal');
        $response->assertSee('palette_btn_0', false);
        $response->assertSee('navigateQuestion', false);
        $response->assertSee('openQuestionPalette', false);
        $response->assertSee('submitBtnContainer');
        $response->assertSee('btnSubmitPage');
        $response->assertSee('isLastQuestion');
    }

    public function test_submit_button_visibility_initial_state_based_on_total_questions(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();

        // 1. Ujian dengan banyak soal (>1) -> tombol kumpulkan di bar disembunyikan awalnya
        $multiExam = $this->createExamWithMultipleTypes($admin);
        $multiAttempt = Attempt::create([
            'exam_id' => $multiExam->id,
            'user_id' => $student->id,
            'started_at' => now(),
            'token_verified' => true,
        ]);

        $resMulti = $this->actingAs($student)->get(route('attempts.show', $multiAttempt));
        $resMulti->assertStatus(200);
        $resMulti->assertSee('id="submitBtnContainer"', false);
        $resMulti->assertSee('display: none !important;', false);

        // 2. Ujian dengan 1 butir soal saja -> soal pertama adalah soal terakhir, langsung tampil
        $singleExam = Exam::create([
            'title' => 'Ujian 1 Soal',
            'subject' => 'TIK',
            'target_type' => 'umum',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(2),
            'duration_minutes' => 30,
            'token' => 'ONEQ',
            'is_published' => true,
            'created_by' => $admin->id,
        ]);
        Question::create([
            'exam_id' => $singleExam->id,
            'position' => 1,
            'type' => 'PG',
            'question_text' => 'Soal Tunggal',
            'options' => ['A' => 'A', 'B' => 'B'],
            'answer_key' => 'A',
            'weight' => 100,
        ]);

        $singleAttempt = Attempt::create([
            'exam_id' => $singleExam->id,
            'user_id' => $student->id,
            'started_at' => now(),
            'token_verified' => true,
        ]);

        $resSingle = $this->actingAs($student)->get(route('attempts.show', $singleAttempt));
        $resSingle->assertStatus(200);
        $resSingle->assertSee('id="submitBtnContainer"', false);
        $resSingle->assertDontSee('id="submitBtnContainer" style="display: none !important;"', false);
    }

    public function test_admin_and_teacher_can_shuffle_questions_by_type(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithMultipleTypes($teacher);

        // Guru pemilik ujian mengacak soal
        $response = $this->actingAs($teacher)
            ->post(route('questions.shuffle', $exam));

        $response->assertRedirect(route('questions.list', $exam));
        $response->assertSessionHas('success');

        // Pastikan kelompok jenis soal tetap berurutan: PG (pos 1-3), BS (pos 4-6), Menjodohkan (pos 7-8)
        $questions = $exam->questions()->orderBy('position')->get();
        $this->assertCount(8, $questions);

        $this->assertEquals('PG', $questions[0]->type);
        $this->assertEquals('PG', $questions[1]->type);
        $this->assertEquals('PG', $questions[2]->type);

        $this->assertEquals('BENAR_SALAH', $questions[3]->type);
        $this->assertEquals('BENAR_SALAH', $questions[4]->type);
        $this->assertEquals('BENAR_SALAH', $questions[5]->type);

        $this->assertEquals('MENJODOHKAN', $questions[6]->type);
        $this->assertEquals('MENJODOHKAN', $questions[7]->type);

        // Pastikan nomor posisi berurutan dari 1 sampai 8
        $positions = $questions->pluck('position')->toArray();
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8], $positions);

        // Admin juga bisa mengacak ujian guru
        $admin = $this->createAdmin();
        $resAdmin = $this->actingAs($admin)
            ->post(route('questions.shuffle', $exam));

        $resAdmin->assertRedirect(route('questions.list', $exam));
        $resAdmin->assertSessionHas('success');
    }

    public function test_teacher_cannot_shuffle_other_teachers_exam(): void
    {
        $teacher1 = $this->createTeacher();
        $teacher2 = User::factory()->create(['role' => 'guru', 'username' => 'guru_lain']);
        $exam = $this->createExamWithMultipleTypes($teacher1);

        $response = $this->actingAs($teacher2)
            ->post(route('questions.shuffle', $exam));

        $response->assertStatus(403);
    }

    public function test_student_cannot_shuffle_questions(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithMultipleTypes($teacher);
        $student = $this->createStudent();

        $response = $this->actingAs($student)
            ->post(route('questions.shuffle', $exam));

        $response->assertStatus(403);
    }

    public function test_exam_randomize_by_type_setting_shuffles_per_student_attempt(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithMultipleTypes($teacher);
        $exam->update(['randomize_by_type' => true]);

        $student1 = $this->createStudent('X RPL 1');
        $student2 = User::factory()->create(['role' => 'siswa', 'username' => 'siswa_dua', 'class_name' => 'X RPL 1']);

        $attempt1 = Attempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student1->id,
            'started_at' => now(),
            'token_verified' => true,
        ]);

        $attempt2 = Attempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student2->id,
            'started_at' => now(),
            'token_verified' => true,
        ]);

        // Student 1 membuka halaman ujian
        $res1 = $this->actingAs($student1)->get(route('attempts.show', $attempt1));
        $res1->assertStatus(200);

        $attempt1->refresh();
        $this->assertNotNull($attempt1->question_order);
        $this->assertCount(8, $attempt1->question_order);

        // Cek bahwa order untuk student 1 tetap dikelompokkan: PG (3 soal), BS (3 soal), Menjodohkan (2 soal)
        $qMap = Question::whereIn('id', $attempt1->question_order)->pluck('type', 'id');
        $types1 = array_map(fn($id) => $qMap[$id], $attempt1->question_order);

        $this->assertEquals(['PG', 'PG', 'PG', 'BENAR_SALAH', 'BENAR_SALAH', 'BENAR_SALAH', 'MENJODOHKAN', 'MENJODOHKAN'], $types1);

        // Student 2 membuka halaman ujian
        $res2 = $this->actingAs($student2)->get(route('attempts.show', $attempt2));
        $res2->assertStatus(200);

        $attempt2->refresh();
        $this->assertNotNull($attempt2->question_order);
        $this->assertCount(8, $attempt2->question_order);

        // Cek bahwa reloading halaman attempt 1 menghasilkan urutan yang persis sama
        $res1Reload = $this->actingAs($student1)->get(route('attempts.show', $attempt1));
        $res1Reload->assertStatus(200);
        $attempt1AfterReload = Attempt::find($attempt1->id);
        $this->assertEquals($attempt1->question_order, $attempt1AfterReload->question_order);
    }

    public function test_question_index_supports_filtering_and_pagination(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithMultipleTypes($teacher);

        // Buka kelola soal biasa
        $res = $this->actingAs($teacher)->get(route('questions.list', $exam));
        $res->assertStatus(200);
        $res->assertSee('Acak Urutan Soal per Jenis');
        $res->assertSee('Pilihan Ganda');
        $res->assertSee('Benar/Salah');
        $res->assertSee('Menjodohkan');

        // Buka kelola soal dengan filter jenis PG
        $resPg = $this->actingAs($teacher)->get(route('questions.list', ['exam' => $exam->id, 'type' => 'PG']));
        $resPg->assertStatus(200);
        $resPg->assertSee('Soal PG 1');
        $resPg->assertDontSee('Soal BS 4');

        // Tambah soal hingga 25 butir untuk menguji render pagination Bootstrap 5 bersih
        for ($i = 9; $i <= 25; $i++) {
            Question::create([
                'exam_id' => $exam->id,
                'position' => $i,
                'type' => 'PG',
                'question_text' => "Soal PG Tambahan {$i}",
                'options' => ['A' => 'A', 'B' => 'B'],
                'answer_key' => 'A',
                'weight' => 1.0,
            ]);
        }

        $resPagination = $this->actingAs($teacher)->get(route('questions.list', $exam));
        $resPagination->assertStatus(200);
        $resPagination->assertSee('Menampilkan');
        $resPagination->assertSee('dari');
        $resPagination->assertSee('pagination');
        $resPagination->assertSee('page-item');
        $resPagination->assertDontSee('Showing 1 to');
        $resPagination->assertDontSee('w-5 h-5');
    }
}
