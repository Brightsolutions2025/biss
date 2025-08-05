<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

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
        Blade::component('dashboard.card', \App\View\Components\Dashboard\Card::class);

        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));

        Storage::extend('google', function ($app, $config) {
            $client = new \Google_Client();
            $client->setClientId($config['clientId']);
            $client->setClientSecret($config['clientSecret']);
            $client->refreshToken($config['refreshToken']);

            $service = new \Google_Service_Drive($client);
            $adapter = new GoogleDriveAdapter($service, $config['folderId']);

            return new \Illuminate\Filesystem\FilesystemAdapter(
                new Filesystem($adapter), // ✅ This is a League\Flysystem\FilesystemOperator
                $adapter,
                $config
            );
        });

        Response::macro('frameOptions', function () {
            return response()->header('X-Frame-Options', 'DENY');
        });
    }
}
