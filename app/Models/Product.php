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

    protected static function booted(): void
    {
        static::updated(function (Product $product): void {
            if (! $product->wasChanged('name')) {
                return;
            }

            $oldName = (string) $product->getOriginal('name');
            $newName = (string) $product->name;

            if ($oldName !== '' && $newName !== '' && $oldName !== $newName) {
                OutboundInvoiceItem::query()->where('item_name', $oldName)->update(['item_name' => $newName]);
                InboundInvoiceItem::query()->where('item_name', $oldName)->update(['item_name' => $newName]);
            }
        });

        static::deleting(function (Product $product): ?bool {
            if ($product->outboundItems()->exists() || $product->inboundItems()->exists()) {
                return false;
            }

            return null;
        });
    }

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

    private ?string $resolvedPhotoKey = null;

    private ?string $resolvedPhotoUrl = null;

    /**
     * Resolves the complete asset URL for the product photo.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (blank($this->photo)) {
            return null;
        }

        $photo = (string) $this->photo;
        if ($this->resolvedPhotoKey === $photo) {
            return $this->resolvedPhotoUrl;
        }

        $filename = basename($photo);
        $url = null;

        if (file_exists(public_path('images/products/'.$filename))) {
            $url = asset('images/products/'.$filename);
        } elseif (file_exists(public_path('images/'.$photo))) {
            $url = asset('images/'.$photo);
        } elseif (file_exists(storage_path('app/public/products/'.$filename))) {
            $url = asset('storage/products/'.$filename);
        } elseif (file_exists(storage_path('app/public/'.$photo))) {
            $url = asset('storage/'.$photo);
        }

        $this->resolvedPhotoKey = $photo;
        $this->resolvedPhotoUrl = $url;

        return $url;
    }
}
