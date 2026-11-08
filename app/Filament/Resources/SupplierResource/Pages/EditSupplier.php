<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use App\Models\Supplier;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action, Supplier $record): void {
                    if ($record->inboundInvoices()->exists()) {
                        Notification::make()
                            ->title('Tidak dapat menghapus supplier')
                            ->body('Supplier ini masih memiliki histori faktur pembelian. Nonaktifkan status aktif sebagai gantinya.')
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }
}
