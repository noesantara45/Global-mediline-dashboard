<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\LaporanPenjualan;
use App\Models\Client;
use App\Models\OutboundInvoice;
use App\Models\OutboundInvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanPenjualanTest extends TestCase
{
    use RefreshDatabase;

    public function test_prints_outbound_invoice_and_surat_jalan_pdf_safely_with_slashed_number_and_missing_client(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'RSUD Wangaya',
            'type' => 'Rumah Sakit',
        ]);

        $invoice = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV/GM/2026/001',
            'issue_date' => '2026-11-05',
            'due_date' => '2026-12-05',
            'status' => 'Paid',
            'subtotal_amount' => 1000000,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
            'paid_amount' => 1110000,
        ]);
        OutboundInvoiceItem::create([
            'outbound_invoice_id' => $invoice->id,
            'item_name' => 'Reagen Hematologi',
            'quantity' => 2,
            'unit' => 'Kit',
            'unit_price' => 500000,
            'discount_percentage' => 0,
            'subtotal' => 1000000,
        ]);

        $invoiceResponse = $this->actingAs($user)->get(route('outbound-invoice.pdf', $invoice));
        $invoiceResponse->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $invoiceResponse->headers->get('content-type'));

        $suratJalanResponse = $this->actingAs($user)->get(route('surat-jalan.pdf', $invoice));
        $suratJalanResponse->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $suratJalanResponse->headers->get('content-type'));

        // Simulate null client relation to verify null-safe PDF template rendering
        $invoiceWithoutClient = $invoice->fresh()->load('items');
        $invoiceWithoutClient->setRelation('client', null);
        $renderedHtml = view('pdf.outbound-invoice', ['invoice' => $invoiceWithoutClient])->render();
        $this->assertStringContainsString('INV/GM/2026/001', $renderedHtml);
        $this->assertStringContainsString('Reagen Hematologi', $renderedHtml);
    }

    public function test_bottom_summary_totals_exclude_cancelled_outbound_invoices(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'RSUP Sanglah',
            'type' => 'Rumah Sakit',
        ]);

        $activeInvoice = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-ACTIVE-01',
            'issue_date' => '2026-11-01',
            'due_date' => '2026-12-01',
            'status' => 'Paid',
            'subtotal_amount' => 1800000,
            'tax_amount' => 198000,
            'total_amount' => 1998000,
            'paid_amount' => 1998000,
        ]);
        OutboundInvoiceItem::create([
            'outbound_invoice_id' => $activeInvoice->id,
            'item_name' => 'Reagen Kimia',
            'quantity' => 2,
            'unit' => 'Kit',
            'unit_price' => 1000000,
            'discount_percentage' => 10,
            'subtotal' => 1800000,
        ]);

        $cancelledInvoice = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-CANCEL-01',
            'issue_date' => '2026-11-02',
            'due_date' => '2026-12-02',
            'status' => 'Cancelled',
            'subtotal_amount' => 4500000,
            'tax_amount' => 495000,
            'total_amount' => 4995000,
            'paid_amount' => 0,
        ]);
        OutboundInvoiceItem::create([
            'outbound_invoice_id' => $cancelledInvoice->id,
            'item_name' => 'Reagen Batal',
            'quantity' => 5,
            'unit' => 'Kit',
            'unit_price' => 1000000,
            'discount_percentage' => 10,
            'subtotal' => 4500000,
        ]);

        Livewire::actingAs($user)
            ->test(LaporanPenjualan::class)
            ->assertCanSeeTableRecords([$activeInvoice, $cancelledInvoice])
            ->assertTableColumnSummarySet('gross_sale', 'total_gross', 2000000)
            ->assertTableColumnSummarySet('total_discount', 'total_discount', 200000)
            ->assertTableColumnSummarySet('tax_amount', 'total_ppn', 198000)
            ->assertTableColumnSummarySet('subtotal_amount', 'total_subtotal', 1800000)
            ->assertTableColumnSummarySet('total_amount', 'total_net_sale', 1998000);
    }

    public function test_sorts_columns_without_issue_date_overriding_and_orders_gross_sale_by_pre_discount_items_sum(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'RSUD Badung',
            'type' => 'Rumah Sakit',
        ]);

        $olderHighGrossDiscounted = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-SORT-01',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-11-01',
            'status' => 'Paid',
            'subtotal_amount' => 2000000,
            'tax_amount' => 220000,
            'total_amount' => 2220000,
            'paid_amount' => 2220000,
        ]);
        OutboundInvoiceItem::create([
            'outbound_invoice_id' => $olderHighGrossDiscounted->id,
            'item_name' => 'Reagen Imunologi',
            'quantity' => 4,
            'unit' => 'Kit',
            'unit_price' => 1000000,
            'discount_percentage' => 50,
            'subtotal' => 2000000,
        ]);

        $middleMediumGrossNoDiscount = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-SORT-02',
            'issue_date' => '2026-10-15',
            'due_date' => '2026-11-15',
            'status' => 'Unpaid',
            'subtotal_amount' => 3000000,
            'tax_amount' => 330000,
            'total_amount' => 3330000,
            'paid_amount' => 0,
        ]);
        OutboundInvoiceItem::create([
            'outbound_invoice_id' => $middleMediumGrossNoDiscount->id,
            'item_name' => 'Reagen Mikrobiologi',
            'quantity' => 3,
            'unit' => 'Kit',
            'unit_price' => 1000000,
            'discount_percentage' => 0,
            'subtotal' => 3000000,
        ]);

        $newestLowestSale = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-SORT-03',
            'issue_date' => '2026-10-25',
            'due_date' => '2026-11-25',
            'status' => 'Paid',
            'subtotal_amount' => 1000000,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
            'paid_amount' => 1110000,
        ]);
        OutboundInvoiceItem::create([
            'outbound_invoice_id' => $newestLowestSale->id,
            'item_name' => 'Kontrol Normal',
            'quantity' => 1,
            'unit' => 'Vial',
            'unit_price' => 1000000,
            'discount_percentage' => 0,
            'subtotal' => 1000000,
        ]);

        Livewire::actingAs($user)
            ->test(LaporanPenjualan::class)
            ->assertCanSeeTableRecords([
                $newestLowestSale,
                $middleMediumGrossNoDiscount,
                $olderHighGrossDiscounted,
            ], inOrder: true)
            ->sortTable('total_amount', 'asc')
            ->assertCanSeeTableRecords([
                $newestLowestSale,
                $olderHighGrossDiscounted,
                $middleMediumGrossNoDiscount,
            ], inOrder: true)
            ->sortTable('gross_sale', 'desc')
            ->assertCanSeeTableRecords([
                $olderHighGrossDiscounted,
                $middleMediumGrossNoDiscount,
                $newestLowestSale,
            ], inOrder: true);
    }

    public function test_due_condition_filter_excludes_cancelled_and_paid_from_overdue_and_renders_indicator(): void
    {
        $this->travelTo('2026-11-15 10:00:00');

        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'RSUD Gianyar',
            'type' => 'Rumah Sakit',
        ]);

        $unpaidOverdue = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-DUE-UNPAID',
            'issue_date' => '2026-10-10',
            'due_date' => '2026-11-10',
            'status' => 'Unpaid',
            'subtotal_amount' => 1000000,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
            'paid_amount' => 0,
        ]);

        $partialOverdue = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-DUE-PARTIAL',
            'issue_date' => '2026-10-12',
            'due_date' => '2026-11-12',
            'status' => 'Partial',
            'subtotal_amount' => 2000000,
            'tax_amount' => 220000,
            'total_amount' => 2220000,
            'paid_amount' => 500000,
        ]);

        $cancelledPastDue = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-DUE-CANCEL',
            'issue_date' => '2026-10-05',
            'due_date' => '2026-11-05',
            'status' => 'Cancelled',
            'subtotal_amount' => 3000000,
            'tax_amount' => 330000,
            'total_amount' => 3330000,
            'paid_amount' => 0,
        ]);

        $paidPastDue = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-DUE-PAID',
            'issue_date' => '2026-10-01',
            'due_date' => '2026-11-01',
            'status' => 'Paid',
            'subtotal_amount' => 1500000,
            'tax_amount' => 165000,
            'total_amount' => 1665000,
            'paid_amount' => 1665000,
        ]);

        $unpaidNextMonth = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-DUE-DEC',
            'issue_date' => '2026-11-10',
            'due_date' => '2026-12-10',
            'status' => 'Unpaid',
            'subtotal_amount' => 2500000,
            'tax_amount' => 275000,
            'total_amount' => 2775000,
            'paid_amount' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(LaporanPenjualan::class)
            ->filterTable('due_condition', ['condition' => 'overdue'])
            ->assertCanSeeTableRecords([$unpaidOverdue, $partialOverdue])
            ->assertCanNotSeeTableRecords([$cancelledPastDue, $paidPastDue, $unpaidNextMonth])
            ->assertSee('Status Jatuh Tempo: Lewat Jatuh Tempo (Overdue)')
            ->filterTable('due_condition', ['condition' => 'due_this_month'])
            ->assertCanSeeTableRecords([$unpaidOverdue, $partialOverdue, $paidPastDue])
            ->assertCanNotSeeTableRecords([$cancelledPastDue, $unpaidNextMonth])
            ->assertSee('Status Jatuh Tempo: Jatuh Tempo Bulan Ini');
    }

    public function test_exports_filtered_csv_respecting_active_sort_and_same_date_tie_breaker(): void
    {
        $this->travelTo('2026-11-15 14:30:00');

        $user = User::factory()->create();
        $client = Client::create([
            'name' => 'RS Bali Mandara',
            'type' => 'Rumah Sakit',
        ]);

        $sameDateHigherNet = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-CSV-HIGH',
            'division' => 'Diagnostic',
            'sales' => 'Budi',
            'issue_date' => '2026-11-05',
            'due_date' => '2026-12-05',
            'status' => 'Paid',
            'subtotal_amount' => 3000000,
            'tax_amount' => 330000,
            'total_amount' => 3330000,
            'paid_amount' => 3330000,
        ]);

        $sameDateLowerNet = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-CSV-LOW',
            'division' => 'Microbiology',
            'sales' => 'Sari',
            'issue_date' => '2026-11-05',
            'due_date' => '2026-12-05',
            'status' => 'Paid',
            'subtotal_amount' => 1000000,
            'tax_amount' => 110000,
            'total_amount' => 1110000,
            'paid_amount' => 1110000,
        ]);

        $excludedCancelled = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => 'INV-CSV-EXCLUDED',
            'issue_date' => '2026-11-05',
            'due_date' => '2026-12-05',
            'status' => 'Cancelled',
            'subtotal_amount' => 5000000,
            'tax_amount' => 550000,
            'total_amount' => 5550000,
            'paid_amount' => 0,
        ]);

        $component = Livewire::actingAs($user)
            ->test(LaporanPenjualan::class)
            ->filterTable('status', 'Paid')
            ->sortTable('total_amount', 'asc')
            ->callTableAction('export_csv');

        $component->assertFileDownloaded('Rekap-Penjualan-20261115_143000.csv');

        $csvContent = base64_decode((string) data_get($component->effects, 'download.content'));
        $lowPosition = strpos($csvContent, 'INV-CSV-LOW');
        $highPosition = strpos($csvContent, 'INV-CSV-HIGH');

        $this->assertNotFalse($lowPosition);
        $this->assertNotFalse($highPosition);
        $this->assertLessThan($highPosition, $lowPosition);
        $this->assertStringNotContainsString('INV-CSV-EXCLUDED', $csvContent);
    }

    public function test_csv_export_neutralizes_formula_injection_and_modal_handles_zero_tax_and_quotes_safely(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'name' => '=HYPERLINK("https://evil.example","Klik")',
            'type' => 'Rumah Sakit',
        ]);

        $invoice = OutboundInvoice::create([
            'client_id' => $client->id,
            'invoice_number' => "INV'QUOTE-01",
            'sales' => '+SUM(1,2)',
            'division' => null,
            'issue_date' => '2026-11-05',
            'due_date' => '2026-12-05',
            'status' => 'Unpaid',
            'subtotal_amount' => 1000000,
            'tax_amount' => 0,
            'total_amount' => 1000000,
            'paid_amount' => 0,
        ]);
        OutboundInvoiceItem::create([
            'outbound_invoice_id' => $invoice->id,
            'item_name' => 'Alat Bebas PPN',
            'quantity' => 1,
            'unit' => 'Unit',
            'unit_price' => 1000000,
            'discount_percentage' => 0,
            'subtotal' => 1000000,
        ]);

        $component = Livewire::actingAs($user)
            ->test(LaporanPenjualan::class)
            ->callTableAction('export_csv');

        $csvContent = base64_decode((string) data_get($component->effects, 'download.content'));
        $this->assertStringContainsString("'=HYPERLINK(", $csvContent);
        $this->assertStringContainsString("'+SUM(1,2)", $csvContent);
        $this->assertStringContainsString(',-,', $csvContent);

        $modalHtml = view('filament.tables.columns.invoice-items-modal', [
            'record' => $invoice->load('items'),
        ])->render();

        $this->assertStringNotContainsString('Rp 110.000', $modalHtml);
        $this->assertStringContainsString('PPN (0%):', $modalHtml);
        $this->assertStringNotContainsString("writeText('INV'QUOTE-01')", $modalHtml);
    }
}
