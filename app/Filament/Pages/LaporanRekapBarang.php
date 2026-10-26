<?php

namespace App\Filament\Pages;

use App\Models\Product;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class LaporanRekapBarang extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Rekap Per Barang';

    protected static ?string $title = 'Rekapitulasi Penjualan Per Produk';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static string $view = 'filament.pages.laporan-rekap-barang';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->withSum('outboundItems as total_qty_sold', 'quantity')
                    ->withSum('outboundItems as total_omzet', 'subtotal')
                    ->withCount('outboundItems as total_transactions')
            )
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->disk('public')
                    ->square(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk / Barang')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('brand')
                    ->label('Merek')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Satuan')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_transactions')
                    ->label('Jml Transaksi')
                    ->alignCenter()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => ($state ?: 0).' kali'),

                Tables\Columns\TextColumn::make('total_qty_sold')
                    ->label('Total Terjual (Qty)')
                    ->alignCenter()
                    ->sortable()
                    ->weight('bold')
                    ->formatStateUsing(fn ($state) => number_format((float) ($state ?: 0), 0, ',', '.'))
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Seluruh Unit Terjual')
                    ),

                Tables\Columns\TextColumn::make('price')
                    ->label('Harga Master')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_omzet')
                    ->label('Total Omzet Penjualan')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((float) ($state ?: 0), 0, ',', '.'))
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Omzet Produk')
                            ->money('IDR', locale: 'id')
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('brand')
                    ->label('Merek')
                    ->options(
                        fn () => Product::whereNotNull('brand')
                            ->where('brand', '!=', '')
                            ->distinct()
                            ->pluck('brand', 'brand')
                            ->toArray()
                    ),
            ])
            ->defaultSort('total_omzet', 'desc')
            ->striped();
    }
}
