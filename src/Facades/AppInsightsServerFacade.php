<?php 

namespace Larasahib\AppInsightsLaravel\Facades;

use Illuminate\Support\Facades\Facade;

class AppInsightsServerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'AppInsightsServer';
    }
}