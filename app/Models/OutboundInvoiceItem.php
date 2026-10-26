<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboundInvoiceItem extends Model
{
    protected $guarded = [];

    public function invoice()
    {
        return $this->belongsTo(OutboundInvoice::class, 'outbound_invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'item_name', 'name');
    }

    public function getBrandAttribute(): string
    {
        return $this->product?->brand ?? '-';
    }
}
