<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use App\Models\User;
class DatabaseSeeder extends Seeder {
 public function run(): void {
  User::firstOrCreate(['username'=>'admin'],['name'=>'Administrator CBT','email'=>'admin@cbt.local','password'=>'Password123!','role'=>'admin']);
  User::firstOrCreate(['username'=>'guru'],['name'=>'Guru Demo','email'=>'guru@cbt.local','password'=>'Password123!','role'=>'guru']);
  User::firstOrCreate(['username'=>'siswa'],['name'=>'Siswa Demo','email'=>'siswa@cbt.local','password'=>'Password123!','role'=>'siswa','student_number'=>'DEMO001','class_name'=>'XI RPL']);
  User::firstOrCreate(['username'=>'kepsek'],['name'=>'Kepala Sekolah','email'=>'kepsek@cbt.local','password'=>'Password123!','plain_password'=>'Password123!','role'=>'kepala_sekolah']);
 }
}
