<?php

namespace Marks\BraintreeCashier;

use Illuminate\Support\Facades\Config;
use Braintree\ClientToken;
use Braintree\Configuration;
use Braintree\Transaction;

class BraintreeGateway
{
    public static function loadConfiguration()
    {
        Configuration::environment(Config::get('services.braintree.environment'));
        Configuration::merchantId(Config::get('services.braintree.merchant_id'));
        Configuration::publicKey(Config::get('services.braintree.key'));
        Configuration::privateKey(Config::get('services.braintree.secret'));
    }

    public static function generateClientToken()
    {
        return ClientToken::generate();
    }

    public function charge($amount, $nonce, array $options)
    {
        $options = array_merge([
            'amount' => $this->normalizeAmount($amount),
            'paymentMethodNonce' => $nonce
            ], $options);
        
        return Transaction::sale($options);
    }

    private function normalizeAmount($amount)
    {
        return number_format(($amount / 100), 2, '.', '');
    }
}
