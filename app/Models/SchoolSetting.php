<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SchoolSetting extends Model
{
    public const CACHE_KEY = 'school_setting';

    protected $fillable = ['school_name', 'school_address', 'school_logo'];

    /**
     * Selalu kembalikan satu baris singleton.
     * Jika belum ada, buat dengan nilai default.
     */
    public static function instance(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $row = static::first();

            if (!$row) {
                $row = static::create([
                    'school_name'    => 'CBT Sekolah',
                    'school_address' => null,
                    'school_logo'    => null,
                ]);
            }

            return $row;
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->school_logo) {
            return null;
        }

        return '/storage/' . ltrim($this->school_logo, '/');
    }
}
