<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class CurrencyService
{
    public function getRates(): array
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->get('https://api.priceto.day/v1/latest/irr/usd');

        if ($response->successful()) {
            return $response->json();
        }

        return ['success' => false, 'message' => 'API request failed'];
    }
}
