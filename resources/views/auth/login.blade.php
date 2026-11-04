@extends('layouts.app')

@section('title', 'Masuk CBT — ' . ($school->school_name ?: 'CBT Sekolah'))

@section('content')
<div class="row justify-content-center align-items-center" style="min-height: calc(80vh - 120px);">
    <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
        <div class="card glass-card p-4 p-md-5">
            <div class="text-center mb-4">
                @if($school->logo_url)
                    <div class="d-inline-flex p-2 rounded-4 mb-3" 
                         style="background: rgba(255,255,255,0.85); box-shadow: 0 8px 24px rgba(0,0,0,0.06), inset 0 1px 1px #fff; border: 1px solid rgba(255,255,255,0.9);">
                        <img src="{{ $school->logo_url }}" alt="{{ $school->school_name }}" 
                             style="height:64px; width:64px; object-fit:contain; border-radius:10px;">
                    </div>
                @else
                    <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-4 mb-3 text-white"
                         style="width:68px; height:68px; font-size:2rem; background: linear-gradient(135deg, #0a84ff, #0071e3); box-shadow: 0 8px 24px rgba(0,113,227,0.35);">
                        🎓
                    </div>
                @endif
                
                <h3 class="h4 mb-1 fw-bold" style="letter-spacing: -0.025em;">{{ $school->school_name ?: 'CBT Sekolah' }}</h3>
                <p class="text-secondary small mb-0">Masuk dengan kredensial yang diberikan sekolah</p>
            </div>

            <form method="post" action="{{ route('login.post') }}" class="d-flex flex-column gap-3">
                @csrf
                
                <div>
                    <label class="form-label" for="username">Username</label>
                    <div class="position-relative">
                        <input class="form-control @error('username') is-invalid @enderror" 
                               type="text" 
                               id="username"
                               name="username" 
                               value="{{ old('username') }}" 
                               required 
                               autofocus 
                               autocomplete="username" 
                               placeholder="Masukkan username Anda">
                    </div>
                    @error('username')
                        <div class="text-danger small mt-1 fw-medium">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label class="form-label" for="password">Kata Sandi</label>
                    <div class="position-relative">
                        <input class="form-control" 
                               type="password" 
                               id="password"
                               name="password" 
                               required 
                               autocomplete="current-password" 
                               placeholder="Masukkan kata sandi">
                    </div>
                </div>

                <div class="pt-2">
                    <button class="btn btn-primary w-100 py-2 fs-6">
                        Masuk ke Akun
                    </button>
                </div>
            </form>

            <div class="text-center mt-4 pt-3 border-top" style="border-color: rgba(0,0,0,0.06) !important;">
                <span class="badge text-secondary" style="background: rgba(0,0,0,0.04); font-size: 0.72rem; font-weight: 500;">
                    🔒 Sistem Ujian Berbasis Komputer Terproteksi
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
