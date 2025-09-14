<?php
namespace Larasahib\AppInsightsLaravel\Middleware;

use Closure;
use Larasahib\AppInsightsLaravel\AppInsightsHelpers;
use Larasahib\AppInsightsLaravel\Support\Logger;

class AppInsightsApiMiddleware
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
        return $next($request);
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
            Logger::error('AppInsightsApiMiddleware telemetry error: ' . $e->getMessage(), ['exception' => $e]);
            // Optionally cache or handle error here
        }
    }

}