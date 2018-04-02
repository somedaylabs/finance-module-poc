<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    public const STATES = ["new", "sent", "paid", "chargeback", "refunded"];

    protected $fillable = ["billing_date", "status", "total", "paid_date", "paid_amount"];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function line_items()
    {
        return $this->hasMany(BillingLineItem::class);
    }

    public function populateLineItems(array $details)
    {
        $lines = collect($details)->map([BillingLineItem::class, "make"])->sortBy("line_number");
        if ($lines->pluck("line_number")->unique()->count() < $lines->count()) {
            throw new \InvalidArgumentException("Line numbers must be unique");
        }
        return $this->line_items()->saveMany($lines);
    }

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope("order", function (Builder $builder) {
            return $builder->orderBy("billing_date")->orderBy("id");
        });
    }
}
