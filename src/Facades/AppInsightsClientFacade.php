<?php namespace Sahib\AppInsightsLaravel\Facades;

use Illuminate\Support\Facades\Facade;

class AppInsightsClientFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'AppInsightsClient';
    }
}