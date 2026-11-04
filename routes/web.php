<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\QuestionImportController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\AttemptController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\GradeExportController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\SchoolSettingController;
use App\Http\Controllers\ExamMonitoringController;

Route::get('/', fn () => redirect()->route('login'));
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/template-soal.xlsx', [QuestionImportController::class, 'template'])->middleware('role:admin,guru')->name('questions.template');

    Route::middleware('role:admin,guru')->group(function () {
        Route::post('/exams/bulk-destroy', [ExamController::class, 'bulkDestroy'])->name('exams.bulk_destroy');
        Route::resource('exams', ExamController::class)->except(['show']);
        Route::get('/exams/{exam}/import', [QuestionImportController::class, 'form'])->name('questions.import.form');
        Route::post('/exams/{exam}/import', [QuestionImportController::class, 'import'])->name('questions.import');
        Route::post('/exams/{exam}/rotate-token', [ExamController::class, 'rotateToken'])->name('exams.rotate_token');

        // Kelola soal manual (dengan gambar)
        Route::get('/exams/{exam}/soal', [QuestionController::class, 'index'])->name('questions.list');
        Route::get('/exams/{exam}/soal/tambah', [QuestionController::class, 'create'])->name('questions.create');
        Route::post('/exams/{exam}/soal', [QuestionController::class, 'store'])->name('questions.store');
        Route::get('/exams/{exam}/soal/{question}/edit', [QuestionController::class, 'edit'])->name('questions.edit');
        Route::put('/exams/{exam}/soal/{question}', [QuestionController::class, 'update'])->name('questions.update');
        Route::delete('/exams/{exam}/soal/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
    });

    Route::middleware('role:siswa')->group(function () {
        Route::get('/ujian', [AttemptController::class, 'index'])->name('attempts.index');
        Route::post('/ujian/{exam}/mulai', [AttemptController::class, 'start'])->name('attempts.start');
        Route::get('/pengerjaan/{attempt}', [AttemptController::class, 'show'])->name('attempts.show');
        Route::post('/pengerjaan/{attempt}/jawaban', [AttemptController::class, 'save'])->name('attempts.save');
        Route::post('/pengerjaan/{attempt}/selesai', [AttemptController::class, 'submit'])->name('attempts.submit');
        Route::post('/pengerjaan/{attempt}/pelanggaran', [AttemptController::class, 'reportViolation'])->name('attempts.violation');
        Route::get('/hasil', [ResultController::class, 'student'])->name('results.student');
    });

    // Monitoring & Hasil Ujian (Admin & Kepala Sekolah)
    Route::middleware('role:admin,kepala_sekolah')->group(function () {
        Route::get('/monitoring', [ExamMonitoringController::class, 'index'])->name('monitoring.index');
        Route::get('/admin/hasil', [ResultController::class, 'admin'])->name('results.admin');
        Route::get('/admin/rekap-nilai', [GradeExportController::class, 'index'])->name('admin.grades.index');
        Route::get('/admin/rekap-nilai/export', [GradeExportController::class, 'export'])->name('admin.grades.export');
    });

    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/hasil/rilis-masal', [ResultController::class, 'bulkRelease'])->name('results.bulk_release');
        Route::post('/admin/hasil/{attempt}/rilis', [ResultController::class, 'release'])->name('results.release');

        // Pengelolaan siswa (hanya admin)
        Route::post('/admin/siswa-bulk-destroy', [StudentController::class, 'bulkDestroy'])->name('admin.students.bulk_destroy');
        Route::resource('admin/siswa', StudentController::class)
            ->except(['show'])
            ->names('admin.students');

        Route::get('/admin/siswa-template.xlsx', [StudentController::class, 'template'])
            ->name('admin.students.template');

        Route::post('/admin/siswa-import', [StudentController::class, 'import'])
            ->name('admin.students.import');

        Route::get('/admin/siswa/{siswa}/cetak-kartu', [StudentController::class, 'printCard'])
            ->name('admin.students.print_card');

        Route::get('/admin/cetak-kartu-massal', [StudentController::class, 'printCards'])
            ->name('admin.students.print_cards');

        // Pengelolaan guru (hanya admin)
        Route::post('/admin/guru-bulk-destroy', [TeacherController::class, 'bulkDestroy'])->name('admin.teachers.bulk_destroy');
        Route::get('/admin/guru-template.xlsx', [TeacherController::class, 'template'])
            ->name('admin.teachers.template');

        Route::post('/admin/guru-import', [TeacherController::class, 'import'])
            ->name('admin.teachers.import');

        Route::resource('admin/guru', TeacherController::class, ['parameters' => ['guru' => 'guru']])
            ->except(['show'])
            ->names('admin.teachers');

        // Pengaturan Sekolah (hanya admin)
        Route::get('/admin/pengaturan-sekolah', [SchoolSettingController::class, 'edit'])
            ->name('admin.school-settings.edit');
        Route::post('/admin/pengaturan-sekolah', [SchoolSettingController::class, 'update'])
            ->name('admin.school-settings.update');
    });
});
