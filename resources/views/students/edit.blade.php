@extends('layouts.app')
@section('title', 'Edit Data Siswa — ' . $student->name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold">Edit Data Siswa</h2>
        <p class="text-secondary small mb-0">{{ $student->name }} ({{ $student->student_number }})</p>
    </div>
    <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">← Kembali ke Daftar</a>
</div>

<div class="card p-4 p-md-5" style="max-width: 720px; margin: 0 auto;">
    <form method="POST" action="{{ route('admin.students.update', $student) }}">
        @csrf
        @method('PUT')
        @include('students.form')
        <div class="d-flex gap-2 mt-4 pt-3 border-top" style="border-color:rgba(0,0,0,0.06) !important;">
            <button class="btn btn-primary px-4">
                <span>💾</span> Simpan Perubahan
            </button>
            <a href="{{ route('admin.students.index') }}" class="btn btn-secondary px-4">Batal</a>
        </div>
    </form>
</div>

@endsection
