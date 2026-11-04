<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KepalaSekolahTest extends TestCase
{
    use RefreshDatabase;

    private function createKepsek(): User
    {
        return User::firstOrCreate(
            ['username' => 'kepsek'],
            [
                'name'           => 'Drs. H. Ahmad Fauzi, M.Pd',
                'email'          => 'kepsek@cbt.local',
                'password'       => 'Password123!',
                'plain_password' => 'Password123!',
                'role'           => 'kepala_sekolah',
            ]
        );
    }

    private function createAdmin(): User
    {
        return User::firstOrCreate(
            ['username' => 'admin_test'],
            [
                'name'     => 'Admin Utama',
                'email'    => 'admin@cbt.local',
                'password' => 'Password123!',
                'role'     => 'admin',
            ]
        );
    }

    public function test_kepala_sekolah_can_login(): void
    {
        $kepsek = $this->createKepsek();

        $res = $this->post(route('login.post'), [
            'username' => 'kepsek',
            'password' => 'Password123!',
        ]);

        $res->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($kepsek);
    }

    public function test_kepala_sekolah_dashboard_displays_executive_metrics(): void
    {
        $kepsek = $this->createKepsek();
        $admin = $this->createAdmin();

        $exam = Exam::create([
            'title'            => 'UAS Bahasa Indonesia',
            'subject'          => 'Bahasa Indonesia',
            'target_type'      => 'umum',
            'starts_at'        => now()->subHour(),
            'ends_at'          => now()->addHour(),
            'duration_minutes' => 60,
            'token'            => 'BINDO1',
            'is_published'     => true,
            'created_by'       => $admin->id,
        ]);

        $res = $this->actingAs($kepsek)->get(route('dashboard'));
        $res->assertStatus(200);
        $res->assertSee('Dashboard Eksekutif Kepala Sekolah');
        $res->assertSee('UAS Bahasa Indonesia');
        $res->assertSee('Live Monitoring Ujian');
    }

    public function test_kepala_sekolah_can_access_monitoring(): void
    {
        $kepsek = $this->createKepsek();
        $admin = $this->createAdmin();

        $student = User::create([
            'name'           => 'Ahmad Santoso',
            'username'       => 'siswa_ahmad',
            'student_number' => 'NIS001',
            'class_name'     => 'XI RPL 1',
            'password'       => 'Password123!',
            'role'           => 'siswa',
        ]);

        $exam = Exam::create([
            'title'            => 'Ujian Matematika Kejuruan',
            'subject'          => 'Matematika',
            'target_type'      => 'umum',
            'starts_at'        => now()->subMinutes(30),
            'ends_at'          => now()->addMinutes(30),
            'duration_minutes' => 60,
            'token'            => 'MTK123',
            'is_published'     => true,
            'created_by'       => $admin->id,
        ]);

        $attempt = Attempt::create([
            'exam_id'        => $exam->id,
            'user_id'        => $student->id,
            'started_at'     => now()->subMinutes(20),
            'token_verified' => true,
            'violations'     => 1,
            'violation_log'  => [['at' => now()->toIso8601String(), 'reason' => 'Pindah Tab']],
        ]);

        $res = $this->actingAs($kepsek)->get(route('monitoring.index', ['exam_id' => $exam->id]));
        $res->assertStatus(200);
        $res->assertSee('Monitoring Kegiatan Ujian');
        $res->assertSee('Ujian Matematika Kejuruan');
        $res->assertSee('Ahmad Santoso');
        $res->assertSee('1x Pelanggaran');
    }

    public function test_kepala_sekolah_can_view_exam_results_and_export_grades(): void
    {
        $kepsek = $this->createKepsek();

        // 1. Akses halaman hasil ujian
        $resResults = $this->actingAs($kepsek)->get(route('results.admin'));
        $resResults->assertStatus(200);

        // 2. Akses halaman rekap nilai
        $resGrades = $this->actingAs($kepsek)->get(route('admin.grades.index'));
        $resGrades->assertStatus(200);

        // 3. Export Excel rekap nilai
        $resExport = $this->actingAs($kepsek)->get(route('admin.grades.export'));
        $resExport->assertStatus(200);
        $resExport->assertHeader('Content-Disposition');
    }

    public function test_kepala_sekolah_cannot_perform_admin_only_management(): void
    {
        $kepsek = $this->createKepsek();

        // Tidak boleh mengelola siswa
        $resStudents = $this->actingAs($kepsek)->get(route('admin.students.index'));
        $resStudents->assertStatus(403);

        // Tidak boleh mengelola guru
        $resTeachers = $this->actingAs($kepsek)->get(route('admin.teachers.index'));
        $resTeachers->assertStatus(403);

        // Tidak boleh mengubah setting sekolah
        $resSettings = $this->actingAs($kepsek)->get(route('admin.school-settings.edit'));
        $resSettings->assertStatus(403);
    }

    public function test_admin_can_update_kepala_sekolah_profile_in_school_settings(): void
    {
        $admin = $this->createAdmin();
        $this->createKepsek();

        $res = $this->actingAs($admin)->post(route('admin.school-settings.update'), [
            'school_name'     => 'SMK Negeri 1 Surabaya',
            'kepsek_name'     => 'Prof. Dr. Bambang Sudarsono, M.Pd',
            'kepsek_password' => 'KepsekBaru2026!',
        ]);

        $res->assertRedirect(route('admin.school-settings.edit'));
        $this->assertDatabaseHas('users', [
            'username' => 'kepsek',
            'name'     => 'Prof. Dr. Bambang Sudarsono, M.Pd',
        ]);
    }
}
