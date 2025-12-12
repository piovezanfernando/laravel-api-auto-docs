<?php

namespace Piovezanfernando\LaravelApiAutoDocs;

use Piovezanfernando\LaravelApiAutoDocs\Commands\ExportRequestDocsCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelApiAutoDocsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-api-auto-docs')
            ->hasConfigFile('api-auto-docs')
            ->hasCommand(ExportRequestDocsCommand::class)
            ->hasViews()
            ->hasRoute('web'); // Use Spatie's route registration

        // Publish all dist assets to public/api-auto-docs
        $this->publishes([
            __DIR__ . '/../resources/dist' => public_path('api-auto-docs'),
        ], 'laravel-api-auto-docs');
    }
}
