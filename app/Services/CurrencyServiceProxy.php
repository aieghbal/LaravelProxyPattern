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
