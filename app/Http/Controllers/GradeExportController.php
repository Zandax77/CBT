<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GradeExportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isGuru = $user->role === 'guru';

        $examQuery = Exam::orderBy('title');
        if ($isGuru) {
            $examQuery->where('created_by', $user->id);
        }
        $exams = $examQuery->get(['id', 'title']);

        $classes = User::where('role', 'siswa')
            ->whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        $query = Attempt::with(['exam', 'user'])
            ->whereNotNull('submitted_at');

        if ($isGuru) {
            $query->whereHas('exam', fn($q) => $q->where('created_by', $user->id));
        }

        if ($request->filled('exam_id')) {
            if ($isGuru) {
                $examBelongsToGuru = Exam::where('id', $request->exam_id)->where('created_by', $user->id)->exists();
                abort_unless($examBelongsToGuru, 403, 'Anda tidak memiliki akses ke rekap nilai ujian ini.');
            }
            $query->where('exam_id', $request->exam_id);
        }

        if ($request->filled('kelas')) {
            $query->whereHas('user', fn ($q) =>
                $q->where('class_name', $request->kelas)
            );
        }

        $attempts = $query->latest('submitted_at')
            ->paginate(30)
            ->withQueryString();

        return view('grades.index', compact('attempts', 'exams', 'classes'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'],
            'kelas' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $isGuru = $user->role === 'guru';

        $query = Attempt::with(['exam', 'user'])
            ->whereNotNull('submitted_at');

        if ($isGuru) {
            $query->whereHas('exam', fn($q) => $q->where('created_by', $user->id));
        }

        if ($request->filled('exam_id')) {
            if ($isGuru) {
                $examBelongsToGuru = Exam::where('id', $request->exam_id)->where('created_by', $user->id)->exists();
                abort_unless($examBelongsToGuru, 403, 'Anda tidak memiliki akses ke rekap nilai ujian ini.');
            }
            $query->where('exam_id', $request->exam_id);
        }

        if ($request->filled('kelas')) {
            $query->whereHas('user', fn ($q) =>
                $q->where('class_name', $request->kelas)
            );
        }

        $attempts = $query->orderBy('exam_id')
            ->orderBy('user_id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Nilai');

        $sheet->fromArray([
            ['No', 'Nomor Induk', 'Nama Siswa', 'Kelas', 'Mata Ujian',
                'Nilai', 'Status Rilis', 'Waktu Mulai', 'Waktu Pengumpulan'],
        ], null, 'A1');

        $row = 2;

        foreach ($attempts as $index => $attempt) {
            $sheet->fromArray([[
                $index + 1,
                $attempt->user->student_number ?? '',
                $attempt->user->name ?? '',
                $attempt->user->class_name ?? '',
                $attempt->exam->title ?? '',
                $attempt->score,
                $attempt->is_released ? 'Sudah dirilis' : 'Belum dirilis',
                $attempt->started_at?->format('d-m-Y H:i:s'),
                $attempt->submitted_at?->format('d-m-Y H:i:s'),
            ]], null, "A{$row}");

            $row++;
        }

        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'rekap-nilai-' . now()->format('Ymd-His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}