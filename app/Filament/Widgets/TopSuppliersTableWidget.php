<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\LaporanPembelian;
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

        $invoiceConstraint = function (Builder $q) use ($startDate, $endDate, $status): void {
            $q->when($startDate, fn (Builder $sq, string $d): Builder => $sq->whereDate('issue_date', '>=', $d))
                ->when($endDate, fn (Builder $sq, string $d): Builder => $sq->whereDate('issue_date', '<=', $d))
                ->when(
                    filled($status),
                    fn (Builder $sq): Builder => $sq->where('status', $status),
                    fn (Builder $sq): Builder => $sq->where('status', '!=', 'Cancelled')
                );
        };

        return $table
            ->query(
                Supplier::query()
                    ->whereHas('inboundInvoices', $invoiceConstraint)
                    ->withCount([
                        'inboundInvoices as invoice_count' => $invoiceConstraint,
                    ])
                    ->withSum([
                        'inboundInvoices as total_subtotal' => $invoiceConstraint,
                    ], 'subtotal_amount')
                    ->withSum([
                        'inboundInvoices as total_tax' => $invoiceConstraint,
                    ], 'tax_amount')
                    ->withSum([
                        'inboundInvoices as total_expense' => $invoiceConstraint,
                    ], 'total_amount')
                    ->withSum([
                        'inboundInvoices as total_paid' => $invoiceConstraint,
                    ], 'paid_amount')
                    ->when($supplierId, fn (Builder $q, $id): Builder => $q->where('id', $id))
                    ->orderByDesc('total_expense')
            )
            ->heading('Rekap Pembelian per Supplier (Inbound Invoices)')
            ->description('Rekapitulasi jumlah faktur masuk, nilai pembelian, pembayaran, dan sisa hutang per supplier.')
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->rowIndex()
                    ->label('No')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Supplier / Vendor')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('top_days')
                    ->label('TOP')
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

                Tables\Columns\TextColumn::make('total_subtotal')
                    ->label('Sub Total')
                    ->money('IDR', locale: 'id'),

                Tables\Columns\TextColumn::make('total_tax')
                    ->label('PPN')
                    ->money('IDR', locale: 'id'),

                Tables\Columns\TextColumn::make('total_expense')
                    ->label('Total Pembelian')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->color('danger'),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label('Sudah Dibayar')
                    ->money('IDR', locale: 'id')
                    ->color('success'),

                Tables\Columns\TextColumn::make('remaining_debt')
                    ->label('Sisa Hutang')
                    ->state(fn (Supplier $record): float => max(0, (float) ($record->total_expense ?? 0) - (float) ($record->total_paid ?? 0)))
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->color(fn (float $state): string => $state > 0 ? 'warning' : 'gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('laporan')
                    ->label('Faktur')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('primary')
                    ->tooltip('Lihat Faktur Supplier di Laporan Pembelian')
                    ->url(fn (Supplier $record): string => LaporanPembelian::getUrl([
                        'tableFilters' => [
                            'supplier_id' => ['value' => $record->id],
                        ],
                    ])),

                Tables\Actions\Action::make('edit')
                    ->label('')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->tooltip('Buka Data Supplier')
                    ->url(fn (Supplier $record): string => SupplierResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Belum Ada Data Inbound Invoices')
            ->emptyStateDescription('Tidak ditemukan faktur pembelian pada kriteria filter yang dipilih.')
            ->emptyStateIcon('heroicon-o-truck')
            ->paginated([5, 10, 25]);
    }
}
