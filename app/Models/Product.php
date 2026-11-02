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

    /**
     * Resolves the complete asset URL for the product photo.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (blank($this->photo)) {
            return null;
        }

        $filename = basename($this->photo);

        if (file_exists(public_path('images/products/'.$filename))) {
            return asset('images/products/'.$filename);
        }

        if (file_exists(public_path('images/'.$this->photo))) {
            return asset('images/'.$this->photo);
        }

        if (file_exists(storage_path('app/public/products/'.$filename))) {
            return asset('storage/products/'.$filename);
        }

        if (file_exists(storage_path('app/public/'.$this->photo))) {
            return asset('storage/'.$this->photo);
        }

        return null;
    }
}
