<?php

namespace App\Models;

use App\Filament\Resources\InboundInvoiceResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InboundInvoice extends Model
{
    protected $guarded = [];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InboundInvoiceItem::class);
    }

    public function getGrossPurchaseAttribute(): float
    {
        if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
            $sum = (float) $this->items->sum(function (InboundInvoiceItem $item): float {
                return (float) $item->quantity * (float) $item->unit_price;
            });

            return $sum > 0 ? $sum : (float) $this->subtotal_amount;
        }

        return (float) $this->subtotal_amount;
    }

    public function getTotalDiscountAttribute(): float
    {
        if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
            return (float) $this->items->sum(function (InboundInvoiceItem $item): float {
                $gross = (float) $item->quantity * (float) $item->unit_price;
                $subtotal = (float) $item->subtotal;

                return max(0, $gross - $subtotal);
            });
        }

        return 0.0;
    }

    public function setPaidAmountAttribute($value): void
    {
        $this->attributes['paid_amount'] = InboundInvoiceResource::parseIndonesianAmount((string) $value);
    }
}
