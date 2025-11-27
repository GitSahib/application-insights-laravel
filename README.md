# Microsoft Application Insights for Laravel 10+
A simple Laravel integration for Microsoft Application Insights

This package was originally inspired by provisions-group/ms-application-insights-laravel. That package was built for Laravel 5 and is no longer maintained.

This package is a fully maintained, standalone implementation for Laravel 10+, updated to support the latest Application Insights requirements. It provides a clean way to push telemetry from your Laravel web app and APIs directly to Application Insights, with additional features such as queue support and separate handling for API and web guards.

## Installation

Update the `require` section of your application's **composer.json** file:

```js
"require": {
	...
	"larasahib/application-insights-laravel": "1.0.4",
	...
}
```

## Instrumentation Key / Connection String

The package will check your application's `.env` file for your **Instrumentation Key**.

⚠ **Note:**
`MS_INSTRUMENTATION_KEY` is **deprecated**.
Use `MS_AI_CONNECTION_STRING` instead.

### `.env` example

```env
# Old way (deprecated)
# MS_INSTRUMENTATION_KEY=00000000-0000-0000-0000-000000000000

# New way (recommended)
MS_AI_CONNECTION_STRING=InstrumentationKey=00000000-0000-0000-0000-000000000000;IngestionEndpoint=https://<region>.in.applicationinsights.azure.com/
```

### Where to find the connection string

You can get the connection string from your Azure Portal:

```
Application Insights → Overview → Connection String
```

You can find your instrumentation key on the [Microsoft Azure Portal](https://portal.azure.com).

Navigate to:

**Microsoft Azure** > **Browse** > **Application Insights** > *(Application Name)* > **Settings** > **Properties**

## Usage

### Request Tracking Middleware

To monitor your application's performance with request tracking, add the middleware to your in your application, found in **app/Http/Kernel.php**. It has to be added after the StartSession middleware has been added.

```php

protected $middleware = [
	'api':
		...
		'AppInsightsApiMiddleware',
		...
	'web':
		...
		'AppInsightsWebMiddleware',
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
use Larasahib\AppInsightsLaravel\Handlers\AppInsightsExceptionHandler as ExceptionHandler;

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
---
---
## Publishing the Package Configuration and Assets

After installing the package, you need to publish the configuration file and JavaScript assets so your application can use them:

1. **Publish the configuration:**

```bash
php artisan vendor:publish --tag=config
```

This will copy the `AppInsightsLaravel.php` configuration file to your app’s `config` folder.

### 2. **Publish the JavaScript assets**

Although Laravel 10+ users will have the assets automatically available after install or update via Composer, you can also manually publish them if needed:

```bash
php artisan vendor:publish --tag=laravel-assets
```

This ensures the JS file is copied to your `public/vendor/appinsights/js` folder and ready to be loaded by the client. 

  > **Tip:** Add `--force` to overwrite existing files if needed.
  
---

### 📦 Version History

#### ✅ **dev-master**

* Initial commit with basic Laravel integration.
* Included dependency on `microsoft/application-insights`.

---

#### 📦 **1.0.1**

* First stable release.
* Basic support for tracking requests and exceptions using Microsoft Application Insights PHP SDK.
* Registered Laravel service provider and middleware for web & API.

---

#### 📦 **1.0.2**

* Refactored and renamed internal classes to avoid naming conflicts and improve maintainability.
* Improved configuration publishing and service bindings.

---

#### 📦 **1.0.3**

* Added queue support via `AppInsightsTelemetryQueue`.
* Enabled asynchronous event logging using Laravel queues.

---

#### 📦 **1.0.4**

* Minor fixes to config merging and bootstrapping.
* Added ability to set instrumentation key via environment variable.

---

#### 📦 **1.0.5**

* Fixed service provider registration issues in Laravel 12.
* Added support for Laravel's `config:cache`.

---

#### 📦 **1.0.6**

* Deprecated usage of `microsoft/application-insights` SDK.
* Prepared for a complete rewrite using a custom telemetry client.

---

#### 🚀 **1.0.7**

* ✅ **Replaced dependency on the deprecated Application Insights SDK** with a custom-built HTTP client.
* ✅ **Implemented custom `Telemetry_Client` class** compatible with Application Insights ingestion API.
* ✅ Support for tracking:

  * Requests
  * Exceptions
  * Custom Events
  * Traces (Logs)
* ✅ Introduced `flush()` mechanism with NDJSON batching.
* ✅ Removed reliance on outdated or unsupported packages.
* ✅ Maintained backward compatibility with existing service provider and class interfaces.
* 🧪 Enhanced logging and error handling for production robustness.

---

#### 🚀 **1.1.8**

* ✅ **Introduced auto-publishable JavaScript assets** (`appinsights-client.js`) for client-side telemetry.

* ✅ **Improved client-side telemetry integration**: Use `AppInsightsClient::javascript()` to include the JS in your pages.

* ✅ **Backend telemetry endpoint** included to receive telemetry data sent from the JS.

### **1.1.9**

* ✅ **Adde appinsights-queue to add onto this queue when AI jobs are queued

* 📝 **New setup instructions**:

  1. **Publish the configuration file**:

  ```bash
  php artisan vendor:publish --tag=config
  ```
---

### 2. **Publish the JavaScript assets**

Although Laravel 10+ users will have the assets automatically available after install or update via Composer, you can also manually publish them if needed:

```bash
php artisan vendor:publish --tag=laravel-assets
```

### 3. **Run a separate worker for only processin AI jobs 
```bash
php artisan queue:work --queue=appinsights-queue
```

This ensures the JS file is copied to your `public/vendor/appinsights/js` folder and ready to be loaded by the client. 

  > **Tip:** Add `--force` to overwrite existing files if needed.

* ✅ **Maintained backward compatibility** with Laravel 10+ and previous package versions.

* 🧪 **Improved logging and error handling** for both server-side and client-side telemetry.

* 🛠 Minor bug fixes and internal refactors.

---


