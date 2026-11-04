<div class="mb-3">
    <label class="form-label fw-semibold">Nomor Induk Siswa</label>
    <input name="student_number" class="form-control" value="{{ old('student_number', $student->student_number) }}"
        required maxlength="50" placeholder="Contoh: 20241001">
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Nama Lengkap</label>
    <input name="name" class="form-control" value="{{ old('name', $student->name) }}" required maxlength="255"
        placeholder="Contoh: Budi Santoso">
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Username Login</label>
    <input type="text" name="username" class="form-control"
        value="{{ old('username', $student->username) }}"
        required maxlength="50" pattern="[a-z0-9_]+"
        placeholder="Contoh: budi1234">
    <small class="text-secondary">Hanya huruf kecil, angka, dan underscore (_). Digunakan siswa untuk login.</small>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Kelas</label>
    <input name="class_name" class="form-control" value="{{ old('class_name', $student->class_name) }}"
        placeholder="Contoh: XI RPL 1" required>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Tingkat / Jenjang <span class="text-secondary small fw-normal">(Opsional)</span></label>
        <select name="grade_level" class="form-select">
            <option value="">-- Deteksi Otomatis dari Kelas --</option>
            <option value="X" @selected(old('grade_level', $student->grade_level) === 'X')>X (Kelas 10)</option>
            <option value="XI" @selected(old('grade_level', $student->grade_level) === 'XI')>XI (Kelas 11)</option>
            <option value="XII" @selected(old('grade_level', $student->grade_level) === 'XII')>XII (Kelas 12)</option>
            <option value="XIII" @selected(old('grade_level', $student->grade_level) === 'XIII')>XIII (Kelas 13)</option>
        </select>
        <small class="text-secondary">Jika kosong, diambil dari kata pertama kelas (misal: "XI").</small>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Jurusan / Peminatan <span class="text-secondary small fw-normal">(Opsional)</span></label>
        <input name="major" class="form-control" value="{{ old('major', $student->major) }}"
            placeholder="Contoh: RPL, TKJ, IPA, IPS">
        <small class="text-secondary">Jika kosong, diambil dari kata kedua kelas (misal: "RPL").</small>
    </div>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Password</label>
    <input type="password" name="password" class="form-control" minlength="8" {{ $student->exists ? '' : 'required' }}>
    @if($student->exists)
        <small class="text-secondary">
            Kosongkan jika tidak ingin mengubah password siswa.
        </small>
    @else
        <small class="text-secondary">
            Minimal 8 karakter. Sampaikan username dan password awal kepada siswa secara aman.
        </small>
    @endif
</div>
