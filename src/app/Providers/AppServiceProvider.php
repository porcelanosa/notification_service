<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Channels\EmailChannel;
use App\Channels\SmsChannel;
use App\Gateways\MockEmailGateway;
use App\Gateways\MockSmsGateway;
use App\Services\Notifier;


class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Notifier::class, function () {
            $notifier = new Notifier();
            $notifier->addChannel(new SmsChannel(new MockSmsGateway()));
            $notifier->addChannel(new EmailChannel(new MockEmailGateway()));
            return $notifier;
        });
    }
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
