<?php
namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\User;
use App\Models\Attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamController extends Controller
{
    /** Scope query berdasarkan peran: guru hanya lihat ujiannya sendiri. */
    private function scopedQuery()
    {
        $q = Exam::with(['creator', 'questions'])
            ->withCount([
                'attempts',
                'attempts as in_progress_count' => fn($q) => $q->whereNotNull('started_at')->whereNull('submitted_at'),
                'attempts as submitted_count'   => fn($q) => $q->whereNotNull('submitted_at'),
                'attempts as violations_count'  => fn($q) => $q->where('violations', '>', 0),
            ]);
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

        // Ambil data siswa satu kali untuk menghitung target eligible tiap ujian
        $allStudents = User::where('role', 'siswa')->get();
        foreach ($exams as $exam) {
            $exam->eligible_count = $exam->countEligibleStudents($allStudents);
        }

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
        $d['randomize_by_type']     = $r->boolean('randomize_by_type');

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
        $d['randomize_by_type'] = $r->boolean('randomize_by_type');
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
                if ($user->role !== 'admin' && $exam->created_by != $user->id) {
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
     * Tampilkan data dan status seluruh peserta pada ujian tertentu.
     */
    public function participants(Request $request, Exam $exam)
    {
        $this->authorizeExam($exam);

        $allStudents = User::where('role', 'siswa')
            ->orderBy('class_name')
            ->orderBy('name')
            ->get();

        $eligibleStudents = $allStudents->filter(fn($s) => $exam->matchesStudent($s));
        $studentMap = $eligibleStudents->keyBy('id');

        $attempts = Attempt::with('user')
            ->where('exam_id', $exam->id)
            ->get()
            ->keyBy('user_id');

        // Sertakan juga siswa yang mengerjakan meskipun di luar target awal
        foreach ($attempts as $a) {
            if ($a->user && !$studentMap->has($a->user_id)) {
                $studentMap->put($a->user_id, $a->user);
            }
        }

        $participants = $studentMap->values()->map(function ($student) use ($attempts, $exam) {
            /** @var Attempt|null $attempt */
            $attempt = $attempts->get($student->id);

            if (!$attempt || !$attempt->started_at) {
                $status = 'belum_mulai';
                $statusLabel = 'Belum Mulai';
                $duration = null;
            } elseif ($attempt->submitted_at) {
                if ($attempt->force_submitted) {
                    $status = 'force_submitted';
                    $statusLabel = 'Dikumpulkan Paksa';
                } else {
                    $status = 'selesai';
                    $statusLabel = 'Selesai';
                }
                $duration = $attempt->started_at->diffInMinutes($attempt->submitted_at) . ' menit';
            } else {
                $status = 'mengerjakan';
                $statusLabel = 'Sedang Mengerjakan';
                $duration = $attempt->started_at->diffInMinutes(now()) . ' menit berjalan';
            }

            return [
                'id'              => $student->id,
                'attempt_id'      => $attempt?->id,
                'name'            => $student->name,
                'student_number'  => $student->student_number ?: '—',
                'class_name'      => $student->class_name ?: '—',
                'status'          => $status,
                'status_label'    => $statusLabel,
                'started_at'      => $attempt?->started_at?->format('d/m/Y H:i:s'),
                'started_time'    => $attempt?->started_at?->format('H:i'),
                'submitted_at'    => $attempt?->submitted_at?->format('d/m/Y H:i:s'),
                'submitted_time'  => $attempt?->submitted_at?->format('H:i'),
                'duration'        => $duration,
                'score'           => $attempt?->submitted_at ? (float) $attempt->score : null,
                'violations'      => $attempt ? (int) $attempt->violations : 0,
                'violation_log'   => $attempt?->violation_log ?: [],
                'force_submitted' => (bool) ($attempt?->force_submitted),
            ];
        });

        // Filter jika ada parameter query
        if ($request->filled('status')) {
            $participants = $participants->filter(fn($p) => $p['status'] === $request->status);
        }

        if ($request->filled('kelas')) {
            $participants = $participants->filter(fn($p) => $p['class_name'] === $request->kelas);
        }

        if ($request->filled('q')) {
            $kw = strtolower($request->q);
            $participants = $participants->filter(function ($p) use ($kw) {
                return str_contains(strtolower($p['name']), $kw)
                    || str_contains(strtolower($p['student_number']), $kw)
                    || str_contains(strtolower($p['class_name']), $kw);
            });
        }

        $totalEligible    = $studentMap->count();
        $startedCount     = $attempts->whereNotNull('started_at')->count();
        $inProgressCount  = $attempts->whereNotNull('started_at')->whereNull('submitted_at')->count();
        $submittedCount   = $attempts->whereNotNull('submitted_at')->count();
        $unstartedCount   = max(0, $totalEligible - $startedCount);
        $violationsCount  = (int) $attempts->sum('violations');

        $stats = [
            'total_eligible'    => $totalEligible,
            'started_count'     => $startedCount,
            'in_progress_count' => $inProgressCount,
            'submitted_count'   => $submittedCount,
            'unstarted_count'   => $unstartedCount,
            'violations_count'  => $violationsCount,
        ];

        $classes = $studentMap->pluck('class_name')->filter()->unique()->sort()->values();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'exam' => [
                    'id'               => $exam->id,
                    'title'            => $exam->title,
                    'subject'          => $exam->subject,
                    'target_badge'     => $exam->getTargetBadgeLabel(),
                    'token'            => $exam->token,
                    'duration_minutes' => $exam->duration_minutes,
                    'is_active'        => $exam->isActive(),
                    'status_label'     => $exam->isActive() ? 'Aktif' : ($exam->ends_at < now() ? 'Selesai' : 'Terjadwal'),
                ],
                'stats'        => $stats,
                'classes'      => $classes,
                'participants' => $participants->values(),
            ]);
        }

        return view('exams.participants', compact('exam', 'participants', 'stats', 'classes'));
    }

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
            'randomize_by_type'    => 'nullable|boolean',
        ]);
    }
}
