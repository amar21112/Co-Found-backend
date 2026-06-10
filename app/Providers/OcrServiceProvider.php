<?php

namespace App\Providers;

use App\Services\Ocr\OcrEnricher;
use Illuminate\Support\ServiceProvider;

class OcrServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(OcrEnricher::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
