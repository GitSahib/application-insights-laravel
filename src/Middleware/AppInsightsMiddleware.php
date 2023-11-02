<?php
namespace Larasahib\AppInsightsLaravel\Middleware;

use Closure;
use Larasahib\AppInsightsLaravel\AppInsightsHelpers;

class AppInsightsMiddleware
{

    /**
     * @var AppInsightsHelpers
     */
    private AppInsightsHelpers $appInsightsHelpers;


    /**
     * @param AppInsightsHelpers $appInsightssHelpers
     */
    public function __construct(AppInsightsHelpers $appInsightsHelpers)
    {
        $this->appInsightsHelpers = $appInsightsHelpers;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->appInsightsHelpers->trackPageViewDuration($request);

        $response = $next($request);

        $this->appInsightsHelpers->flashPageInfo($request);

        return $response;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     * @return void
     */
    public function terminate($request, $response)
    {
        $this->appInsightsHelpers->trackRequest($request, $response);
    }

}