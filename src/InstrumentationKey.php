<?php
namespace Larasahib\AppInsightsLaravel;

use Larasahib\AppInsightsLaravel\Exceptions\InvalidInstrumentationKeyException;

class InstrumentationKey
{
    protected $flushQueueAfterSeconds;
    protected $connectionString;
    protected $instrumentationKey;

    public function __construct()
    {
        $this->setConnectionString();
    }

    protected function setConnectionString()
    {
        $this->flushQueueAfterSeconds = config('AppInsightsLaravel.flushQueueAfterSeconds');
        $this->instrumentationKey = config('AppInsightsLaravel.instrumentationKey');

        $connectionString = config('AppInsightsLaravel.connectionString');
        if (!empty($connectionString)) {
            $this->connectionString = $connectionString;
            return;
        }
        else if (!empty($this->instrumentationKey)) {
            //deprecated
            \Log::warning('Set MS_AI_CONNECTION_STRING in your .env file to use connection string instead of instrumentation key.');
            return;
        }

        $this->connectionString = null;
    }

    public function getFlushQueueAfterSeconds()
    {
        return $this->flushQueueAfterSeconds ?? 0;
    }
}