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

    protected function getRedirectUrl(): string
    {
        if (filled($this->previousUrl) && ! str_contains($this->previousUrl, $this->getResource()::getUrl('edit', ['record' => $this->record]))) {
            return $this->previousUrl;
        }

        return $this->getResource()::getUrl('index');
    }
}
