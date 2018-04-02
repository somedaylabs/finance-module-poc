<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ["email", "name"];

    public function billings()
    {
        return $this->hasMany(Billing::class);
    }
}
