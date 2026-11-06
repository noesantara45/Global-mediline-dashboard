<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\LaporanPenjualan;
use App\Filament\Resources\OutboundInvoiceResource;
use App\Models\OutboundInvoice;
use App\Models\Product;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OverdueInvoicesWidget extends BaseWidget
{
    protected static ?string $heading = 'Piutang Jatuh Tempo & Prioritas Penagihan';

    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OutboundInvoice::query()
                    ->with('client')
                    ->whereIn('status', ['Unpaid', 'Partial'])
                    ->orderBy('due_date', 'asc')
                    ->limit(5)
            )
            ->heading('Piutang Jatuh Tempo & Prioritas Penagihan')
            ->description('5 faktur belum lunas dengan tanggal jatuh tempo terdekat atau sudah lewat tempo.')
            ->headerActions([
                Tables\Actions\Action::make('view_all')
                    ->label('Lihat Semua Piutang')
                    ->icon('heroicon-m-arrow-right')
                    ->iconPosition('after')
                    ->size('xs')
                    ->color('primary')
                    ->url(fn (): string => LaporanPenjualan::getUrl([
                        'tableFilters' => [
                            'due_condition' => ['condition' => 'overdue'],
                        ],
                    ])),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No. Faktur')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Customer')
                    ->limit(28)
                    ->tooltip(fn (OutboundInvoice $record): ?string => $record->client?->name),

                Tables\Columns\TextColumn::make('division')
                    ->label('Divisi')
                    ->badge()
                    ->color(fn (?string $state): string => Product::divisionColor($state))
                    ->default('-'),

                Tables\Columns\TextColumn::make('sales')
                    ->label('Sales')
                    ->default('-'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->description(function (OutboundInvoice $record): ?string {
                        if (! $record->due_date) {
                            return null;
                        }

                        $dueDate = Carbon::parse($record->due_date)->startOfDay();
                        $today = now()->startOfDay();

                        if ($dueDate->lt($today)) {
                            $days = (int) $dueDate->diffInDays($today);

                            return "Lewat {$days} hari";
                        }

                        if ($dueDate->equalTo($today)) {
                            return 'Jatuh tempo hari ini';
                        }

                        $days = (int) $today->diffInDays($dueDate);

                        return "{$days} hari lagi";
                    })
                    ->color(function (OutboundInvoice $record): string {
                        if (! $record->due_date) {
                            return 'gray';
                        }

                        $dueDate = Carbon::parse($record->due_date)->startOfDay();

                        return $dueDate->lt(now()->startOfDay()) ? 'danger' : 'warning';
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Faktur')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->alignRight(),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa Piutang')
                    ->state(fn (OutboundInvoice $record): float => max(0, (float) $record->total_amount - (float) $record->paid_amount))
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->weight('bold')
                    ->alignRight()
                    ->color('danger'),

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
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->tooltip('Cetak Invoice PDF')
                    ->url(fn (OutboundInvoice $record): string => route('outbound-invoice.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('edit')
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->tooltip('Buka / Update Pembayaran')
                    ->url(fn (OutboundInvoice $record): string => OutboundInvoiceResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Semua Piutang Aman')
            ->emptyStateDescription('Tidak ada tagihan klien yang menunggak atau belum dibayar.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->striped()
            ->paginated(false);
    }
}
