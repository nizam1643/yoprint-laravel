<?php

namespace App\Providers;

use App\Interfaces\UploadRepositoryInterface;
use App\Repositories\UploadRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UploadRepositoryInterface::class, UploadRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
