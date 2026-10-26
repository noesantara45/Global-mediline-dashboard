@php
    $record = $getRecord();
    $items = $record->items ?? collect();
@endphp

<div class="p-3 my-2 rounded-xl border border-primary-200/60 bg-primary-50/20 dark:border-primary-800/40 dark:bg-gray-800/60 shadow-sm">
    <div class="flex items-center justify-between pb-2 mb-2.5 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900/50 dark:text-primary-300 text-xs font-bold">
                ✓
            </span>
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                Detail Isi Faktur: <span class="font-bold text-primary-700 dark:text-primary-400 font-mono">{{ $record->invoice_number }}</span>
                @if($record->client)
                    <span class="text-gray-400 font-normal">({{ $record->client->name }})</span>
                @endif
            </span>
        </div>
        <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
            @if($record->sales)
                <span>Sales: <strong class="text-gray-700 dark:text-gray-200">{{ $record->sales }}</strong></span>
            @endif
            @if($record->division)
                <span>Divisi: <strong class="text-gray-700 dark:text-gray-200">{{ $record->division }}</strong></span>
            @endif
            <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium">
                {{ $items->count() }} Jenis Barang
            </span>
        </div>
    </div>

    @if($items->isNotEmpty())
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase font-semibold text-[11px] border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-3 py-2 text-center w-12">No</th>
                        <th class="px-3 py-2">Nama Barang</th>
                        <th class="px-3 py-2">Merek</th>
                        <th class="px-3 py-2 text-center">SN / Batch</th>
                        <th class="px-3 py-2 text-center">Qtt</th>
                        <th class="px-3 py-2 text-center">Satuan</th>
                        <th class="px-3 py-2 text-right">Harga Jual</th>
                        <th class="px-3 py-2 text-right">Diskon</th>
                        <th class="px-3 py-2 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($items as $index => $item)
                        @php
                            $grossItem = (float) $item->quantity * (float) $item->unit_price;
                            $discountAmount = max(0, $grossItem - (float) $item->subtotal);
                        @endphp
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition-colors">
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
                                @if($discountAmount > 0)
                                    <span class="text-amber-600 dark:text-amber-400">
                                        Rp {{ number_format($discountAmount, 0, ',', '.') }}
                                        @if($item->discount_percentage > 0)
                                            <span class="text-[10px]">({{ floatval($item->discount_percentage) }}%)</span>
                                        @endif
                                    </span>
                                @else
                                    <span>-</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right font-mono font-bold text-gray-900 dark:text-white">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50/90 dark:bg-gray-800/90 font-bold border-t border-gray-200 dark:border-gray-700">
                    <tr>
                        <td colspan="8" class="px-3 py-2 text-right text-gray-700 dark:text-gray-300 uppercase text-[11px]">Subtotal Bersih Seluruh Item:</td>
                        <td class="px-3 py-2 text-right font-mono text-primary-600 dark:text-primary-400 text-xs font-bold">
                            Rp {{ number_format($items->sum('subtotal'), 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <p class="text-xs text-gray-500 italic py-2">Tidak ada rincian item barang pada faktur ini.</p>
    @endif
</div>
