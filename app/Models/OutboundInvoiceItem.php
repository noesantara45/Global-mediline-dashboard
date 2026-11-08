<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutboundInvoiceItem extends Model
{
    protected $fillable = [
        'outbound_invoice_id',
        'item_name',
        'quantity',
        'unit',
        'unit_price',
        'discount_percentage',
        'discount_2_percentage',
        'subtotal',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(OutboundInvoice::class, 'outbound_invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_name', 'name');
    }

    public function getBrandAttribute(): string
    {
        return $this->product?->brand ?? '-';
    }

    public function getDivisionAttribute(): string
    {
        return $this->product?->division ?? $this->invoice?->division ?? '-';
    }
}
