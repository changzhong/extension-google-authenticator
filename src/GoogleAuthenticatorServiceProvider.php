<?php

namespace Dcat\Admin\Extension\GoogleAuthenticator;

use Dcat\Admin\Extension\GoogleAuthenticator\Http\Controllers\GoogleAuthenticatorController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

use Dcat\Admin\Extension\GoogleAuthenticator\Lib\GoogleAuthenticator as GoogleAuthenticatorLib;

class GoogleAuthenticatorServiceProvider extends ServiceProvider
{
    protected $commandName = 'google:secret';

    /**
     * {@inheritdoc}
     */
    public function boot()
    {

        $extension = GoogleAuthenticator::make();

        if ($views = $extension->views()) {
            $this->loadViewsFrom($views, 'google-authenticator');
        }


        if ($this->app->runningInConsole() && $assets = $extension->assets()) {
            $this->publishes(
                [$assets => public_path('vendors/dcat-admin-extensions/'.GoogleAuthenticator::NAME)],
                GoogleAuthenticator::NAME
            );
        }


        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
        $this->app->booted(function () use ($extension) {
            $extension->routes(__DIR__.'/../routes/web.php');
        });

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('GoogleAuthenticator', function ($app) {
            return new GoogleAuthenticatorLib();
        });
    }
}
