<?php

namespace Kabret\BaseCrudGenerator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Kabret\BaseCrudGenerator\BaseCrudGenerator
 */
class BaseCrudGenerator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Kabret\BaseCrudGenerator\BaseCrudGenerator::class;
    }
}
