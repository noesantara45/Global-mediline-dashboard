<x-filament-panels::page>
    <div class="laporan-detail-penjualan-wrapper space-y-3">
        <style>
            /* Fixed/sticky filter header + bounded scrollable table body */
            .laporan-detail-penjualan-wrapper .fi-ta-ctn {
                overflow: visible !important;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-header-ctn {
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
            .dark .laporan-detail-penjualan-wrapper .fi-ta-header-ctn {
                background-color: #111827;
                border-bottom-color: rgb(255 255 255 / 0.1);
                box-shadow: 0 2px 8px -2px rgb(0 0 0 / 0.35);
            }

            /* Place compact toolbar (Filters + Reset + Search) at top, filter grid directly below */
            .laporan-detail-penjualan-wrapper .fi-ta-header-toolbar {
                order: -1;
                padding: 0.5rem 1rem !important;
                background-color: #ffffff;
                border-top-width: 0 !important;
                border-bottom: 1px solid #e2e8f0 !important;
                border-top-left-radius: 0.75rem;
                border-top-right-radius: 0.75rem;
            }
            .dark .laporan-detail-penjualan-wrapper .fi-ta-header-toolbar {
                background-color: #111827;
                border-bottom-color: rgb(255 255 255 / 0.1) !important;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-filters-above-content-ctn {
                padding: 0.5rem 1rem !important;
                border-top-width: 0 !important;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-filters > div:first-child {
                display: none !important;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-filters {
                row-gap: 0.25rem !important;
            }

            /* Compact filter form & toolbar controls */
            .laporan-detail-penjualan-wrapper .fi-ta-filters .fi-fo-component-ctn {
                gap: 0.5rem !important;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-filters .fi-fo-field-wrp > div {
                row-gap: 0.2rem !important;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-filters .fi-fo-field-wrp-label span {
                font-size: 0.6875rem !important; /* 11px */
                line-height: 0.95rem !important;
                font-weight: 600 !important;
                white-space: nowrap;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-filters .fi-input-wrp,
            .laporan-detail-penjualan-wrapper .fi-ta-header-toolbar .fi-input-wrp {
                min-height: 1.875rem !important; /* 30px */
                border-radius: 0.425rem !important;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-filters .fi-input,
            .laporan-detail-penjualan-wrapper .fi-ta-header-toolbar .fi-input {
                height: 1.875rem !important;
                padding-top: 0.2rem !important;
                padding-bottom: 0.2rem !important;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
                font-size: 0.75rem !important; /* 12px */
                line-height: 1.125rem !important;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-filters select.fi-select-input {
                height: 1.875rem !important;
                padding-top: 0.2rem !important;
                padding-bottom: 0.2rem !important;
                padding-inline-start: 0.5rem !important;
                padding-inline-end: 1.55rem !important;
                font-size: 0.75rem !important;
                line-height: 1.125rem !important;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-filters .choices__inner {
                min-height: 1.875rem !important;
                padding: 0.175rem 1.55rem 0.175rem 0.5rem !important;
                font-size: 0.75rem !important;
                line-height: 1.125rem !important;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-filters .choices__list--single {
                padding: 0 !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-filters .choices__list--dropdown .choices__item,
            .laporan-detail-penjualan-wrapper .fi-ta-filters .choices__list[aria-expanded] .choices__item {
                padding: 0.35rem 0.6rem !important;
                font-size: 0.75rem !important;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-filter-indicators {
                padding-top: 0.35rem !important;
                padding-bottom: 0.35rem !important;
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }

            /* Compact horizontal & vertical spreadsheet styling */
            .laporan-detail-penjualan-wrapper .fi-ta-table {
                font-size: 0.8125rem; /* 13px */
            }
            .laporan-detail-penjualan-wrapper .fi-ta-content {
                max-height: calc(100vh - 14.5rem);
                overflow-x: auto !important;
                overflow-y: auto !important;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-content > .fi-ta-table > thead {
                position: sticky;
                top: 0;
                z-index: 10;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-content > .fi-ta-table > thead > tr {
                background-color: #f8fafc !important;
                box-shadow: 0 1px 0 0 #e2e8f0;
            }
            .dark .laporan-detail-penjualan-wrapper .fi-ta-content > .fi-ta-table > thead > tr {
                background-color: #1e293b !important;
                box-shadow: 0 1px 0 0 rgb(255 255 255 / 0.1);
            }
            .laporan-detail-penjualan-wrapper .fi-ta-table th {
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
                padding-left: 0.6rem !important;
                padding-right: 0.6rem !important;
                white-space: nowrap !important;
                font-size: 0.72rem !important;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-table td {
                padding-top: 0.35rem !important;
                padding-bottom: 0.35rem !important;
                padding-left: 0.6rem !important;
                padding-right: 0.6rem !important;
                white-space: nowrap !important;
            }
            .laporan-detail-penjualan-wrapper .fi-ta-row {
                height: 2.35rem !important;
            }
        </style>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
