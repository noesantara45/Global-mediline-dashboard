<?php

namespace Tests\Feature;

use App\Filament\Pages\LaporanDetailPenjualan;
use App\Filament\Pages\LaporanPenjualan;
use App\Models\Client;
use App\Models\OutboundInvoice;
use App\Models\OutboundInvoiceItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanFiltersFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_laporan_detail_penjualan_page_and_filters_work(): void
    {
        $client = Client::create([
            'name' => 'Klinik Medika',
            'type' => 'Klinik',
            'contact_person' => 'dr. Sarah',
            'phone' => '08111111111',
            'address' => 'Jl. Merdeka No. 10',
        ]);

        $product = Product::create([
            'name' => 'Paracetamol 500mg',
            'brand' => 'Kimia Farma',
            'unit' => 'Box',
            'price' => 15000,
        ]);

        $invoice = OutboundInvoice::create([
            'client_id' => $client->id,
            'sales' => 'John Doe',
            'division' => 'Pharma',
            'invoice_number' => 'INV-2026-001',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-31',
            'status' => 'Paid',
            'subtotal_amount' => 30000,
            'tax_amount' => 3300,
            'total_amount' => 33300,
            'paid_amount' => 33300,
        ]);

        $item = OutboundInvoiceItem::create([
            'outbound_invoice_id' => $invoice->id,
            'item_name' => 'Paracetamol 500mg',
            'quantity' => 2,
            'unit' => 'Box',
            'unit_price' => 15000,
            'discount_percentage' => 0,
            'discount_2_percentage' => 0,
            'subtotal' => 30000,
        ]);

        Livewire::test(LaporanDetailPenjualan::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$item])
            ->filterTable('issue_date', ['from' => '2026-10-01', 'until' => '2026-10-31'])
            ->assertCanSeeTableRecords([$item])
            ->filterTable('invoice_number', ['code' => 'INV-2026-001'])
            ->assertCanSeeTableRecords([$item])
            ->filterTable('sales', 'John Doe')
            ->assertCanSeeTableRecords([$item])
            ->filterTable('division', 'Pharma')
            ->assertCanSeeTableRecords([$item])
            ->filterTable('brand', 'Kimia Farma')
            ->assertCanSeeTableRecords([$item])
            ->filterTable('client_id', $client->id)
            ->assertCanSeeTableRecords([$item]);
    }

    public function test_laporan_penjualan_page_and_filters_work(): void
    {
        $client = Client::create([
            'name' => 'RSUD Sejahtera',
            'type' => 'Rumah Sakit',
            'contact_person' => 'dr. Andi',
            'phone' => '08222222222',
            'address' => 'Jl. Sehat No. 5',
        ]);

        $product = Product::create([
            'name' => 'Amoxicillin 500mg',
            'brand' => 'Sanbe',
            'unit' => 'Strip',
            'price' => 25000,
        ]);

        $invoice = OutboundInvoice::create([
            'client_id' => $client->id,
            'sales' => 'Jane Smith',
            'division' => 'Alkes',
            'invoice_number' => 'INV-2026-002',
            'issue_date' => '2026-10-05',
            'due_date' => '2026-11-05',
            'status' => 'Unpaid',
            'subtotal_amount' => 50000,
            'tax_amount' => 5500,
            'total_amount' => 55500,
            'paid_amount' => 0,
        ]);

        OutboundInvoiceItem::create([
            'outbound_invoice_id' => $invoice->id,
            'item_name' => 'Amoxicillin 500mg',
            'quantity' => 2,
            'unit' => 'Strip',
            'unit_price' => 25000,
            'discount_percentage' => 0,
            'discount_2_percentage' => 0,
            'subtotal' => 50000,
        ]);

        Livewire::test(LaporanPenjualan::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$invoice])
            ->filterTable('issue_date', ['from' => '2026-10-01', 'until' => '2026-10-31'])
            ->assertCanSeeTableRecords([$invoice])
            ->filterTable('invoice_number', ['code' => 'INV-2026-002'])
            ->assertCanSeeTableRecords([$invoice])
            ->filterTable('sales', 'Jane Smith')
            ->assertCanSeeTableRecords([$invoice])
            ->filterTable('division', 'Alkes')
            ->assertCanSeeTableRecords([$invoice])
            ->filterTable('brand', 'Sanbe')
            ->assertCanSeeTableRecords([$invoice])
            ->filterTable('client_id', $client->id)
            ->assertCanSeeTableRecords([$invoice])
            ->filterTable('status', 'Unpaid')
            ->assertCanSeeTableRecords([$invoice]);
    }
}
