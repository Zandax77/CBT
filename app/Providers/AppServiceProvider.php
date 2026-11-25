<?php

namespace App\Providers;

use App\Models\SchoolSetting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use App\Models\User;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('*', function ($view) {
            try {
                $view->with('school', SchoolSetting::instance());
            } catch (\Throwable) {
                $view->with('school', new SchoolSetting([
                    'school_name'    => 'CBT Sekolah',
                    'school_address' => null,
                    'school_logo'    => null,
                ]));
            }
        });

        // Ensure an admin account exists on first run (tables empty)
        if (\App\Models\User::count() === 0) {
            \App\Models\User::create([
                'name' => 'Administrator CBT',
                'username' => 'admin',
                'email' => 'admin@cbt.local',
                'password' => 'Password123!', // hashed via model casts
                'role' => 'admin',
            ]);
        }
    }
}
