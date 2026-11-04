<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        if (!User::where('username', 'kepsek')->exists()) {
            User::create([
                'name'           => 'Kepala Sekolah',
                'username'       => 'kepsek',
                'email'          => 'kepsek@cbt.local',
                'password'       => 'Password123!',
                'plain_password' => 'Password123!',
                'role'           => 'kepala_sekolah',
            ]);
        }
    }

    public function down(): void
    {
        User::where('username', 'kepsek')->delete();
    }
};
