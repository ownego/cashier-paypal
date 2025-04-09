<?php

namespace Ownego\Cashier\Concerns;

use Ownego\Cashier\Cashier;
use Ownego\Cashier\Customer;

trait ManageCustomer
{
    public function createAsCustomer(array $options = [])
    {
        if ($customer = $this->customer) {
            return $customer;
        }

        if (! array_key_exists('name', $options) && $name = $this->paypalName()) {
            $options['name'] = $name;
        }

        if (! array_key_exists('email', $options) && $email = $this->paypalEmail()) {
            $options['email'] = $email;
        }

        if (! isset($options['email'])) {
            throw new \LogicException('Unable to create customer without an email.');
        }

        return $this->customer()->create($options);
    }

    public function customer()
    {
        return $this->morphOne(Cashier::$customerModel, 'billable');
    }

    public function paypalName()
    {
        return $this->name;
    }

    public function paypalEmail()
    {
        return $this->email;
    }
}
