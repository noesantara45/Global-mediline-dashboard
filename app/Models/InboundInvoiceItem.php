<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundInvoiceItem extends Model
{
    protected $fillable = [
        'inbound_invoice_id',
        'item_name',
        'quantity',
        'unit',
        'unit_price',
        'subtotal',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InboundInvoice::class, 'inbound_invoice_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_name', 'name');
    }

    public function getBrandAttribute(): string
    {
        return $this->product?->brand ?? '-';
    }
}
