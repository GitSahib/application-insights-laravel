<?php
namespace Larasahib\AppInsightsLaravel\Middleware;

use Closure;
use Larasahib\AppInsightsLaravel\AppInsightsHelpers;
use Larasahib\AppInsightsLaravel\Support\Logger;
class AppInsightsWebMiddleware
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
        try {
            $this->appInsightsHelpers->trackPageViewDuration($request);
        } catch (\Throwable $e) {
            Logger::error('AppInsightsWebMiddleware trackPageViewDuration error: ' . $e->getMessage(), ['exception' => $e]);
        }

        $response = $next($request);

        try {
            $this->appInsightsHelpers->flashPageInfo($request);
        } catch (\Throwable $e) {
            Logger::error('AppInsightsWebMiddleware flashPageInfo error: ' . $e->getMessage(), ['exception' => $e]);
        }

        return $response;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     * @return void
     */
    public function terminate($request, $response)
    {
        try {
            $this->appInsightsHelpers->trackRequest($request, $response);
        } catch (\Throwable $e) {
            Logger::error('AppInsightsWebMiddleware telemetry error: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

}