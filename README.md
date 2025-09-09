<div dir="rtl">

# الگوی طراحی Proxy در لاراول (مثال واقعی)

الگوی **Proxy** به ما کمک می‌کند قبل یا بعد از دسترسی به یک سرویس اصلی، منطق اضافه‌ای (مثل کش کردن، لاگ گرفتن یا محدودیت دسترسی) اضافه کنیم. در این مثال ما یک **سرویس نرخ ارز** را پیاده‌سازی می‌کنیم. بدون Proxy، هر بار مستقیم به API درخواست می‌دهیم (هزینه و زمان زیاد). با Proxy، ابتدا **کش** بررسی می‌شود و در صورت نبود داده، به API خارجی متصل می‌شویم.

</div>

## Service اصلی (Real Subject)
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


## تست
* اجرای سرور لاراول:
```php
php artisan serve
```

* مرورگر → http://127.0.0.1:8000/rates
* خروجی نمونه:
```php
{
  "base": "USD",
  "date": "2025-09-09",
  "rates": {
    "IRR": 575000
  }
}
```


### نکات
* خطای cURL error 60 به دلیل مشکل گواهی SSL است. برای تست سریع، می‌توانید verify => false قرار دهید. برای محیط واقعی بهتر است فایل cacert.pem را به PHP معرفی کنید.
* می‌توانید Proxy را برای کارهای دیگر مثل احراز هویت، Rate Limiting یا لاگ‌گیری نیز استفاده کنید.
* در این مثال، از API رایگان priceto.day

## نتیجه‌گیری
* CurrencyService → سرویس اصلی برای گرفتن نرخ ارز.
* CurrencyServiceProxy → واسطه برای کش کردن داده‌ها.
* Controller → استفاده از Proxy به جای سرویس مستقیم.
  با این الگو، می‌توانیم منطق اضافه را بدون دست زدن به کد اصلی سرویس مدیریت کنیم.
