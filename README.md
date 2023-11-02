# Microsoft Application Insights for Laravel 10

A simple Laravel implementation for [Microsoft Application Insights](http://azure.microsoft.com/en-gb/services/application-insights/)

This package is based on https://github.com/provisions-group/ms-application-insights-laravel
The above package was for laravel 5 and had no updates so we started a new one for laravel 10 with some extra features for example queuing and handling api and web guards differently

## Installation

Update the `require` section of your application's **composer.json** file:

```js
"require": {
	...
	"sahib/application-insights-laravel": "1.0.1",
	...
}
```

### Instrumentation Key

The package will check your application's **.env** file for your *Instrumentation Key*.

Add the following to your **.env** file:

```
...
MS_INSTRUMENTATION_KEY=<your instrumentation key>
...
```

You can find your instrumentation key on the [Microsoft Azure Portal](https://portal.azure.com).

Navigate to:

**Microsoft Azure** > **Browse** > **Application Insights** > *(Application Name)* > **Settings** > **Properties**

## Usage

### Request Tracking Middleware

To monitor your application's performance with request tracking, add the middleware to your in your application, found in **app/Http/Kernel.php**. It has to be added after the StartSession middleware has been added.

```php

protected $middleware = [
	...
	'AppInsightsMiddleware',
	...
]

```

The request will send the following additional properties to Application Insights:

- **ajax** *(boolean)*: *true* if the request is an AJAX request
- **ip** *(string)*: The client's IP address
- **pjax** *(boolean)*: *true* if the request is a PJAX request
- **secure** *(boolean)*: *true* if the request was sent over HTTPS
- **route** *(string)*: The name of the route, if applicable
- **user** *(integer)*: The ID of the logged in user, if applicable
- **referer** *(string)*: The HTTP_REFERER value from the request, if available

The middleware is also used to estimate the time that a user has spent on a particular page.  This is sent as a *trace* event named **browse_duration**.

### Exception Handler

To report exceptions that occur in your application, use the provided exception handler.  *Replace* the following line in your application's **app/Handlers/Exception.php** file:

```php
...

# Delete this line
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

# Insert this line
use Sahib\AppInsightsLaravel\Handlers\AppInsightsExceptionHandler as ExceptionHandler;

...
```

The exception handler will send additional properties to Application Insights, as above.

### Client Side

In order to register page view information from the client with Application Insights, simply insert the following code into your Blade views:

```php
{!! \AIClient::javascript() !!}
```

NOTE: Microsoft recommend that you put the script in the `<head>` section of your pages, in order to calculate the fullest extent of page load time on the client.

### Custom

If you want to use any of the underlying [ApplicationInsights-PHP](https://github.com/Microsoft/ApplicationInsights-PHP) functionality, you can call the methods directly from the server facade:

```php
...
\AIServer::trackEvent('Test event');
\AIServer::flush();//immediate send
\AIQueue::dispatch(\AIServer::getChannel()->getQueue())->delay(now()->addSeconds(3));//use laravel queue to send data later
...
```

See the [ApplicationInsights-PHP](https://github.com/Microsoft/ApplicationInsights-PHP) page for more information on the available methods.

## Version History

### dev-master
- Initial commit.
### 1.0.1
- Stable release