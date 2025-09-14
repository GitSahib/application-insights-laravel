<?php

return [

    /*
     * Instrumentation Key
     * ===================
     *
     * The instrumentation key can be found on the Application Insights dashboard on portal.azure.com
     * Microsoft Azure > Browse > Application Insights > (Application Name) > Settings > Properties
     *
     * Add the MS_INSTRUMENTATION_KEY field to your application's .env file,
     * then paste in the value found on the properties page shown above.
     *
     * Alternatively, replace the env call below with a string containing your key.
     */

    'instrumentation_key' => env('MS_INSTRUMENTATION_KEY', null),
    'flush_queue_after_seconds' => env('MS_AI_FLUSH_QUEUE_AFTER_SECONDS', 0),
    'enable_local_logging' => env('MS_AI_ENABLE_LOGGING', 0),
    'connection_string' => env('MS_AI_CONNECTION_STRING', null),

];
