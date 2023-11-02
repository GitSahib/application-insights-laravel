<?php
namespace Larasahib\AppInsightsLaravel;

use ApplicationInsights\Telemetry_Client;

class AppInsightsServer extends InstrumentationKey
{
    /**
     * @var Telemetry_Client
     */
    public $telemetryClient;

    public function __construct(Telemetry_Client $telemetryClient)
    {
        parent::__construct();

        if (isset($this->instrumentationKey))
        {
            $this->telemetryClient = $telemetryClient;
            $this->telemetryClient->getContext()->setInstrumentationKey($this->instrumentationKey);
        }
    }

    public function __call($name, $arguments)
    {
        if (isset($this->instrumentationKey, $this->telemetryClient)) {
            return call_user_func_array([&$this->telemetryClient, $name], $arguments);
        }
    }
}