<?php

namespace App\Filament\Pages;

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
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanPenjualan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Penjualan';

    protected static ?string $title = 'Laporan Penjualan Barang';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.laporan-penjualan';

    public function table(Table $table): Table
    {
        return $table
            ->view('filament.tables.laporan-penjualan-table')
            ->query(
                OutboundInvoice::query()
                    ->with(['client', 'items.product'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('index')
                    ->rowIndex()
                    ->label('No')
                    ->alignCenter(),

                Tables\Columns\ViewColumn::make('invoice_number')
                    ->label('No Faktur')
                    ->searchable()
                    ->sortable()
                    ->view('filament.tables.columns.invoice-toggle-button'),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->limit(24)
                    ->tooltip(fn ($record) => $record->client?->name),

                Tables\Columns\TextColumn::make('sales')
                    ->label('Sales')
                    ->searchable()
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('division')
                    ->label('Divisi')
                    ->badge()
                    ->color(fn (?string $state): string => Product::divisionColor($state))
                    ->searchable()
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('gross_sale')
                    ->label('Gross Sale')
                    ->money('IDR', locale: 'id')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $sqlDirection = $direction === 'desc' ? 'DESC' : 'ASC';

                        return $query->orderByRaw(
                            "COALESCE(NULLIF((SELECT SUM(quantity * unit_price) FROM outbound_invoice_items WHERE outbound_invoice_items.outbound_invoice_id = outbound_invoices.id), 0), outbound_invoices.subtotal_amount) {$sqlDirection}"
                        );
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make('total_gross')
                            ->label('Total Gross')
                            ->using(function ($query): float {
                                $ids = (clone $query)
                                    ->where('outbound_invoices.status', '!=', 'Cancelled')
                                    ->pluck('outbound_invoices.id');
                                if ($ids->isEmpty()) {
                                    return 0.0;
                                }

                                return (float) OutboundInvoiceItem::whereIn('outbound_invoice_id', $ids)
                                    ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as total')
                                    ->value('total');
                            })
                            ->money('IDR', locale: 'id')
                    ),

                Tables\Columns\TextColumn::make('total_discount')
                    ->label('Diskon')
                    ->money('IDR', locale: 'id')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $sqlDirection = $direction === 'desc' ? 'DESC' : 'ASC';

                        return $query->orderByRaw(
                            "COALESCE((SELECT SUM(CASE WHEN (quantity * unit_price) > subtotal THEN (quantity * unit_price) - subtotal ELSE 0 END) FROM outbound_invoice_items WHERE outbound_invoice_items.outbound_invoice_id = outbound_invoices.id), 0) {$sqlDirection}"
                        );
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make('total_discount')
                            ->label('Total Diskon')
                            ->using(function ($query): float {
                                $ids = (clone $query)
                                    ->where('outbound_invoices.status', '!=', 'Cancelled')
                                    ->pluck('outbound_invoices.id');
                                if ($ids->isEmpty()) {
                                    return 0.0;
                                }

                                return (float) OutboundInvoiceItem::whereIn('outbound_invoice_id', $ids)
                                    ->selectRaw('COALESCE(SUM(CASE WHEN (quantity * unit_price) > subtotal THEN (quantity * unit_price) - subtotal ELSE 0 END), 0) as total')
                                    ->value('total');
                            })
                            ->money('IDR', locale: 'id')
                    ),

                Tables\Columns\TextColumn::make('tax_amount')
                    ->label('PPN')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make('total_ppn')
                            ->label('Total PPN')
                            ->query(fn ($query) => $query->where('outbound_invoices.status', '!=', 'Cancelled'))
                            ->money('IDR', locale: 'id')
                    ),

                Tables\Columns\TextColumn::make('subtotal_amount')
                    ->label('Sub Total')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make('total_subtotal')
                            ->label('Total Subtotal')
                            ->query(fn ($query) => $query->where('outbound_invoices.status', '!=', 'Cancelled'))
                            ->money('IDR', locale: 'id')
                    ),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Net Sale')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make('total_net_sale')
                            ->label('Total Net Sale')
                            ->query(fn ($query) => $query->where('outbound_invoices.status', '!=', 'Cancelled'))
                            ->money('IDR', locale: 'id')
                    ),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->colors([
                        'danger' => 'Unpaid',
                        'warning' => 'Partial',
                        'success' => 'Paid',
                        'secondary' => 'Cancelled',
                    ]),
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
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('issue_date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('issue_date', '<=', $date));
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
                            fn ($q) => $q->where('invoice_number', 'like', '%'.trim($data['code']).'%')
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
                    ->relationship('client', 'name')
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
                            $query->whereHas('items.product', fn ($pq) => $pq->where('brand', $data['value']));
                        }
                    }),

                Tables\Filters\SelectFilter::make('sales')
                    ->label('Sales')
                    ->placeholder('Semua')
                    ->options(fn () => OutboundInvoice::query()
                        ->whereNotNull('sales')
                        ->where('sales', '!=', '')
                        ->distinct()
                        ->pluck('sales', 'sales')
                        ->toArray()
                    )
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
                                    ->orWhereHas('items.product', fn ($pq) => $pq->where('division', $division));
                            });
                        }
                    })
                    ->searchable(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Bayar')
                    ->placeholder('Semua')
                    ->options([
                        'Paid' => 'Paid (Lunas)',
                        'Unpaid' => 'Unpaid (Belum Lunas)',
                        'Partial' => 'Partial (Cicilan)',
                        'Cancelled' => 'Cancelled (Batal)',
                    ]),

                Tables\Filters\Filter::make('due_condition')
                    ->form([
                        Forms\Components\Select::make('condition')
                            ->label('Jatuh Tempo')
                            ->placeholder('Semua')
                            ->options([
                                'overdue' => 'Lewat Jatuh Tempo (Overdue)',
                                'due_this_month' => 'Jatuh Tempo Bulan Ini',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(($data['condition'] ?? null) === 'overdue', function (Builder $q): void {
                                $q->whereIn('outbound_invoices.status', ['Unpaid', 'Partial'])
                                    ->whereDate('due_date', '<', now()->toDateString());
                            })
                            ->when(($data['condition'] ?? null) === 'due_this_month', function (Builder $q): void {
                                $q->where('outbound_invoices.status', '!=', 'Cancelled')
                                    ->whereMonth('due_date', now()->month)
                                    ->whereYear('due_date', now()->year);
                            });
                    })
                    ->indicateUsing(function (array $data): ?string {
                        return match ($data['condition'] ?? null) {
                            'overdue' => 'Status Jatuh Tempo: Lewat Jatuh Tempo (Overdue)',
                            'due_this_month' => 'Status Jatuh Tempo: Jatuh Tempo Bulan Ini',
                            default => null,
                        };
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns([
                'default' => 1,
                'sm' => 3,
                'lg' => 5,
                'xl' => 9,
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('Export Rekap Jual')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (HasTable $livewire): StreamedResponse {
                        return response()->streamDownload(function () use ($livewire): void {
                            $handle = fopen('php://output', 'w');
                            // UTF-8 BOM for Excel support
                            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

                            fputcsv($handle, [
                                'No Faktur',
                                'Customer',
                                'Sales',
                                'Divisi',
                                'Tanggal',
                                'Gross Sale',
                                'Diskon',
                                'PPN',
                                'Sub Total',
                                'Net Sale',
                                'Jatuh Tempo',
                                'Status',
                            ]);

                            $query = $livewire->getFilteredSortedTableQuery()
                                ->with(['client', 'items']);

                            foreach ($query->lazy(200) as $record) {
                                fputcsv($handle, [
                                    self::sanitizeCsvCell($record->invoice_number),
                                    self::sanitizeCsvCell($record->client?->name ?? '-'),
                                    self::sanitizeCsvCell($record->sales ?? '-'),
                                    self::sanitizeCsvCell($record->division ?? '-'),
                                    self::sanitizeCsvCell($record->issue_date),
                                    $record->gross_sale,
                                    $record->total_discount,
                                    $record->tax_amount,
                                    $record->subtotal_amount,
                                    $record->total_amount,
                                    self::sanitizeCsvCell($record->due_date),
                                    self::sanitizeCsvCell($record->status),
                                ]);
                            }

                            fclose($handle);
                        }, 'Rekap-Penjualan-'.now()->format('Ymd_His').'.csv', [
                            'Content-Type' => 'text/csv; charset=UTF-8',
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('cetak_pdf')
                    ->label('Invoice')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->tooltip('Cetak Invoice PDF')
                    ->url(fn (OutboundInvoice $record) => route('outbound-invoice.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('surat_jalan')
                    ->label('Surat Jalan')
                    ->icon('heroicon-o-truck')
                    ->color('info')
                    ->tooltip('Cetak Surat Jalan PDF')
                    ->url(fn (OutboundInvoice $record) => route('surat-jalan.pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('issue_date', 'desc')
            ->defaultKeySort()
            ->striped();
    }

    public static function sanitizeCsvCell(mixed $value): string|int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $stringValue = (string) $value;
        if ($stringValue === '' || $stringValue === '-') {
            return $stringValue;
        }

        if (in_array($stringValue[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$stringValue;
        }

        return $stringValue;
    }
}
