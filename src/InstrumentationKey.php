<?php
namespace Larasahib\AppInsightsLaravel;
use Illuminate\Support\Facades\Log;
use Larasahib\AppInsightsLaravel\Support\Config;

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
        $this->flushQueueAfterSeconds = Config::get('flush_queue_after_seconds');
        $this->instrumentationKey = Config::get('instrumentation_key');

        $connectionString = Config::get('connection_string');
        if (!empty($connectionString)) {
            $this->connectionString = $connectionString;
            return;
        }
        else if (!empty($this->instrumentationKey)) {
            //deprecated
            Log::warning('Set MS_AI_CONNECTION_STRING in your .env file to use connection string instead of instrumentation key.');
            return;
        }

        $this->connectionString = null;
    }

    public function getFlushQueueAfterSeconds(): int
    {
        return intval($this->flushQueueAfterSeconds ?? 0);
    }
}