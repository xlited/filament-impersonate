<?php

namespace XliteDev\FilamentImpersonate\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \XliteDev\FilamentImpersonate\FilamentImpersonate
 */
class FilamentImpersonate extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \XliteDev\FilamentImpersonate\FilamentImpersonate::class;
    }
}
