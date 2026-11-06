<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\GrafikPenjualan;
use App\Filament\Pages\LaporanDetailPenjualan;
use App\Filament\Pages\LaporanPenjualan;
use App\Filament\Pages\LaporanRekapBarang;
use App\Filament\Resources\OutboundInvoiceResource\Pages\CreateOutboundInvoice;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Widgets\TopClientsTableWidget;
use App\Models\Client;
use App\Models\OutboundInvoice;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductDivisionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_product_with_division_and_filters_product_tabs_and_bulk_assigns_division(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateProduct::class)
            ->fillForm([
                'name' => 'Kantong Darah Triple',
                'division' => Product::DIVISION_BLOOD_TRANSFUSION,
                'brand' => 'Terumo',
                'unit' => 'Pcs',
                'price' => 85000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $bloodProduct = Product::where('name', 'Kantong Darah Triple')->firstOrFail();
        $this->assertSame(Product::DIVISION_BLOOD_TRANSFUSION, $bloodProduct->division);

        $unassignedProduct = Product::create([
            'name' => 'Media Agar MacConkey',
            'brand' => 'MERCK',
            'unit' => 'Botol',
            'price' => 450000,
        ]);

        Livewire::actingAs($user)
            ->test(ListProducts::class)
            ->callTableBulkAction('set_division', [$unassignedProduct], [
                'division' => Product::DIVISION_MICROBIOLOGY,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame(Product::DIVISION_MICROBIOLOGY, $unassignedProduct->fresh()->division);

        Livewire::actingAs($user)
            ->test(ListProducts::class)
            ->set('activeTab', Product::DIVISION_BLOOD_TRANSFUSION)
            ->assertCanSeeTableRecords([$bloodProduct])
            ->assertCanNotSeeTableRecords([$unassignedProduct]);
    }

    public function test_editing_product_division_preserves_existing_photo(): void
    {
        $user = User::factory()->create();

        $product = Product::create([
            'name' => 'Hematology',
            'brand' => 'DIRUI',
            'division' => null,
            'photo' => 'hematology.png',
            'unit' => 'Unit',
            'price' => 150000000,
        ]);

        $this->assertSame('hematology.png', $product->photo);

        Livewire::actingAs($user)
            ->test(EditProduct::class, ['record' => $product->id])
            ->fillForm([
                'division' => Product::DIVISION_DIAGNOSTIC,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertSame(Product::DIVISION_DIAGNOSTIC, $product->division);
        $this->assertSame('hematology.png', $product->photo);
    }

    public function test_outbound_invoice_supports_cross_division_items_auto_fills_division_and_filters_reports(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'RSUP Sanglah',
            'type' => 'Rumah Sakit',
        ]);

        $diagnosticProduct = Product::create([
            'name' => 'Reagen Kimia Klinik',
            'division' => Product::DIVISION_DIAGNOSTIC,
            'brand' => 'DIRUI',
            'unit' => 'Kit',
            'price' => 1000000,
        ]);

        $microProduct = Product::create([
            'name' => 'Media Kultur Darah',
            'division' => Product::DIVISION_MICROBIOLOGY,
            'brand' => 'Zhuhai',
            'unit' => 'Box',
            'price' => 500000,
        ]);

        $patologyProduct = Product::create([
            'name' => 'Pewarnaan Hematoxylin',
            'division' => Product::DIVISION_PATOLOGY_ANATOMI,
            'brand' => 'MERCK',
            'unit' => 'Botol',
            'price' => 750000,
        ]);

        $component = Livewire::actingAs($user)
            ->test(CreateOutboundInvoice::class)
            ->set('data.client_id', $client->id);

        $itemsState = $component->get('data.items');
        $firstItemKey = array_key_first($itemsState);

        // Selecting first product auto-fills invoice division to Diagnostic
        $component->set("data.items.{$firstItemKey}.item_name", $diagnosticProduct->name);
        $this->assertSame(Product::DIVISION_DIAGNOSTIC, $component->get('data.division'));

        // Scenario 2: Add a second item from Microbiology in the same invoice
        $component->set('data.items', [
            $firstItemKey => [
                'item_name' => $diagnosticProduct->name,
                'quantity' => 2,
                'unit' => 'Kit',
                'unit_price' => 1000000,
                'discount_percentage' => 0,
                'discount_2_percentage' => 0,
                'subtotal' => 2000000,
            ],
            'second-item' => [
                'item_name' => $microProduct->name,
                'quantity' => 1,
                'unit' => 'Box',
                'unit_price' => 500000,
                'discount_percentage' => 0,
                'discount_2_percentage' => 0,
                'subtotal' => 500000,
            ],
        ])
            ->set('data.subtotal_amount', 2500000)
            ->set('data.tax_amount', 275000)
            ->set('data.total_amount', 2775000)
            ->call('create')
            ->assertHasNoFormErrors();

        $invoice = OutboundInvoice::firstOrFail();
        $this->assertSame(Product::DIVISION_DIAGNOSTIC, $invoice->division);
        $this->assertCount(2, $invoice->items);

        // Laporan Rekap Per Barang filters cleanly via division tab
        Livewire::actingAs($user)
            ->test(LaporanRekapBarang::class)
            ->call('setDivision', Product::DIVISION_MICROBIOLOGY)
            ->assertCanSeeTableRecords([$microProduct])
            ->assertCanNotSeeTableRecords([$diagnosticProduct, $patologyProduct]);

        // Laporan Penjualan finds mixed-division invoice when filtering by Microbiology (via item product division)
        Livewire::actingAs($user)
            ->test(LaporanPenjualan::class)
            ->filterTable('division', Product::DIVISION_MICROBIOLOGY)
            ->assertCanSeeTableRecords([$invoice])
            ->resetTableFilters()
            ->filterTable('division', Product::DIVISION_BLOOD_TRANSFUSION)
            ->assertCanNotSeeTableRecords([$invoice]);

        // Laporan Detail Penjualan filters items by division
        Livewire::actingAs($user)
            ->test(LaporanDetailPenjualan::class)
            ->assertSee(Product::DIVISION_DIAGNOSTIC)
            ->assertSee(Product::DIVISION_MICROBIOLOGY);

        // Grafik Penjualan & TopClientsTableWidget render and filter by division cleanly
        $this->actingAs($user)
            ->get(GrafikPenjualan::getUrl())
            ->assertOk();

        Livewire::actingAs($user)
            ->test(TopClientsTableWidget::class, [
                'filters' => [
                    'start_date' => now()->startOfYear()->toDateString(),
                    'end_date' => now()->endOfYear()->toDateString(),
                    'division' => Product::DIVISION_MICROBIOLOGY,
                    'client_type' => null,
                    'client_id' => null,
                    'status' => null,
                ],
            ])
            ->assertCanSeeTableRecords([$client]);
    }
}
