<?php

namespace Larasahib\AppInsightsLaravel\Support;

use Illuminate\Support\Facades\Config as LaravelConfig;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static mixed env(string $key, mixed $default = null)
 */
class Config
{
    /**
     * Safely get a Laravel config value.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        try {
            $parentKey = "appinsights-laravel.";
            $key = $parentKey . $key;
            if(function_exists('config')) {
                /** @disregard Undefined type 'config' */ 
                return config($key, $default);
            }
            // If the Laravel Config Facade exists, use it
            if (class_exists(LaravelConfig::class)) {
                return LaravelConfig::get($key, $default);
            }

            // Fallback: try env() helper
            if (function_exists('env')) {
                return env(strtoupper(str_replace('.', '_', $key)), $default);
            }

            return $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Get directly from environment variables as fallback.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function env(string $key, $default = null)
    {
        try {
            if (function_exists('env')) {
                return env(strtoupper(str_replace('.', '_', $key)), $default);
            }

            return $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }
}
