<?php

namespace Ownego\Cashier;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
    ];

    public function billable()
    {
        return $this->morphTo();
    }
}
