<?php

namespace App\Filament\Pages;

use App\Models\OutboundInvoiceItem;
use App\Models\Product;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class LaporanDetailPenjualan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Detail Penjualan';

    protected static ?string $title = 'Laporan Detail Penjualan Barang';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static string $view = 'filament.pages.laporan-detail-penjualan';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OutboundInvoiceItem::query()
                    ->with(['invoice.client', 'product'])
                    ->join('outbound_invoices', 'outbound_invoice_items.outbound_invoice_id', '=', 'outbound_invoices.id')
                    ->select('outbound_invoice_items.*')
                    ->orderBy('outbound_invoices.issue_date', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('No Faktur')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('invoice.client.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('invoice.issue_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('item_name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('brand')
                    ->label('Merek')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Qty')
                    ),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Satuan')
                    ->alignCenter()
                    ->default('Pcs'),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Harga Jual')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label('Diskon')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state.'%' : '-')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Total (Net)')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Grand Total')
                            ->money('IDR', locale: 'id')
                    ),
            ])
            ->filters([
                Tables\Filters\Filter::make('issue_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereHas('invoice', fn ($iq) => $iq->whereDate('issue_date', '>=', $date)))
                            ->when($data['until'], fn ($q, $date) => $q->whereHas('invoice', fn ($iq) => $iq->whereDate('issue_date', '<=', $date)));
                    }),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Customer')
                    ->relationship('invoice.client', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('brand')
                    ->label('Merek')
                    ->options(
                        fn () => Product::whereNotNull('brand')
                            ->where('brand', '!=', '')
                            ->distinct()
                            ->pluck('brand', 'brand')
                            ->toArray()
                    )
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('product', fn ($pq) => $pq->where('brand', $data['value']));
                        }
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->striped();
    }
}
