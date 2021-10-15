<?php

namespace Crudfy\JsManager\Facades;

use Illuminate\Support\Facades\Facade;

class JsManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'js-manager';
    }
}
