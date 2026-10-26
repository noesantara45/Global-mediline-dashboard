<?php

namespace App\Filament\Pages;

use App\Models\OutboundInvoice;
use App\Models\OutboundInvoiceItem;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

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
                    ->latest('issue_date')
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
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('subtotal_amount', $direction);
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total Gross')
                            ->using(function ($query) {
                                $ids = (clone $query)->pluck('outbound_invoices.id');
                                if ($ids->isEmpty()) {
                                    return 0;
                                }

                                return (float) OutboundInvoiceItem::whereIn('outbound_invoice_id', $ids)
                                    ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as total')
                                    ->value('total');
                            })
                            ->money('IDR', locale: 'id')
                    ),

                Tables\Columns\TextColumn::make('total_discount')
                    ->label('Disk. 1')
                    ->money('IDR', locale: 'id')
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total Diskon')
                            ->using(function ($query) {
                                $ids = (clone $query)->pluck('outbound_invoices.id');
                                if ($ids->isEmpty()) {
                                    return 0;
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
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total PPN')
                            ->money('IDR', locale: 'id')
                    ),

                Tables\Columns\TextColumn::make('subtotal_amount')
                    ->label('Sub Total')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Subtotal')
                            ->money('IDR', locale: 'id')
                    ),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Net Sale')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Net Sale')
                            ->money('IDR', locale: 'id')
                    ),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'danger' => 'Unpaid',
                        'warning' => 'Partial',
                        'success' => 'Paid',
                        'secondary' => 'Cancelled',
                    ]),
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
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('issue_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('issue_date', '<=', $date));
                    }),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Customer')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('sales')
                    ->label('Sales')
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
                    ->options(fn () => OutboundInvoice::query()
                        ->whereNotNull('division')
                        ->where('division', '!=', '')
                        ->distinct()
                        ->pluck('division', 'division')
                        ->toArray()
                    )
                    ->searchable(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Pembayaran')
                    ->options([
                        'Paid' => 'Paid (Lunas)',
                        'Unpaid' => 'Unpaid (Belum Lunas)',
                        'Partial' => 'Partial (Cicilan)',
                        'Cancelled' => 'Cancelled (Batal)',
                    ]),

                Tables\Filters\Filter::make('due_condition')
                    ->form([
                        Forms\Components\Select::make('condition')
                            ->label('Status Jatuh Tempo')
                            ->options([
                                'overdue' => 'Lewat Jatuh Tempo (Overdue)',
                                'due_this_month' => 'Jatuh Tempo Bulan Ini',
                            ]),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['condition'] === 'overdue', function ($q) {
                                $q->where('status', '!=', 'Paid')
                                    ->whereDate('due_date', '<', now());
                            })
                            ->when($data['condition'] === 'due_this_month', function ($q) {
                                $q->whereMonth('due_date', now()->month)
                                    ->whereYear('due_date', now()->year);
                            });
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(6)
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('Export Rekap Jual')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($livewire) {
                        return response()->streamDownload(function () use ($livewire) {
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
                                'Disk. 1',
                                'PPN',
                                'Sub Total',
                                'Net Sale',
                                'Jatuh Tempo',
                                'Status',
                            ]);

                            $query = $livewire->getFilteredTableQuery()->with(['client', 'items']);
                            foreach ($query->lazy() as $record) {
                                fputcsv($handle, [
                                    $record->invoice_number,
                                    $record->client?->name ?? '-',
                                    $record->sales ?? '-',
                                    $record->division ?? '-',
                                    $record->issue_date,
                                    $record->gross_sale,
                                    $record->total_discount,
                                    $record->tax_amount,
                                    $record->subtotal_amount,
                                    $record->total_amount,
                                    $record->due_date,
                                    $record->status,
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
            ->striped();
    }
}
