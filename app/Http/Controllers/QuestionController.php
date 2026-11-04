<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QuestionController extends Controller
{
    private function authorizeExam(Exam $exam): void
    {
        abort_unless(
            $exam->created_by == auth()->id() || auth()->user()->role === 'admin',
            403,
            'Anda tidak punya akses ke ujian ini.'
        );
    }

    /** Daftar soal ujian. */
    public function index(Exam $exam)
    {
        $this->authorizeExam($exam);
        $questions = $exam->questions()->orderBy('position')->get();
        return view('questions.index', compact('exam', 'questions'));
    }

    /** Form tambah soal. */
    public function create(Exam $exam)
    {
        $this->authorizeExam($exam);
        return view('questions.form', ['exam' => $exam, 'question' => new Question()]);
    }

    /** Simpan soal baru. */
    public function store(Request $request, Exam $exam)
    {
        $this->authorizeExam($exam);

        $data = $this->validateQuestion($request);
        $data['exam_id']  = $exam->id;
        $data['position'] = $exam->questions()->max('position') + 1;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('questions', 'public');
        }

        $data['options']    = $this->buildOptions($request, $data['type']);
        $data['answer_key'] = $this->buildAnswerKey($request, $data['type'], $data['options']);

        Question::create($data);

        return redirect()->route('questions.list', $exam)
            ->with('success', 'Soal berhasil ditambahkan.');
    }

    /** Form edit soal. */
    public function edit(Exam $exam, Question $question)
    {
        $this->authorizeExam($exam);
        abort_unless($question->exam_id === $exam->id, 404);
        return view('questions.form', compact('exam', 'question'));
    }

    /** Update soal. */
    public function update(Request $request, Exam $exam, Question $question)
    {
        $this->authorizeExam($exam);
        abort_unless($question->exam_id === $exam->id, 404);

        $data = $this->validateQuestion($request);

        // Hapus gambar lama jika ada gambar baru
        if ($request->hasFile('image')) {
            if ($question->image_path) {
                Storage::disk('public')->delete($question->image_path);
            }
            $data['image_path'] = $request->file('image')->store('questions', 'public');
        }

        // Hapus gambar jika diminta
        if ($request->boolean('remove_image') && $question->image_path) {
            Storage::disk('public')->delete($question->image_path);
            $data['image_path'] = null;
        }

        $data['options']    = $this->buildOptions($request, $data['type']);
        $data['answer_key'] = $this->buildAnswerKey($request, $data['type'], $data['options']);

        $question->update($data);

        return redirect()->route('questions.list', $exam)
            ->with('success', 'Soal berhasil diperbarui.');
    }

    /** Hapus soal. */
    public function destroy(Exam $exam, Question $question)
    {
        $this->authorizeExam($exam);
        abort_unless($question->exam_id === $exam->id, 404);

        if ($question->image_path) {
            Storage::disk('public')->delete($question->image_path);
        }

        $question->delete();

        return redirect()->route('questions.list', $exam)
            ->with('success', 'Soal berhasil dihapus.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validateQuestion(Request $r): array
    {
        return $r->validate([
            'type'          => 'required|in:PG,BENAR_SALAH,MENJODOHKAN',
            'question_text' => 'required|string|max:5000',
            'weight'        => 'required|numeric|min:0.1|max:100',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:3072',
            'remove_image'  => 'nullable|boolean',
            // Opsi PG / Benar-Salah
            'opt_a'         => 'nullable|string|max:500',
            'opt_b'         => 'nullable|string|max:500',
            'opt_c'         => 'nullable|string|max:500',
            'opt_d'         => 'nullable|string|max:500',
            'opt_e'         => 'nullable|string|max:500',
            'answer_pg'     => 'nullable|in:A,B,C,D,E',
            'answer_bs'     => 'nullable|in:A,B',
            // Menjodohkan
            'premise.*'     => 'nullable|string|max:300',
            'pair.*'        => 'nullable|string|max:300',
            'match_key'     => 'nullable|string|max:200',
        ]);
    }

    private function buildOptions(Request $r, string $type): array
    {
        if ($type === 'PG') {
            $opts = [];
            foreach (['A' => 'opt_a', 'B' => 'opt_b', 'C' => 'opt_c', 'D' => 'opt_d', 'E' => 'opt_e'] as $k => $f) {
                $val = trim($r->input($f, ''));
                if ($val !== '') $opts[$k] = $val;
            }
            return $opts;
        }

        if ($type === 'BENAR_SALAH') {
            return ['A' => 'Benar', 'B' => 'Salah'];
        }

        // MENJODOHKAN
        $opts = [];
        $keys = ['A', 'B', 'C', 'D', 'E'];
        foreach ($r->input('premise', []) as $i => $text) {
            $text = trim($text);
            if ($text !== '' && isset($keys[$i])) {
                $opts[$keys[$i]] = $text;
            }
        }
        $pairs = array_values(array_filter(array_map('trim', $r->input('pair', [])), fn($x) => $x !== ''));
        if (!empty($pairs)) {
            $opts['__pairs'] = $pairs;
        }
        return $opts;
    }

    private function buildAnswerKey(Request $r, string $type, array $options): string
    {
        if ($type === 'PG')         return strtoupper($r->input('answer_pg', 'A'));
        if ($type === 'BENAR_SALAH') return strtoupper($r->input('answer_bs', 'A'));
        return trim($r->input('match_key', ''));
    }
}
