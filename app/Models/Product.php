<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    public const DIVISION_DIAGNOSTIC = 'Diagnostic';

    public const DIVISION_MICROBIOLOGY = 'Microbiology';

    public const DIVISION_PATOLOGY_ANATOMI = 'Patology Anatomi';

    public const DIVISION_BLOOD_TRANSFUSION = 'Blood Transfusion';

    /**
     * @var array<string, string>
     */
    public const DIVISIONS = [
        self::DIVISION_DIAGNOSTIC => self::DIVISION_DIAGNOSTIC,
        self::DIVISION_MICROBIOLOGY => self::DIVISION_MICROBIOLOGY,
        self::DIVISION_PATOLOGY_ANATOMI => self::DIVISION_PATOLOGY_ANATOMI,
        self::DIVISION_BLOOD_TRANSFUSION => self::DIVISION_BLOOD_TRANSFUSION,
    ];

    protected $fillable = [
        'name',
        'division',
        'brand',
        'photo',
        'description',
        'unit',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * @return array<string, string>
     */
    public static function divisionOptions(): array
    {
        return self::DIVISIONS;
    }

    public static function divisionColor(?string $division): string
    {
        return match ($division) {
            self::DIVISION_DIAGNOSTIC => 'info',
            self::DIVISION_MICROBIOLOGY => 'success',
            self::DIVISION_PATOLOGY_ANATOMI => 'warning',
            self::DIVISION_BLOOD_TRANSFUSION => 'danger',
            default => 'gray',
        };
    }

    public function outboundItems(): HasMany
    {
        return $this->hasMany(OutboundInvoiceItem::class, 'item_name', 'name');
    }

    public function inboundItems(): HasMany
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
