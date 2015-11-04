<?php

namespace Marks\BraintreeCashier;

use Braintree\Result\Error;

trait Billable
{
    public function charge($amount, $nonce, array $options = [])
    {
        $result = (new BraintreeGateway)->charge($amount, $nonce, $options);
        if($result instanceof Error) {
            return false;
        }
        return $result;
    }
}