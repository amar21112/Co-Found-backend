<?php

namespace App\Providers;

use App\Repositories\Contracts\IdentityVerificationRepositoryInterface;
use App\Repositories\Eloquent\IdentityVerificationRepository;
use App\Services\Ocr\OcrEnricher;
use App\Services\Verification\IdentityVerificationService;
use Illuminate\Support\ServiceProvider;

class VerificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            IdentityVerificationRepositoryInterface::class,
            IdentityVerificationRepository::class
        );

        $this->app->bind(IdentityVerificationService::class, function ($app) {
            return new IdentityVerificationService(
                $app->make(IdentityVerificationRepositoryInterface::class),
                $app->make(OcrEnricher::class)
            );
        });
    }

    public function boot(): void {}
}
