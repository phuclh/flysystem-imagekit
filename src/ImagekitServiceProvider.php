<?php

namespace Phuclh\Imagekit;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ImagekitServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('flysystem-imagekit')
            ->hasConfigFile();
    }
}
