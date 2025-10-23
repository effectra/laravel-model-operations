<?php

namespace Effectra\Operations\Provider;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class LaravelModelOperationsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('your-package-name')
            ->hasConfigFile('model-operations.php')
            ->sharesDataWithAllViews('downloads', 3)
            ->hasTranslations()
            ->publishesServiceProvider('LaravelModelOperationsServiceProvider')
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub();
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