<?php

namespace App\Filament\Resources\InboundInvoiceResource\Pages;

use App\Filament\Resources\InboundInvoiceResource;
use App\Filament\Resources\OutboundInvoiceResource\Pages\EditOutboundInvoice;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInboundInvoice extends EditRecord
{
    protected static string $resource = InboundInvoiceResource::class;

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
            EditOutboundInvoice::isSafeRedirectUrl($this->previousUrl) &&
            ! str_contains((string) $this->previousUrl, $editUrl)
        ) {
            return (string) $this->previousUrl;
        }

        return $this->getResource()::getUrl('index');
    }
}
