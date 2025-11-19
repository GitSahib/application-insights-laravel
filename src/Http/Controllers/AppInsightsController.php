<?php

namespace Larasahib\AppInsightsLaravel\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Larasahib\AppInsightsLaravel\AppInsightsServer;
use Larasahib\AppInsightsLaravel\Support\Logger;

class AppInsightsController extends Controller
{
    public function collect(Request $request)
    {
        try {
            $payload = $request->all();

            /** @var AppInsightsServer $server */
            $server = app('AppInsightsServer');

            // Support batching: if multiple telemetry items are sent at once
            $items = isset($payload[0]) && is_array($payload) ? $payload : [$payload];

            foreach ($items as $item) {
                $type = $item['type'] ?? null;

                if ($type === 'exception') {
                    $server->trackExceptionFromArray([
                        'message'    => $item['error']['message'] ?? 'Unknown JS error',
                        'stack'      => $item['error']['stack'] ?? null,
                        'properties' => array_merge(
                            $item['error']['properties'] ?? [],
                            [
                                'filename' => $item['error']['filename'] ?? null,
                                'lineno'   => $item['error']['lineno'] ?? null,
                                'colno'    => $item['error']['colno'] ?? null,
                            ]
                        ),
                    ]);
                    $this->flush();
                } elseif ($type === 'event') {
                    $server->trackEventFromArray([
                        'name' => is_string($item['name']) ? $item['name'] : json_encode($item['name']) ?? '',
                        'properties' => $item['properties'] ?? []
                    ]);
                    $this->flush();
                } else {
                    Logger::warning('Unknown telemetry type received', ['payload' => $item]);
                }
            }

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Logger::error('Telemetry backend error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['status' => 'error'], 500);
        }
    }

    private function flush()
    {
        $server = app('AppInsightsServer');
        $queue_seconds = $server->getFlushQueueAfterSeconds();
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
                Logger::debug('Exception: Could not flush AIServer server. Error:'.$e->getMessage());
            }
        }
    }
}
