@extends('layouts.app')
@section('title', 'Tambah Guru')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-0 fw-bold">Tambah Akun Guru</h2>
        <p class="text-secondary small mb-0">Daftarkan akun pengajar baru dengan hak akses kelola ujian dan bank soal</p>
    </div>
    <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary">← Kembali ke Daftar</a>
</div>

<div class="card p-4 p-md-5" style="max-width: 680px; margin: 0 auto;">
    <form method="POST" action="{{ route('admin.teachers.store') }}">
        @csrf
        @include('teachers.form')
        <div class="d-flex gap-2 mt-4 pt-3 border-top" style="border-color:rgba(0,0,0,0.06) !important;">
            <button class="btn btn-primary px-4">
                <span>💾</span> Simpan Akun Guru
            </button>
            <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary px-4">Batal</a>
        </div>
    </form>
</div>

@endsection
