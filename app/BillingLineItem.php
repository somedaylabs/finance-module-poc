<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BillingLineItem extends Model
{
    protected $fillable = ["line_number", "description", "amount"];

    public function billing()
    {
        return $this->belongsTo(Billing::class);
    }
}
