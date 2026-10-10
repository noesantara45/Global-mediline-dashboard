<?php

namespace App\Filament\Resources\InboundInvoiceResource\Pages;

use App\Filament\Resources\InboundInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInboundInvoices extends ListRecords
{
    protected static string $resource = InboundInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
