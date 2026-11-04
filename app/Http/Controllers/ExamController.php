<?php
namespace App\Http\Controllers;

use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamController extends Controller
{
    /** Scope query berdasarkan peran: guru hanya lihat ujiannya sendiri. */
    private function scopedQuery()
    {
        $q = Exam::with(['creator', 'questions']);
        if (auth()->user()->role === 'guru') {
            $q->where('created_by', auth()->id());
        }
        return $q;
    }

    public function index()
    {
        // Rotasi token untuk semua ujian aktif milik user jika ada yang sudah saatnya
        $this->scopedQuery()->get()->each->rotateTokenIfNeeded();

        $exams = $this->scopedQuery()->latest()->paginate(15);
        return view('exams.index', compact('exams'));
    }

    public function create()
    {
        return view('exams.form', ['exam' => new Exam(['target_type' => 'umum'])]);
    }

    public function store(Request $r)
    {
        $d = $this->validateData($r);
        $d['created_by']            = $r->user()->id;
        $d['token']                 = !empty($d['token']) ? Str::upper($d['token']) : Str::upper(Str::random(6));
        $d['token_last_rotated_at'] = now();

        $startTime = Carbon::parse($d['starts_at']);
        if (!empty($d['token_valid_minutes'])) {
            $baseTime = $startTime->isFuture() ? $startTime : now();
            $d['token_expires_at'] = $baseTime->copy()->addMinutes((int) $d['token_valid_minutes']);
        } else {
            $d['token_expires_at'] = null;
        }

        $exam = Exam::create($d);
        return redirect()->route('questions.list', $exam)
            ->with('success', 'Ujian "' . $exam->title . '" berhasil dibuat. Token akses: ' . $exam->token);
    }

    public function edit(Exam $exam)
    {
        $this->authorizeExam($exam);
        return view('exams.form', compact('exam'));
    }

    public function update(Request $r, Exam $exam)
    {
        $this->authorizeExam($exam);
        $d = $this->validateData($r);
        
        if (empty($d['token'])) {
            unset($d['token']);
        } else {
            $d['token'] = Str::upper($d['token']);
        }

        $startTime = Carbon::parse($d['starts_at']);
        if (!empty($d['token_valid_minutes'])) {
            // Jika token diubah atau token_valid_minutes baru diatur/diubah, set ulang masa aktifnya
            if ($exam->token_valid_minutes != $d['token_valid_minutes'] 
                || !empty($r->reset_token_expiry) 
                || $exam->isTokenExpired()
                || empty($exam->token_expires_at)) {
                $baseTime = $startTime->isFuture() ? $startTime : now();
                $d['token_expires_at'] = $baseTime->copy()->addMinutes((int) $d['token_valid_minutes']);
            }
        } else {
            $d['token_expires_at'] = null;
        }

        $d['is_published'] = $r->boolean('is_published');
        $exam->update($d);
        return redirect()->route('exams.index')->with('success', 'Pengaturan ujian berhasil diperbarui.');
    }

    public function destroy(Exam $exam)
    {
        // Admin bisa hapus semua ujian; guru hanya ujiannya sendiri
        $this->authorizeExam($exam, allowAdmin: true, allowOwner: true);

        DB::transaction(function () use ($exam) {
            foreach ($exam->attempts as $attempt) {
                $attempt->answers()->delete();
                $attempt->delete();
            }
            $exam->questions()->delete();
            $exam->delete();
        });

        return redirect()->route('exams.index')
            ->with('success', 'Ujian "' . $exam->title . '" berhasil dihapus beserta semua soal dan hasil pengerjaannya.');
    }

    /**
     * Hapus massal beberapa ujian sekaligus (hanya admin & pemilik ujian).
     */
    public function bulkDestroy(Request $r)
    {
        $r->validate([
            'exam_ids'   => 'required|array',
            'exam_ids.*' => 'exists:exams,id',
        ]);

        $user = auth()->user();
        $exams = Exam::whereIn('id', $r->exam_ids)->get();
        $deletedCount = 0;

        DB::transaction(function () use ($exams, $user, &$deletedCount) {
            foreach ($exams as $exam) {
                // Cek izin per ujian
                if ($user->role !== 'admin' && $exam->created_by !== $user->id) {
                    continue;
                }

                foreach ($exam->attempts as $attempt) {
                    $attempt->answers()->delete();
                    $attempt->delete();
                }
                $exam->questions()->delete();
                $exam->delete();
                $deletedCount++;
            }
        });

        return redirect()->route('exams.index')
            ->with('success', "Sebanyak {$deletedCount} ujian berhasil dihapus.");
    }

    /**
     * Endpoint AJAX / POST: rotasi / perbarui token sekarang.
     * Hanya pemilik ujian atau admin.
     */
    public function rotateToken(Exam $exam)
    {
        $this->authorizeExam($exam, allowAdmin: true, allowOwner: true);

        $updates = [
            'token'                 => Str::upper(Str::random(6)),
            'token_last_rotated_at' => now(),
        ];

        if ($exam->token_valid_minutes) {
            $updates['token_expires_at'] = now()->addMinutes($exam->token_valid_minutes);
        }

        $exam->update($updates);

        if (request()->expectsJson()) {
            return response()->json([
                'token'                 => $exam->token,
                'seconds_remaining'     => $exam->secondsUntilNextRotation(),
                'token_expires_at'      => $exam->token_expires_at?->format('H:i:s'),
                'token_seconds_left'    => $exam->tokenSecondsRemaining(),
            ]);
        }

        return back()->with('success', 'Token ujian baru: ' . $exam->token);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Otorisasi akses ke ujian.
     * Default: hanya pemilik atau admin.
     */
    private function authorizeExam(
        Exam $exam,
        bool $allowAdmin = true,
        bool $allowOwner = true
    ): void {
        $user = auth()->user();
        $isAdmin = $allowAdmin && $user->role === 'admin';
        $isOwner = $allowOwner && $exam->created_by == $user->id;
        abort_unless($isAdmin || $isOwner, 403, 'Anda tidak punya akses ke ujian ini.');
    }

    private function validateData(Request $r): array
    {
        return $r->validate([
            'title'                => 'required|string|max:180',
            'subject'              => 'required|string|max:120',
            'target_type'          => 'required|string|in:umum,tingkat,jurusan,kelas',
            'target_level'         => 'nullable|string|max:20',
            'target_major'         => 'nullable|string|max:60',
            'class_name'           => 'nullable|string|max:150',
            'starts_at'            => 'required|date',
            'ends_at'              => 'required|date|after:starts_at',
            'duration_minutes'     => 'required|integer|min:1|max:600',
            'token'                => 'nullable|string|max:32',
            'token_valid_minutes'  => 'nullable|integer|min:1|max:1440',
            'token_rotate_minutes' => 'nullable|integer|min:1|max:120',
            'is_published'         => 'nullable|boolean',
        ]);
    }
}
