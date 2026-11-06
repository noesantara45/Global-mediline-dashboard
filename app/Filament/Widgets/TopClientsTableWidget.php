<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopClientsTableWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $startDate = $this->filters['start_date'] ?? null;
        $endDate = $this->filters['end_date'] ?? null;
        $division = $this->filters['division'] ?? null;
        $clientType = $this->filters['client_type'] ?? null;
        $clientId = $this->filters['client_id'] ?? null;
        $status = $this->filters['status'] ?? null;

        $invoiceConstraint = function (Builder $q) use ($startDate, $endDate, $division, $status): void {
            $q->when($startDate, fn (Builder $sq, string $d): Builder => $sq->whereDate('issue_date', '>=', $d))
                ->when($endDate, fn (Builder $sq, string $d): Builder => $sq->whereDate('issue_date', '<=', $d))
                ->when($division, function (Builder $sq, string $div): Builder {
                    return $sq->where(function (Builder $divQuery) use ($div): void {
                        $divQuery->where('division', $div)
                            ->orWhereHas('items.product', fn (Builder $pq): Builder => $pq->where('division', $div));
                    });
                })
                ->when($status, fn (Builder $sq, string $s): Builder => $sq->where('status', $s));
        };

        return $table
            ->query(
                Client::query()
                    ->whereHas('outboundInvoices', $invoiceConstraint)
                    ->withSum([
                        'outboundInvoices as total_sales' => $invoiceConstraint,
                    ], 'total_amount')
                    ->withCount([
                        'outboundInvoices as invoice_count' => $invoiceConstraint,
                    ])
                    ->when($clientType, fn (Builder $q, $t): Builder => $q->where('type', $t))
                    ->when($clientId, fn (Builder $q, $id): Builder => $q->where('id', $id))
                    ->orderByDesc('total_sales')
            )
            ->heading('Rincian Kontribusi Klien & Rumah Sakit')
            ->description('Daftar seluruh klien yang bertransaksi dengan rincian omzet dan volume faktur.')
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->rowIndex()
                    ->label('No')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Customer / RS')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Rumah Sakit' => 'danger',
                        'Klinik' => 'warning',
                        'Apotek' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Kontak / PIC')
                    ->default('-'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->default('-'),

                Tables\Columns\TextColumn::make('invoice_count')
                    ->label('Volume Faktur')
                    ->alignCenter()
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state): string => $state.' Faktur'),

                Tables\Columns\TextColumn::make('total_sales')
                    ->label('Total Omzet (Belanja)')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->color('success'),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->tooltip('Buka Data Klien')
                    ->url(fn (Client $record): string => ClientResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Belum Ada Transaksi')
            ->emptyStateDescription('Tidak ditemukan transaksi penjualan pada kriteria dan filter yang dipilih.')
            ->emptyStateIcon('heroicon-o-chart-bar-square')
            ->paginated([5, 10, 25]);
    }
}
