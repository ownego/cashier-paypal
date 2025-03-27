<?php

namespace Ownego\Cashier;

use Carbon\Carbon;
use Ownego\Cashier\Enums\PaypalSubscriptionStatus;

class PaypalSubscription implements \ArrayAccess
{
    public function __construct(protected array $data) {}

    public function trialEndsAt(): ?string
    {
        $trialCycleExecution = $this->getCycleExecution('TRIAL');

        if (!$trialCycleExecution) {
            return null;
        }

        $regularCycleExecution = $this->getCycleExecution('REGULAR');

        if ($regularCycleExecution['cycles_completed'] > 0) {
            return null;
        }

        return new Carbon($this->nextBillingTime());
    }

    protected function hasTrialCycle(): bool
    {
        return $this->getCycleExecution('TRIAL') !== null;
    }

    protected function getCycleExecution($type): ?array
    {
        $cycleExecutions = $this->data['billing_info']['cycle_executions'];

        foreach ($cycleExecutions as $cycleExecution) {
            if ($cycleExecution['tenure_type'] === strtoupper($type)) {
                return $cycleExecution;
            }
        }

        return null;
    }

    public function active(): bool
    {
        return $this->data['status'] === PaypalSubscriptionStatus::ACTIVE->value;
    }

    public function nextBillingTime(): ?string
    {
        return $this->data['billing_info']['next_billing_time'];
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->data[$offset];
    }

    /**
     * Set the value at the given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     *
     * @throws \LogicException
     */
    public function offsetSet($offset, $value): void
    {
        throw new \LogicException('Data may not be mutated using array access.');
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  string  $offset
     * @return void
     *
     * @throws \LogicException
     */
    public function offsetUnset($offset): void
    {
        throw new \LogicException('Data may not be mutated using array access.');
    }
}
