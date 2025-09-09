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
