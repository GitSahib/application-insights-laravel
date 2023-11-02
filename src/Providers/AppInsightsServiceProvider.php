<?php 

namespace Larasahib\AppInsightsLaravel\Providers;

use ApplicationInsights\Telemetry_Client;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Larasahib\AppInsightsLaravel\Queue\AppInsightsTelemeteryQueue;
use Larasahib\AppInsightsLaravel\Middleware\AppInsightsMiddleware;
use Larasahib\AppInsightsLaravel\AppInsightsClient;
use Larasahib\AppInsightsLaravel\AppInsightsHelpers;
use Larasahib\AppInsightsLaravel\AppInsightsServer;

class AppInsightsServiceProvider extends LaravelServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot() {
        $this->handleConfigs();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('AppInsightsServer', function ($app) {
            $telemetryClient = new Telemetry_Client();
            return new AppInsightsServer($telemetryClient);
        });

        $this->app->singleton('AppInsightsMiddleware', function ($app) {
            $appInsightsHelpers = new AppInsightsHelpers($app['AppInsightsServer']);
            return new AppInsightsMiddleware($appInsightsHelpers);
        });

        $this->app->singleton('AppInsightsClient', function ($app) {
            return new AppInsightsClient();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {

        return [
            'AppInsightsServer',
            'AppInsightsMiddleware',
            'AppInsightsClient'
        ];
    }

    private function handleConfigs() {

        $configPath = __DIR__ . '/../../config/AppInsightsLaravel.php';

        $this->publishes([$configPath => config_path('AppInsightsLaravel.php')]);

        $this->mergeConfigFrom($configPath, 'AppInsightsLaravel');
    }
}
