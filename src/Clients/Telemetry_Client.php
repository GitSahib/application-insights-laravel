<?php
namespace Larasahib\AppInsightsLaravel\Clients;
use Larasahib\AppInsightsLaravel\Exceptions\AppInsightsException;
use Illuminate\Support\Facades\Http;
class Telemetry_Client
{
    
    protected $baseUrl = 'https://dc.services.visualstudio.com';
    /**
     * Buffer for telemetry items to be sent.
     *
     * @var array
     */
    protected $buffer = [];
    
    /**
     * Limit for the buffer before it automatically flushes.
     *
     * @var int
     */
    protected $bufferLimit = 10; // Optional: auto-flush after N items

    /**
     * Global properties to be sent with every telemetry item.
     *
     * @var array
     */
    protected $globalProperties = [];

    /**
     * The instrumentation key for the Application Insights service.
     *
     * @var string
     */
    protected $instrumentationKey;

    /**
     * The connection string for the Application Insights service.
     *
     * @var string
     */
    protected $connectionString;

    /**
     * Telemetry_Client constructor.
     */
    public function __construct()
    {
        // Flush at script end
        register_shutdown_function(function () {
            $this->flush();
        });
    }

    /**
     * Sets the queue for telemetry data.
     *
     * @param array $data
     * @throws AppInsightsException
     */
    public function setQueue(array $data)
    {
        if (empty($data)) {
            \Log::error('Telemetry data cannot be empty.');
            return;
        }
        $this->buffer = $data;
    }

    /**
     * Gets the current queue of telemetry data.
     *
     * @return array
     */
    public function getQueue()
    {
        return $this->buffer;
    }
    
    /**
     * Sets the connection string for the Application Insights service.
     *
     * @param string $connectionString
     * @throws AppInsightsException
     */ 
    public function setConnectionString($connectionString)
    {
        if (!empty($connectionString))
        {
            $this->connectionString = $connectionString;
            preg_match('/IngestionEndpoint=(.+?);/', $connectionString, $matches);
            $endpoint = $matches[1] ?? $this->baseUrl;
            $instrumentationKey = preg_match('/InstrumentationKey=(.+?);/', $connectionString, $matches) ? $matches[1] : $this->instrumentationKey;
            $url = rtrim($endpoint, '/');
            $this->baseUrl = $url;
        }
        if(!empty($instrumentationKey)) {
            $this->instrumentationKey = $instrumentationKey;
        }
    }
    /**
     * Sets the instrumentation key for the Application Insights service.
     *
     * @param string $instrumentationKey
     * @throws AppInsightsException
     */
    public function setInstrumentationKey($instrumentationKey)
    {
        if (empty($instrumentationKey)) {
            throw new AppInsightsException('Instrumentation key cannot be empty.');
        }
        $this->instrumentationKey = $instrumentationKey;
    }

    /**
     * Sets global properties that will be included in every telemetry item.
     *
     * @param array $properties
     */
    public function setGlobalProperties(array $properties)
    {
        $this->globalProperties = $properties;
    }

    /**
     * Format the duration in milliseconds to a string format.
     *
     * @param float $durationMs
     * @return string
     */
    public function trackRequest(string $name, string $url, float $durationMs, int $responseCode, bool $success)
    {
        $urlParts = parse_url($url);

        $baseUrl = ($urlParts['scheme'] ?? '') . '://' .
               ($urlParts['host'] ?? '') .
               ($urlParts['path'] ?? '');
        // Query parameters (array)
        $queryParams = [];
        if (!empty($urlParts['query'])) {
            parse_str($urlParts['query'], $queryParams);
        }

        // If you want to limit query params to max 5
        $queryParams = array_slice($queryParams, 0, 5, true);
        $payload = [
            'name' => 'Microsoft.ApplicationInsights.Request',
            'time' => now()->toIso8601ZuluString(),
            'iKey' => $this->instrumentationKey,
            'data' => [
                'baseType' => 'RequestData',
                'baseData' => [
                    'ver' => 1,
                    'id' => uniqid(),
                    'name' => $name,
                    'duration' => $this->formatDuration($durationMs),
                    'responseCode' => (string) $responseCode,
                    'success' => $success,
                    'url' => $baseUrl,
                ],
                'properties' => array_merge(
                    $this->globalProperties,
                    [
                        'url' => $baseUrl,
                        'query_params' => json_encode($queryParams),
                        'duration_ms' => $durationMs,
                        'response_code' => $responseCode,
                        'success' => $success ? 'true' : 'false',
                    ]
                )
            ]
        ];

        $this->sendPayload($payload);
    }

    /**
     * Tracks an exception with the Application Insights service.
     * @param \Throwable $exception The exception to track.
     * @return void
     */
    public function trackException(\Throwable $exception, $properties = [])
    {
        $payload = [
            'name' => 'Microsoft.ApplicationInsights.Exception',
            'time' => now()->toIso8601ZuluString(),
            'iKey' => $this->instrumentationKey,
            'data' => [
                'baseType' => 'ExceptionData',
                'baseData' => [
                    'ver' => 2,
                    'exceptions' => [[
                        'typeName' => get_class($exception),
                        'message' => $exception->getMessage(),
                        'hasFullStack' => true,
                        'stack' => $exception->getTraceAsString(),
                    ]],
                    'severityLevel' => 3, // 0=Verbose, 1=Info, 2=Warning, 3=Error, 4=Critical
                    'properties' => array_merge(
                        $this->globalProperties,
                        $properties
                    )
                ]
            ]
        ];
        // Log the payload before sending
        $this->sendPayload($payload);
    }
    /**
     * Gets request properties from the request object.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function getRequestProperties($request)
    {
        return [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
            'user_id' => optional($request->user())->id,
            'route_name' => $request->route() ? $request->route()->getName() : null,
        ];
    }

    /**
     * Tracks a custom event with the Application Insights service.
     * @param string $eventName The name of the event.
     * @param array $properties Additional properties to include with the event.
     * @return void
     */
    public function trackEvent(string $eventName, array $properties = [])
    {
        $payload = [
            'name' => 'Microsoft.ApplicationInsights.Event',
            'time' => now()->toIso8601ZuluString(),
            'iKey' => $this->instrumentationKey,
            'data' => [
                'baseType' => 'EventData',
                'baseData' => [
                    'ver' => 2,
                    'name' => $eventName,
                    'properties' => array_merge($this->globalProperties, $properties)
                ]
            ]
        ];

        $this->sendPayload($payload);
    }

    /**
     * Tracks a message with the Application Insights service.
     * @param string $message The message to track.
     * @param int $severity The severity level of the message (0=Verbose, 1=Info, 2=Warning, 3=Error, 4=Critical).
     * @param array $properties Additional properties to include with the message.
     * @return void
     */
    public function trackMessage(string $message, array $properties = [], int $severity = 1)
    {
        $this->trackTrace($message, $severity, $properties);
    }
    /**
     * Tracks a trace message with the Application Insights service.
     * @param string $message The trace message.
     * @param int $severity The severity level of the trace (0=Verbose, 1=Info, 2=Warning, 3=Error, 4=Critical).
     * @param array $properties Additional properties to include with the trace.
     * @return void
     */
    public function trackTrace(string $message, int $severity = 1, array $properties = [])
    {
        $payload = [
            'name' => 'Microsoft.ApplicationInsights.Message',
            'time' => now()->toIso8601ZuluString(),
            'iKey' => $this->instrumentationKey,
            'data' => [
                'baseType' => 'MessageData',
                'baseData' => [
                    'ver' => 2,
                    'message' => $message,
                    'severityLevel' => $severity,
                    'properties' => array_merge($this->globalProperties, $properties)
                ]
            ]
        ];

        $this->sendPayload($payload);
    }

    protected function sendPayload(array $payload)
    {
        $this->buffer[] = $payload;

        if (count($this->buffer) >= $this->bufferLimit) {
            $this->flush(); // Auto-flush
        }
    }

    public function flush()
    {
        if (empty($this->buffer)) {
            \Log::info('AppInsights flush called but buffer is empty.');
            return;
        }
        
        try {

            if (config('AppInsightsLaravel.enableLocalLogging')) {
                $this->logPayloadBeforeFlush();
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/x-ndjson',
            ])->withBody(
                $this->formatBatchPayload(), // returns a raw string with "\n" between JSON objects
                'application/x-ndjson'
            )->post($this->baseUrl . '/v2/track', [
                'iKey' => $this->instrumentationKey,
            ]);
            
            // Log the successful flush
            if (config('AppInsightsLaravel.enableLocalLogging')) {
                \Log::debug('Raw AppInsights response', ['body' => $response->body()]);
            }
            
            // Clear buffer after successful send
            $this->buffer = [];
        } catch (\Exception $e) {
            \Log::error('AppInsights flush failed: ' . $e->getMessage());
        }
    }

    protected function logPayloadBeforeFlush()
    {
        \Log::debug("AppInsights Payload Posting to", ['url' => $this->baseUrl . '/v2/track']);
        foreach ($this->buffer as $index => $item) {
            \Log::debug("AppInsights Payload [{$index}]", ['json' => json_encode($item)]);
        }

        try {
            $ndjson = $this->formatBatchPayload();
            \Log::debug("AppInsights NDJSON Payload", ['ndjson' => $ndjson]);
        } catch (\Throwable $e) {
            \Log::error("Failed to format AppInsights NDJSON payload: " . $e->getMessage());
        }
    }


    protected function formatBatchPayload()
    {
        return implode("\n", array_map(fn($item) => json_encode($item), $this->buffer));
    }

    /**
     * Formats the duration in milliseconds to a string format.
     *
     * @param float $milliseconds
     * @return string
     */
    protected function formatDuration($milliseconds): string
    {
        $hours = floor($milliseconds / 3600000);
        $minutes = floor(($milliseconds % 3600000) / 60000);
        $seconds = floor(($milliseconds % 60000) / 1000);
        $ms = $milliseconds % 1000;
        \Log::info("AppInsights duration formatted: ${milliseconds} {$hours}:{$minutes}:{$seconds}.{$ms}");
        return sprintf('%02d:%02d:%02d.%03d', $hours, $minutes, $seconds, $ms);
    }

    /**
     * Get the instrumentation key.
     *
     * @return string|null
     */
    public function getInstrumentationKey()
    {
        return $this->instrumentationKey;
    }
}