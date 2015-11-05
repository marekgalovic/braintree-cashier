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

    public function subscription($plan = null)
    {
        return new BraintreeGateway($this, $plan);
    }

    public function getBraintreeId()
    {
        return $this->braintree_id;
    }

    public function getBraintreePlan()
    {
        return $this->braintree_plan;
    }

    public function getBraintreeSubscriptionId()
    {
        return $this->braintree_subscription_id;
    }

    public function getBraintereSubscriptionStatus()
    {
        return $this->braintree_subscription_status;
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

    public function subscribed()
    {
        return $this->subscription_ends_at->isFuture();  
    }

    public function onTrial()
    {
        return $this->trial_ends_at->isFuture();
    }

    public function onGracePeriod()
    {
        return ($this->cancelled()&&$this->subscribed());
    }

    public function onPlan($plan)
    {
        return ($this->getBrainterePlan() === $plan);
    }

    public function cancelled()
    {
        return ($this->getBraintereSubscriptionStatus() === 'Canceled');
    }
}