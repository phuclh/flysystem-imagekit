<?php

namespace Phuclh\Imagekit;

use Illuminate\Filesystem\FilesystemAdapter;
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
        $this->app['config']['filesystems.disks.imagekit'] = ['driver' => 'imagekit'];

        Storage::extend('imagekit', function ($app, $config) {
            $client = new ImageKit(
                config('imagekit.public'),
                config('imagekit.private'),
                config('imagekit.endpoint')
            );

            $adapter = new ImagekitAdapter($client);

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
