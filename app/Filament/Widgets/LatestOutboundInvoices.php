<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OutboundInvoiceResource;
use App\Models\OutboundInvoice;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Str;

class LatestOutboundInvoices extends BaseWidget
{
    protected static string $view = 'filament.widgets.dashboard-invoice-table-widget';

    protected static ?string $heading = 'Faktur Penjualan Terbaru (Outbound)';

    protected static ?int $sort = 5;

    protected static bool $isLazy = false;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 1,
        'xl' => 3,
    ];

    /**
     * @return array{
     *     visibleCount: int,
     *     activeCount: int,
     *     activeAmount: string,
     *     unpaidLabel: string,
     *     unpaidAmount: string,
     *     hasUnpaid: bool,
     *     fillerTitle: string,
     *     fillerDescription: string,
     *     ctaLabel: string,
     *     ctaUrl: string,
     * }
     */
    public function getSummaryFooterData(): array
    {
        $stats = OutboundInvoice::query()
            ->selectRaw(
                "COUNT(*) as total_count,
                 COALESCE(SUM(CASE WHEN status != 'Cancelled' THEN 1 ELSE 0 END), 0) as active_count,
                 COALESCE(SUM(CASE WHEN status != 'Cancelled' THEN total_amount ELSE 0 END), 0) as active_total,
                 COALESCE(SUM(CASE WHEN status IN ('Unpaid', 'Partial') AND total_amount > paid_amount THEN total_amount - paid_amount ELSE 0 END), 0) as remaining_amount"
            )
            ->first();

        $visibleCount = min(5, (int) ($stats?->total_count ?? 0));
        $activeCount = (int) ($stats?->active_count ?? 0);
        $activeTotal = (float) ($stats?->active_total ?? 0);
        $remaining = (float) ($stats?->remaining_amount ?? 0);

        return [
            'visibleCount' => $visibleCount,
            'activeCount' => $activeCount,
            'activeAmount' => 'Rp '.number_format($activeTotal, 0, ',', '.'),
            'unpaidLabel' => 'Sisa Piutang',
            'unpaidAmount' => 'Rp '.number_format($remaining, 0, ',', '.'),
            'hasUnpaid' => $remaining > 0,
            'fillerTitle' => "Menampilkan {$visibleCount} faktur penjualan terakhir",
            'fillerDescription' => 'Terbitkan faktur baru untuk mencatat transaksi dan cetak Surat Jalan.',
            'ctaLabel' => 'Buat Faktur Jual',
            'ctaUrl' => OutboundInvoiceResource::getUrl('create'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OutboundInvoice::query()
                    ->with('client')
                    ->latest('created_at')
                    ->limit(5)
            )
            ->heading('Faktur Penjualan Terbaru (Outbound)')
            ->description('5 faktur penjualan terakhir yang diterbitkan.')
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
                    ->label('No. Faktur / Customer')
                    ->weight('bold')
                    ->description(fn (OutboundInvoice $record): string => Str::limit($record->client?->name ?? '-', 26))
                    ->tooltip(fn (OutboundInvoice $record): ?string => $record->client?->name),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Tempo')
                    ->date('d M Y')
                    ->color(fn (OutboundInvoice $record): string => (in_array($record->status, ['Unpaid', 'Partial'], true) && $record->due_date && Carbon::parse($record->due_date)->startOfDay()->lt(now()->startOfDay())) ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->alignRight()
                    ->weight('semibold'),

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
                    ->tooltip('Edit Faktur')
                    ->url(fn (OutboundInvoice $record): string => OutboundInvoiceResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Belum Ada Faktur Penjualan')
            ->emptyStateDescription('Faktur penjualan terbaru akan tampil otomatis di sini.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->striped()
            ->paginated(false);
    }
}
