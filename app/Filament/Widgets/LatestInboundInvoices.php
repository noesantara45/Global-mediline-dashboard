<?php

namespace App\Filament\Widgets;

use App\Models\InboundInvoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestInboundInvoices extends BaseWidget
{
    protected static ?string $heading = 'Faktur Pembelian Terbaru (Inbound)';

    protected static ?int $sort = 5;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InboundInvoice::query()->latest('created_at')->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No. Faktur')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->color(fn (InboundInvoice $record): string => ($record->status !== 'Paid' && $record->due_date && $record->due_date < now()->toDateString()) ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Tagihan')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Paid' => 'success',
                        'Partial' => 'warning',
                        'Unpaid' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->paginated(false);
    }
}
