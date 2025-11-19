<?php
namespace Larasahib\AppInsightsLaravel;

use Carbon\Carbon;
use Throwable;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Log;
class AppInsightsHelpers
{

    /**
     * @var AppInsightsServer
     */
    private $appInsights;


    public function __construct(AppInsightsServer $appInsights)
    {
        $this->appInsights = $appInsights; 
    }

    /**
     * Track a page view
     *
     * @param $request
     * @return void
     */
    public function trackPageViewDuration($request)
    {
        if (!$this->telemetryEnabled()) 
        {
            return;
        }

        if (!$request->session()->has('ms_application_insights_page_info')) 
        {
           return;
        }

        $properties = $this->getPageViewProperties($request);
        /** @disregard Undefined type 'AIServer' */
        \AIServer::trackMessage('browse_duration', $properties);
        $this->flush();
        
    }


    /**
     * Track application performance
     *
     * @param $request
     * @param $response
     */
    public function trackRequest($request, $response)
    {
        if (!$this->telemetryEnabled())
        {
            return;
        }
        if (!$this->appInsights->telemetryClient)
        {
            return;
        }
        $properties = $this->getRequestProperties($request);
        /** @disregard Undefined type 'AIServer' */
        \AIServer::trackRequest(
            $properties['route'] ?? 'application',
            $request->fullUrl(),
            $this->getRequestDuration(),
            $this->getResponseCode($response),
            $this->isSuccessful($response),
            $properties,
            $this->getRequestMeasurements($request, $response)
        );
        $this->flush();
    }

    /**
     * Track application exceptions
     *
     * @param Exception $e
     */
    public function trackException(Throwable $e)
    {
        if (!$this->telemetryEnabled()) 
        {
            return;
        }
        /** @disregard Undefined type 'AIServer' */
        \AIServer::trackException($e, $this->getRequestPropertiesFromException($e) ?? []);
        $this->flush();
    }

    /**
     * flushes the telemery queue, will wait for the time provided in config
     * if time was not set in config then it wil flush immediately
     */
    private function flush()
    {
        $queue_seconds = $this->appInsights->getFlushQueueAfterSeconds();
        if($queue_seconds)
        {
            /** @disregard Undefined type 'AIServer' */
            \AIQueue::dispatch(\AIServer::getQueue())
            ->onQueue('appinsights-queue')
            ->delay(Carbon::now()->addSeconds($queue_seconds));
        }
        else
        {
            try 
            {  
                /** @disregard Undefined type 'AIServer' */
               \AIServer::flush();
            }
            catch(\Exception $e)
            {
                Log::debug('Exception: Could not flush AIServer server. Error:'.$e->getMessage());
            }
        }
    }

    /**
     * Get request properties from the exception trace, if available
     *
     * @param Exception $e
     *
     * @return array|null
     */
    private function getRequestPropertiesFromException(Throwable $e)
    {
        foreach ($e->getTrace() as $item)
        {
            if (isset($item['args']))
            {
                foreach ($item['args'] as $arg)
                {
                    
                    /** @disregard Undefined type 'Request' */ 
                    if ($arg instanceof Request)
                    {
                        return $this->getRequestProperties($arg);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Flash page info for use in following page request
     *
     * @param $request
     */
    public function flashPageInfo($request)
    {
        if (!$this->telemetryEnabled())
        {
            return;
        }

        $request->session()->flash('ms_application_insights_page_info', [
            'url' => $request->fullUrl(),
            'load_time' => microtime(true),
            'properties' => $this->getRequestProperties($request)
        ]);

    }

    /**
     * Determines whether the Telemetry Client is enabled
     *
     * @return bool
     */
    private function telemetryEnabled()
    {
        return isset($this->appInsights->telemetryClient);
    }


    /**
     * Get properties from the Laravel request
     *
     * @param $request
     *
     * @return array|null
     */
    private function getRequestProperties($request)
    {
        $properties = [
            'ajax' => $request->ajax(),
            'fullUrl' => $request->fullUrl(),
            'ip' => $request->ip(),
            'pjax' => $request->pjax(),
            'secure' => $request->secure(),
        ];

        if ($request->route()
            && $request->route()->getName())
        {
            $properties['route'] = $request->route()->getName();
        }

        if ($request->user())
        {
            $properties['user'] = $request->user()->id;
        }

        if ($request->server('HTTP_REFERER'))
        {
            $properties['referer'] = $request->server('HTTP_REFERER');
        }

        return $properties;
    }


    /**
     * Doesn't do a lot right now!
     *
     * @param $request
     * @param $response
     *
     * @return array|null
     */
    private function getRequestMeasurements($request, $response)
    {
        $measurements = [];

        return ( ! empty($measurements)) ? $measurements : null;
    }


    /**
     * Estimate the time spent viewing the previous page
     *
     * @param $loadTime
     *
     * @return mixed
     */
    private function getPageViewDuration($loadTime)
    {
        return round(($_SERVER['REQUEST_TIME_FLOAT'] - $loadTime), 2);
    }

    /**
     * Calculate the time spent processing the request
     *
     * @return mixed
     */
    private function getRequestDuration()
    {
        return (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000;
    }


    /**
     * Determine if the request was successful
     *
     * @param $response
     *
     * @return bool
     */
    private function isSuccessful($response)
    {
        return ($this->getResponseCode($response) < 400);
    }


    /**
     * Get additional properties for page view at the end of the request
     *
     * @param $request
     *
     * @return mixed
     */
    private function getPageViewProperties($request)
    {
        $pageInfo = $request->session()->get('ms_application_insights_page_info');

        $properties = $pageInfo['properties'];

        $properties['url'] = $pageInfo['url'];
        $properties['duration'] = $this->getPageViewDuration($pageInfo['load_time']);
        $properties['duration_formatted'] = $this->formatTime($properties['duration']);

        return $properties;
    }


    /**
     * Formats time strings into a human-readable format
     *
     * @param $duration
     *
     * @return string
     */
    private function formatTime($duration)
    {
        $milliseconds = str_pad((round($duration - floor($duration), 2) * 100), 2, '0', STR_PAD_LEFT);

        if ($duration < 1) {
            return "0.{$milliseconds} seconds";
        }

        $seconds = floor($duration % 60);

        if ($duration < 60) {
            return "{$seconds}.{$milliseconds} seconds";
        }

        $string = str_pad($seconds, 2, '0', STR_PAD_LEFT) . '.' . $milliseconds;

        $minutes = floor(($duration % 3600) / 60);

        if ($duration < 3600) {
            return "{$minutes}:{$string}";
        }

        $string = str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':' . $string;

        $hours = floor(($duration % 86400) / 3600);

        if ($duration < 86400) {
            return "{$hours}:{$string}";
        }

        $string = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . $string;

        $days = floor($duration / 86400);

        return $days . ':' . $string;
    }

    /**
     * If you use stream() or streamDownload() then the response object isn't a standard one. Here we check different
     * places for the status code depending on the object that Laravel sends us.
     *
     * @param StreamedResponse|Response $response The response object
     *
     * @return int The HTTP status code
     */
    private function getResponseCode($response)
    {
        /** @disregard Undefined type 'StreamedResponse' */ 
        return $response instanceof StreamedResponse ? $response->getStatusCode() : $response->status();
    }
}
