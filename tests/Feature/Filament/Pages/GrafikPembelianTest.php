<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\GrafikPembelian;
use App\Filament\Widgets\InboundItemsRecapTableWidget;
use App\Filament\Widgets\PurchaseAnalyticsStatsOverview;
use App\Filament\Widgets\PurchaseMonthlyChart;
use App\Filament\Widgets\TopSuppliersChart;
use App\Filament\Widgets\TopSuppliersTableWidget;
use App\Models\InboundInvoice;
use App\Models\InboundInvoiceItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GrafikPembelianTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_grafik_pembelian_page_and_recaps_inbound_invoices_across_years(): void
    {
        $user = User::factory()->create();
        $supplierA = Supplier::create([
            'name' => 'PT. BIO CARE SEJAHTERA',
            'top_days' => 30,
            'is_active' => true,
        ]);
        $supplierB = Supplier::create([
            'name' => 'PT. MEDIKA PRIMA',
            'top_days' => 14,
            'is_active' => true,
        ]);

        $invoiceA = InboundInvoice::create([
            'supplier_id' => $supplierA->id,
            'invoice_number' => 'INV/BS25/XI/3355',
            'issue_date' => '2025-11-19',
            'due_date' => '2025-12-19',
            'status' => 'Unpaid',
            'subtotal_amount' => 5866501,
            'tax_amount' => 645315,
            'total_amount' => 7081816,
            'paid_amount' => 0,
        ]);
        InboundInvoiceItem::create([
            'inbound_invoice_id' => $invoiceA->id,
            'item_name' => 'DrACCU CEA FIA',
            'quantity' => 25,
            'unit' => 'TST',
            'unit_price' => 135440,
            'subtotal' => 2539500,
        ]);
        InboundInvoiceItem::create([
            'inbound_invoice_id' => $invoiceA->id,
            'item_name' => 'DrACCU Alpha Fetal Protein',
            'quantity' => 25,
            'unit' => 'TST',
            'unit_price' => 135440,
            'subtotal' => 3327001,
        ]);

        $invoiceB = InboundInvoice::create([
            'supplier_id' => $supplierB->id,
            'invoice_number' => 'INV/MP26/001',
            'issue_date' => '2026-03-10',
            'due_date' => '2026-03-24',
            'status' => 'Paid',
            'subtotal_amount' => 2000000,
            'tax_amount' => 220000,
            'total_amount' => 2220000,
            'paid_amount' => 2220000,
        ]);
        InboundInvoiceItem::create([
            'inbound_invoice_id' => $invoiceB->id,
            'item_name' => 'DrACCU CEA FIA',
            'quantity' => 10,
            'unit' => 'TST',
            'unit_price' => 200000,
            'subtotal' => 2000000,
        ]);

        // Cancelled invoice should be excluded from active purchase recap by default
        InboundInvoice::create([
            'supplier_id' => $supplierB->id,
            'invoice_number' => 'INV/CANCEL/001',
            'issue_date' => '2026-03-15',
            'due_date' => '2026-03-29',
            'status' => 'Cancelled',
            'subtotal_amount' => 9000000,
            'tax_amount' => 990000,
            'total_amount' => 9990000,
            'paid_amount' => 0,
        ]);

        $this->actingAs($user)
            ->get(GrafikPembelian::getUrl())
            ->assertOk();

        // Stats overview aggregates both 2025 & 2026 active Inbound Invoices (7,081,816 + 2,220,000 = 9,301,816)
        Livewire::actingAs($user)
            ->test(PurchaseAnalyticsStatsOverview::class, [
                'filters' => [
                    'period_preset' => 'all',
                    'start_date' => null,
                    'end_date' => null,
                    'supplier_id' => null,
                    'status' => null,
                ],
            ])
            ->assertSee('Rp 9.301.816')
            ->assertSee('Rp 2.220.000')
            ->assertSee('Rp 7.081.816')
            ->assertSee('2 Faktur')
            ->assertSee('60 Qty Barang dari 2 Supplier');

        // Monthly chart includes 2025-11 and 2026-03 Inbound Invoices when Semua Waktu is active
        Livewire::actingAs($user)
            ->test(PurchaseMonthlyChart::class, [
                'filters' => [
                    'period_preset' => 'all',
                    'start_date' => null,
                    'end_date' => null,
                    'supplier_id' => null,
                    'status' => null,
                ],
            ])
            ->assertSee('Tren Pembelian Inbound');

        // Top suppliers chart renders active suppliers
        Livewire::actingAs($user)
            ->test(TopSuppliersChart::class, [
                'filters' => [
                    'period_preset' => 'all',
                    'start_date' => null,
                    'end_date' => null,
                    'supplier_id' => null,
                    'status' => null,
                ],
            ])
            ->assertSee('Top Supplier Pembelian Terbesar');

        // Supplier recap table renders without SQL HAVING pagination errors and respects filters
        Livewire::actingAs($user)
            ->test(TopSuppliersTableWidget::class, [
                'filters' => [
                    'period_preset' => 'all',
                    'start_date' => null,
                    'end_date' => null,
                    'supplier_id' => null,
                    'status' => null,
                ],
            ])
            ->assertCanSeeTableRecords([$supplierA, $supplierB])
            ->assertSee('PT. BIO CARE SEJAHTERA')
            ->assertSee('PT. MEDIKA PRIMA');

        // Inbound items recap table aggregates purchased items across Inbound Invoices (25 + 10 = 35 TST for DrACCU CEA FIA)
        Livewire::actingAs($user)
            ->test(InboundItemsRecapTableWidget::class, [
                'filters' => [
                    'period_preset' => 'all',
                    'start_date' => null,
                    'end_date' => null,
                    'supplier_id' => null,
                    'status' => null,
                ],
            ])
            ->assertSee('DrACCU CEA FIA')
            ->assertSee('DrACCU Alpha Fetal Protein')
            ->assertSee('2 Faktur')
            ->assertSee('35');
    }

    public function test_filters_grafik_pembelian_widgets_by_supplier_and_status(): void
    {
        $user = User::factory()->create();
        $supplierA = Supplier::create(['name' => 'Supplier Satu', 'is_active' => true]);
        $supplierB = Supplier::create(['name' => 'Supplier Dua', 'is_active' => true]);

        InboundInvoice::create([
            'supplier_id' => $supplierA->id,
            'invoice_number' => 'INV-A1',
            'issue_date' => '2026-02-10',
            'due_date' => '2026-03-10',
            'status' => 'Unpaid',
            'subtotal_amount' => 1000000,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
            'paid_amount' => 0,
        ]);

        InboundInvoice::create([
            'supplier_id' => $supplierB->id,
            'invoice_number' => 'INV-B1',
            'issue_date' => '2026-02-15',
            'due_date' => '2026-03-15',
            'status' => 'Paid',
            'subtotal_amount' => 3000000,
            'tax_amount' => 330000,
            'total_amount' => 3330000,
            'paid_amount' => 3330000,
        ]);

        Livewire::actingAs($user)
            ->test(TopSuppliersTableWidget::class, [
                'filters' => [
                    'period_preset' => 'all',
                    'start_date' => null,
                    'end_date' => null,
                    'supplier_id' => $supplierA->id,
                    'status' => 'Unpaid',
                ],
            ])
            ->assertCanSeeTableRecords([$supplierA])
            ->assertCanNotSeeTableRecords([$supplierB]);
    }
}
