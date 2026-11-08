<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\GrafikPenjualan;
use App\Filament\Widgets\SalesAnalyticsStatsOverview;
use App\Filament\Widgets\SalesMonthlyChart;
use App\Filament\Widgets\TopClientsChart;
use App\Filament\Widgets\TopClientsTableWidget;
use App\Models\Client;
use App\Models\OutboundInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GrafikPenjualanTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_monthly_chart_supports_all_time_multi_year_range_and_inverted_dates(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'RSUD Sanjiwani',
            'type' => 'Rumah Sakit',
        ]);

        OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-2025-ALL',
            'division' => 'Diagnostic',
            'issue_date' => '2025-11-10',
            'due_date' => '2025-12-10',
            'status' => 'Paid',
            'subtotal_amount' => 1000000,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
            'paid_amount' => 1110000,
        ]);

        OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-2026-ALL',
            'division' => 'Diagnostic',
            'issue_date' => '2026-03-15',
            'due_date' => '2026-04-15',
            'status' => 'Paid',
            'subtotal_amount' => 2000000,
            'tax_amount' => 220000,
            'total_amount' => 2220000,
            'paid_amount' => 2220000,
        ]);

        OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-2026-CANCEL',
            'division' => 'Diagnostic',
            'issue_date' => '2026-03-20',
            'due_date' => '2026-04-20',
            'status' => 'Cancelled',
            'subtotal_amount' => 9000000,
            'tax_amount' => 990000,
            'total_amount' => 9990000,
            'paid_amount' => 0,
        ]);

        $allFilters = [
            'period_preset' => 'all',
            'start_date' => null,
            'end_date' => null,
            'division' => null,
            'client_type' => null,
            'client_id' => null,
            'status' => null,
        ];

        $this->actingAs($user)
            ->get(GrafikPenjualan::getUrl())
            ->assertOk();

        Livewire::actingAs($user)
            ->test(SalesAnalyticsStatsOverview::class, ['filters' => $allFilters])
            ->assertSee('Rp 3.330.000')
            ->assertSee('2 Faktur');

        Livewire::actingAs($user)
            ->test(TopClientsChart::class, ['filters' => $allFilters])
            ->assertSee('Top Klien / RS Terbesar');

        Livewire::actingAs($user)
            ->test(TopClientsTableWidget::class, ['filters' => $allFilters])
            ->assertCanSeeTableRecords([$client]);

        $chart = new class extends SalesMonthlyChart
        {
            /**
             * @return array<string, mixed>
             */
            public function exposedData(): array
            {
                return $this->getData();
            }
        };

        $chart->filters = $allFilters;
        $allData = $chart->exposedData();

        $this->assertSame(['Oct 2025', 'Nov 2025', 'Dec 2025', 'Jan 2026', 'Feb 2026', 'Mar 2026'], $allData['labels']->all());
        $this->assertSame(0.0, $allData['datasets'][0]['data'][0]);
        $this->assertSame(1110000.0, $allData['datasets'][0]['data'][1]);
        $this->assertSame(2220000.0, $allData['datasets'][0]['data'][5]);
        $this->assertSame(3330000.0, (float) $allData['datasets'][0]['data']->sum());

        $chart->filters = [
            'start_date' => '2026-05-20',
            'end_date' => '2026-05-01',
        ];
        $invertedData = $chart->exposedData();

        $this->assertNotEmpty($invertedData['labels']);
    }

    public function test_last_month_preset_on_march_31_selects_february_without_overflow(): void
    {
        $this->travelTo('2026-03-31 12:00:00');

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GrafikPenjualan::class)
            ->set('filters.period_preset', 'last_month')
            ->assertSet('filters.start_date', '2026-02-01')
            ->assertSet('filters.end_date', '2026-02-28');
    }
}
