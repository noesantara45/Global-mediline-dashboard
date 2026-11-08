<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\IncomeExpenseChart;
use App\Filament\Widgets\InvoiceStatusChart;
use App\Filament\Widgets\LatestInboundInvoices;
use App\Filament\Widgets\LatestOutboundInvoices;
use App\Filament\Widgets\OverdueInvoicesWidget;
use App\Filament\Widgets\StatsOverview;
use App\Models\Client;
use App\Models\InboundInvoice;
use App\Models\OutboundInvoice;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_dashboard_with_quick_actions_and_all_six_widgets(): void
    {
        $user = User::factory()->create(['name' => 'Admin Mediline']);

        $response = $this->actingAs($user)->get(Dashboard::getUrl());

        $response->assertOk();
        $response->assertSee('Selamat datang kembali, Admin Mediline');
        $response->assertSee('Input Pembelian (OCR)');
        $response->assertSee('Buat Faktur Jual');
        $response->assertSee('Tren Penjualan vs Pembelian');
        $response->assertSee('Komposisi Status Tagihan');
        $response->assertSee('Piutang Jatuh Tempo & Prioritas Penagihan');
        $response->assertSee('Faktur Penjualan Terbaru (Outbound)');
        $response->assertSee('Faktur Pembelian Terbaru (Inbound)');
    }

    public function test_stats_overview_and_income_expense_chart_exclude_cancelled_invoices(): void
    {
        $this->travelTo('2026-11-15 10:00:00');

        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'RSUP Prof Ngoerah',
            'type' => 'Rumah Sakit',
        ]);
        $supplier = Supplier::create([
            'name' => 'PT Mensa Bina Sukses',
        ]);

        OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'OUT-NOV-PAID',
            'issue_date' => '2026-11-05',
            'due_date' => '2026-12-05',
            'status' => 'Paid',
            'subtotal_amount' => 2000000,
            'tax_amount' => 220000,
            'total_amount' => 2220000,
            'paid_amount' => 2220000,
        ]);

        OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'OUT-NOV-PARTIAL-OVERDUE',
            'issue_date' => '2026-11-01',
            'due_date' => '2026-11-10',
            'status' => 'Partial',
            'subtotal_amount' => 1000000,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
            'paid_amount' => 310000,
        ]);

        OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'OUT-NOV-CANCELLED',
            'issue_date' => '2026-11-08',
            'due_date' => '2026-11-09',
            'status' => 'Cancelled',
            'subtotal_amount' => 9000000,
            'tax_amount' => 990000,
            'total_amount' => 9990000,
            'paid_amount' => 0,
        ]);

        InboundInvoice::create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INB-NOV-UNPAID',
            'issue_date' => '2026-11-06',
            'due_date' => '2026-12-06',
            'status' => 'Unpaid',
            'subtotal_amount' => 1000000,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
            'paid_amount' => 0,
        ]);

        InboundInvoice::create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INB-NOV-CANCELLED',
            'issue_date' => '2026-11-07',
            'due_date' => '2026-11-08',
            'status' => 'Cancelled',
            'subtotal_amount' => 5000000,
            'tax_amount' => 550000,
            'total_amount' => 5550000,
            'paid_amount' => 0,
        ]);

        // Active Outbound = 2.220.000 + 1.110.000 = 3.330.000 (2 Faktur Aktif)
        // Piutang Klien = 1.110.000 - 310.000 = 800.000 (1 Faktur Lewat Jatuh Tempo)
        // Hutang ke Supplier = 1.110.000 (1 Faktur Inbound Belum Lunas)
        // Estimasi Margin Kas = 3.330.000 - 1.110.000 = 2.220.000
        Livewire::actingAs($user)
            ->test(StatsOverview::class)
            ->assertSee('Rp 3.330.000')
            ->assertSee('2 Faktur Aktif')
            ->assertSee('Rp 800.000')
            ->assertSee('1 Faktur Lewat Jatuh Tempo')
            ->assertSee('Rp 1.110.000')
            ->assertSee('1 Faktur Inbound Belum Lunas')
            ->assertSee('Rp 2.220.000');

        $chartWidget = new class extends IncomeExpenseChart
        {
            /**
             * @return array<string, mixed>
             */
            public function exposedData(): array
            {
                return $this->getData();
            }
        };

        $chartData = $chartWidget->exposedData();
        $novemberIndex = 10; // 0-based index for November

        $this->assertSame(3330000.0, $chartData['datasets'][0]['data'][$novemberIndex]);
        $this->assertSame(1110000.0, $chartData['datasets'][1]['data'][$novemberIndex]);
    }

    public function test_dashboard_table_widgets_prioritize_receivables_and_handle_cancelled_due_dates_cleanly(): void
    {
        $this->travelTo('2026-11-15 10:00:00');

        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'RSUD Wangaya',
            'type' => 'Rumah Sakit',
        ]);
        $supplier = Supplier::create([
            'name' => 'PT Rajawali Nusindo',
        ]);

        $overdueInvoice = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'OUT-OVERDUE-01',
            'division' => 'Diagnostic',
            'sales' => 'Budi',
            'issue_date' => '2026-10-10',
            'due_date' => '2026-11-10',
            'status' => 'Partial',
            'subtotal_amount' => 2000000,
            'tax_amount' => 220000,
            'total_amount' => 2220000,
            'paid_amount' => 220000,
        ]);

        $paidInvoice = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'OUT-PAID-01',
            'division' => 'Microbiology',
            'sales' => 'Sari',
            'issue_date' => '2026-10-05',
            'due_date' => '2026-11-05',
            'status' => 'Paid',
            'subtotal_amount' => 1000000,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
            'paid_amount' => 1110000,
        ]);

        $cancelledOutbound = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'OUT-CANCEL-01',
            'division' => 'Blood Transfusion',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-11-01',
            'status' => 'Cancelled',
            'subtotal_amount' => 1500000,
            'tax_amount' => 165000,
            'total_amount' => 1665000,
            'paid_amount' => 0,
        ]);

        $cancelledInbound = InboundInvoice::create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INB-CANCEL-01',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-11-01',
            'status' => 'Cancelled',
            'subtotal_amount' => 1500000,
            'tax_amount' => 165000,
            'total_amount' => 1665000,
            'paid_amount' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(OverdueInvoicesWidget::class)
            ->assertCanSeeTableRecords([$overdueInvoice])
            ->assertCanNotSeeTableRecords([$paidInvoice, $cancelledOutbound])
            ->assertSee('Diagnostic')
            ->assertSee('Budi')
            ->assertSee('Lewat 5 hari');

        Livewire::actingAs($user)
            ->test(LatestOutboundInvoices::class)
            ->assertCanSeeTableRecords([$overdueInvoice, $paidInvoice, $cancelledOutbound])
            ->assertSee('RSUD Wangaya')
            ->assertSee('Total Aktif (2 Faktur):')
            ->assertSee('Sisa Piutang:');

        Livewire::actingAs($user)
            ->test(LatestInboundInvoices::class)
            ->assertCanSeeTableRecords([$cancelledInbound])
            ->assertSee('PT Rajawali Nusindo')
            ->assertSee('Input Pembelian (OCR)')
            ->assertSee('Sisa Hutang:');

        $statusChart = new class extends InvoiceStatusChart
        {
            public ?string $filter = 'nominal';

            /**
             * @return array<string, mixed>
             */
            public function exposedOptions(): array
            {
                return $this->getOptions();
            }

            /**
             * @return array<string, mixed>
             */
            public function exposedData(): array
            {
                return $this->getData();
            }
        };

        $options = $statusChart->exposedOptions();
        $this->assertFalse($options['scales']['x']['display']);
        $this->assertFalse($options['scales']['y']['display']);

        $nominalData = $statusChart->exposedData();
        $this->assertSame([1110000.0, 0.0, 2000000.0], $nominalData['datasets'][0]['data']);

        $statusChart->filter = 'count';
        $countData = $statusChart->exposedData();
        $this->assertSame([1, 0, 1], $countData['datasets'][0]['data']);
    }
}
