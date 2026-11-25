<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $r)
    {
        $role = $r->user()->role;

        if ($role === 'siswa') {
            return view('dashboard', [
                'exams'        => Exam::where('is_published', true)->orderBy('starts_at')->get(),
                'attemptCount' => Attempt::where('user_id', $r->user()->id)->count(),
                'student'      => true,
                'isKepsek'     => false,
            ]);
        }

        if ($role === 'kepala_sekolah') {
            $now = now();
            $activeExams = Exam::where('is_published', true)
                ->where('starts_at', '<=', $now)
                ->where('ends_at', '>=', $now)
                ->get();

            $completedAttempts = Attempt::whereNotNull('submitted_at');
            $inProgressAttempts = Attempt::whereNull('submitted_at')->whereNotNull('started_at');

            return view('dashboard', [
                'student'              => false,
                'isKepsek'             => true,
                'examCount'            => Exam::count(),
                'activeExamsCount'     => $activeExams->count(),
                'inProgressCount'      => $inProgressAttempts->count(),
                'completedCount'       => $completedAttempts->count(),
                'averageScore'         => round((float) $completedAttempts->avg('score'), 2),
                'totalViolations'      => (int) Attempt::sum('violations'),
                'exams'                => Exam::latest('starts_at')->take(8)->get(),
                'recentAttempts'       => Attempt::with(['exam', 'user'])->whereNotNull('submitted_at')->latest('submitted_at')->take(8)->get(),
            ]);
        }

        if ($role === 'guru') {
            $user = $r->user();
            $myExams = Exam::where('created_by', $user->id);
            $myExamIds = (clone $myExams)->pluck('id');

            return view('dashboard', [
                'student'      => false,
                'isKepsek'     => false,
                'isGuru'       => true,
                'exams'        => (clone $myExams)->latest()->take(10)->get(),
                'examCount'    => (clone $myExams)->count(),
                'userCount'    => User::where('role', 'siswa')->count(),
                'attemptCount' => Attempt::whereIn('exam_id', $myExamIds)->whereNotNull('submitted_at')->count(),
            ]);
        }

        // Tampilan Admin
        return view('dashboard', [
            'student'      => false,
            'isKepsek'     => false,
            'isGuru'       => false,
            'exams'        => Exam::latest()->take(10)->get(),
            'examCount'    => Exam::count(),
            'userCount'    => User::count(),
            'attemptCount' => Attempt::count(),
        ]);
    }
}
