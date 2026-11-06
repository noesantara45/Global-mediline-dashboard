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
        if (filled($this->previousUrl) && ! str_contains($this->previousUrl, $this->getResource()::getUrl('edit', ['record' => $this->record]))) {
            return $this->previousUrl;
        }

        return $this->getResource()::getUrl('index');
    }
}
