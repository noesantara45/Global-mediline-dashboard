<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name',
        'type',
        'contact_person',
        'phone',
        'email',
        'address',
        'tax_id',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Client $client): ?bool {
            if ($client->outboundInvoices()->exists()) {
                return false;
            }

            return null;
        });
    }

    public function outboundInvoices(): HasMany
    {
        return $this->hasMany(OutboundInvoice::class);
    }
}
