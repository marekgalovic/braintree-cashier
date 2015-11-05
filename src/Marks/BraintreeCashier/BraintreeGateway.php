<?php

namespace Marks\BraintreeCashier;

use Braintree\ClientToken;
use Braintree\Configuration;
use Braintree\Customer;
use Braintree\PaymentMethod;
use Braintree\Subscription;
use Braintree\Transaction;
use Illuminate\Support\Facades\Config;
use Marks\BraintreeCashier\Contracts\Billable as BillableContract;

class BraintreeGateway
{

    private $billable;

    private $plan;

    private $options = [];

    public function __construct(BillableContract $billable = null, $plan = null)
    {
        $this->billable = $billable;
        $this->plan = $plan;
    }

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
            'paymentMethodNonce' => $nonce,
            'options' => [
                'submitForSettlement' => true
                ]
            ], $options);
        
        return Transaction::sale($options);
    }

    public function create($nonce, array $options = [])
    {
        $options = array_merge([
            'paymentMethodToken' => $this->createPaymentMethodToken($nonce),
            'planId' => $this->plan
            ], $options, $this->options);

        $result = Subscription::create($options);

        if($result->success) {
            return $result->subscription;
        }
        return false;
    }

    public function withCoupon($coupon)
    {
        $this->options['discounts']['add'][] = [
            'amount' => $coupon,
            'inheritedFromId' => $this->plan
        ];

        return $this;
    }

    public function ensureCustomer()
    {
        try {
            $customer = $this->getCustomer();
        } catch( \Exception $e) {
            $customer = $this->createCustomer();
        }

        return $customer;
    }

    public function getCustomer()
    {
        return Customer::find($this->billable->getBraintreeId());
    }

    public function createCustomer()
    {
        $result = Customer::create([
            'id' => $this->billable->getBraintreeId(),
            'firstName' => $this->billable->getBraintreeFirstName(),
            'lastName' => $this->billable->getBraintreeLastName(),
            'email' => $this->billable->getBraintreeEmail()
            ]);

        return $result->customer;
    }

    public function createPaymentMethodToken($nonce)
    {
        $customer = $this->ensureCustomer();
        $result = PaymentMethod::create([
            'customerId' => $customer->id,
            'paymentMethodNonce' => $nonce
            ]);

        if($result->success) {
            return $result->paymentMethod->token;
        }

        return null;
    }

    private function getOption($key = null)
    {
        if($key === null) {
            return $this->options;
        }

        return array_get($this->options, $key);
    }

    private function setOption($name, $value)
    {
        array_set($this->options, $name, $value);

        return $this;
    }

    private function normalizeAmount($amount)
    {
        return number_format(($amount / 100), 2, '.', '');
    }
}
