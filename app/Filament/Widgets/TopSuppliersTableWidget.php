<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SupplierResource;
use App\Models\Supplier;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopSuppliersTableWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;
        $supplierId = $this->filters['supplier_id'] ?? null;
        $status = $this->filters['status'] ?? null;

        return $table
            ->query(
                Supplier::query()
                    ->withSum([
                        'inboundInvoices as total_expense' => function (Builder $q) use ($startDate, $endDate, $status): void {
                            $q->when($startDate, fn (Builder $sq, $d): Builder => $sq->whereDate('issue_date', '>=', $d))
                                ->when($endDate, fn (Builder $sq, $d): Builder => $sq->whereDate('issue_date', '<=', $d))
                                ->when($status, fn (Builder $sq, $s): Builder => $sq->where('status', $s));
                        },
                    ], 'total_amount')
                    ->withCount([
                        'inboundInvoices as invoice_count' => function (Builder $q) use ($startDate, $endDate, $status): void {
                            $q->when($startDate, fn (Builder $sq, $d): Builder => $sq->whereDate('issue_date', '>=', $d))
                                ->when($endDate, fn (Builder $sq, $d): Builder => $sq->whereDate('issue_date', '<=', $d))
                                ->when($status, fn (Builder $sq, $s): Builder => $sq->where('status', $s));
                        },
                    ])
                    ->when($supplierId, fn (Builder $q, $id): Builder => $q->where('id', $id))
                    ->having('total_expense', '>', 0)
                    ->orderByDesc('total_expense')
            )
            ->heading('Rincian Pengadaan per Supplier')
            ->description('Daftar seluruh supplier / principal dengan total belanja dan status termin pembayaran.')
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->rowIndex()
                    ->label('No')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Supplier / Vendor')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Kontak / PIC')
                    ->default('-'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->default('-'),

                Tables\Columns\TextColumn::make('top_days')
                    ->label('Term of Payment')
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state): string => ($state ?? 0).' Hari'),

                Tables\Columns\TextColumn::make('invoice_count')
                    ->label('Volume Faktur')
                    ->alignCenter()
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state): string => $state.' Faktur'),

                Tables\Columns\TextColumn::make('total_expense')
                    ->label('Total Pengeluaran (Belanja)')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->color('danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->tooltip('Buka Data Supplier')
                    ->url(fn (Supplier $record): string => SupplierResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Belum Ada Transaksi Pengadaan')
            ->emptyStateDescription('Tidak ditemukan transaksi pembelian pada kriteria dan filter yang dipilih.')
            ->emptyStateIcon('heroicon-o-truck')
            ->paginated([5, 10, 25]);
    }
}
