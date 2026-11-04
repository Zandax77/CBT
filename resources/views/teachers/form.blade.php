<div class="mb-3">
    <label class="form-label">Nama Lengkap</label>
    <input name="name" class="form-control" value="{{ old('name', $teacher->name) }}" required maxlength="255">
</div>

<div class="mb-3">
    <label class="form-label">Username Login</label>
    <input type="text" name="username" class="form-control"
        value="{{ old('username', $teacher->username) }}"
        required maxlength="50" pattern="[a-z0-9_]+"
        placeholder="Contoh: budi_guru">
    <small class="text-muted">Hanya huruf kecil, angka, dan underscore (_).</small>
</div>

<div class="mb-3">
    <label class="form-label">Password</label>
    <input type="password" name="password" class="form-control" minlength="8"
        {{ $teacher->exists ? '' : 'required' }}>
    @if($teacher->exists)
        <small class="text-muted">Kosongkan jika tidak ingin mengubah password.</small>
    @else
        <small class="text-muted">Minimal 8 karakter.</small>
    @endif
</div>
