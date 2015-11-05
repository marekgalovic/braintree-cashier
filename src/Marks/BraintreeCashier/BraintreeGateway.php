<?php

namespace Marks\BraintreeCashier;

use Braintree\ClientToken;
use Braintree\Configuration;
use Braintree\Customer;
use Braintree\PaymentMethod;
use Braintree\Plan;
use Braintree\Subscription;
use Braintree\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Marks\BraintreeCashier\Contracts\Billable as BillableContract;

class BraintreeGateway
{

    private $billable;

    private $plan;

    private $prorate = false;

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

    //one time payment

    public function charge($amount, $nonce, array $options)
    {
        $options = array_merge([
            'amount' => $this->normalizeAmount($amount),
            'paymentMethodNonce' => $nonce,
            'options' => [
                'submitForSettlement' => true
                ]
            ], $options);
        
        $result = Transaction::sale($options);

        if($result->success) {
            return $result->transaction;
        }
        return false;
    }

    //subscriptions

    public function create($nonce, array $options = [])
    {
        $options = array_merge([
            'paymentMethodToken' => $this->createPaymentMethodToken($nonce),
            'planId' => $this->plan
            ], $options, $this->options);

        $result = Subscription::create($options);

        if($result->success) {
            $subscription = $result->subscription;

            $this->updateBillable($this->buildSubscriptionPayload($subscription));
            return $subscription;
        }
        return false;
    }

    public function swap(array $options = [])
    {
        if($this->shouldSwap()) {
            $plan = $this->getPlanDetails();

            $options = array_merge([
                'planId' => $plan->id,
                'price' => $plan->price,
                'neverExpires' => (is_null($plan->numberOfBillingCycles) ? true : false)
                ], $options, $this->options);

            $result = Subscription::update($this->billable->getBraintreeSubscriptionId(), $options);

            if($result->success) {
                $subscription = $result->subscription;

                $this->updateBillable($this->buildSubscriptionPayload($subscription));
                return $subscription;
            }
        }
        return false;
    }

    public function cancel()
    {
        $result = Subscription::cancel($this->billable->getBraintreeSubscriptionId());

        if($result->success) {
            $subscription = $result->subscription;

            $this->updateBillable([
                'braintree_subscription_status' => $subscription->status,
                'subscription_ends_at' => $subscription->nextBillingDate
                ]);

            return $subscription;
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

    public function prorate() 
    {
        $this->prorate = true;

        return $this;
    }

    public function noProrate() 
    {
        $this->prorate = false;

        return $this;
    }

    //customers

    public function getCustomer()
    {
        if($this->billable->getBraintreeId() === null) {
            return $this->createCustomer();
        }
        return Customer::find($this->billable->getBraintreeId());
    }

    public function createCustomer()
    {
        $result = Customer::create([
            'firstName' => $this->billable->getBraintreeFirstName(),
            'lastName' => $this->billable->getBraintreeLastName(),
            'email' => $this->billable->getBraintreeEmail()
            ]);

        $customer = $result->customer;

        $this->updateBillable(['braintree_id' => $customer->id]);

        return $customer;
    }

    public function createPaymentMethodToken($nonce)
    {
        $customer = $this->getCustomer();
        $result = PaymentMethod::create([
            'customerId' => $customer->id,
            'paymentMethodNonce' => $nonce
            ]);

        if($result->success) {
            return $result->paymentMethod->token;
        }
        return null;
    }

    private function getTrialEndDate($subscription)
    {
        if($subscription->trialPeriod === false) {
            return null;
        }
        return $subscription->firstBillingDate;
    }

    private function getSubscriptionEndDate($subscription)
    {
        if($subscription->neverExpires === true) {
            return null;
        }

        $plan = $this->getPlanDetails();

        $monthsUntilSubscriptionEnds = $plan->billingFrequency * $subscription->numberOfBillingCycles;
        return Carbon::instance($subscription->firstBillingDate)->addMonths($monthsUntilSubscriptionEnds);
    }

    private function getPlanDetails()
    {
        return collect(Plan::all())->keyBy('id')->get($this->plan);
    }

    private function getOption($key = null)
    {
        if($key === null) {
            return $this->options;
        }

        return array_get($this->options, $key);
    }

    private function shouldSwap()
    {
        return $this->plan !== $this->billable->getBraintreePlan();
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

    private function buildSubscriptionPayload($subscription)
    {
        return  [
                'braintree_subscription_id' => $subscription->id,
                'braintree_plan' => $subscription->planId,
                'braintree_subscription_status' => $subscription->status,
                'trial_ends_at' => $this->getTrialEndDate($subscription),
                'subscription_ends_at' => $this->getSubscriptionEndDate($subscription)
                ]; 
    }

    private function updateBillable(array $attributes)
    {
        foreach($attributes as $key => $value) {
            $this->billable->setAttribute($key, $value);
        }
        return $this->billable->save();
    }
}
