<?php

namespace Phuclh\Imagekit;

use Closure;
use Illuminate\Support\Facades\Storage;
use ImageKit\ImageKit;
use League\Flysystem\Filesystem;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ImagekitServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('flysystem-imagekit')
            ->hasConfigFile('imagekit');
    }

    public function bootingPackage()
    {
        if (! config('imagekit.extend_storage')) {
            return;
        }

        Storage::extend('imagekit', function () {
            $client = new ImageKit(
                config('imagekit.public'),
                config('imagekit.private'),
                config('imagekit.endpoint')
            );

            $adapter = new ImagekitAdapter($client);

            return new Filesystem($adapter);
        });
    }
}
