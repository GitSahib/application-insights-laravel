<?php 

namespace Larasahib\AppInsightsLaravel\Providers;

use Larasahib\AppInsightsLaravel\Clients\Telemetry_Client;
use Laravel\Lumen\Application as LumenApplication;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Illuminate\Support\Facades\DB;
use Larasahib\AppInsightsLaravel\Middleware\AppInsightsWebMiddleware;
use Larasahib\AppInsightsLaravel\Middleware\AppInsightsApiMiddleware;
use Larasahib\AppInsightsLaravel\AppInsightsClient;
use Larasahib\AppInsightsLaravel\AppInsightsHelpers;
use Larasahib\AppInsightsLaravel\AppInsightsServer;
use Larasahib\AppInsightsLaravel\Support\Config;
use Larasahib\AppInsightsLaravel\Support\Logger;

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
        $this->registerDependencyListeners();
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
        $routesPath = $this->getRoutesFile(); 

        $this->publishes([
            $configPath => $this->app->configPath('appinsights-laravel.php'),
        ], 'config');

        $this->publishes([
            $this->getAssetsPath("js") => public_path('vendor/app-insights-laravel/js'),
        ], 'laravel-assets');

        $this->loadRoutesFrom($routesPath);

        $this->mergeConfigFrom($configPath, 'appinsights-laravel');
        
    }

    /**
     * Hook lightweight listeners to automatically track dependencies (e.g., DB queries).
     */
    private function registerDependencyListeners(): void
    {
        if (!Config::get('enable_dependency_telemetry', false)) {
            return;
        }

        try {
            $appInsights = $this->app->make('AppInsightsServer');

            // DB query listener (logs successful queries as dependencies)
            DB::listen(function ($query) use ($appInsights) {
                // Avoid work if telemetry client is unavailable
                if (!$appInsights || !$appInsights->telemetryClient) {
                    return;
                }

                $durationMs = (float) ($query->time ?? 0.0);
                $connection = $query->connection ?? null;
                $config = method_exists($connection, 'getConfig') ? ($connection->getConfig() ?? []) : [];

                $targetHost = $config['host'] ?? 'database';
                $targetPort = $config['port'] ?? null;
                $target = $targetPort ? $targetHost . ':' . $targetPort : $targetHost;

                $properties = [
                    'connection' => $query->connectionName ?? null,
                    'database' => $config['database'] ?? null,
                ];

                if (Config::get('db_dependency_capture_bindings', false)) {
                    $properties['bindings'] = $query->bindings ?? [];
                }

                $data = $query->sql ?? '';

                // Use SQL dependency type per App Insights schema
                $appInsights->trackDependency(
                    'sql:' . ($query->connectionName ?? 'default'),
                    'SQL',
                    $target,
                    $durationMs,
                    true,
                    $data,
                    null,
                    $properties
                );
            });
        } catch (\Throwable $e) {
            Logger::error('AppInsights dependency listener registration failed: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    /**
     * @return string
     */
    private function getAssetsPath(string $path): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $path;
    }
    /**
     * @return string
     */
    protected function getRoutesFile(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web.php';
    }

    /**
     * @return string
     */
    protected function getConfigFile(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'AppInsightsLaravel.php';
    }
}
