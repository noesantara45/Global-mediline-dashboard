<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand',
        'photo',
        'description',
        'unit',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function outboundItems()
    {
        return $this->hasMany(OutboundInvoiceItem::class, 'item_name', 'name');
    }

    public function inboundItems()
    {
        return $this->hasMany(InboundInvoiceItem::class, 'item_name', 'name');
    }
}
