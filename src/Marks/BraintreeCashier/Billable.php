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

    public function subscription($plan)
    {
        return new BraintreeGateway($this, $plan);
    }

    public function getBraintreeId()
    {
        return md5($this->id).'csd';
    }

    public function getBraintreeFirstName()
    {
        return $this->name;
    }

    public function getBraintreeLastName()
    {
        return $this->last_name;
    }

    public function getBraintreeEmail()
    {
        return $this->email;
    }
}