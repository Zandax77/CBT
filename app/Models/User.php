<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable {
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role',
        'student_number', 'class_name', 'grade_level', 'major',
        'plain_password'
    ];
    
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function exams() { return $this->hasMany(Exam::class, 'created_by'); }
    public function attempts() { return $this->hasMany(Attempt::class); }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isGuru(): bool { return $this->role === 'guru'; }
    public function isSiswa(): bool { return $this->role === 'siswa'; }
    public function isKepalaSekolah(): bool { return $this->role === 'kepala_sekolah'; }

    /**
     * Dapatkan tingkat kelas (misal: "X", "XI", "XII", atau "10", "11", "12").
     * Jika kolom grade_level kosong, fallback otomatis mengekstrak dari class_name.
     */
    public function getResolvedGradeLevel(): ?string {
        if (!empty($this->grade_level)) {
            return trim($this->grade_level);
        }
        if (!empty($this->class_name)) {
            $parts = preg_split('/\s+/', trim($this->class_name));
            return $parts[0] ?? null;
        }
        return null;
    }

    /**
     * Dapatkan jurusan siswa (misal: "RPL", "TKJ", "IPA", "IPS").
     * Jika kolom major kosong, fallback otomatis mengekstrak dari class_name.
     */
    public function getResolvedMajor(): ?string {
        if (!empty($this->major)) {
            return trim($this->major);
        }
        if (!empty($this->class_name)) {
            $parts = preg_split('/\s+/', trim($this->class_name));
            if (count($parts) >= 2) {
                return $parts[1];
            }
        }
        return null;
    }
}
