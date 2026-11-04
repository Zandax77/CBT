<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $school->school_name ?: 'CBT Sekolah')</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Apple Liquid Glass Styling -->
    <link href="/css/liquid-glass.css" rel="stylesheet">
</head>
<body>

{{-- Ambient Liquid Glow Aura --}}
<div class="liquid-glow-container" aria-hidden="true">
    <div class="liquid-glow-orb liquid-glow-orb-1"></div>
    <div class="liquid-glow-orb liquid-glow-orb-2"></div>
    <div class="liquid-glow-orb liquid-glow-orb-3"></div>
</div>

{{-- Apple Floating Liquid Glass Navigation Bar --}}
<header class="apple-glass-nav">
    <nav class="navbar navbar-expand-lg apple-glass-nav-bar py-1">
        <div class="container-fluid px-1">
            @include('partials.school-brand', ['brandClass' => 'navbar-brand me-2 flex-shrink-0'])
            
            <button class="navbar-toggler border-0 shadow-none px-2 py-1" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon" style="filter: brightness(0.2);"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                @auth
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
                    @if(auth()->user()->role === 'admin')
                        <li class="nav-item">
                            <a class="apple-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                               href="{{ route('dashboard') }}">
                                <span>📊</span> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="apple-nav-link {{ request()->routeIs('monitoring.*') ? 'active' : '' }}"
                               href="{{ route('monitoring.index') }}">
                                <span>📡</span> Monitoring
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="apple-nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}"
                               href="{{ route('admin.students.index') }}">
                                <span>👥</span> Siswa
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="apple-nav-link {{ request()->routeIs('admin.teachers.*') ? 'active' : '' }}"
                               href="{{ route('admin.teachers.index') }}">
                                <span>👨‍🏫</span> Guru
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="apple-nav-link {{ request()->routeIs('admin.grades.*') ? 'active' : '' }}"
                               href="{{ route('admin.grades.index') }}">
                                <span>📈</span> Rekap Nilai
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="apple-nav-link {{ request()->routeIs('results.admin') ? 'active' : '' }}"
                               href="{{ route('results.admin') }}">
                                <span>📋</span> Hasil Ujian
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="apple-nav-link {{ request()->routeIs('admin.school-settings.*') ? 'active' : '' }}"
                               href="{{ route('admin.school-settings.edit') }}">
                                <span>⚙️</span> Sekolah
                            </a>
                        </li>
                    @endif
                    @if(auth()->user()->role === 'kepala_sekolah')
                        <li class="nav-item">
                            <a class="apple-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                               href="{{ route('dashboard') }}">
                                <span>📊</span> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="apple-nav-link {{ request()->routeIs('monitoring.*') ? 'active' : '' }}"
                               href="{{ route('monitoring.index') }}">
                                <span>📡</span> Monitoring Ujian
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="apple-nav-link {{ request()->routeIs('results.admin') ? 'active' : '' }}"
                               href="{{ route('results.admin') }}">
                                <span>📋</span> Hasil Ujian
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="apple-nav-link {{ request()->routeIs('admin.grades.*') ? 'active' : '' }}"
                               href="{{ route('admin.grades.index') }}">
                                <span>📈</span> Rekap Nilai
                            </a>
                        </li>
                    @endif
                    @if(in_array(auth()->user()->role, ['admin', 'guru']))
                        <li class="nav-item">
                            <a class="apple-nav-link {{ request()->routeIs('exams.*') ? 'active' : '' }}"
                               href="{{ route('exams.index') }}">
                                <span>📝</span> Kelola Ujian
                            </a>
                        </li>
                    @endif
                    @if(auth()->user()->role === 'siswa')
                        <li class="nav-item">
                            <a class="apple-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                               href="{{ route('dashboard') }}">
                                <span>🏠</span> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="apple-nav-link {{ request()->routeIs('attempts.*') ? 'active' : '' }}"
                               href="{{ route('attempts.index') }}">
                                <span>✍️</span> Daftar Ujian
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="apple-nav-link {{ request()->routeIs('results.student') ? 'active' : '' }}"
                               href="{{ route('results.student') }}">
                                <span>🏆</span> Hasil Saya
                            </a>
                        </li>
                    @endif
                </ul>
                @endauth

                <div class="d-flex align-items-center gap-2 ms-auto mt-2 mt-lg-0 flex-nowrap">
                    @auth
                        <div class="user-glass-pill flex-shrink-0">
                            <span class="user-avatar-indicator">👤</span>
                            <span class="user-name-text">{{ auth()->user()->name }}</span>
                            <span class="user-role-badge {{ auth()->user()->role === 'kepala_sekolah' ? 'user-role-kepsek' : '' }}">
                                {{ auth()->user()->role === 'kepala_sekolah' ? 'KEPALA SEKOLAH' : strtoupper(auth()->user()->role) }}
                            </span>
                        </div>
                        <form method="post" action="{{ route('logout') }}" class="m-0 flex-shrink-0">
                            @csrf
                            <button class="btn btn-nav-logout btn-outline-danger" title="Keluar dari akun">
                                <span>🚪</span> Keluar
                            </button>
                        </form>
                    @else
                        @if(!request()->routeIs('login'))
                            <a href="{{ route('login') }}" class="btn btn-sm btn-primary">Masuk</a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>
</header>

<main class="container-fluid py-2" style="max-width: 1280px;">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <span class="fs-5">✨</span>
            <div class="flex-grow-1">{{ session('success') }}</div>
            <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <span class="fs-5">ℹ️</span>
            <div class="flex-grow-1">{{ session('info') }}</div>
            <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <span class="fs-5">⚠️</span>
            <div class="flex-grow-1">
                <strong>Periksa kembali:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
