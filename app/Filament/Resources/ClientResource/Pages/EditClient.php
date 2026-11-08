<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action, Client $record): void {
                    if ($record->outboundInvoices()->exists()) {
                        Notification::make()
                            ->title('Tidak dapat menghapus customer')
                            ->body('Customer ini masih memiliki histori faktur penjualan. Nonaktifkan status aktif sebagai gantinya.')
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }
}
