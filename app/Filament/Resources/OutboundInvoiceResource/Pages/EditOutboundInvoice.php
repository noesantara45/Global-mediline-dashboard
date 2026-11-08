<?php

namespace App\Filament\Resources\OutboundInvoiceResource\Pages;

use App\Filament\Resources\OutboundInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOutboundInvoice extends EditRecord
{
    protected static string $resource = OutboundInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $subtotal = (float) ($data['subtotal_amount'] ?? 0);
        $tax = (float) ($data['tax_amount'] ?? 0);

        if ($subtotal > 0 && $tax > 0) {
            $data['tax_percentage'] = round(($tax / $subtotal) * 100);
        } elseif ($subtotal > 0 && $tax == 0.0) {
            $data['tax_percentage'] = 0;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        $editUrl = $this->getResource()::getUrl('edit', ['record' => $this->record]);

        if (
            self::isSafeRedirectUrl($this->previousUrl) &&
            ! str_contains((string) $this->previousUrl, $editUrl)
        ) {
            return (string) $this->previousUrl;
        }

        return $this->getResource()::getUrl('index');
    }

    public static function isSafeRedirectUrl(?string $url): bool
    {
        if (blank($url)) {
            return false;
        }

        $trimmed = trim($url);

        if (str_starts_with($trimmed, '/') && ! str_starts_with($trimmed, '//') && ! str_starts_with($trimmed, '/\\')) {
            return true;
        }

        $appUrl = parse_url(url('/'));
        $targetUrl = parse_url($trimmed);

        if (! is_array($appUrl) || ! is_array($targetUrl) || empty($targetUrl['scheme']) || empty($targetUrl['host'])) {
            return false;
        }

        return strtolower($targetUrl['scheme']) === strtolower((string) ($appUrl['scheme'] ?? ''))
            && strtolower($targetUrl['host']) === strtolower((string) ($appUrl['host'] ?? ''))
            && ($targetUrl['port'] ?? null) === ($appUrl['port'] ?? null);
    }
}
