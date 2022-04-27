<?php

namespace Kilobyteno\LaravelPlausible\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Kilobyteno\LaravelPlausible\Plausible
 */
class LaravelPlausible extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-plausible';
    }
}
