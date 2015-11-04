<?php

namespace Marks\BraintreeCashier\Contracts;

interface Billable
{
    public function charge($amount, $nonce, array $options = []);
}