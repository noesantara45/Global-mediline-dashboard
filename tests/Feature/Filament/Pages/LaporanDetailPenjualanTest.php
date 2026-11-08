<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\LaporanDetailPenjualan;
use App\Models\Client;
use App\Models\OutboundInvoice;
use App\Models\OutboundInvoiceItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanDetailPenjualanTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_search_by_brand_and_division(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'RSUD Bali Mandara',
            'type' => 'Rumah Sakit',
        ]);

        $product = Product::create([
            'name' => 'Produk Khusus',
            'division' => 'Diagnostic',
            'brand' => 'Sysmex',
            'unit' => 'Kit',
            'price' => 1500000,
        ]);

        $invoice = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV/TEST/001',
            'issue_date' => '2026-11-01',
            'due_date' => '2026-12-01',
            'status' => 'Paid',
            'subtotal_amount' => 1500000,
            'tax_amount' => 165000,
            'total_amount' => 1665000,
            'paid_amount' => 1665000,
        ]);

        OutboundInvoiceItem::create([
            'outbound_invoice_id' => $invoice->id,
            'item_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 1500000,
            'discount_percentage' => 0,
            'subtotal' => 1500000,
        ]);

        Livewire::actingAs($user)
            ->test(LaporanDetailPenjualan::class)
            ->searchTable('Sysmex')
            ->assertSee('Produk Khusus');
    }

    public function test_can_sort_by_client_name(): void
    {
        $user = User::factory()->create();
        $clientA = Client::create(['name' => 'RSUD A', 'type' => 'Rumah Sakit']);
        $clientB = Client::create(['name' => 'RSUD B', 'type' => 'Rumah Sakit']);

        $invA = OutboundInvoice::create([
            'client_id' => $clientA->id,
            'invoice_number' => 'INV/A/001',
            'issue_date' => '2026-11-01',
            'due_date' => '2026-12-01',
            'status' => 'Paid',
            'subtotal_amount' => 1000,
            'tax_amount' => 110,
            'total_amount' => 1110,
            'paid_amount' => 1110,
        ]);
        $invB = OutboundInvoice::create([
            'client_id' => $clientB->id,
            'invoice_number' => 'INV/B/001',
            'issue_date' => '2026-11-02',
            'due_date' => '2026-12-02',
            'status' => 'Paid',
            'subtotal_amount' => 2000,
            'tax_amount' => 220,
            'total_amount' => 2220,
            'paid_amount' => 2220,
        ]);

        $itemA = OutboundInvoiceItem::create([
            'outbound_invoice_id' => $invA->id,
            'item_name' => 'Item A',
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
        ]);
        $itemB = OutboundInvoiceItem::create([
            'outbound_invoice_id' => $invB->id,
            'item_name' => 'Item B',
            'quantity' => 1,
            'unit_price' => 2000,
            'subtotal' => 2000,
        ]);

        Livewire::actingAs($user)
            ->test(LaporanDetailPenjualan::class)
            ->assertCanSeeTableRecords([$itemB, $itemA], inOrder: true)
            ->sortTable('subtotal', 'asc')
            ->assertCanSeeTableRecords([$itemA, $itemB], inOrder: true)
            ->sortTable('invoice.client.name', 'desc')
            ->assertCanSeeTableRecords([$itemB, $itemA], inOrder: true)
            ->sortTable('invoice.client.name', 'asc')
            ->assertCanSeeTableRecords([$itemA, $itemB], inOrder: true)
            ->assertSuccessful();
    }

    public function test_excludes_cancelled_invoices_from_grand_totals_and_filters_by_status(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['name' => 'RSUD Tabanan', 'type' => 'Rumah Sakit']);

        $paidInvoice = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV/DET/PAID',
            'issue_date' => '2026-11-01',
            'due_date' => '2026-12-01',
            'status' => 'Paid',
            'subtotal_amount' => 2000000,
            'tax_amount' => 220000,
            'total_amount' => 2220000,
            'paid_amount' => 2220000,
        ]);
        $paidItem = OutboundInvoiceItem::create([
            'outbound_invoice_id' => $paidInvoice->id,
            'item_name' => 'Reagen Aktif',
            'quantity' => 2,
            'unit_price' => 1000000,
            'subtotal' => 2000000,
        ]);

        $cancelledInvoice = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV/DET/CANCEL',
            'issue_date' => '2026-11-02',
            'due_date' => '2026-12-02',
            'status' => 'Cancelled',
            'subtotal_amount' => 5000000,
            'tax_amount' => 550000,
            'total_amount' => 5550000,
            'paid_amount' => 0,
        ]);
        $cancelledItem = OutboundInvoiceItem::create([
            'outbound_invoice_id' => $cancelledInvoice->id,
            'item_name' => 'Reagen Batal',
            'quantity' => 5,
            'unit_price' => 1000000,
            'subtotal' => 5000000,
        ]);

        Livewire::actingAs($user)
            ->test(LaporanDetailPenjualan::class)
            ->assertCanSeeTableRecords([$paidItem, $cancelledItem])
            ->assertTableColumnSummarySet('quantity', 'total_qty', 2)
            ->assertTableColumnSummarySet('subtotal', 'grand_total', 2000000)
            ->filterTable('status', 'Paid')
            ->assertCanSeeTableRecords([$paidItem])
            ->assertCanNotSeeTableRecords([$cancelledItem]);
    }
}
