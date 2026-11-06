<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InboundItemsRecapTableWidget;
use App\Filament\Widgets\PurchaseAnalyticsStatsOverview;
use App\Filament\Widgets\PurchaseMonthlyChart;
use App\Filament\Widgets\TopSuppliersChart;
use App\Filament\Widgets\TopSuppliersTableWidget;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class GrafikPembelian extends Page
{
    use HasFiltersForm;

    protected static ?string $navigationGroup = 'Grafik & Analitik';

    protected static ?string $navigationLabel = 'Grafik Pembelian';

    protected static ?string $title = 'Grafik & Rekap Analitik Pembelian';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static string $view = 'filament.pages.grafik-pembelian';

    public function getFiltersSessionKey(): string
    {
        return md5(static::class).'_inbound_recap_filters';
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make([
                    'default' => 1,
                    'sm' => 2,
                    'md' => 3,
                    'xl' => 5,
                ])->schema([
                    Forms\Components\Select::make('period_preset')
                        ->label('Periode Cepat')
                        ->selectablePlaceholder(false)
                        ->options([
                            'all' => 'Semua Waktu',
                            'this_year' => 'Tahun '.now()->year,
                            'this_month' => 'Bulan Ini',
                            'last_month' => 'Bulan Lalu',
                            'last_30_days' => '30 Hari Terakhir',
                            'last_year' => 'Tahun '.(now()->year - 1),
                            'custom' => 'Kustom Tanggal',
                        ])
                        ->default('all')
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set): void {
                            match ($state) {
                                'this_year' => [
                                    $set('start_date', now()->startOfYear()->toDateString()),
                                    $set('end_date', now()->endOfYear()->toDateString()),
                                ],
                                'this_month' => [
                                    $set('start_date', now()->startOfMonth()->toDateString()),
                                    $set('end_date', now()->endOfMonth()->toDateString()),
                                ],
                                'last_month' => [
                                    $set('start_date', now()->subMonth()->startOfMonth()->toDateString()),
                                    $set('end_date', now()->subMonth()->endOfMonth()->toDateString()),
                                ],
                                'last_30_days' => [
                                    $set('start_date', now()->subDays(30)->toDateString()),
                                    $set('end_date', now()->toDateString()),
                                ],
                                'last_year' => [
                                    $set('start_date', now()->subYear()->startOfYear()->toDateString()),
                                    $set('end_date', now()->subYear()->endOfYear()->toDateString()),
                                ],
                                'all' => [
                                    $set('start_date', null),
                                    $set('end_date', null),
                                ],
                                default => null,
                            };
                        }),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('Dari Tanggal')
                        ->placeholder('dd/mm/yyyy')
                        ->default(null)
                        ->displayFormat('d/m/Y')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->afterStateUpdated(function ($state, Forms\Set $set): void {
                            if ($state !== null) {
                                $set('period_preset', 'custom');
                            }
                        }),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('Sampai Tanggal')
                        ->placeholder('dd/mm/yyyy')
                        ->default(null)
                        ->displayFormat('d/m/Y')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->afterStateUpdated(function ($state, Forms\Set $set): void {
                            if ($state !== null) {
                                $set('period_preset', 'custom');
                            }
                        }),

                    Forms\Components\Select::make('supplier_id')
                        ->label('Supplier / Vendor')
                        ->placeholder('Semua')
                        ->options(fn (): array => Supplier::orderBy('name')->pluck('name', 'id')->toArray())
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('status')
                        ->label('Status Bayar')
                        ->placeholder('Semua Aktif')
                        ->options([
                            'Paid' => 'Lunas (Paid)',
                            'Unpaid' => 'Belum Lunas (Unpaid)',
                            'Partial' => 'Dicicil (Partial)',
                            'Cancelled' => 'Dibatalkan (Cancelled)',
                        ]),
                ]),
            ]);
    }

    public function resetPurchaseFilters(): void
    {
        $this->filters = [
            'period_preset' => 'all',
            'start_date' => null,
            'end_date' => null,
            'supplier_id' => null,
            'status' => null,
        ];

        $this->getFiltersForm()->fill($this->filters);
        session()->put($this->getFiltersSessionKey(), $this->filters);
    }

    public function getActivePeriodLabel(): string
    {
        $start = $this->filters['start_date'] ?? null;
        $end = $this->filters['end_date'] ?? null;

        if ($start && $end) {
            return Carbon::parse($start)->format('d M Y').' – '.Carbon::parse($end)->format('d M Y');
        }

        if ($start) {
            return 'Sejak '.Carbon::parse($start)->format('d M Y');
        }

        if ($end) {
            return 'Sampai '.Carbon::parse($end)->format('d M Y');
        }

        return 'Semua Waktu';
    }

    public function getWidgets(): array
    {
        return [
            PurchaseAnalyticsStatsOverview::class,
            PurchaseMonthlyChart::class,
            TopSuppliersChart::class,
            TopSuppliersTableWidget::class,
            InboundItemsRecapTableWidget::class,
        ];
    }

    public function getVisibleWidgets(): array
    {
        return $this->filterVisibleWidgets($this->getWidgets());
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}
