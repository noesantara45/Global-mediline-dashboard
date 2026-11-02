<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $guarded = [];

    public function inboundInvoices()
    {
        return $this->hasMany(InboundInvoice::class);
    }
}
