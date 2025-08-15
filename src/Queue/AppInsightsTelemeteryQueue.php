<?php
namespace Larasahib\AppInsightsLaravel\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class AppInsightsTelemeteryQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $data;

    public function __construct($data = [])
    {
        $this->data = $data;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if(empty($this->data))
        {
          return;
        }
        try 
        { 
           \AIServer::setQueue($this->data);
           \AIServer::flush();
        }        
        catch (RequestException $e) 
        {
            Log::debug('RequestException: Could not flush AIServer server. Error:'.$e->getMessage());
            Log::debug('Queue: '. json_encode($this->data));
        }
        catch(Exception $e)
        {
            Log::debug('Exception: Could not flush AIServer server. Error:'.$e->getMessage());
            Log::debug('Queue: '. json_encode($this->data));
        }
    }
}
