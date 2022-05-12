<?php

namespace Phuclh\Imagekit\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Phuclh\Imagekit\Imagekit
 */
class Imagekit extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'flysystem-imagekit';
    }
}
