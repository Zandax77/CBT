<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CEK DATA USER ===\n";
$users = App\Models\User::all(['id','name','username','email','password','role']);
foreach ($users as $u) {
    $passLen = strlen($u->password);
    $isHashed = str_starts_with($u->password, '$2y$') || str_starts_with($u->password, '$argon');
    echo "ID:{$u->id} | role:{$u->role} | username:".($u->username ?? 'NULL')." | password_len:{$passLen} | is_hashed:".($isHashed ? 'YES' : 'NO')."\n";
}

echo "\n=== TEST HASH PASSWORD ===\n";
$plain = 'Password123!';
$test = App\Models\User::where('username', 'admin')->first();
if ($test) {
    $match = Illuminate\Support\Facades\Hash::check($plain, $test->password);
    echo "admin password check ('Password123!'): " . ($match ? 'MATCH ✓' : 'TIDAK COCOK ✗') . "\n";
    echo "Auth identifier name: " . $test->getAuthIdentifierName() . "\n";
    echo "Auth identifier value: " . $test->getAuthIdentifier() . "\n";
} else {
    echo "User dengan username 'admin' tidak ditemukan!\n";
}
