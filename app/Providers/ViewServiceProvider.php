<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;

class ViewServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        View::composer('*', function ($view) {
            $kategori = DB::table('kategori')->orderBy('nama_kategori', 'asc')->get();
            $view->with('kategori', $kategori);
        });
    }
} 