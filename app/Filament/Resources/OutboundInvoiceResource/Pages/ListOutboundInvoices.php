<?php

namespace App\Filament\Resources\OutboundInvoiceResource\Pages;

use App\Filament\Resources\OutboundInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOutboundInvoices extends ListRecords
{
    protected static string $resource = OutboundInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
