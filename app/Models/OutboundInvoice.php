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
}
