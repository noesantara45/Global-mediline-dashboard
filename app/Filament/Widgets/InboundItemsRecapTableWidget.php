<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\LaporanPembelian;
use App\Models\InboundInvoiceItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InboundItemsRecapTableWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->id;
    }

    public function table(Table $table): Table
    {
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;
        $supplierId = $this->filters['supplier_id'] ?? null;
        $status = $this->filters['status'] ?? null;

        $query = InboundInvoiceItem::query()
            ->join('inbound_invoices', 'inbound_invoice_items.inbound_invoice_id', '=', 'inbound_invoices.id')
            ->when($startDate, fn (Builder $q, string $d): Builder => $q->whereDate('inbound_invoices.issue_date', '>=', $d))
            ->when($endDate, fn (Builder $q, string $d): Builder => $q->whereDate('inbound_invoices.issue_date', '<=', $d))
            ->when($supplierId, fn (Builder $q, $id): Builder => $q->where('inbound_invoices.supplier_id', $id))
            ->when(
                filled($status),
                fn (Builder $q): Builder => $q->where('inbound_invoices.status', $status),
                fn (Builder $q): Builder => $q->where('inbound_invoices.status', '!=', 'Cancelled')
            )
            ->selectRaw('
                MIN(inbound_invoice_items.id) as id,
                inbound_invoice_items.item_name,
                inbound_invoice_items.unit,
                COUNT(DISTINCT inbound_invoice_items.inbound_invoice_id) as invoice_count,
                SUM(inbound_invoice_items.quantity) as total_qty,
                AVG(inbound_invoice_items.unit_price) as avg_unit_price,
                SUM(inbound_invoice_items.subtotal) as total_subtotal
            ')
            ->groupBy('inbound_invoice_items.item_name', 'inbound_invoice_items.unit');

        return $table
            ->query($query)
            ->defaultKeySort(false)
            ->defaultSort('total_subtotal', 'desc')
            ->heading('Rekap Barang yang Dibeli (Inbound Items)')
            ->description('Ringkasan seluruh barang yang dibeli dari Inbound Invoices (terpisah dari Master Produk Penjualan).')
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->rowIndex()
                    ->label('No')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('item_name')
                    ->label('Nama Barang Pembelian')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('inbound_invoice_items.item_name', 'like', "%{$search}%");
                    })
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Satuan')
                    ->alignCenter()
                    ->badge()
                    ->color('gray')
                    ->default('Pcs'),

                Tables\Columns\TextColumn::make('invoice_count')
                    ->label('Frekuensi Beli')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state): string => $state.' Faktur')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_qty')
                    ->label('Total Qty Dibeli')
                    ->alignCenter()
                    ->numeric(locale: 'id')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('avg_unit_price')
                    ->label('Harga Beli Rata-rata')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_subtotal')
                    ->label('Total Nilai Beli')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->color('danger')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat_faktur')
                    ->label('Lihat Faktur')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('primary')
                    ->url(fn (InboundInvoiceItem $record): string => LaporanPembelian::getUrl([
                        'tableFilters' => [
                            'item_name' => ['name' => $record->item_name],
                        ],
                    ])),
            ])
            ->emptyStateHeading('Belum Ada Rincian Barang Pembelian')
            ->emptyStateDescription('Rincian barang dari Inbound Invoices akan otomatis direkap di sini.')
            ->emptyStateIcon('heroicon-o-archive-box-arrow-down')
            ->paginated([5, 10, 25]);
    }
}
