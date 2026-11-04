<?php

namespace App\Providers;

use App\Models\SchoolSetting;
use Illuminate\Support\Facades\View;
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
    }
}
