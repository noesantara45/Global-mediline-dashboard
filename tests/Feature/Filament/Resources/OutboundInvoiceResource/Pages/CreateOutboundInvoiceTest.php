<?php

namespace Tests\Feature\Filament\Resources\OutboundInvoiceResource\Pages;

use App\Filament\Resources\OutboundInvoiceResource;
use App\Filament\Resources\OutboundInvoiceResource\Pages\CreateOutboundInvoice;
use App\Filament\Resources\OutboundInvoiceResource\Pages\EditOutboundInvoice;
use App\Models\Client;
use App\Models\OutboundInvoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateOutboundInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_clamps_negative_values(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'RSUD Bali Mandara',
            'type' => 'Rumah Sakit',
        ]);

        $product = Product::create([
            'name' => 'Produk A',
            'division' => 'Diagnostic',
            'unit' => 'Pcs',
            'price' => 100000,
        ]);

        Livewire::actingAs($user)
            ->test(CreateOutboundInvoice::class)
            ->fillForm([
                'client_id' => $client->id,
                'items' => [
                    [
                        'item_name' => $product->name,
                        'quantity' => 1,
                        'unit' => 'Pcs',
                        'unit_price' => 100000,
                        'discount_percentage' => -10,
                        'discount_2_percentage' => -5,
                    ],
                ],
            ])
            ->assertFormSet([
                'items.0.discount_percentage' => 0,
                'items.0.discount_2_percentage' => 0,
                'items.0.subtotal' => 100000,
            ]);
    }

    public function test_generates_sequential_daily_invoice_numbers_and_validates_duplicate_and_due_date(): void
    {
        $this->travelTo('2026-11-08 09:00:00');

        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'RSUD Bali Mandara',
            'type' => 'Rumah Sakit',
        ]);
        $product = Product::create([
            'name' => 'Produk Uji',
            'division' => 'Diagnostic',
            'unit' => 'Pcs',
            'price' => 100000,
        ]);

        $this->assertSame('JL261108-001', OutboundInvoiceResource::generateNextInvoiceNumber());

        OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'JL261108-001',
            'issue_date' => '2026-11-08',
            'due_date' => '2026-12-08',
            'status' => 'Unpaid',
            'subtotal_amount' => 100000,
            'tax_amount' => 11000,
            'total_amount' => 111000,
            'paid_amount' => 0,
        ]);

        $this->assertSame('JL261108-002', OutboundInvoiceResource::generateNextInvoiceNumber());

        Livewire::actingAs($user)
            ->test(CreateOutboundInvoice::class)
            ->fillForm([
                'client_id' => $client->id,
                'invoice_number' => 'JL261108-001',
                'issue_date' => '2026-11-08',
                'due_date' => '2026-11-01',
                'items' => [
                    [
                        'item_name' => $product->name,
                        'quantity' => 1,
                        'unit' => 'Pcs',
                        'unit_price' => 100000,
                        'discount_percentage' => 0,
                        'discount_2_percentage' => 0,
                        'subtotal' => 100000,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors([
                'invoice_number' => 'unique',
                'due_date' => 'after_or_equal',
            ]);
    }

    public function test_edit_outbound_invoice_rejects_external_previous_url_open_redirect_and_syncs_paid_status(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'RSUD Payangan',
            'type' => 'Rumah Sakit',
        ]);
        $invoice = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'JL261108-099',
            'issue_date' => '2026-11-08',
            'due_date' => '2026-12-08',
            'status' => 'Unpaid',
            'subtotal_amount' => 1000000,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
            'paid_amount' => 0,
        ]);

        $this->assertFalse(EditOutboundInvoice::isSafeRedirectUrl('https://evil.example/phish'));
        $this->assertFalse(EditOutboundInvoice::isSafeRedirectUrl('//evil.example/phish'));
        $this->assertFalse(EditOutboundInvoice::isSafeRedirectUrl('javascript:alert(1)'));
        $this->assertTrue(EditOutboundInvoice::isSafeRedirectUrl(url('/admin/laporan-penjualan')));

        Livewire::actingAs($user)
            ->test(EditOutboundInvoice::class, ['record' => $invoice->getRouteKey()])
            ->set('previousUrl', 'https://evil.example/phish')
            ->fillForm(['status' => 'Paid'])
            ->assertSet('data.paid_amount', '1.110.000')
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(OutboundInvoiceResource::getUrl('index'));

        $this->assertSame(1110000.0, (float) $invoice->fresh()->paid_amount);
    }

    public function test_selecting_product_formats_unit_price_without_strip_characters_multiplier_bug(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'RSUD Tabanan',
            'type' => 'Rumah Sakit',
        ]);
        $product = Product::create([
            'name' => 'Reagen Hematology Pro',
            'division' => 'Diagnostic',
            'unit' => 'Kit',
            'price' => 500000,
        ]);

        Livewire::actingAs($user)
            ->test(CreateOutboundInvoice::class)
            ->fillForm([
                'client_id' => $client->id,
                'invoice_number' => 'JL261108-777',
                'items' => [
                    [
                        'item_name' => $product->name,
                        'quantity' => 2,
                    ],
                ],
            ])
            ->assertFormSet([
                'division' => 'Diagnostic',
                'items.0.unit' => 'Kit',
                'items.0.unit_price' => '500.000',
                'items.0.subtotal' => 1000000,
                'subtotal_amount' => 1000000,
                'total_amount' => 1110000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $invoice = OutboundInvoice::query()
            ->with('items')
            ->where('invoice_number', 'JL261108-777')
            ->first();

        $this->assertNotNull($invoice);
        $this->assertSame(500000.0, (float) $invoice->items->first()?->unit_price);
        $this->assertSame(1000000.0, (float) $invoice->subtotal_amount);
        $this->assertSame(1110000.0, (float) $invoice->total_amount);
    }
}
