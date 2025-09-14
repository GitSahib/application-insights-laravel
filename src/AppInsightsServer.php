<?php
namespace Larasahib\AppInsightsLaravel;

use Larasahib\AppInsightsLaravel\Clients\Telemetry_Client;
use Larasahib\AppInsightsLaravel\Support\Logger;

class AppInsightsServer extends InstrumentationKey
{
    /**
     * @var Telemetry_Client
     */
    public $telemetryClient;

    public function __construct(Telemetry_Client $telemetryClient)
    {
        try {
            parent::__construct();
            Logger::info('AppInsightsServer initialized with ' . (isset($this->connectionString) ? 'connection string.' : (isset($this->instrumentationKey) ? 'instrumentation key.' : 'no configuration.')));
            if (isset($this->connectionString)) {
                $this->telemetryClient = $telemetryClient;
                $this->telemetryClient->setConnectionString($this->connectionString);
            }
            else if (isset($this->instrumentationKey)) {
                //deprecated
                Logger::warning('Set MS_AI_CONNECTION_STRING in your .env file to use connection string instead of instrumentation key.');
                $this->telemetryClient = $telemetryClient;
                $this->telemetryClient->setInstrumentationKey($this->instrumentationKey);
            }
        } catch (\Throwable $e) {
            Logger::error('AppInsightsServer constructor error: ' . $e->getMessage(), ['exception' => $e]);
            // Optionally cache or handle error here
        }
    }

    public function getChannel()
    {
        return $this->telemetryClient;
    }

    public function __call($name, $arguments)
    {
        try {
            if (isset($this->connectionString, $this->telemetryClient)) {
                return call_user_func_array([&$this->telemetryClient, $name], $arguments);
            }
        } catch (\Throwable $e) {
            Logger::error('AppInsightsServer __call error: ' . $e->getMessage(), ['exception' => $e]);
            // Optionally cache or handle error here
        }
    }
}