<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamParticipantsTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'username' => 'admin_cbt',
        ]);
    }

    private function createTeacher(string $username = 'guru_cbt'): User
    {
        return User::factory()->create([
            'role' => 'guru',
            'username' => $username,
        ]);
    }

    private function createStudent(string $name, string $username, string $className = 'X RPL 1', string $nis = '1001'): User
    {
        return User::factory()->create([
            'role' => 'siswa',
            'name' => $name,
            'username' => $username,
            'class_name' => $className,
            'student_number' => $nis,
        ]);
    }

    public function test_manage_exams_page_displays_participant_data_and_status(): void
    {
        $teacher = $this->createTeacher();
        $student1 = $this->createStudent('Budi Santoso', 'budi', 'X RPL 1', '101');
        $student2 = $this->createStudent('Siti Rahma', 'siti', 'X RPL 1', '102');
        $student3 = $this->createStudent('Doni Kusuma', 'doni', 'X RPL 1', '103');

        $exam = Exam::create([
            'title' => 'Penilaian Harian Pemrograman Web',
            'subject' => 'Pemrograman Web',
            'target_type' => 'kelas',
            'class_name' => 'X RPL 1',
            'starts_at' => now()->subMinutes(30),
            'ends_at' => now()->addMinutes(60),
            'duration_minutes' => 90,
            'token' => 'WEBDEV',
            'is_published' => true,
            'created_by' => $teacher->id,
        ]);

        // Student 1: Sudah selesai
        Attempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student1->id,
            'started_at' => now()->subMinutes(25),
            'submitted_at' => now()->subMinutes(5),
            'score' => 88.5,
            'token_verified' => true,
        ]);

        // Student 2: Sedang mengerjakan dengan 1 pelanggaran
        Attempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student2->id,
            'started_at' => now()->subMinutes(15),
            'submitted_at' => null,
            'violations' => 1,
            'violation_log' => [['time' => '10:00:00', 'reason' => 'Pindah tab']],
            'token_verified' => true,
        ]);

        // Student 3: Belum mulai (tidak ada attempt)

        // Akses halaman kelola ujian oleh guru pemilik
        $response = $this->actingAs($teacher)->get(route('exams.index'));

        $response->assertStatus(200);
        $response->assertSee('Data &amp; Status Peserta', false);
        $response->assertSee('Status Peserta');
        $response->assertSee('2</span>', false); // 2 yang mengikuti
        $response->assertSee('/ 3 Siswa', false); // dari 3 siswa eligible
        $response->assertSee('1 Mengerjakan');
        $response->assertSee('1 Selesai');
        $response->assertSee('1 Belum Mulai');
        $response->assertSee('1 Melanggar');
        $response->assertSee('openParticipantsModal');
        $response->assertSee('participantsModal');
    }

    public function test_teacher_can_fetch_participant_details_via_json(): void
    {
        $teacher = $this->createTeacher();
        $student1 = $this->createStudent('Ahmad Dahlan', 'ahmad', 'X TKJ 1', '201');
        $student2 = $this->createStudent('Bambang Pamungkas', 'bambang', 'X TKJ 1', '202');

        $exam = Exam::create([
            'title' => 'Ujian Jaringan Dasar',
            'subject' => 'Jaringan Komputer',
            'target_type' => 'kelas',
            'class_name' => 'X TKJ 1',
            'starts_at' => now()->subMinutes(20),
            'ends_at' => now()->addMinutes(40),
            'duration_minutes' => 60,
            'token' => 'NETWRK',
            'is_published' => true,
            'created_by' => $teacher->id,
        ]);

        Attempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student1->id,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now()->subMinute(),
            'score' => 95.0,
            'token_verified' => true,
        ]);

        $response = $this->actingAs($teacher)->json('GET', route('exams.participants', $exam));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'exam' => ['id', 'title', 'subject', 'token', 'status_label'],
            'stats' => ['total_eligible', 'started_count', 'in_progress_count', 'submitted_count', 'unstarted_count'],
            'classes',
            'participants' => [
                '*' => ['id', 'name', 'student_number', 'class_name', 'status', 'status_label']
            ]
        ]);

        $json = $response->json();
        $this->assertEquals(2, $json['stats']['total_eligible']);
        $this->assertEquals(1, $json['stats']['submitted_count']);
        $this->assertEquals(1, $json['stats']['unstarted_count']);
        $this->assertEquals(0, $json['stats']['in_progress_count']);

        $names = collect($json['participants'])->pluck('name')->toArray();
        $this->assertContains('Ahmad Dahlan', $names);
        $this->assertContains('Bambang Pamungkas', $names);
    }

    public function test_teacher_cannot_view_participants_of_other_teachers_exam(): void
    {
        $teacher1 = $this->createTeacher('guru_satu');
        $teacher2 = $this->createTeacher('guru_dua');

        $exam = Exam::create([
            'title' => 'Ujian Fisika Guru 1',
            'subject' => 'Fisika',
            'target_type' => 'umum',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'duration_minutes' => 60,
            'token' => 'FISIKA',
            'is_published' => true,
            'created_by' => $teacher1->id,
        ]);

        $response = $this->actingAs($teacher2)->get(route('exams.participants', $exam));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_participants_of_any_exam(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $student = $this->createStudent('Citra Lestari', 'citra', 'XI IPA 1', '301');

        $exam = Exam::create([
            'title' => 'Ujian Kimia',
            'subject' => 'Kimia',
            'target_type' => 'umum',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'duration_minutes' => 60,
            'token' => 'KIMIA1',
            'is_published' => true,
            'created_by' => $teacher->id,
        ]);

        $response = $this->actingAs($admin)->get(route('exams.participants', $exam));
        $response->assertStatus(200);
        $response->assertSee('Data &amp; Status Peserta: Ujian Kimia', false);
        $response->assertSee('Citra Lestari');
    }

    public function test_student_cannot_access_participants_page(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent('Eko Prasetyo', 'eko', 'XII TKJ 2', '401');

        $exam = Exam::create([
            'title' => 'Ujian Bahasa Inggris',
            'subject' => 'Bahasa Inggris',
            'target_type' => 'umum',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'duration_minutes' => 60,
            'token' => 'ENG001',
            'is_published' => true,
            'created_by' => $teacher->id,
        ]);

        $response = $this->actingAs($student)->get(route('exams.participants', $exam));
        $response->assertStatus(403);
    }
}
