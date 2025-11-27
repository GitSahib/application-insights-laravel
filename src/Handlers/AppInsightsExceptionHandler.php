<?php
namespace Larasahib\AppInsightsLaravel\Handlers;
use Larasahib\AppInsightsLaravel\AppInsightsHelpers;
use Throwable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Larasahib\AppInsightsLaravel\Support\Logger;
 /** @disregard Undefined type 'ExceptionHandler' */
class AppInsightsExceptionHandler extends ExceptionHandler
{
    /**
     * @var appInsightsHelpers
     */
    private AppInsightsHelpers $appInsightsHelpers;


    public function __construct(AppInsightsHelpers $appInsightsHelpers, Container $container)
    {
        /** @disregard Undefined type 'parent' */
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
        try {
            $this->appInsightsHelpers->trackException($e, []);
        } catch (\Throwable $ex) {
            Logger::error('AppInsightsExceptionHandler telemetry error: ' . $ex->getMessage(), ['exception' => $ex]);
        }
        /** @disregard Undefined type 'parent' */
        return parent::report($e);
    }
}