<?php

namespace App\Filament\Pages;

use App\Filament\Resources\InboundInvoiceResource;
use App\Models\InboundInvoice;
use App\Models\InboundInvoiceItem;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanPembelian extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Pembelian';

    protected static ?string $title = 'Laporan Pembelian Barang';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static string $view = 'filament.pages.laporan-pembelian';

    public function table(Table $table): Table
    {
        return $table
            ->view('filament.tables.laporan-pembelian-table')
            ->query(
                InboundInvoice::query()
                    ->with(['supplier', 'items'])
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

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->limit(24)
                    ->tooltip(fn (InboundInvoice $record): ?string => $record->supplier?->name),

                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('gross_purchase')
                    ->label('Gross Beli')
                    ->money('IDR', locale: 'id')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('subtotal_amount', $direction);
                    })
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total Gross')
                            ->using(function ($query): float {
                                $ids = (clone $query)->pluck('inbound_invoices.id');
                                if ($ids->isEmpty()) {
                                    return 0.0;
                                }

                                return (float) InboundInvoiceItem::whereIn('inbound_invoice_id', $ids)
                                    ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as total')
                                    ->value('total');
                            })
                            ->money('IDR', locale: 'id')
                    ),

                Tables\Columns\TextColumn::make('total_discount')
                    ->label('Diskon')
                    ->money('IDR', locale: 'id')
                    ->summarize(
                        Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total Diskon')
                            ->using(function ($query): float {
                                $ids = (clone $query)->pluck('inbound_invoices.id');
                                if ($ids->isEmpty()) {
                                    return 0.0;
                                }

                                return (float) InboundInvoiceItem::whereIn('inbound_invoice_id', $ids)
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
                    ->label('Total Pembelian')
                    ->money('IDR', locale: 'id')
                    ->weight('bold')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Pembelian')
                            ->money('IDR', locale: 'id')
                    ),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Sudah Dibayar')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Dibayar')
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
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('issue_date', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('issue_date', '<=', $date));
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
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['code'] ?? null),
                            fn (Builder $q): Builder => $q->where('invoice_number', 'like', '%'.trim((string) $data['code']).'%')
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! filled($data['code'] ?? null)) {
                            return null;
                        }

                        return 'Faktur: '.$data['code'];
                    }),

                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->placeholder('Semua')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('item_name')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Barang')
                            ->placeholder('Cari barang...')
                            ->live(debounce: 500),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['name'] ?? null),
                            fn (Builder $q): Builder => $q->whereHas(
                                'items',
                                fn (Builder $itemQuery): Builder => $itemQuery->where('item_name', 'like', '%'.trim((string) $data['name']).'%')
                            )
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! filled($data['name'] ?? null)) {
                            return null;
                        }

                        return 'Barang: '.$data['name'];
                    }),

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
                                $q->whereIn('inbound_invoices.status', ['Unpaid', 'Partial'])
                                    ->whereDate('due_date', '<', now()->toDateString());
                            })
                            ->when(($data['condition'] ?? null) === 'due_this_month', function (Builder $q): void {
                                $q->where('inbound_invoices.status', '!=', 'Cancelled')
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
                'lg' => 4,
                'xl' => 7,
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('Export Rekap Beli')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (self $livewire): StreamedResponse {
                        return response()->streamDownload(function () use ($livewire): void {
                            $handle = fopen('php://output', 'w');
                            if ($handle === false) {
                                return;
                            }

                            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

                            fputcsv($handle, [
                                'No Faktur',
                                'Supplier',
                                'Tanggal',
                                'Gross Beli',
                                'Diskon',
                                'PPN',
                                'Sub Total',
                                'Total Pembelian',
                                'Sudah Dibayar',
                                'Jatuh Tempo',
                                'Status',
                                'Rincian Barang',
                            ]);

                            $query = $livewire->getFilteredTableQuery()->with(['supplier', 'items']);
                            foreach ($query->lazy() as $record) {
                                $itemsSummary = $record->items
                                    ->map(fn (InboundInvoiceItem $item): string => "{$item->item_name} ({$item->quantity} {$item->unit})")
                                    ->implode('; ');

                                fputcsv($handle, [
                                    $record->invoice_number,
                                    $record->supplier?->name ?? '-',
                                    $record->issue_date,
                                    $record->gross_purchase,
                                    $record->total_discount,
                                    $record->tax_amount,
                                    $record->subtotal_amount,
                                    $record->total_amount,
                                    $record->paid_amount,
                                    $record->due_date,
                                    $record->status,
                                    $itemsSummary ?: '-',
                                ]);
                            }

                            fclose($handle);
                        }, 'Rekap-Pembelian-'.now()->format('Ymd_His').'.csv', [
                            'Content-Type' => 'text/csv; charset=UTF-8',
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('cetak_pdf')
                    ->label('Invoice')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->tooltip('Cetak Rekap Pembelian PDF')
                    ->url(fn (InboundInvoice $record): string => route('inbound-invoice.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('file_asli')
                    ->label('Faktur Asli')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('info')
                    ->tooltip('Lihat File Faktur Supplier')
                    ->visible(fn (InboundInvoice $record): bool => filled($record->pdf_file))
                    ->url(fn (InboundInvoice $record): string => Storage::disk('public')->url((string) $record->pdf_file))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->tooltip('Edit Inbound Invoice')
                    ->url(fn (InboundInvoice $record): string => InboundInvoiceResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('issue_date', 'desc')
            ->striped();
    }
}
