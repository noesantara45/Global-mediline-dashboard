<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboundInvoice extends Model
{
    protected $guarded = [];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(OutboundInvoiceItem::class);
    }

    public function getGrossSaleAttribute(): float
    {
        if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
            $sum = (float) $this->items->sum(function ($item) {
                return (float) $item->quantity * (float) $item->unit_price;
            });

            return $sum > 0 ? $sum : (float) $this->subtotal_amount;
        }

        return (float) $this->subtotal_amount;
    }

    public function getTotalDiscountAttribute(): float
    {
        if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
            return (float) $this->items->sum(function ($item) {
                $gross = (float) $item->quantity * (float) $item->unit_price;
                $sub = (float) $item->subtotal;

                return max(0, $gross - $sub);
            });
        }

        return 0;
    }

    public function getDivisiAttribute(): ?string
    {
        return $this->division;
    }
}
