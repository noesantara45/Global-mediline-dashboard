<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'tax_id',
        'top_days',
        'ocr_invoice_keyword',
        'ocr_date_keyword',
        'ocr_subtotal_keyword',
        'ocr_total_keyword',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Supplier $supplier): ?bool {
            if ($supplier->inboundInvoices()->exists()) {
                return false;
            }

            return null;
        });
    }

    public function inboundInvoices(): HasMany
    {
        return $this->hasMany(InboundInvoice::class);
    }
}
