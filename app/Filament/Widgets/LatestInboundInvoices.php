<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\InboundInvoiceResource;
use App\Models\InboundInvoice;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Str;

class LatestInboundInvoices extends BaseWidget
{
    protected static string $view = 'filament.widgets.dashboard-invoice-table-widget';

    protected static ?string $heading = 'Faktur Pembelian Terbaru (Inbound)';

    protected static ?int $sort = 6;

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
        $stats = InboundInvoice::query()
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
            'unpaidLabel' => 'Sisa Hutang',
            'unpaidAmount' => 'Rp '.number_format($remaining, 0, ',', '.'),
            'hasUnpaid' => $remaining > 0,
            'fillerTitle' => "Baru {$visibleCount} faktur pembelian tercatat",
            'fillerDescription' => 'Upload PDF faktur supplier untuk ekstrak otomatis via OCR Scanner.',
            'ctaLabel' => 'Input Pembelian (OCR)',
            'ctaUrl' => InboundInvoiceResource::getUrl('create'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InboundInvoice::query()
                    ->with('supplier')
                    ->latest('created_at')
                    ->limit(5)
            )
            ->heading('Faktur Pembelian Terbaru (Inbound)')
            ->description('5 faktur pembelian terakhir dari supplier.')
            ->headerActions([
                Tables\Actions\Action::make('view_all')
                    ->label('Lihat Semua')
                    ->icon('heroicon-m-arrow-right')
                    ->iconPosition('after')
                    ->size('xs')
                    ->color('gray')
                    ->url(fn (): string => InboundInvoiceResource::getUrl('index')),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('No. Faktur / Supplier')
                    ->weight('bold')
                    ->description(fn (InboundInvoice $record): string => Str::limit($record->supplier?->name ?? '-', 26))
                    ->tooltip(fn (InboundInvoice $record): ?string => $record->supplier?->name),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Tempo')
                    ->date('d M Y')
                    ->color(fn (InboundInvoice $record): string => (in_array($record->status, ['Unpaid', 'Partial'], true) && $record->due_date && Carbon::parse($record->due_date)->startOfDay()->lt(now()->startOfDay())) ? 'danger' : 'gray'),

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
                    ->tooltip('Cetak Rekap Pembelian PDF')
                    ->url(fn (InboundInvoice $record): string => route('inbound-invoice.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('edit')
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->tooltip('Edit Faktur Pembelian')
                    ->url(fn (InboundInvoice $record): string => InboundInvoiceResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Belum Ada Faktur Pembelian')
            ->emptyStateDescription('Faktur pembelian dari supplier akan tampil otomatis di sini.')
            ->emptyStateIcon('heroicon-o-document-arrow-down')
            ->striped()
            ->paginated(false);
    }
}
