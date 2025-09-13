# Proxy Design Pattern in Laravel (Real-World Example)

The Proxy pattern helps us add extra logic (such as caching, logging, or access control) before or after accessing a core service.
In this example, we implement a Currency Rate Service.
Without a Proxy, every request goes directly to the external API (costly and time-consuming).
With a Proxy, we check the cache first, and if no data is found, we connect to the external API.

## Real Subject (Main Service)
`app/Services/CurrencyService.php`
```php
<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class CurrencyService
{
    public function getRates(): array
    {
        $response = Http::withOptions([
            'verify' => false, // موقت: جلوگیری از خطای SSL در لوکال
        ])->get('https://api.priceto.day/v1/latest/irr/usd');

        if ($response->successful()) {
            return $response->json();
        }

        return ['success' => false, 'message' => 'API request failed'];
    }
}
```


## Proxy
`app/Services/CurrencyServiceProxy.php`
```php
<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CurrencyServiceProxy
{
    protected CurrencyService $service;

    public function __construct(CurrencyService $service)
    {
        $this->service = $service;
    }

    public function getRates(): array
    {
        return Cache::remember('usd_to_irr', now()->addMinutes(5), function () {
            return $this->service->getRates();
        });
    }
}
```


## Controller
`app/Http/Controllers/CurrencyController.php`
```php
<?php
namespace App\Http\Controllers;

use App\Services\CurrencyServiceProxy;

class CurrencyController extends Controller
{
    protected CurrencyServiceProxy $proxy;

    public function __construct(CurrencyServiceProxy $proxy)
    {
        $this->proxy = $proxy;
    }

    public function index()
    {
        $rates = $this->proxy->getRates();
        return response()->json($rates);
    }
}
```


## Route
`routes/web.php`
```php
use App\Http\Controllers\CurrencyController;

Route::get('/rates', [CurrencyController::class, 'index']);
```


## Testing
* Start the Laravel server:
```php
php artisan serve
```

* Visit in browser → http://127.0.0.1:8000/rates
* Example output:
```php
{
  "base": "USD",
  "date": "2025-09-09",
  "rates": {
    "IRR": 575000
  }
}
```


### Notes

The cURL error 60 occurs due to SSL certificate issues. For quick testing, you can set verify => false.
For production, it’s better to configure PHP with a proper cacert.pem.

The Proxy can also be used for tasks like authentication, rate limiting, or logging.

In this example, we’re using the free API priceto.day.priceto.day

## Conclusion

CurrencyService → The main service for fetching currency rates.

CurrencyServiceProxy → The intermediary for caching data.

Controller → Uses the Proxy instead of accessing the service directly.

With this pattern, we can manage additional logic without modifying the original service code.

---
[نسخه فارسی](./README.fa.md)
