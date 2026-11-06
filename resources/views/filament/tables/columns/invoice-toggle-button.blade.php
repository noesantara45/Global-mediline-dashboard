@php
    $recordId = $getRecord()->getKey();
@endphp

<button
    type="button"
    class="inline-flex items-center gap-2 cursor-pointer select-none group/toggle"
    style="background: none; border: 0; padding: 0; text-align: left;"
    title="Klik untuk melihat / menutup rincian barang"
    onclick="
        event.stopPropagation();
        var row = document.getElementById('invoice-detail-{{ $recordId }}');
        var icon = document.getElementById('invoice-toggle-icon-{{ $recordId }}');
        if (!row) { return; }
        var open = row.style.display !== 'none';
        row.style.display = open ? 'none' : 'table-row';
        if (icon) { icon.textContent = open ? '+' : '\u2212'; }
    "
>
    <span
        id="invoice-toggle-icon-{{ $recordId }}"
        class="inline-flex items-center justify-center font-bold"
        style="width: 16px; height: 16px; border: 1px solid #2D8A56; border-radius: 3px; font-size: 11px; line-height: 1; background: #EFF8F2; color: #1F4E34;"
    >+</span>
    <span class="font-bold text-primary-600 dark:text-primary-400 group-hover/toggle:underline">
        {{ $getState() }}
    </span>
</button>
