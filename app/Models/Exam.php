<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Exam extends Model {
    protected $fillable = [
        'title', 'subject', 'target_type', 'target_level', 'target_major', 'class_name',
        'starts_at', 'ends_at', 'duration_minutes', 'token', 'token_valid_minutes',
        'token_expires_at', 'is_published', 'created_by', 'results_released',
        'token_rotate_minutes', 'token_last_rotated_at',
    ];

    protected function casts(): array {
        return [
            'starts_at'             => 'datetime',
            'ends_at'               => 'datetime',
            'is_published'          => 'boolean',
            'results_released'      => 'boolean',
            'token_last_rotated_at' => 'datetime',
            'token_expires_at'      => 'datetime',
            'token_valid_minutes'   => 'integer',
        ];
    }

    public function questions() { return $this->hasMany(Question::class)->orderBy('position'); }
    public function attempts()  { return $this->hasMany(Attempt::class); }
    public function creator()   { return $this->belongsTo(User::class, 'created_by'); }

    /** Apakah ujian sedang aktif (dalam rentang waktu pelaksanaan). */
    public function isActive(): bool {
        $now = now();
        return $this->is_published
            && $this->starts_at <= $now
            && $this->ends_at   >= $now;
    }

    /** Apakah token ujian sudah kedaluwarsa berdasarkan masa aktifnya. */
    public function isTokenExpired(): bool {
        if (!$this->token_expires_at) {
            return false;
        }
        // Jika ujian belum mulai, token belum kedaluwarsa
        if ($this->starts_at && now()->lessThan($this->starts_at)) {
            return false;
        }
        return now()->greaterThan($this->token_expires_at);
    }

    /** Berapa detik tersisa sampai token kedaluwarsa. Null jika tanpa masa aktif. */
    public function tokenSecondsRemaining(): ?int {
        if (!$this->token_expires_at) {
            return null;
        }
        return max(0, now()->diffInSeconds($this->token_expires_at, false));
    }

    /**
     * Memeriksa apakah ujian ini ditujukan untuk siswa yang bersangkutan.
     * Mendukung: Umum, Kelompok Tingkat, Jurusan, dan Kelas Spesifik.
     */
    public function matchesStudent(User $student): bool {
        $type = $this->target_type ?: 'umum';

        if ($type === 'umum') {
            return true;
        }

        $studentClass = trim((string) $student->class_name);
        $studentLevel = strtolower(trim((string) $student->getResolvedGradeLevel()));
        $studentMajor = strtolower(trim((string) $student->getResolvedMajor()));

        if ($type === 'tingkat') {
            $targetLevel = strtolower(trim((string) $this->target_level));
            if ($targetLevel === '') return true;
            return $this->normalizeLevel($studentLevel) === $this->normalizeLevel($targetLevel);
        }

        if ($type === 'jurusan') {
            $targetMajor = strtolower(trim((string) $this->target_major));
            $targetLevel = strtolower(trim((string) $this->target_level));

            $majorMatch = ($targetMajor === '' || $studentMajor === $targetMajor);
            $levelMatch = ($targetLevel === '' || $this->normalizeLevel($studentLevel) === $this->normalizeLevel($targetLevel));

            return $majorMatch && $levelMatch;
        }

        if ($type === 'kelas') {
            if (!$this->class_name) return true;
            // Dukungan multiple kelas dipisah koma
            $targetClasses = array_map('trim', explode(',', strtolower($this->class_name)));
            return in_array(strtolower($studentClass), $targetClasses, true);
        }

        return true;
    }

    /** Normalisasi format tingkat (misal X = 10, XI = 11, XII = 12) */
    private function normalizeLevel(string $val): string {
        $map = [
            'x' => '10', 'xi' => '11', 'xii' => '12',
            'vii' => '7', 'viii' => '8', 'ix' => '9',
        ];
        return $map[$val] ?? $val;
    }

    /** Label human-readable untuk target peserta ujian */
    public function getTargetBadgeLabel(): string {
        $type = $this->target_type ?: 'umum';
        if ($type === 'umum') {
            return '🌐 Semua Kelas (Umum)';
        }
        if ($type === 'tingkat') {
            return '📚 Tingkat ' . ($this->target_level ?: 'Semua');
        }
        if ($type === 'jurusan') {
            $label = '🔧 Jurusan ' . ($this->target_major ?: 'Semua');
            if ($this->target_level) {
                $label .= ' (Tingkat ' . $this->target_level . ')';
            }
            return $label;
        }
        if ($type === 'kelas') {
            return '🏷️ Kelas ' . ($this->class_name ?: 'Semua');
        }
        return 'Semua Kelas';
    }

    /**
     * Rotasi token jika sudah waktunya.
     * Mengembalikan true jika token berubah.
     */
    public function rotateTokenIfNeeded(): bool {
        if (!$this->token_rotate_minutes || !$this->isActive()) {
            return false;
        }

        $lastRotated = $this->token_last_rotated_at ?? $this->starts_at;
        $nextRotate  = $lastRotated->copy()->addMinutes($this->token_rotate_minutes);

        if (now() >= $nextRotate) {
            $newToken = Str::upper(Str::random(6));
            $updates = [
                'token'                 => $newToken,
                'token_last_rotated_at' => now(),
            ];

            if ($this->token_valid_minutes) {
                $updates['token_expires_at'] = now()->addMinutes($this->token_valid_minutes);
            }

            $this->update($updates);
            return true;
        }
        return false;
    }

    /**
     * Berapa detik tersisa sampai rotasi berikutnya.
     */
    public function secondsUntilNextRotation(): ?int {
        if (!$this->token_rotate_minutes || !$this->isActive()) {
            return null;
        }
        $lastRotated = $this->token_last_rotated_at ?? $this->starts_at;
        $nextRotate  = $lastRotated->copy()->addMinutes($this->token_rotate_minutes);
        $diff = now()->diffInSeconds($nextRotate, false);
        return max(0, $diff);
    }
}
