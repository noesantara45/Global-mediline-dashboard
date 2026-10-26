@php
    $items = $record->items ?? collect();
    $grossTotal = (float) $record->gross_sale;
    $discTotal = (float) $record->total_discount;
    $subtotal = (float) $record->subtotal_amount > 0 ? (float) $record->subtotal_amount : (float) $items->sum('subtotal');
    $tax = (float) $record->tax_amount > 0 ? (float) $record->tax_amount : round($subtotal * 0.11);
    $netTotal = (float) $record->total_amount > 0 ? (float) $record->total_amount : ($subtotal + $tax);
@endphp

<div class="space-y-4">
    <!-- No Faktur & Copy Banner -->
    <div class="flex items-center justify-between bg-primary-50/60 dark:bg-primary-950/40 border border-primary-200 dark:border-primary-800 rounded-lg px-3.5 py-2">
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500 dark:text-gray-400">No Faktur:</span>
            <span class="text-sm font-bold font-mono text-primary-700 dark:text-primary-300">{{ $record->invoice_number }}</span>
        </div>
        <button
            type="button"
            x-data="{ copied: false }"
            x-on:click="
                window.navigator.clipboard.writeText('{{ $record->invoice_number }}');
                copied = true;
                setTimeout(() => copied = false, 2000);
            "
            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
        >
            <x-filament::icon icon="heroicon-m-clipboard-document" class="w-3.5 h-3.5" />
            <span x-text="copied ? 'Tersalin!' : 'Salin'">Salin</span>
        </button>
    </div>

    <!-- Header Ringkasan Faktur -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/60 text-xs">
        <div>
            <div class="text-gray-500 dark:text-gray-400">Customer</div>
            <div class="font-bold text-gray-900 dark:text-white mt-0.5">{{ $record->client?->name ?? '-' }}</div>
            <div class="text-gray-500 text-[11px]">{{ $record->client?->phone ?? '' }}</div>
        </div>
        <div>
            <div class="text-gray-500 dark:text-gray-400">Sales & Divisi</div>
            <div class="font-semibold text-gray-800 dark:text-gray-200 mt-0.5">
                {{ $record->sales ?? '-' }} / {{ $record->division ?? '-' }}
            </div>
            <div class="text-gray-500 text-[11px]">Status: 
                <span class="font-semibold {{ $record->status === 'Paid' ? 'text-green-600' : ($record->status === 'Unpaid' ? 'text-red-600' : 'text-amber-600') }}">
                    {{ $record->status }}
                </span>
            </div>
        </div>
        <div>
            <div class="text-gray-500 dark:text-gray-400">Tanggal Faktur</div>
            <div class="font-semibold text-gray-800 dark:text-gray-200 mt-0.5">
                {{ \Carbon\Carbon::parse($record->issue_date)->format('d M Y') }}
            </div>
            <div class="text-gray-500 text-[11px]">Jatuh Tempo: {{ \Carbon\Carbon::parse($record->due_date)->format('d M Y') }}</div>
        </div>
        <div>
            <div class="text-gray-500 dark:text-gray-400">Total Net Sale</div>
            <div class="text-sm font-bold text-primary-600 dark:text-primary-400 font-mono mt-0.5">
                Rp {{ number_format($netTotal, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-gray-500">PPN: Rp {{ number_format($tax, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Tabel Daftar Rincian Barang -->
    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-xs">
        <table class="w-full text-left text-xs">
            <thead class="bg-gray-100/90 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase font-semibold text-[11px] border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-3 py-2.5 text-center w-10">No</th>
                    <th class="px-3 py-2.5">Nama Barang</th>
                    <th class="px-3 py-2.5">Merek</th>
                    <th class="px-3 py-2.5 text-center">SN / Batch</th>
                    <th class="px-3 py-2.5 text-center w-14">Qtt</th>
                    <th class="px-3 py-2.5 text-center w-16">Satuan</th>
                    <th class="px-3 py-2.5 text-right">Harga Jual</th>
                    <th class="px-3 py-2.5 text-right">Diskon</th>
                    <th class="px-3 py-2.5 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($items as $index => $item)
                    @php
                        $grossItem = (float) $item->quantity * (float) $item->unit_price;
                        $discItem = max(0, $grossItem - (float) $item->subtotal);
                    @endphp
                    <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition-colors">
                        <td class="px-3 py-2 text-center text-gray-400 font-medium">{{ $index + 1 }}</td>
                        <td class="px-3 py-2 font-semibold text-gray-900 dark:text-white">{{ $item->item_name }}</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                {{ $item->brand ?? '-' }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-center text-gray-400 font-mono text-[11px]">-</td>
                        <td class="px-3 py-2 text-center font-bold text-gray-900 dark:text-white">{{ $item->quantity }}</td>
                        <td class="px-3 py-2 text-center text-gray-500 dark:text-gray-400">{{ $item->unit ?: 'Pcs' }}</td>
                        <td class="px-3 py-2 text-right font-mono text-gray-700 dark:text-gray-300">
                            Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                        </td>
                        <td class="px-3 py-2 text-right font-mono text-gray-600 dark:text-gray-400">
                            @if($discItem > 0)
                                <span class="text-amber-600 dark:text-amber-400 font-medium">
                                    Rp {{ number_format($discItem, 0, ',', '.') }}
                                    @if($item->discount_percentage > 0)
                                        <span class="text-[10px]">({{ floatval($item->discount_percentage) }}%)</span>
                                    @endif
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right font-mono font-bold text-gray-900 dark:text-white">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-4 text-center text-gray-500 italic">
                            Tidak ada rincian item barang pada faktur ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-800/90 font-semibold border-t border-gray-200 dark:border-gray-700 text-xs">
                <tr>
                    <td colspan="8" class="px-3 py-2 text-right text-gray-600 dark:text-gray-400 uppercase text-[11px]">Gross Sale (Sebelum Diskon):</td>
                    <td class="px-3 py-2 text-right font-mono text-gray-800 dark:text-gray-200">
                        Rp {{ number_format($grossTotal, 0, ',', '.') }}
                    </td>
                </tr>
                @if($discTotal > 0)
                <tr>
                    <td colspan="8" class="px-3 py-2 text-right text-amber-600 dark:text-amber-400 uppercase text-[11px]">Total Potongan Diskon (Disk. 1):</td>
                    <td class="px-3 py-2 text-right font-mono text-amber-600 dark:text-amber-400 font-semibold">
                        - Rp {{ number_format($discTotal, 0, ',', '.') }}
                    </td>
                </tr>
                @endif
                <tr>
                    <td colspan="8" class="px-3 py-2 text-right text-gray-600 dark:text-gray-400 uppercase text-[11px]">Sub Total (DPP):</td>
                    <td class="px-3 py-2 text-right font-mono text-gray-800 dark:text-gray-200">
                        Rp {{ number_format($subtotal, 0, ',', '.') }}
                    </td>
                </tr>
                <tr>
                    <td colspan="8" class="px-3 py-2 text-right text-gray-600 dark:text-gray-400 uppercase text-[11px]">PPN (11%):</td>
                    <td class="px-3 py-2 text-right font-mono text-gray-800 dark:text-gray-200">
                        Rp {{ number_format($tax, 0, ',', '.') }}
                    </td>
                </tr>
                <tr class="bg-primary-50/60 dark:bg-primary-950/30 text-primary-900 dark:text-primary-100 font-bold border-t border-primary-200 dark:border-primary-800">
                    <td colspan="8" class="px-3 py-2.5 text-right uppercase text-xs">Total Tagihan (Net Sale):</td>
                    <td class="px-3 py-2.5 text-right font-mono text-primary-700 dark:text-primary-400 text-sm">
                        Rp {{ number_format($netTotal, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
