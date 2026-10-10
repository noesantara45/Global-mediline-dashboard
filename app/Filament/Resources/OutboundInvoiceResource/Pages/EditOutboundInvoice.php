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
}
