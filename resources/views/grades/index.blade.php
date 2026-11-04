@extends('layouts.app')
@section('title', 'Rekap Nilai Ujian')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold">Rekap Nilai Ujian</h2>
        <p class="text-secondary small mb-0">Semua peserta yang sudah mengumpulkan jawaban (termasuk yang belum dirilis)</p>
    </div>
    <a class="btn btn-success" href="{{ route('admin.grades.export', request()->only('exam_id', 'kelas')) }}">
        <span>📊</span> Unduh Rekap Excel (.xlsx)
    </a>
</div>

{{-- Filter Card --}}
<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('admin.grades.index') }}" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label" for="exam_id">Pilih Ujian</label>
            <select name="exam_id" id="exam_id" class="form-select">
                <option value="">Semua Ujian</option>
                @foreach($exams as $exam)
                    <option value="{{ $exam->id }}" @selected((string) request('exam_id') === (string) $exam->id)>
                        {{ $exam->title }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="kelas">Pilih Kelas</label>
            <select name="kelas" id="kelas" class="form-select">
                <option value="">Semua Kelas</option>
                @foreach($classes as $class)
                    <option value="{{ $class }}" @selected(request('kelas') === $class)>
                        {{ $class }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1">
                <span>🔍</span> Filter
            </button>
            <a href="{{ route('admin.grades.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</div>

{{-- Tabel Rekap Nilai --}}
<div class="card p-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:50px" class="text-center">No.</th>
                    <th>Nomor Induk</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th>Ujian</th>
                    <th class="text-center">Nilai</th>
                    <th>Status Rilis</th>
                    <th>Waktu Kumpul</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attempts as $attempt)
                    <tr>
                        <td class="text-center text-secondary fw-bold">{{ $attempts->firstItem() + $loop->index }}</td>
                        <td><code>{{ $attempt->user->student_number ?? '-' }}</code></td>
                        <td class="fw-bold text-dark">{{ $attempt->user->name ?? '-' }}</td>
                        <td><span class="badge bg-secondary">{{ $attempt->user->class_name ?? '-' }}</span></td>
                        <td>{{ $attempt->exam->title ?? '-' }}</td>
                        <td class="text-center">
                            <span class="badge bg-primary fs-6 px-3" style="font-family:'JetBrains Mono', monospace;">
                                {{ $attempt->score ?? '-' }}
                            </span>
                        </td>
                        <td>
                            @if($attempt->is_released)
                                <span class="badge bg-success">Sudah dirilis</span>
                            @else
                                <span class="badge bg-warning">Belum dirilis</span>
                            @endif
                        </td>
                        <td><small class="text-secondary">{{ $attempt->submitted_at?->format('d-m-Y H:i') ?? '-' }}</small></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <div style="font-size:2.5rem;">📊</div>
                            <div class="mt-2">Belum ada hasil nilai untuk kriteria filter ini.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($attempts->hasPages())
    <div class="mt-3">
        {{ $attempts->links() }}
    </div>
    @endif
</div>

@endsection
