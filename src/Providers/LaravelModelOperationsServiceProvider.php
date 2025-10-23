<?php

namespace Effectra\LaravelModelOperations\Providers;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class LaravelModelOperationsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-model-operations')
            ->hasConfigFile('model-operations')
            ->hasTranslations()
            ->publishesServiceProvider('LaravelModelOperationsServiceProvider')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub('effectra/laravel-model-operations');
            });
    }
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register bindings or singletons here
    }

    
}