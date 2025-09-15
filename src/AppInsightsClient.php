<?php namespace Larasahib\AppInsightsLaravel;
class AppInsightsClient extends InstrumentationKey
{
    /**
     * @return string
     */
    public function javascript()
    {
        $endpoint = url('/appinsights/collect'); // generates full URL based on environment
        $jsAsset = asset('vendor/app-insights-laravel/js/appinsights-client.min.js');

        return <<<HTML
    <script>
        window.AppInsightsConfig = {
            collectEndpoint: "{$endpoint}"
        };
    </script>
    <script src="{$jsAsset}"></script>
    HTML;
    }
}