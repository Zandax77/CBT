<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin Utama',
            'username' => 'admin_' . uniqid(),
            'password' => 'Password123!',
            'role' => 'admin',
        ]);
    }

    private function createTeacher(): User
    {
        return User::create([
            'name' => 'Guru Pengampu',
            'username' => 'guru_' . uniqid(),
            'password' => 'Password123!',
            'role' => 'guru',
        ]);
    }

    private function createStudent(string $className = 'XI RPL 1'): User
    {
        return User::create([
            'name' => 'Siswa Test ' . uniqid(),
            'username' => 'siswa_' . uniqid(),
            'student_number' => 'NIS' . rand(10000, 99999),
            'class_name' => $className,
            'password' => 'Password123!',
            'role' => 'siswa',
        ]);
    }

    public function test_admin_can_delete_single_exam(): void
    {
        $admin = $this->createAdmin();
        $exam = Exam::create([
            'title' => 'Ujian Bahasa Indonesia',
            'subject' => 'Bahasa Indonesia',
            'target_type' => 'umum',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'duration_minutes' => 60,
            'token' => 'ABCDEF',
            'created_by' => $admin->id,
            'is_published' => true,
        ]);

        $res = $this->actingAs($admin)->delete(route('exams.destroy', $exam));
        $res->assertRedirect(route('exams.index'));
        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }

    public function test_admin_can_bulk_delete_exams(): void
    {
        $admin = $this->createAdmin();
        $e1 = Exam::create([
            'title' => 'Ujian Matematika 1',
            'subject' => 'Matematika',
            'target_type' => 'umum',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'duration_minutes' => 60,
            'token' => 'TOK001',
            'created_by' => $admin->id,
        ]);
        $e2 = Exam::create([
            'title' => 'Ujian Matematika 2',
            'subject' => 'Matematika',
            'target_type' => 'umum',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'duration_minutes' => 60,
            'token' => 'TOK002',
            'created_by' => $admin->id,
        ]);

        $res = $this->actingAs($admin)->post(route('exams.bulk_destroy'), [
            'exam_ids' => [$e1->id, $e2->id],
        ]);
        $res->assertRedirect(route('exams.index'));
        $this->assertDatabaseMissing('exams', ['id' => $e1->id]);
        $this->assertDatabaseMissing('exams', ['id' => $e2->id]);
    }

    public function test_admin_can_delete_single_teacher_and_their_exams(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $exam = Exam::create([
            'title' => 'Ujian Milik Guru',
            'subject' => 'Fisika',
            'target_type' => 'umum',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'duration_minutes' => 60,
            'token' => 'FISIKA',
            'created_by' => $teacher->id,
        ]);

        $res = $this->actingAs($admin)->delete(route('admin.teachers.destroy', $teacher));
        $res->assertRedirect(route('admin.teachers.index'));
        $this->assertDatabaseMissing('users', ['id' => $teacher->id]);
        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }

    public function test_admin_can_bulk_delete_teachers(): void
    {
        $admin = $this->createAdmin();
        $t1 = $this->createTeacher();
        $t2 = $this->createTeacher();

        $res = $this->actingAs($admin)->post(route('admin.teachers.bulk_destroy'), [
            'teacher_ids' => [$t1->id, $t2->id],
        ]);
        $res->assertRedirect(route('admin.teachers.index'));
        $this->assertDatabaseMissing('users', ['id' => $t1->id]);
        $this->assertDatabaseMissing('users', ['id' => $t2->id]);
    }

    public function test_admin_can_delete_single_student(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();

        $res = $this->actingAs($admin)->delete(route('admin.students.destroy', $student));
        $res->assertRedirect(route('admin.students.index'));
        $this->assertDatabaseMissing('users', ['id' => $student->id]);
    }

    public function test_admin_can_bulk_delete_students(): void
    {
        $admin = $this->createAdmin();
        $s1 = $this->createStudent();
        $s2 = $this->createStudent();

        $res = $this->actingAs($admin)->post(route('admin.students.bulk_destroy'), [
            'student_ids' => [$s1->id, $s2->id],
        ]);
        $res->assertRedirect(route('admin.students.index'));
        $this->assertDatabaseMissing('users', ['id' => $s1->id]);
        $this->assertDatabaseMissing('users', ['id' => $s2->id]);
    }

    public function test_token_validity_setting_and_expiration(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();

        // 1. Buat ujian dengan masa aktif token 15 menit
        $exam = Exam::create([
            'title' => 'Ujian Kimia',
            'subject' => 'Kimia',
            'target_type' => 'umum',
            'starts_at' => now()->subMinutes(20),
            'ends_at' => now()->addHour(),
            'duration_minutes' => 60,
            'token' => 'KIM123',
            'token_valid_minutes' => 15,
            'token_expires_at' => now()->subMinutes(5), // Sudah kedaluwarsa 5 menit lalu
            'is_published' => true,
            'created_by' => $admin->id,
        ]);

        $this->assertTrue($exam->isTokenExpired());

        // 2. Siswa mencoba masuk ujian dengan token yang kedaluwarsa -> harus ditolak
        $response = $this->actingAs($student)->post(route('attempts.start', $exam), [
            'token' => 'KIM123',
        ]);
        $response->assertSessionHasErrors(['token']);
        $this->assertDatabaseMissing('attempts', [
            'exam_id' => $exam->id,
            'user_id' => $student->id,
        ]);

        // 3. Admin merotasi / memperbarui token
        $rotateRes = $this->actingAs($admin)->post(route('exams.rotate_token', $exam));
        $exam->refresh();

        $this->assertFalse($exam->isTokenExpired());
        $this->assertNotNull($exam->token_expires_at);

        // 4. Siswa mencoba masuk lagi dengan token baru -> berhasil
        $response = $this->actingAs($student)->post(route('attempts.start', $exam), [
            'token' => $exam->token,
        ]);
        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('attempts', [
            'exam_id' => $exam->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_admin_can_bulk_release_results(): void
    {
        $admin = $this->createAdmin();
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();

        $exam = Exam::create([
            'title' => 'Ujian Biologi',
            'subject' => 'Biologi',
            'target_type' => 'umum',
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->addHour(),
            'duration_minutes' => 60,
            'token' => 'BIO001',
            'is_published' => true,
            'results_released' => false,
            'created_by' => $admin->id,
        ]);

        $attempt1 = Attempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student1->id,
            'started_at' => now()->subHours(2),
            'submitted_at' => now()->subHour(),
            'score' => 85.50,
            'is_released' => false,
            'token_verified' => true,
        ]);

        $attempt2 = Attempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student2->id,
            'started_at' => now()->subHours(2),
            'submitted_at' => now()->subHour(),
            'score' => 90.00,
            'is_released' => false,
            'token_verified' => true,
        ]);

        // 1. Rilis massal berdasarkan checkbox attempt_ids
        $res = $this->actingAs($admin)->post(route('results.bulk_release'), [
            'attempt_ids' => [$attempt1->id],
        ]);
        $res->assertSessionHas('success');
        $this->assertTrue($attempt1->fresh()->is_released);
        $this->assertFalse($attempt2->fresh()->is_released);

        // 2. Rilis massal berdasarkan ujian (semua peserta dalam ujian tersebut)
        $res = $this->actingAs($admin)->post(route('results.bulk_release'), [
            'exam_id' => $exam->id,
        ]);
        $res->assertSessionHas('success');
        $this->assertTrue($attempt2->fresh()->is_released);
        $this->assertTrue($exam->fresh()->results_released);
    }
}
