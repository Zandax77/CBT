<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$setting = \App\Models\SchoolSetting::first();
echo "Before: " . $setting->school_name . "\n";

$result = $setting->update(['school_name' => 'Test Sekolah Baru', 'school_address' => 'Jl. Test No. 1']);
echo "Update result: " . ($result ? 'true' : 'false') . "\n";

$fresh = \App\Models\SchoolSetting::first();
echo "After: " . $fresh->school_name . "\n";
echo "Address: " . $fresh->school_address . "\n";
