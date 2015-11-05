<?php

namespace Marks\BraintreeCashier\Contracts;

interface Billable
{
    public function charge($amount, $nonce, array $options = []);
    public function subscription($plan = null);
    public function getBraintreeId();
    public function getBraintreeFirstName();
    public function getBraintreeLastName();
    public function getBraintreeEmail();
}