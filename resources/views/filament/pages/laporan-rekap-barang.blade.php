<x-filament-panels::page>
    <div class="laporan-rekap-barang-wrapper space-y-2.5">
        <style>
            /* Slim division tabs bar */
            .laporan-rekap-barang-wrapper .gm-division-tabs .fi-tabs {
                padding: 0.25rem !important;
                gap: 0.25rem !important;
                border-radius: 0.625rem !important;
            }
            .laporan-rekap-barang-wrapper .gm-division-tabs .fi-tabs-item {
                padding: 0.3rem 0.65rem !important;
                font-size: 0.75rem !important;
                line-height: 1.1rem !important;
            }
            .laporan-rekap-barang-wrapper .gm-rekap-create-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                height: 1.875rem;
                padding: 0 0.75rem;
                border-radius: 0.425rem;
                font-size: 0.75rem;
                font-weight: 600;
                background-color: #2D8A56;
                color: #ffffff;
                transition: background-color 150ms ease;
            }
            .laporan-rekap-barang-wrapper .gm-rekap-create-btn:hover {
                background-color: #1F4E34;
            }

            /* Fixed/sticky filter header + bounded scrollable table body */
            .laporan-rekap-barang-wrapper .fi-ta-ctn {
                overflow: visible !important;
            }
            .laporan-rekap-barang-wrapper .fi-ta-header-ctn {
                display: flex;
                flex-direction: column;
                position: sticky;
                top: 4rem;
                z-index: 15;
                background-color: #F4F9F6;
                border-bottom: 1px solid #d7e8de;
                border-top-left-radius: 0.75rem;
                border-top-right-radius: 0.75rem;
                box-shadow: 0 2px 6px -2px rgb(15 23 42 / 0.06);
            }
            .dark .laporan-rekap-barang-wrapper .fi-ta-header-ctn {
                background-color: #111827;
                border-bottom-color: rgb(255 255 255 / 0.1);
                box-shadow: 0 2px 8px -2px rgb(0 0 0 / 0.35);
            }

            /* Place compact toolbar (Filters + Reset + Search + Tambah Produk) at top */
            .laporan-rekap-barang-wrapper .fi-ta-header-toolbar {
                order: -1;
                padding: 0.5rem 1rem !important;
                background-color: #ffffff;
                border-top-width: 0 !important;
                border-bottom: 1px solid #e2e8f0 !important;
                border-top-left-radius: 0.75rem;
                border-top-right-radius: 0.75rem;
            }
            .laporan-rekap-barang-wrapper .fi-ta-header-toolbar .ms-auto {
                gap: 0.5rem !important;
            }
            .dark .laporan-rekap-barang-wrapper .fi-ta-header-toolbar {
                background-color: #111827;
                border-bottom-color: rgb(255 255 255 / 0.1) !important;
            }
            .laporan-rekap-barang-wrapper .fi-ta-filters-above-content-ctn {
                padding: 0.5rem 1rem !important;
                border-top-width: 0 !important;
            }
            .laporan-rekap-barang-wrapper .fi-ta-filters > div:first-child {
                display: none !important;
            }
            .laporan-rekap-barang-wrapper .fi-ta-filters {
                row-gap: 0.25rem !important;
            }

            /* Uniform 30px single-row filter controls */
            .laporan-rekap-barang-wrapper .fi-ta-filters .fi-fo-component-ctn {
                gap: 0.5rem !important;
            }
            .laporan-rekap-barang-wrapper .fi-ta-filters .fi-fo-field-wrp > div {
                row-gap: 0.2rem !important;
            }
            .laporan-rekap-barang-wrapper .fi-ta-filters .fi-fo-field-wrp-label span {
                font-size: 0.6875rem !important; /* 11px */
                line-height: 0.95rem !important;
                font-weight: 600 !important;
                white-space: nowrap;
            }
            .laporan-rekap-barang-wrapper .fi-ta-filters .fi-input-wrp,
            .laporan-rekap-barang-wrapper .fi-ta-header-toolbar .fi-input-wrp {
                height: 1.875rem !important;
                min-height: 1.875rem !important;
                max-height: 1.875rem !important;
                border-radius: 0.425rem !important;
                align-items: center;
            }
            .laporan-rekap-barang-wrapper .fi-ta-filters .fi-input,
            .laporan-rekap-barang-wrapper .fi-ta-header-toolbar .fi-input {
                height: 1.875rem !important;
                padding-top: 0.2rem !important;
                padding-bottom: 0.2rem !important;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
                font-size: 0.75rem !important; /* 12px */
                line-height: 1.125rem !important;
            }
            .laporan-rekap-barang-wrapper .fi-ta-filters select.fi-select-input {
                height: 1.875rem !important;
                padding-top: 0.2rem !important;
                padding-bottom: 0.2rem !important;
                padding-inline-start: 0.5rem !important;
                padding-inline-end: 1.55rem !important;
                font-size: 0.75rem !important;
                line-height: 1.125rem !important;
            }
            .laporan-rekap-barang-wrapper .fi-ta-filters .choices {
                margin-bottom: 0 !important;
                width: 100%;
            }
            .laporan-rekap-barang-wrapper .fi-ta-filters .choices__inner {
                height: 1.875rem !important;
                min-height: 1.875rem !important;
                max-height: 1.875rem !important;
                display: flex !important;
                align-items: center !important;
                padding: 0 1.55rem 0 0.5rem !important;
                font-size: 0.75rem !important;
                line-height: 1.125rem !important;
                overflow: hidden !important;
            }
            .laporan-rekap-barang-wrapper .fi-ta-filters .choices__list--single {
                padding: 0 !important;
                width: 100%;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
            .laporan-rekap-barang-wrapper .fi-ta-filters .choices__list--single .choices__item {
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                word-break: normal !important;
            }
            .laporan-rekap-barang-wrapper .fi-ta-filters .choices__list--dropdown .choices__item,
            .laporan-rekap-barang-wrapper .fi-ta-filters .choices__list[aria-expanded] .choices__item {
                padding: 0.35rem 0.6rem !important;
                font-size: 0.75rem !important;
            }
            .laporan-rekap-barang-wrapper .fi-ta-filter-indicators {
                padding-top: 0.35rem !important;
                padding-bottom: 0.35rem !important;
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            /* Compact ERP table & sticky column headers */
            .laporan-rekap-barang-wrapper .fi-ta-table {
                font-size: 0.8125rem; /* 13px */
            }
            .laporan-rekap-barang-wrapper .fi-ta-content {
                max-height: calc(100vh - 16.5rem);
                overflow-x: auto !important;
                overflow-y: auto !important;
            }
            .laporan-rekap-barang-wrapper .fi-ta-content > .fi-ta-table > thead {
                position: sticky;
                top: 0;
                z-index: 10;
            }
            .laporan-rekap-barang-wrapper .fi-ta-content > .fi-ta-table > thead > tr {
                background-color: #f8fafc !important;
                box-shadow: 0 1px 0 0 #e2e8f0;
            }
            .dark .laporan-rekap-barang-wrapper .fi-ta-content > .fi-ta-table > thead > tr {
                background-color: #1e293b !important;
                box-shadow: 0 1px 0 0 rgb(255 255 255 / 0.1);
            }
            .laporan-rekap-barang-wrapper .fi-ta-table th {
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
                padding-left: 0.6rem !important;
                padding-right: 0.6rem !important;
                white-space: nowrap !important;
                font-size: 0.72rem !important;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }
            .laporan-rekap-barang-wrapper .fi-ta-table td {
                padding-top: 0.35rem !important;
                padding-bottom: 0.35rem !important;
                padding-left: 0.6rem !important;
                padding-right: 0.6rem !important;
                white-space: nowrap !important;
            }
        </style>

        <div class="gm-division-tabs flex overflow-x-auto">
            <x-filament::tabs label="Filter Divisi Produk">
                @foreach ($this->getDivisionTabs() as $divisionKey => $tab)
                    <x-filament::tabs.item
                        :active="$this->activeDivision === $divisionKey"
                        :badge="$tab['count']"
                        :badge-color="$tab['color']"
                        wire:click="setDivision('{{ $divisionKey }}')"
                    >
                        {{ $tab['label'] }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
