<?php

namespace Tests\Feature;

use App\Filament\Pages\LaporanPenjualan;
use App\Models\Client;
use App\Models\OutboundInvoice;
use App\Models\OutboundInvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PdfPrintTest extends TestCase
{
    use RefreshDatabase;

    private function createSampleInvoice(): OutboundInvoice
    {
        $client = Client::create([
            'name' => 'RS Harapan Sehat',
            'type' => 'Rumah Sakit',
            'contact_person' => 'Dr. Budi',
            'phone' => '08123456789',
            'address' => 'Jl. Kesehatan No. 1',
        ]);

        $invoice = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-TEST-001',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'Unpaid',
            'subtotal_amount' => 100000,
            'tax_amount' => 11000,
            'total_amount' => 111000,
            'paid_amount' => 0,
        ]);

        OutboundInvoiceItem::create([
            'outbound_invoice_id' => $invoice->id,
            'item_name' => 'Paracetamol 500mg',
            'quantity' => 10,
            'unit' => 'Box',
            'unit_price' => 10000,
            'discount_percentage' => 0,
            'subtotal' => 100000,
        ]);

        return $invoice;
    }

    public function test_authenticated_user_can_view_laporan_penjualan_page(): void
    {
        $user = User::factory()->create();
        $this->createSampleInvoice();

        $response = $this->actingAs($user)->get('/admin/laporan-penjualan');

        $response->assertSuccessful();
    }

    public function test_authenticated_user_can_access_outbound_invoice_pdf(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createSampleInvoice();

        $response = $this->actingAs($user)->get(route('outbound-invoice.pdf', $invoice));

        $response->assertSuccessful();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_authenticated_user_can_access_surat_jalan_pdf(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createSampleInvoice();

        $response = $this->actingAs($user)->get(route('surat-jalan.pdf', $invoice));

        $response->assertSuccessful();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_unauthenticated_user_is_redirected_when_accessing_surat_jalan(): void
    {
        $invoice = $this->createSampleInvoice();

        $response = $this->get(route('surat-jalan.pdf', $invoice));

        $response->assertRedirect(route('login'));
    }

    public function test_laporan_penjualan_renders_and_export_action_works(): void
    {
        $user = User::factory()->create();
        $invoice = $this->createSampleInvoice();
        $invoice->update([
            'sales' => 'Microbiologi',
            'division' => 'Reagen',
        ]);

        $this->assertEquals(100000, $invoice->gross_sale);
        $this->assertEquals(0, $invoice->total_discount);
        $this->assertEquals('Reagen', $invoice->divisi);

        Livewire::actingAs($user)
            ->test(LaporanPenjualan::class)
            ->assertSuccessful()
            ->assertSee($invoice->invoice_number)
            ->assertSee('Microbiologi')
            ->assertSee('Reagen')
            ->callTableAction('export_csv');
    }
}
