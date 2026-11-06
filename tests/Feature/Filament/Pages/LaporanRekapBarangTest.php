<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\LaporanRekapBarang;
use App\Models\Client;
use App\Models\OutboundInvoice;
use App\Models\OutboundInvoiceItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanRekapBarangTest extends TestCase
{
    use RefreshDatabase;

    public function test_excludes_cancelled_invoices_from_recap_totals(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'RSUD Sanjiwani',
            'type' => 'Rumah Sakit',
        ]);

        $productA = Product::create([
            'name' => 'Reagen Test A',
            'division' => 'Diagnostic',
            'brand' => 'DIRUI',
            'unit' => 'Kit',
            'price' => 500000,
        ]);

        $productB = Product::create([
            'name' => 'Reagen Test B (Only Cancelled)',
            'division' => 'Diagnostic',
            'brand' => 'DIRUI',
            'unit' => 'Kit',
            'price' => 750000,
        ]);

        // Active invoice for Product A
        $activeInvoice = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV/ACT/001',
            'issue_date' => '2026-11-01',
            'due_date' => '2026-12-01',
            'status' => 'Paid',
            'subtotal_amount' => 1000000,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
            'paid_amount' => 1110000,
        ]);

        OutboundInvoiceItem::create([
            'outbound_invoice_id' => $activeInvoice->id,
            'item_name' => $productA->name,
            'quantity' => 2,
            'unit_price' => 500000,
            'discount_percentage' => 0,
            'subtotal' => 1000000,
        ]);

        // Cancelled invoice for Product B
        $cancelledInvoice = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV/CAN/001',
            'issue_date' => '2026-11-02',
            'due_date' => '2026-12-02',
            'status' => 'Cancelled',
            'subtotal_amount' => 1500000,
            'tax_amount' => 165000,
            'total_amount' => 1665000,
            'paid_amount' => 0,
        ]);

        OutboundInvoiceItem::create([
            'outbound_invoice_id' => $cancelledInvoice->id,
            'item_name' => $productB->name,
            'quantity' => 2,
            'unit_price' => 750000,
            'discount_percentage' => 0,
            'subtotal' => 1500000,
        ]);

        Livewire::actingAs($user)
            ->test(LaporanRekapBarang::class)
            ->assertSuccessful()
            ->filterTable('has_sales', true)
            ->assertSee($productA->name)
            ->assertDontSee($productB->name);
    }
}

