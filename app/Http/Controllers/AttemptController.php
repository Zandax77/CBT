<?php
namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Attempt;
use App\Models\Answer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AttemptController extends Controller
{
    public function index(Request $r)
    {
        $user      = $r->user();
        $userClass = $user->class_name; // kelas siswa yang login

        $allActiveExams = Exam::where('is_published', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->get();

        // Filter sesuai target kelompok kelas/tingkat/jurusan/umum
        $exams = $allActiveExams->filter(fn ($exam) => $exam->matchesStudent($user));

        return view('attempts.index', compact('exams', 'userClass'));
    }

    public function start(Request $r, Exam $exam)
    {
        $r->validate(['token' => 'required|string']);

        // Pastikan ujian aktif
        abort_unless(
            $exam->is_published && now()->between($exam->starts_at, $exam->ends_at),
            403,
            'Ujian belum dibuka atau sudah ditutup.'
        );

        // Verifikasi token
        if (!hash_equals((string) $exam->token, (string) $r->token)) {
            return back()->withErrors(['token' => 'Token ujian salah.']);
        }

        // Verifikasi masa aktif token
        if ($exam->isTokenExpired()) {
            return back()->withErrors(['token' => 'Masa aktif token ujian telah kedaluwarsa. Silakan minta token aktif terbaru dari pengawas.']);
        }

        // Verifikasi target kelompok kelas (tingkat / jurusan / kelas / umum)
        if (!$exam->matchesStudent($r->user())) {
            abort(403, 'Ujian ini (' . $exam->getTargetBadgeLabel() . ') tidak ditujukan untuk kelas atau jurusan Anda.');
        }

        $attempt = Attempt::firstOrCreate(
            ['exam_id' => $exam->id, 'user_id' => $r->user()->id],
            ['started_at' => now(), 'token_verified' => true]
        );

        if ($attempt->submitted_at) {
            return redirect()->route('results.student')->with('info', 'Ujian ini sudah dikumpulkan.');
        }

        if (!$attempt->token_verified) {
            $attempt->update(['token_verified' => true]);
        }

        return redirect()->route('attempts.show', $attempt);
    }

    public function show(Request $r, Attempt $attempt)
    {
        abort_unless($attempt->user_id === $r->user()->id, 403);

        if ($attempt->submitted_at) {
            return redirect()->route('results.student');
        }

        if (now()->greaterThanOrEqualTo($this->deadline($attempt))) {
            $this->finish($attempt);
            return redirect()->route('results.student')->with('info', 'Waktu ujian habis.');
        }

        $questions = $this->resolveAttemptQuestions($attempt);

        $answers = $attempt->answers->pluck('answer_value', 'question_id');

        return view('attempts.show', compact('attempt', 'questions', 'answers'));
    }

    /**
     * Mengambil daftar soal untuk attempt.
     * Jika ujian mengaktifkan pengacakan menurut jenis, hasil acak disimpan
     * di attempt->question_order agar urutan tetap konsisten bagi siswa bersangkutan.
     */
    private function resolveAttemptQuestions(Attempt $attempt)
    {
        $exam = $attempt->exam;

        if ($exam->randomize_by_type) {
            $orderedIds = $attempt->question_order;
            if (empty($orderedIds) || !is_array($orderedIds)) {
                $typeOrder = ['PG', 'PG_MULTI', 'BENAR_SALAH', 'MENJODOHKAN'];
                $allQuestions = $exam->questions()->get();
                $grouped = $allQuestions->groupBy('type');
                $orderedIds = [];
                foreach ($typeOrder as $t) {
                    if ($grouped->has($t)) {
                        $orderedIds = array_merge($orderedIds, $grouped->get($t)->shuffle()->pluck('id')->all());
                    }
                }
                foreach ($grouped as $type => $items) {
                    if (!in_array($type, $typeOrder, true)) {
                        $orderedIds = array_merge($orderedIds, $items->shuffle()->pluck('id')->all());
                    }
                }
                $attempt->update(['question_order' => $orderedIds]);
            }

            return $exam->questions()
                ->get(['id', 'exam_id', 'position', 'type', 'question_text', 'image_path', 'options', 'weight'])
                ->sortBy(function ($q) use ($orderedIds) {
                    $idx = array_search($q->id, $orderedIds);
                    return $idx === false ? $q->position : $idx;
                })
                ->values();
        }

        return $exam->questions()
            ->get(['id', 'exam_id', 'position', 'type', 'question_text', 'image_path', 'options', 'weight']);
    }

    private function deadline(Attempt $a): Carbon
    {
        return min(
            $a->started_at->copy()->addMinutes($a->exam->duration_minutes),
            $a->exam->ends_at
        );
    }

    public function save(Request $r, Attempt $attempt)
    {
        abort_unless($attempt->user_id === $r->user()->id, 403);

        if ($attempt->submitted_at || now()->greaterThanOrEqualTo($this->deadline($attempt))) {
            return response()->json(['ok' => false, 'message' => 'Waktu ujian berakhir.'], 409);
        }

        $d = $r->validate([
            'question_id' => 'required|integer',
            'answer'      => 'nullable', // Accept string or array for PG/PG_MULTI
        ]);

        $q = $attempt->exam->questions()->findOrFail($d['question_id']);

        if ($q->type === 'MENJODOHKAN') {
            // Format jawaban: "A:1,B:3,C:2"
            // Validasi: setiap premise:pair_index harus valid
            $premises  = array_diff_key($q->options, ['__pairs' => '']);
            $pairs     = $q->options['__pairs'] ?? [];
            $parts     = explode(',', $d['answer']);
            foreach ($parts as $part) {
                $part = trim($part);
                if (!preg_match('/^([A-E]):(\d+)$/', $part, $m)) {
                    return response()->json(['ok' => false, 'message' => 'Format jawaban tidak valid.'], 422);
                }
                if (!array_key_exists($m[1], $premises) || (int) $m[2] < 1 || (int) $m[2] > count($pairs)) {
                    return response()->json(['ok' => false, 'message' => 'Jawaban tidak sesuai opsi.'], 422);
                }
            }
            $answerValue = $d['answer'];
        } else {
            // PG / BENAR_SALAH
            $allowed = array_keys(array_diff_key($q->options, ['__pairs' => '']));
            // Normalize answer to array of upper‑case options
            $answers = is_array($d['answer']) ? $d['answer'] : explode(',', $d['answer']);
            $answers = array_map('trim', $answers);
            $answers = array_filter($answers); // remove empty strings
            $answersUpper = array_map('strtoupper', $answers);
            // Validate each selected answer
            foreach ($answersUpper as $ans) {
                abort_unless(in_array($ans, $allowed, true), 422);
            }
            // Store as comma‑separated string (order‑independent)
            sort($answersUpper);
            $answerValue = implode(',', $answersUpper);
        }

        Answer::updateOrCreate(
            ['attempt_id' => $attempt->id, 'question_id' => $q->id],
            ['answer_value' => $answerValue]
        );

        return response()->json(['ok' => true, 'saved_at' => now()->format('H:i:s')]);
    }

    public function submit(Request $r, Attempt $attempt)
    {
        abort_unless($attempt->user_id === $r->user()->id, 403);
        if (!$attempt->submitted_at) {
            $this->finish($attempt);
        }
        return redirect()->route('results.student')->with('success', 'Jawaban berhasil dikumpulkan.');
    }

    /**
     * ExamBro: laporan pelanggaran (ganti tab, keluar fullscreen, screenshot, dll).
     * Menerima `reason` dari client, disimpan ke violation_log.
     * Jika violations >= batas, jawaban dikumpulkan paksa.
     */
    public function reportViolation(Request $r, Attempt $attempt)
    {
        abort_unless($attempt->user_id === $r->user()->id, 403);

        if ($attempt->submitted_at) {
            return response()->json(['submitted' => true, 'violations' => $attempt->violations]);
        }

        $reason        = $r->input('reason', 'Pelanggaran tidak diketahui');
        $maxViolations = (int) config('exambro.max_violations', 3);
        $newCount      = $attempt->violations + 1;

        // Simpan detail log pelanggaran (append)
        $log   = $attempt->violation_log ?? [];
        $log[] = ['at' => now()->toIso8601String(), 'reason' => $reason];

        $attempt->update([
            'violations'    => $newCount,
            'violation_log' => $log,
        ]);

        $forceSubmit = $newCount >= $maxViolations;
        if ($forceSubmit) {
            $this->finish($attempt);
            $attempt->update(['force_submitted' => true]);
        }

        return response()->json([
            'violations'   => $newCount,
            'max'          => $maxViolations,
            'remaining'    => max(0, $maxViolations - $newCount),
            'force_submit' => $forceSubmit,
            'submitted'    => $forceSubmit,
        ]);
    }

    private function finish(Attempt $a): void
    {
        DB::transaction(function () use ($a) {
            $a->load('exam.questions', 'answers');
            $total  = 0;
            $earned = 0;

            foreach ($a->exam->questions as $q) {
                $total += (float) $q->weight;
                $ans    = $a->answers->firstWhere('question_id', $q->id);

                if ($q->type === 'MENJODOHKAN') {
                    // Penilaian partial: hitung pasangan yang benar
                    $correct = false;
                    $earnedScore = 0;

                    if ($ans) {
                        $keyParts    = $this->parseMatchingKey($q->answer_key);
                        $studentParts = $this->parseMatchingKey($ans->answer_value);
                        $total_pairs  = count($keyParts);
                        $correct_pairs = 0;

                        foreach ($keyParts as $premise => $pairIdx) {
                            if (($studentParts[$premise] ?? null) === $pairIdx) {
                                $correct_pairs++;
                            }
                        }

                        if ($total_pairs > 0) {
                            $earnedScore = round(($correct_pairs / $total_pairs) * (float) $q->weight, 2);
                        }
                        $correct = ($correct_pairs === $total_pairs);
                        $ans->update(['is_correct' => $correct, 'earned_score' => $earnedScore]);
                        $earned += $earnedScore;
                    }
                } elseif ($q->type === 'PG_MULTI') {
                    // Pilihan Ganda Kompleks: kunci jawaban berupa array atau koma-dipisah
                    $correct = false;
                    if ($ans && !empty($ans->answer_value)) {
                        $rawKey = $q->answer_key;
                        $keyArr = is_array($rawKey) ? $rawKey : explode(',', (string) $rawKey);
                        $keyArr = array_values(array_filter(array_map('trim', array_map('strtoupper', $keyArr))));
                        sort($keyArr);

                        $ansArr = explode(',', (string) $ans->answer_value);
                        $ansArr = array_values(array_filter(array_map('trim', array_map('strtoupper', $ansArr))));
                        sort($ansArr);

                        $correct = ($keyArr === $ansArr);
                    }
                    if ($ans) {
                        $ans->update([
                            'is_correct'   => $correct,
                            'earned_score' => $correct ? $q->weight : 0,
                        ]);
                    }
                    if ($correct) {
                        $earned += (float) $q->weight;
                    }
                } else {
                    // PG & BENAR_SALAH: all-or-nothing
                    $rawKey = is_array($q->answer_key) ? implode(',', $q->answer_key) : (string)$q->answer_key;
                    $correct = $ans && strtoupper(trim((string) $ans->answer_value)) === strtoupper(trim($rawKey));
                    if ($ans) {
                        $ans->update([
                            'is_correct'   => $correct,
                            'earned_score' => $correct ? $q->weight : 0,
                        ]);
                    }
                    if ($correct) {
                        $earned += (float) $q->weight;
                    }
                }
            }

            $score = $total > 0 ? round($earned / $total * 100, 2) : 0;
            $a->update(['score' => $score, 'submitted_at' => now()]);
        });
    }

    /**
     * Parse "A:1,B:3,C:2" → ['A' => 1, 'B' => 3, 'C' => 2]
     */
    private function parseMatchingKey(string $key): array
    {
        $result = [];
        foreach (explode(',', $key) as $part) {
            $part = trim($part);
            if (preg_match('/^([A-E]):(\d+)$/', $part, $m)) {
                $result[$m[1]] = (int) $m[2];
            }
        }
        return $result;
    }
}
