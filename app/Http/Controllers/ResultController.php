<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Exam;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function student(Request $r)
    {
        $attempts = Attempt::with('exam')
            ->where('user_id', $r->user()->id)
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->get();

        return view('results.student', compact('attempts'));
    }

    public function admin(Request $r)
    {
        $query = Attempt::with(['exam', 'user'])
            ->whereNotNull('submitted_at')
            ->latest('submitted_at');

        if ($r->filled('exam_id')) {
            $query->where('exam_id', $r->exam_id);
        }

        if ($r->filled('status')) {
            if ($r->status === 'released') {
                $query->where('is_released', true);
            } elseif ($r->status === 'unreleased') {
                $query->where('is_released', false);
            }
        }

        if ($r->filled('q')) {
            $keyword = $r->q;
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('student_number', 'like', "%{$keyword}%")
                  ->orWhere('class_name', 'like', "%{$keyword}%");
            });
        }

        $attempts = $query->paginate(30)->withQueryString();
        $exams = Exam::orderBy('title')->get(['id', 'title', 'subject']);

        return view('results.admin', compact('attempts', 'exams'));
    }

    public function release(Request $r, Attempt $attempt)
    {
        $attempt->update(['is_released' => true]);
        $attempt->exam->update(['results_released' => true]);

        return back()->with('success', 'Nilai siswa ' . $attempt->user->name . ' berhasil dirilis.');
    }

    /**
     * Rilis nilai massal:
     * - Berdasarkan ID attempt yang dipilih (checkbox)
     * - Atau berdasarkan Ujian tertentu (seluruh peserta dalam ujian)
     * - Atau seluruh ujian yang selesai
     */
    public function bulkRelease(Request $r)
    {
        // 1. Rilis berdasarkan pilihan checkbox peserta
        if ($r->filled('attempt_ids') && is_array($r->attempt_ids)) {
            $attempts = Attempt::with('exam')->whereIn('id', $r->attempt_ids)->get();
            $count = 0;

            foreach ($attempts as $a) {
                $a->update(['is_released' => true]);
                if ($a->exam && !$a->exam->results_released) {
                    $a->exam->update(['results_released' => true]);
                }
                $count++;
            }

            return back()->with('success', "Sebanyak {$count} nilai peserta berhasil dirilis.");
        }

        // 2. Rilis semua nilai untuk satu ujian tertentu
        if ($r->filled('exam_id')) {
            $exam = Exam::findOrFail($r->exam_id);
            $updated = Attempt::where('exam_id', $exam->id)
                ->whereNotNull('submitted_at')
                ->update(['is_released' => true]);

            $exam->update(['results_released' => true]);

            return back()->with('success', "Seluruh nilai ({$updated} peserta) untuk ujian '{$exam->title}' berhasil dirilis ke siswa.");
        }

        // 3. Rilis semua nilai di sistem
        if ($r->boolean('release_all')) {
            $updated = Attempt::whereNotNull('submitted_at')->update(['is_released' => true]);
            Exam::whereHas('attempts', fn($q) => $q->whereNotNull('submitted_at'))->update(['results_released' => true]);

            return back()->with('success', "Seluruh ({$updated}) nilai ujian yang telah selesai berhasil dirilis.");
        }

        return back()->withErrors(['release' => 'Pilih peserta atau ujian yang akan dirilis nilainya.']);
    }
}
