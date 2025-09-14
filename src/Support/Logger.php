<?php
namespace Larasahib\AppInsightsLaravel\Support;
use Illuminate\Support\Facades\Log as LaravelLog;

class Logger
{
    public static function info(string $message, array $context = [])
    {
        if (class_exists(LaravelLog::class)) {
            LaravelLog::info($message, $context);
        }
    }

    public static function error(string $message, array $context = [])
    {
        if (class_exists(LaravelLog::class)) {
            LaravelLog::error($message, $context);
        }
    }

    public static function debug(string $message, array $context = [])
    {
        if (class_exists(LaravelLog::class)) {
            LaravelLog::debug($message, $context);
        }
    }

    public static function warning(string $message, array $context = [])
    {
        if (class_exists(LaravelLog::class)) {
            LaravelLog::warning($message, $context);
        }
    }
}
