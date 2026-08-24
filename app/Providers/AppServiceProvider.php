<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AddressFormatterInterface;
use App\Contracts\GeocoderInterface;
use App\Services\GoogleGeocoder;
use App\Services\OpenAiAddressFormatter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RedisFactory::class, fn (Application $app) => $app['redis']);

        $this->app->bind(AddressFormatterInterface::class, OpenAiAddressFormatter::class);
        $this->app->bind(GeocoderInterface::class, GoogleGeocoder::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
