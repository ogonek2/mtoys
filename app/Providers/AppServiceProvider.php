<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Paginator::useBootstrap();
        require_once app_path('Helpers/helpers.php');

        View::composer(
            'includes.main.mega-menu',
            fn ($view) => $view->with('megaMenuItems', get_mega_menu_data())
        );
    }
}
