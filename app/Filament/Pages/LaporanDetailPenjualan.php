<?php

namespace App\Filament\Pages;

use App\Filament\Resources\OutboundInvoiceResource;
use App\Models\OutboundInvoice;
use App\Models\OutboundInvoiceItem;
use App\Models\Product;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

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
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('No Faktur')
                    ->searchable()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('outbound_invoices.invoice_number', $direction))
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
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('outbound_invoices.issue_date', $direction)),

                Tables\Columns\TextColumn::make('item_name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('division')
                    ->label('Divisi')
                    ->badge()
                    ->color(fn (?string $state): string => Product::divisionColor($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(function (Builder $sub) use ($search): void {
                        $sub->where('outbound_invoices.division', 'like', "%{$search}%")
                            ->orWhereHas('product', fn (Builder $q): Builder => $q->where('division', 'like', "%{$search}%"));
                    })),

                Tables\Columns\TextColumn::make('brand')
                    ->label('Merek')
                    ->badge()
                    ->color('gray')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('product', fn (Builder $q): Builder => $q->where('brand', 'like', "%{$search}%"))),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make('total_qty')
                            ->label('Total Qty')
                            ->query(fn ($query) => $query->whereIn(
                                'outbound_invoice_id',
                                OutboundInvoice::query()->where('status', '!=', 'Cancelled')->select('id')
                            ))
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
                    ->label('Diskon 1')
                    ->formatStateUsing(fn ($state) => (float) $state > 0 ? (float) $state.'%' : '-')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('discount_2_percentage')
                    ->label('Diskon 2')
                    ->formatStateUsing(fn ($state) => (float) $state > 0 ? (float) $state.'%' : '-')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Total (Net)')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make('grand_total')
                            ->label('Grand Total')
                            ->query(fn ($query) => $query->whereIn(
                                'outbound_invoice_id',
                                OutboundInvoice::query()->where('status', '!=', 'Cancelled')->select('id')
                            ))
                            ->money('IDR', locale: 'id')
                    ),
            ])
            ->filters([
                Tables\Filters\Filter::make('issue_date')
                    ->columnSpan(['default' => 1, 'sm' => 2, 'lg' => 2, 'xl' => 2])
                    ->columns(['default' => 1, 'sm' => 2, 'lg' => 2, 'xl' => 2])
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->placeholder('dd/mm/yyyy')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection(),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->placeholder('dd/mm/yyyy')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('outbound_invoices.issue_date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('outbound_invoices.issue_date', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (! empty($data['from'])) {
                            $indicators['from'] = 'Dari: '.Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if (! empty($data['until'])) {
                            $indicators['until'] = 'Sampai: '.Carbon::parse($data['until'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),

                Tables\Filters\Filter::make('invoice_number')
                    ->form([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Faktur')
                            ->placeholder('No Faktur...')
                            ->live(debounce: 500),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            filled($data['code'] ?? null),
                            fn ($q) => $q->where('outbound_invoices.invoice_number', 'like', '%'.trim($data['code']).'%')
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! filled($data['code'] ?? null)) {
                            return null;
                        }

                        return 'Faktur: '.$data['code'];
                    }),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Customer')
                    ->placeholder('Semua')
                    ->relationship('invoice.client', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('brand')
                    ->label('Merek')
                    ->placeholder('Semua')
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

                Tables\Filters\SelectFilter::make('sales')
                    ->label('Sales')
                    ->placeholder('Semua')
                    ->options(
                        fn () => OutboundInvoice::query()
                            ->whereNotNull('sales')
                            ->where('sales', '!=', '')
                            ->distinct()
                            ->pluck('sales', 'sales')
                            ->toArray()
                    )
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            $query->where('outbound_invoices.sales', $data['value']);
                        }
                    })
                    ->searchable(),

                Tables\Filters\SelectFilter::make('division')
                    ->label('Divisi')
                    ->placeholder('Semua')
                    ->options(function (): array {
                        $existing = OutboundInvoice::query()
                            ->whereNotNull('division')
                            ->where('division', '!=', '')
                            ->distinct()
                            ->pluck('division', 'division')
                            ->toArray();

                        return array_merge(Product::divisionOptions(), $existing);
                    })
                    ->query(function ($query, array $data): void {
                        if (! empty($data['value'])) {
                            $division = $data['value'];
                            $query->where(function ($subQuery) use ($division): void {
                                $subQuery->where('outbound_invoices.division', $division)
                                    ->orWhereHas('product', fn ($pq) => $pq->where('division', $division));
                            });
                        }
                    })
                    ->searchable(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Faktur')
                    ->placeholder('Semua')
                    ->options([
                        'Unpaid' => 'Unpaid',
                        'Partial' => 'Partial',
                        'Paid' => 'Paid',
                        'Cancelled' => 'Cancelled',
                    ])
                    ->query(function ($query, array $data): void {
                        if (! empty($data['value'])) {
                            $query->where('outbound_invoices.status', $data['value']);
                        }
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns([
                'default' => 1,
                'sm' => 3,
                'lg' => 4,
                'xl' => 8,
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->color('primary')
                    ->tooltip('Buka & Edit Faktur Penjualan')
                    ->url(fn (OutboundInvoiceItem $record): string => OutboundInvoiceResource::getUrl('edit', ['record' => $record->outbound_invoice_id])),
            ])
            ->defaultSort('invoice.issue_date', 'desc')
            ->defaultKeySort()
            ->striped();
    }
}
