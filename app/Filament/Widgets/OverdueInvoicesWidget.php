<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OutboundInvoiceResource;
use App\Models\OutboundInvoice;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OverdueInvoicesWidget extends BaseWidget
{
    protected static ?string $heading = 'Piutang Jatuh Tempo & Perlu Ditagih';

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OutboundInvoice::query()
                    ->whereIn('status', ['Unpaid', 'Partial'])
                    ->orderBy('due_date', 'asc')
                    ->limit(5)
            )
            ->heading('Piutang Jatuh Tempo & Perlu Ditagih')
            ->description('Prioritas penagihan klien berdasarkan tanggal jatuh tempo.')
            ->headerActions([
                Tables\Actions\Action::make('view_all')
                    ->label('Lihat Semua')
                    ->icon('heroicon-m-arrow-right')
                    ->iconPosition('after')
                    ->size('xs')
                    ->color('gray')
                    ->url(fn (): string => OutboundInvoiceResource::getUrl('index')),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No. Faktur')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Customer')
                    ->searchable()
                    ->limit(18)
                    ->tooltip(fn (OutboundInvoice $record): ?string => $record->client?->name),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->description(function (OutboundInvoice $record): ?string {
                        if (! $record->due_date) {
                            return null;
                        }

                        $dueDate = Carbon::parse($record->due_date)->startOfDay();
                        $today = now()->startOfDay();

                        if ($dueDate->isPast()) {
                            $days = (int) $dueDate->diffInDays($today);

                            return $days === 0 ? 'Hari ini' : "Lewat {$days} hr";
                        }

                        $days = (int) $today->diffInDays($dueDate);

                        return "{$days} hr lagi";
                    })
                    ->color(function (OutboundInvoice $record): string {
                        if (! $record->due_date) {
                            return 'gray';
                        }

                        $dueDate = Carbon::parse($record->due_date)->startOfDay();

                        return $dueDate->isPast() ? 'danger' : 'warning';
                    }),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa Piutang')
                    ->state(fn (OutboundInvoice $record): float => max(0, (float) $record->total_amount - (float) $record->paid_amount))
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
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
                    ->tooltip('Cetak PDF')
                    ->url(fn (OutboundInvoice $record): string => url('/print/outbound-invoice/'.$record->id))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('edit')
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->tooltip('Buka / Edit Faktur')
                    ->url(fn (OutboundInvoice $record): string => OutboundInvoiceResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Semua Piutang Aman')
            ->emptyStateDescription('Tidak ada tagihan klien yang menunggak atau belum dibayar.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->paginated(false);
    }
}
