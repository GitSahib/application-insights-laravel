<?php
namespace Larasahib\AppInsightsLaravel\Handlers;
use Larasahib\AppInsightsLaravel\AppInsightsHelpers;
use Throwable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class AppInsightsExceptionHandler extends ExceptionHandler
{
    /**
     * @var appInsightsHelpers
     */
    private AppInsightsHelpers $appInsightsHelpers;


    public function __construct(AppInsightsHelpers $appInsightsHelpers, Container $container)
    {
        parent::__construct($container);
        $this->appInsightsHelpers = $appInsightsHelpers;        
    }
    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Throwable $e)
    { 
        $this->appInsightsHelpers->trackException($e);
        return parent::report($e);
    }
}