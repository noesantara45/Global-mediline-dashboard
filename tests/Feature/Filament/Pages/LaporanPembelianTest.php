<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\LaporanPembelian;
use App\Filament\Pages\LaporanRekapBarang;
use App\Models\InboundInvoice;
use App\Models\InboundInvoiceItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanPembelianTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_laporan_pembelian_with_saved_inbound_data_without_polluting_rekap_barang(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::create([
            'name' => 'PT. BIO CARE SEJAHTERA',
            'top_days' => 30,
            'is_active' => true,
        ]);
        $invoice = InboundInvoice::create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV/BS25/XI/3355',
            'issue_date' => '2025-11-19',
            'due_date' => '2025-12-19',
            'status' => 'Unpaid',
            'subtotal_amount' => 2539500,
            'tax_amount' => 279345,
            'total_amount' => 2818845,
            'paid_amount' => 0,
        ]);
        InboundInvoiceItem::create([
            'inbound_invoice_id' => $invoice->id,
            'item_name' => 'DrACCU CEA FIA',
            'quantity' => 25,
            'unit' => 'TST',
            'unit_price' => 135440,
            'subtotal' => 2539500,
        ]);

        $this->actingAs($user)
            ->get(LaporanPembelian::getUrl())
            ->assertOk();

        Livewire::actingAs($user)
            ->test(LaporanPembelian::class)
            ->assertCanSeeTableRecords([$invoice])
            ->assertSee('INV/BS25/XI/3355')
            ->assertSee('PT. BIO CARE SEJAHTERA')
            ->assertSee('DrACCU CEA FIA');

        $this->assertSame(3386000.0, $invoice->load('items')->gross_purchase);
        $this->assertSame(846500.0, $invoice->total_discount);
        $this->assertDatabaseCount('products', 0);

        Livewire::actingAs($user)
            ->test(LaporanRekapBarang::class)
            ->assertDontSee('DrACCU CEA FIA');
    }

    public function test_filters_inbound_invoices_by_supplier_item_name_and_status(): void
    {
        $user = User::factory()->create();
        $supplierA = Supplier::create([
            'name' => 'PT. BIO CARE SEJAHTERA',
            'is_active' => true,
        ]);
        $supplierB = Supplier::create([
            'name' => 'PT. ALKES NUSANTARA',
            'is_active' => true,
        ]);

        $invoiceA = InboundInvoice::create([
            'supplier_id' => $supplierA->id,
            'invoice_number' => 'INV-BELI-001',
            'issue_date' => '2025-11-19',
            'due_date' => '2025-12-19',
            'status' => 'Unpaid',
            'subtotal_amount' => 1000000,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
            'paid_amount' => 0,
        ]);
        InboundInvoiceItem::create([
            'inbound_invoice_id' => $invoiceA->id,
            'item_name' => 'DrACCU CEA FIA',
            'quantity' => 10,
            'unit' => 'TST',
            'unit_price' => 100000,
            'subtotal' => 1000000,
        ]);

        $invoiceB = InboundInvoice::create([
            'supplier_id' => $supplierB->id,
            'invoice_number' => 'INV-BELI-002',
            'issue_date' => '2025-11-20',
            'due_date' => '2025-12-20',
            'status' => 'Paid',
            'subtotal_amount' => 500000,
            'tax_amount' => 55000,
            'total_amount' => 555000,
            'paid_amount' => 555000,
        ]);
        InboundInvoiceItem::create([
            'inbound_invoice_id' => $invoiceB->id,
            'item_name' => 'Jarum Suntik Steril',
            'quantity' => 50,
            'unit' => 'Box',
            'unit_price' => 10000,
            'subtotal' => 500000,
        ]);

        Livewire::actingAs($user)
            ->test(LaporanPembelian::class)
            ->filterTable('supplier_id', $supplierA->id)
            ->assertCanSeeTableRecords([$invoiceA])
            ->assertCanNotSeeTableRecords([$invoiceB])
            ->resetTableFilters()
            ->filterTable('item_name', ['name' => 'Jarum Suntik'])
            ->assertCanSeeTableRecords([$invoiceB])
            ->assertCanNotSeeTableRecords([$invoiceA])
            ->resetTableFilters()
            ->filterTable('status', 'Paid')
            ->assertCanSeeTableRecords([$invoiceB])
            ->assertCanNotSeeTableRecords([$invoiceA]);
    }

    public function test_exports_rekap_pembelian_csv(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::create([
            'name' => 'PT. BIO CARE SEJAHTERA',
            'is_active' => true,
        ]);
        InboundInvoice::create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-CSV-001',
            'issue_date' => '2025-11-19',
            'due_date' => '2025-12-19',
            'status' => 'Paid',
            'subtotal_amount' => 1000000,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
            'paid_amount' => 1110000,
        ]);

        Livewire::actingAs($user)
            ->test(LaporanPembelian::class)
            ->callTableAction('export_csv')
            ->assertFileDownloaded();
    }

    public function test_prints_inbound_invoice_pdf_only_for_authenticated_users(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::create([
            'name' => 'PT. BIO CARE SEJAHTERA',
            'phone' => '0361-123456',
            'address' => 'Denpasar',
            'is_active' => true,
        ]);
        $invoice = InboundInvoice::create([
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV/BS25/XI/3355',
            'issue_date' => '2025-11-19',
            'due_date' => '2025-12-19',
            'status' => 'Unpaid',
            'subtotal_amount' => 2539500,
            'tax_amount' => 279345,
            'total_amount' => 2818845,
            'paid_amount' => 0,
        ]);
        InboundInvoiceItem::create([
            'inbound_invoice_id' => $invoice->id,
            'item_name' => 'DrACCU CEA FIA',
            'quantity' => 25,
            'unit' => 'TST',
            'unit_price' => 135440,
            'subtotal' => 2539500,
        ]);

        $this->get(route('inbound-invoice.pdf', $invoice))
            ->assertRedirect(route('login'));

        $response = $this->actingAs($user)
            ->get(route('inbound-invoice.pdf', $invoice));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }
}
