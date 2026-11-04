<?php

namespace App\Http\Controllers;

use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SchoolSettingController extends Controller
{
    public function edit()
    {
        $setting = SchoolSetting::instance();
        $kepsek = User::firstOrCreate(
            ['username' => 'kepsek'],
            [
                'name'           => 'Kepala Sekolah',
                'email'          => 'kepsek@cbt.local',
                'password'       => 'Password123!',
                'plain_password' => 'Password123!',
                'role'           => 'kepala_sekolah',
            ]
        );

        return view('school-settings.edit', compact('setting', 'kepsek'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'school_name'     => ['required', 'string', 'max:255'],
            'school_address'  => ['nullable', 'string', 'max:500'],
            'school_logo'     => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'kepsek_name'     => ['nullable', 'string', 'max:255'],
            'kepsek_password' => ['nullable', 'string', 'min:6'],
        ]);

        $setting = SchoolSetting::instance();

        // Update nama dan alamat
        $setting->school_name    = $validated['school_name'];
        $setting->school_address = $validated['school_address'] ?? null;

        // Hapus logo jika dicentang
        if ($request->boolean('remove_logo') && $setting->school_logo) {
            if (Storage::disk('public')->exists($setting->school_logo)) {
                Storage::disk('public')->delete($setting->school_logo);
            }
            $setting->school_logo = null;
        }

        // Upload logo baru
        if ($request->hasFile('school_logo') && $request->file('school_logo')->isValid()) {
            // Hapus logo lama jika ada
            if ($setting->school_logo && Storage::disk('public')->exists($setting->school_logo)) {
                Storage::disk('public')->delete($setting->school_logo);
            }
            $setting->school_logo = $request->file('school_logo')->store('school', 'public');
        }

        $setting->save();
        SchoolSetting::forgetCache();

        // Perbarui akun Kepala Sekolah jika diisi
        if ($request->filled('kepsek_name')) {
            $kepsek = User::where('role', 'kepala_sekolah')->first();
            if ($kepsek) {
                $kepsekData = ['name' => $request->kepsek_name];
                if ($request->filled('kepsek_password')) {
                    $kepsekData['password'] = Hash::make($request->kepsek_password);
                    $kepsekData['plain_password'] = $request->kepsek_password;
                }
                $kepsek->update($kepsekData);
            }
        }

        return redirect()->route('admin.school-settings.edit')
            ->with('success', 'Pengaturan sekolah dan akun kepala sekolah berhasil disimpan.');
    }
}
