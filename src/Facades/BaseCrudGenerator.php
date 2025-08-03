<?php

namespace MarwanOsama\BaseCrudGenerator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MarwanOsama\BaseCrudGenerator\BaseCrudGenerator
 */
class BaseCrudGenerator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \MarwanOsama\BaseCrudGenerator\BaseCrudGenerator::class;
    }
}
