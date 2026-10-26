<?php

namespace App\Filament\Widgets;

use App\Models\OutboundInvoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOutboundInvoices extends BaseWidget
{
    protected static ?string $heading = 'Faktur Penjualan Terbaru';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OutboundInvoice::query()->latest('created_at')->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No Faktur')
                    ->searchable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Tagihan')
                    ->numeric()
                    ->prefix('Rp ')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (OutboundInvoice $record): string => url('/print/outbound-invoice/'.$record->id))
                    ->openUrlInNewTab(),
            ]);
    }
}
