<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SalesAnalyticsStatsOverview;
use App\Filament\Widgets\SalesMonthlyChart;
use App\Filament\Widgets\TopClientsChart;
use App\Filament\Widgets\TopClientsTableWidget;
use App\Models\Client;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;

class GrafikPenjualan extends Page
{
    use HasFiltersForm;

    protected static ?string $navigationGroup = 'Grafik & Analitik';

    protected static ?string $navigationLabel = 'Grafik Penjualan';

    protected static ?string $title = 'Grafik & Analitik Penjualan';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.grafik-penjualan';

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Filter Analitik Penjualan')
                    ->description('Sesuaikan rentang waktu dan kriteria untuk menganalisis data penjualan secara real-time.')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make([
                            'default' => 1,
                            'sm' => 2,
                            'md' => 3,
                            'xl' => 6,
                        ])->schema([
                            Forms\Components\Select::make('period_preset')
                                ->label('Periode Cepat')
                                ->options([
                                    'this_year' => 'Tahun Ini ('.now()->year.')',
                                    'this_month' => 'Bulan Ini',
                                    'last_month' => 'Bulan Lalu',
                                    'last_30_days' => '30 Hari Terakhir',
                                    'last_year' => 'Tahun Lalu ('.(now()->year - 1).')',
                                    'all' => 'Semua Waktu',
                                    'custom' => 'Kustom Tanggal',
                                ])
                                ->default('this_year')
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
                                ->default(now()->startOfYear()->toDateString())
                                ->displayFormat('d/m/Y')
                                ->native(false),

                            Forms\Components\DatePicker::make('end_date')
                                ->label('Sampai Tanggal')
                                ->default(now()->endOfYear()->toDateString())
                                ->displayFormat('d/m/Y')
                                ->native(false),

                            Forms\Components\Select::make('client_type')
                                ->label('Tipe Klien')
                                ->placeholder('Semua Tipe Klien')
                                ->options([
                                    'Rumah Sakit' => 'Rumah Sakit',
                                    'Klinik' => 'Klinik',
                                    'Apotek' => 'Apotek',
                                    'Lainnya' => 'Lainnya',
                                ]),

                            Forms\Components\Select::make('client_id')
                                ->label('Customer / RS')
                                ->placeholder('Semua Customer')
                                ->options(fn (): array => Client::orderBy('name')->pluck('name', 'id')->toArray())
                                ->searchable()
                                ->preload(),

                            Forms\Components\Select::make('status')
                                ->label('Status Bayar')
                                ->placeholder('Semua Status')
                                ->options([
                                    'Paid' => 'Lunas (Paid)',
                                    'Unpaid' => 'Belum Lunas (Unpaid)',
                                    'Partial' => 'Dicicil (Partial)',
                                ]),
                        ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset_filters')
                ->label('Reset Filter')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action(function (): void {
                    $this->filters = [
                        'period_preset' => 'this_year',
                        'start_date' => now()->startOfYear()->toDateString(),
                        'end_date' => now()->endOfYear()->toDateString(),
                        'client_type' => null,
                        'client_id' => null,
                        'status' => null,
                    ];
                    $this->getFiltersForm()->fill($this->filters);
                }),
        ];
    }

    public function getWidgets(): array
    {
        return [
            SalesAnalyticsStatsOverview::class,
            SalesMonthlyChart::class,
            TopClientsChart::class,
            TopClientsTableWidget::class,
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
