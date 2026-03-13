<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;

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
        $this->app['mail.manager']->extend('brevo', function ($config) {
            $configuration = $this->app->make('config');

            return (new BrevoTransportFactory())->create(
                Dsn::fromString($configuration->get('services.brevo.dsn'))
            );
        });
    }
}
