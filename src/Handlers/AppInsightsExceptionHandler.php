<?php
namespace Sahib\AppInsightsLaravel\Handlers;

use Throwable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class AppInsightsExceptionHandler extends ExceptionHandler
{
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
        \AIServer::trackException($e);
        \AIQueue::dispatch(\AIServer::getChannel()->getQueue())->delay(now()->addSeconds(3));
        return parent::report($e);
    }
}