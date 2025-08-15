<?php
namespace Larasahib\AppInsightsLaravel;

use Larasahib\AppInsightsLaravel\Clients\Telemetry_Client;

class AppInsightsServer extends InstrumentationKey
{
    /**
     * @var Telemetry_Client
     */
    public $telemetryClient;

    public function __construct(Telemetry_Client $telemetryClient)
    {
        parent::__construct();
        if (isset($this->connectionString)) {
            $this->telemetryClient = $telemetryClient;
            $this->telemetryClient->setConnectionString($this->connectionString);
        }
        else if (isset($this->instrumentationKey)) {
            //deprecated
            \Log::warning('Set MS_AI_CONNECTION_STRING in your .env file to use connection string instead of instrumentation key.');
            $this->telemetryClient = $telemetryClient;
            $this->telemetryClient->setInstrumentationKey($this->instrumentationKey);
        }
    }

    public function __call($name, $arguments)
    {
        if (isset($this->connectionString, $this->telemetryClient)) {
            return call_user_func_array([&$this->telemetryClient, $name], $arguments);
        }
    }
}