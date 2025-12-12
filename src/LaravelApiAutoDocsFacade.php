<?php

namespace Piovezanfernando\LaravelApiAutoDocs;

use Illuminate\Support\Facades\Facade;

/**
 * @codeCoverageIgnore
 * @see \Piovezanfernando\LaravelApiAutoDocs\LaravelApiAutoDocs
 */
class LaravelApiAutoDocsFacade extends Facade
{
    /**
     * @inheritDoc
     */
    protected static function getFacadeAccessor()
    {
        return LaravelApiAutoDocs::class;
    }
}
