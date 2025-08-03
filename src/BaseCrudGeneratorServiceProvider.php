<?php

namespace MarwanOsama\BaseCrudGenerator;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use MarwanOsama\BaseCrudGenerator\Commands\GenerateCrudCommand;

class BaseCrudGeneratorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('base-crud-generator')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(GenerateCrudCommand::class);
    }
}
