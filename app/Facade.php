<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class PaymentTransaction extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'payment-transaction';
    }
}