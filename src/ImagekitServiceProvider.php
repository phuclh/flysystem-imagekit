<?php

namespace Phuclh\Imagekit;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Phuclh\Imagekit\Commands\ImagekitCommand;

class ImagekitServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('flysystem-imagekit')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_flysystem-imagekit_table')
            ->hasCommand(ImagekitCommand::class);
    }
}
