<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create a default admin account if none exists.
        // The password will be automatically hashed via the User model's casts.
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator CBT',
                'email' => 'admin@cbt.local',
                'password' => 'Password123!',
                'role' => 'admin',
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::where('username', 'admin')->delete();
    }
};