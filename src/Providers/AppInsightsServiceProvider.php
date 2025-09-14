<?php 

namespace Larasahib\AppInsightsLaravel\Providers;

use Larasahib\AppInsightsLaravel\Clients\Telemetry_Client;
use Laravel\Lumen\Application as LumenApplication;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Larasahib\AppInsightsLaravel\Middleware\AppInsightsWebMiddleware;
use Larasahib\AppInsightsLaravel\Middleware\AppInsightsApiMiddleware;
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

        $this->app->singleton('AppInsightsWebMiddleware', function ($app) {
            $appInsightsHelpers = new AppInsightsHelpers($app['AppInsightsServer']);
            return new AppInsightsWebMiddleware($appInsightsHelpers);
        });

        $this->app->singleton('AppInsightsApiMiddleware', function ($app) {
            $appInsightsHelpers = new AppInsightsHelpers($app['AppInsightsServer']);
            return new AppInsightsApiMiddleware($appInsightsHelpers);
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
            'AppInsightsWebMiddleware',
            'AppInsightsClient',
            "AppInsightsApiMiddleware"
        ];
    }

    private function handleConfigs() {

        $configPath = $this->getConfigFile();

        $this->publishes([
            $configPath => $this->app->configPath('appinsights-laravel.php'),
        ], 'config');

        $this->mergeConfigFrom($configPath, 'appinsights-laravel');
    }

    /**
     * @return string
     */
    protected function getConfigFile(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'AppInsightsLaravel.php';
    }
}
