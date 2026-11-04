<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Http\Request;

class ExamMonitoringController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil daftar seluruh ujian yang dipublikasikan
        $now = now();
        $nowStr = $now->toDateTimeString();
        $exams = Exam::where('is_published', true)
            ->orderByRaw("CASE WHEN starts_at <= ? AND ends_at >= ? THEN 1 ELSE 2 END", [$nowStr, $nowStr])
            ->latest('starts_at')
            ->get();

        // 2. Tentukan ujian yang sedang dipantau
        $selectedExam = null;
        if ($request->filled('exam_id')) {
            $selectedExam = Exam::find($request->exam_id);
        }

        if (!$selectedExam) {
            // Prioritas: Ujian yang sedang aktif saat ini
            $selectedExam = $exams->first(fn($e) => $e->isActive()) ?? $exams->first();
        }

        // 3. Ringkasan Global Aktivitas Hari Ini
        $now = now();
        $globalStats = [
            'active_exams'     => Exam::where('is_published', true)->where('starts_at', '<=', $now)->where('ends_at', '>=', $now)->count(),
            'students_online'  => Attempt::whereNull('submitted_at')->whereNotNull('started_at')->count(),
            'total_submitted'  => Attempt::whereNotNull('submitted_at')->count(),
            'total_violations' => (int) Attempt::sum('violations'),
        ];

        // 4. Statistik & Data Peserta Ujian Terpilih
        $attempts = collect();
        $stats = [
            'total_eligible'        => 0,
            'started_count'         => 0,
            'in_progress_count'     => 0,
            'submitted_count'       => 0,
            'unstarted_count'       => 0,
            'violations_count'      => 0,
            'force_submitted_count' => 0,
            'avg_score'             => 0,
            'max_score'             => 0,
            'min_score'             => 0,
        ];

        if ($selectedExam) {
            $allStudents = User::where('role', 'siswa')->get();
            $eligibleStudents = $allStudents->filter(fn($s) => $selectedExam->matchesStudent($s));
            $totalEligible = $eligibleStudents->count();

            $baseAttempts = Attempt::with('user')->where('exam_id', $selectedExam->id)->get();

            $startedCount        = $baseAttempts->whereNotNull('started_at')->count();
            $inProgressCount     = $baseAttempts->whereNotNull('started_at')->whereNull('submitted_at')->count();
            $submittedAttempts   = $baseAttempts->whereNotNull('submitted_at');
            $submittedCount      = $submittedAttempts->count();
            $violationsCount     = (int) $baseAttempts->sum('violations');
            $forceSubmittedCount = $baseAttempts->where('force_submitted', true)->count();

            $avgScore = $submittedCount > 0 ? round((float) $submittedAttempts->avg('score'), 2) : 0;
            $maxScore = $submittedCount > 0 ? round((float) $submittedAttempts->max('score'), 2) : 0;
            $minScore = $submittedCount > 0 ? round((float) $submittedAttempts->min('score'), 2) : 0;

            $stats = [
                'total_eligible'        => $totalEligible,
                'started_count'         => $startedCount,
                'in_progress_count'     => $inProgressCount,
                'submitted_count'       => $submittedCount,
                'unstarted_count'       => max(0, $totalEligible - $startedCount),
                'violations_count'      => $violationsCount,
                'force_submitted_count' => $forceSubmittedCount,
                'avg_score'             => $avgScore,
                'max_score'             => $maxScore,
                'min_score'             => $minScore,
            ];

            // Filter Query untuk Tabel
            $query = Attempt::with(['user', 'exam'])
                ->where('exam_id', $selectedExam->id);

            if ($request->filled('status')) {
                if ($request->status === 'in_progress') {
                    $query->whereNotNull('started_at')->whereNull('submitted_at');
                } elseif ($request->status === 'submitted') {
                    $query->whereNotNull('submitted_at');
                } elseif ($request->status === 'violations') {
                    $query->where('violations', '>', 0);
                } elseif ($request->status === 'force_submitted') {
                    $query->where('force_submitted', true);
                }
            }

            if ($request->filled('kelas')) {
                $query->whereHas('user', fn($q) => $q->where('class_name', $request->kelas));
            }

            if ($request->filled('q')) {
                $kw = $request->q;
                $query->whereHas('user', function ($q) use ($kw) {
                    $q->where('name', 'like', "%{$kw}%")
                      ->orWhere('student_number', 'like', "%{$kw}%")
                      ->orWhere('class_name', 'like', "%{$kw}%");
                });
            }

            $attempts = $query->latest('started_at')->paginate(25)->withQueryString();
        }

        $classes = User::where('role', 'siswa')
            ->whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'globalStats' => $globalStats,
                'stats'       => $stats,
            ]);
        }

        return view('monitoring.index', compact('exams', 'selectedExam', 'attempts', 'stats', 'globalStats', 'classes'));
    }
}
