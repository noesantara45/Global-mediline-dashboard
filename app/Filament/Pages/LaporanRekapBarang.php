<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;

class LaporanRekapBarang extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Rekap Per Barang';

    protected static ?string $title = 'Rekapitulasi Penjualan Per Produk';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static string $view = 'filament.pages.laporan-rekap-barang';

    #[Url]
    public string $activeDivision = 'all';

    public function setDivision(string $division): void
    {
        $this->activeDivision = $division;
        $this->resetTable();
    }

    public function resetAllFilters(): void
    {
        $this->activeDivision = 'all';
        $this->resetTableFiltersForm();
    }

    /**
     * @return array<string, array{label: string, count: int, color: string}>
     */
    public function getDivisionTabs(): array
    {
        $counts = Product::query()
            ->selectRaw('division, COUNT(*) as aggregate')
            ->groupBy('division')
            ->pluck('aggregate', 'division');

        $tabs = [
            'all' => [
                'label' => 'Semua Divisi',
                'count' => (int) $counts->sum(),
                'color' => 'gray',
            ],
        ];

        foreach (Product::divisionOptions() as $divisionKey => $divisionLabel) {
            $tabs[$divisionKey] = [
                'label' => $divisionLabel,
                'count' => (int) ($counts[$divisionKey] ?? 0),
                'color' => Product::divisionColor($divisionKey),
            ];
        }

        return $tabs;
    }

    public function table(Table $table): Table
    {
        $activeItemConstraint = fn (Builder $q): Builder => $q->whereHas(
            'invoice',
            fn (Builder $iq): Builder => $iq->where('status', '!=', 'Cancelled')
        );

        return $table
            ->query(
                Product::query()
                    ->when(
                        $this->activeDivision !== 'all',
                        fn (Builder $query): Builder => $query->where('division', $this->activeDivision)
                    )
                    ->withSum(['outboundItems as total_qty_sold' => $activeItemConstraint], 'quantity')
                    ->withSum(['outboundItems as total_omzet' => $activeItemConstraint], 'subtotal')
                    ->withCount(['outboundItems as total_transactions' => $activeItemConstraint])
            )
            ->columns([
                Tables\Columns\ImageColumn::make('photo_url')
                    ->label('Foto')
                    ->state(fn (Product $record): ?string => $record->photo_url)
                    ->defaultImageUrl(asset('images/placeholder-product.svg'))
                    ->circular()
                    ->size(34)
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk / Barang')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Product $record): ?string => $record->description ? Str::limit($record->description, 45) : null),

                Tables\Columns\TextColumn::make('division')
                    ->label('Divisi')
                    ->badge()
                    ->color(fn (?string $state): string => Product::divisionColor($state))
                    ->placeholder('Belum diatur')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('brand')
                    ->label('Merek')
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-m-tag')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Satuan')
                    ->badge()
                    ->color('gray')
                    ->alignCenter()
                    ->default('Pcs'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Harga Master')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('total_transactions')
                    ->label('Frekuensi')
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state): string => ($state ?: 0).'x'),

                Tables\Columns\TextColumn::make('total_qty_sold')
                    ->label('Total Terjual')
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => (float) $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn (Product $record, $state): string => number_format((float) ($state ?: 0), 0, ',', '.').' '.($record->unit ?: 'Pcs'))
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Seluruh Unit')
                    ),

                Tables\Columns\TextColumn::make('total_omzet')
                    ->label('Total Omzet Penjualan')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->weight('bold')
                    ->alignRight()
                    ->color(fn ($state): string => (float) $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) ($state ?: 0), 0, ',', '.'))
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Grand Total Omzet')
                            ->money('IDR', locale: 'id')
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('')
                    ->icon('heroicon-o-queue-list')
                    ->color('gray')
                    ->tooltip('Lihat Riwayat Transaksi Barang Ini')
                    ->url(fn (Product $record): string => url('/admin/laporan-detail-penjualan?tableSearch='.urlencode($record->name)))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('edit')
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->tooltip('Buka Master Produk')
                    ->url(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record])),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('division')
                    ->label('Divisi')
                    ->placeholder('Semua')
                    ->options(Product::divisionOptions()),

                Tables\Filters\SelectFilter::make('brand')
                    ->label('Merek')
                    ->placeholder('Semua')
                    ->options(
                        fn (): array => Product::whereNotNull('brand')
                            ->where('brand', '!=', '')
                            ->distinct()
                            ->pluck('brand', 'brand')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('has_sales')
                    ->label('Status Penjualan')
                    ->placeholder('Semua Produk')
                    ->trueLabel('Pernah Terjual (Omzet > 0)')
                    ->falseLabel('Belum Pernah Terjual')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('outboundItems', $activeItemConstraint),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('outboundItems', $activeItemConstraint),
                    ),

                Tables\Filters\SelectFilter::make('unit')
                    ->label('Satuan')
                    ->placeholder('Semua')
                    ->options(
                        fn (): array => Product::whereNotNull('unit')
                            ->where('unit', '!=', '')
                            ->distinct()
                            ->pluck('unit', 'unit')
                            ->toArray()
                    ),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns([
                'default' => 1,
                'sm' => 2,
                'md' => 4,
                'xl' => 4,
            ])
            ->emptyStateHeading('Tidak Ada Data Produk')
            ->emptyStateDescription('Belum ada produk yang sesuai dengan kriteria divisi, pencarian, atau filter yang dipilih.')
            ->emptyStateIcon('heroicon-o-cube')
            ->defaultSort('total_omzet', 'desc')
            ->striped();
    }
}
