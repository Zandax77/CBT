<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Siswa – {{ $setting->school_name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --brand:       #1d4ed8;
            --brand-dark:  #1e3a8a;
            --brand-light: #eff6ff;
            --text:        #1e293b;
            --muted:       #64748b;
            --card-w:      86mm;
            --card-h:      58mm;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            color: var(--text);
            min-height: 100vh;
        }

        /* ── Toolbar ── */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(24px) saturate(190%);
            -webkit-backdrop-filter: blur(24px) saturate(190%);
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            color: #1d1d1f;
            padding: 14px 28px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 24px rgba(0,0,0,.06);
        }
        .toolbar .brand  { font-size: 18px; font-weight: 800; letter-spacing: -.3px; color: #1d1d1f; }
        .toolbar .sub    { font-size: 12px; color: #86868b; margin-top: 2px; }
        .toolbar .ctrls  { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        .toolbar select {
            background: rgba(255,255,255,.9);
            border: 1px solid rgba(0,0,0,.15);
            color: #1d1d1f;
            border-radius: 10px;
            padding: 7px 14px;
            font-size: 13px;
            font-family: inherit;
            cursor: pointer;
        }
        .toolbar select option { color: var(--text); background: #fff; }

        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 18px; border-radius: 10px; font-size: 13px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: none; text-decoration: none;
            transition: transform .1s, background .15s;
        }
        .btn:active { transform: scale(.97); }
        .btn-ghost  { background: rgba(0,0,0,.05); color:#1d1d1f; border:1px solid rgba(0,0,0,.1); }
        .btn-ghost:hover { background: rgba(0,0,0,.1); }
        .btn-print  { background: linear-gradient(180deg, #0a84ff 0%, #0071e3 100%); color: #fff; box-shadow: 0 4px 14px rgba(0,113,227,.35); }
        .btn-print:hover { background: linear-gradient(180deg, #1f8eff 0%, #0077ed 100%); }

        /* ── Grid ── */
        .page-wrap { padding: 28px; max-width: 1200px; margin: 0 auto; }
        .stats-bar { font-size: 13px; color: var(--muted); margin-bottom: 18px; }
        .stats-bar strong { color: var(--brand); }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, var(--card-w));
            gap: 16px;
            justify-content: center;
        }

        /* ── Card ── */
        .id-card {
            width: var(--card-w);
            height: var(--card-h);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,.12);
            display: flex;
            flex-direction: column;
            background: #fff;
            position: relative;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        /* Header */
        .c-header {
            background: linear-gradient(135deg, var(--brand-dark) 0%, var(--brand) 100%);
            padding: 7px 10px 6px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
            min-height: 0;
        }
        .c-logo {
            width: 28px; height: 28px;
            object-fit: contain;
            border-radius: 4px;
            background: rgba(255,255,255,.15);
            padding: 2px;
            flex-shrink: 0;
        }
        .c-logo-placeholder {
            width: 28px; height: 28px;
            border-radius: 4px;
            background: rgba(255,255,255,.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }
        .c-school-info { flex: 1; min-width: 0; }
        .c-school-name {
            font-size: 8.5px;
            font-weight: 800;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: .5px;
            line-height: 1.25;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .c-school-addr {
            font-size: 6.5px;
            color: rgba(255,255,255,.75);
            line-height: 1.3;
            margin-top: 1.5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .c-card-type {
            font-size: 6.5px;
            color: rgba(255,255,255,.55);
            font-style: italic;
            margin-top: 1px;
        }

        /* Body: avatar left + info right */
        .c-body {
            flex: 1;
            display: flex;
            gap: 9px;
            padding: 8px 10px;
            min-height: 0;
        }

        /* Avatar column */
        .c-avatar-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 38px;
        }
        .c-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--brand-light), #dbeafe);
            border: 2px solid #bfdbfe;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }

        /* Info column */
        .c-info-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
            gap: 4px;
        }
        .c-name {
            font-size: 12px;
            font-weight: 800;
            color: var(--brand-dark);
            line-height: 1.2;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .c-class {
            font-size: 8px;
            font-weight: 600;
            color: var(--muted);
            line-height: 1;
        }
        .c-fields {
            background: var(--brand-light);
            border-radius: 5px;
            padding: 5px 7px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 2px;
        }
        .c-field {
            display: flex;
            align-items: baseline;
            gap: 4px;
            font-size: 8px;
            line-height: 1;
        }
        .c-field-lbl {
            color: var(--muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .2px;
            width: 44px;
            flex-shrink: 0;
        }
        .c-field-sep { color: var(--muted); flex-shrink: 0; }
        .c-field-val {
            font-weight: 700;
            color: var(--brand-dark);
            font-size: 8.5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .c-field-val.pwd {
            color: #b91c1c;
            letter-spacing: 0.5px;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 8.5px;
        }

        /* Footer bar */
        .c-footer {
            height: 3.5px;
            background: linear-gradient(90deg, var(--brand-dark), #3b82f6);
            flex-shrink: 0;
        }

        /* Empty */
        .empty-state { text-align:center; padding:64px 24px; color:var(--muted); }
        .empty-state h3 { font-size:16px; margin-bottom:6px; }

        /* ── Print ── */
        @media print {
            @page { size: A4; margin: 10mm; }

            body { background: #fff; }
            .toolbar, .stats-bar { display: none !important; }
            .page-wrap { padding: 0; max-width: 100%; }

            .cards-grid {
                display: grid;
                grid-template-columns: repeat(3, var(--card-w));
                gap: 5mm;
                justify-content: start;
            }

            .id-card {
                box-shadow: none;
                border: 1px solid #ccc;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

{{-- Toolbar --}}
<div class="toolbar">
    <div class="d-flex align-items-center" style="gap:12px">
        @if($setting->school_logo)
            <img src="{{ $setting->logo_url }}" alt="{{ $setting->school_name }}"
                 style="height:40px;width:40px;object-fit:contain;background:#fff;border-radius:8px;padding:3px">
        @endif
        <div>
            <div class="brand">{{ $setting->school_name }}</div>
            <div class="sub">Cetak Kartu Siswa</div>
        </div>
    </div>
    <div class="ctrls">
        @if(!$single)
        <form method="GET" id="ff" style="display:contents">
            <select name="kelas" onchange="document.getElementById('ff').submit()" title="Filter kelas">
                <option value="">📋 Semua Kelas</option>
                @foreach($classes as $cls)
                    <option value="{{ $cls }}" @selected(request('kelas') === $cls)>{{ $cls }}</option>
                @endforeach
            </select>
        </form>
        @endif
        <a href="{{ url()->previous() }}" class="btn btn-ghost">← Kembali</a>
        <a href="{{ route('admin.school-settings.edit') }}" class="btn btn-ghost">⚙️ Pengaturan</a>
        <button onclick="window.print()" class="btn btn-print">🖨️ Cetak / PDF</button>
    </div>
</div>

{{-- Cards --}}
<div class="page-wrap">
    @if($students->isEmpty())
        <div class="empty-state">
            <h3>Tidak ada data siswa</h3>
            <p>Belum ada siswa{{ request('kelas') ? ' di kelas '.request('kelas') : ' yang terdaftar' }}.</p>
        </div>
    @else
        <div class="stats-bar">
            Menampilkan <strong>{{ $students->count() }}</strong> kartu siswa
            @if(request('kelas')) — kelas <strong>{{ request('kelas') }}</strong>@endif
        </div>

        <div class="cards-grid">
            @foreach($students as $student)
            <div class="id-card">

                {{-- Header --}}
                <div class="c-header">
                    @if($setting->school_logo)
                        <img src="{{ Storage::url($setting->school_logo) }}" class="c-logo" alt="Logo">
                    @else
                        <div class="c-logo-placeholder">🏫</div>
                    @endif
                    <div class="c-school-info">
                        <div class="c-school-name">{{ $setting->school_name }}</div>
                        @if($setting->school_address)
                            <div class="c-school-addr">{{ $setting->school_address }}</div>
                        @endif
                        <div class="c-card-type">Kartu Identitas Siswa</div>
                    </div>
                </div>

                {{-- Body --}}
                <div class="c-body">
                    {{-- Avatar --}}
                    <div class="c-avatar-col">
                        <div class="c-avatar">👤</div>
                    </div>

                    {{-- Info --}}
                    <div class="c-info-col">
                        <div class="c-name" title="{{ $student->name }}">{{ $student->name }}</div>
                        <div class="c-class">{{ $student->class_name ?? '-' }}</div>

                        <div class="c-fields">
                            <div class="c-field">
                                <span class="c-field-lbl">No. Induk</span>
                                <span class="c-field-sep">:</span>
                                <span class="c-field-val">{{ $student->student_number ?? '-' }}</span>
                            </div>
                            <div class="c-field">
                                <span class="c-field-lbl">Username</span>
                                <span class="c-field-sep">:</span>
                                <span class="c-field-val">{{ $student->username }}</span>
                            </div>
                            <div class="c-field">
                                <span class="c-field-lbl">Password</span>
                                <span class="c-field-sep">:</span>
                                <span class="c-field-val pwd">{{ $student->plain_password ?: '••••••••' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="c-footer"></div>
            </div>
            @endforeach
        </div>
    @endif
</div>

<script>
    if (new URLSearchParams(location.search).get('print') === '1') {
        setTimeout(() => window.print(), 800);
    }
</script>
</body>
</html>
