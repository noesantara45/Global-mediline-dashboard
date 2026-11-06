<x-filament-panels::page>
    <div class="laporan-pembelian-wrapper space-y-3">
        <style>
            /* Fixed/sticky filter header + bounded scrollable table body */
            .laporan-pembelian-wrapper .fi-ta-ctn {
                overflow: visible !important;
            }
            .laporan-pembelian-wrapper .fi-ta-header-ctn {
                position: sticky;
                top: 4rem;
                z-index: 15;
                background-color: #F4F9F6;
                border-bottom: 1px solid #d7e8de;
                border-top-left-radius: 0.75rem;
                border-top-right-radius: 0.75rem;
                box-shadow: 0 2px 6px -2px rgb(15 23 42 / 0.06);
            }
            .laporan-pembelian-wrapper .fi-ta-compact-toolbar {
                background-color: #ffffff;
                border-top-left-radius: 0.75rem;
                border-top-right-radius: 0.75rem;
            }
            .dark .laporan-pembelian-wrapper .fi-ta-header-ctn {
                background-color: #111827;
                border-bottom-color: rgb(255 255 255 / 0.1);
                box-shadow: 0 2px 8px -2px rgb(0 0 0 / 0.35);
            }
            .dark .laporan-pembelian-wrapper .fi-ta-compact-toolbar {
                background-color: #111827;
            }

            /* Compact filter form & toolbar controls */
            .laporan-pembelian-wrapper .fi-ta-filters-compact .fi-fo-component-ctn {
                gap: 0.5rem !important;
            }
            .laporan-pembelian-wrapper .fi-ta-filters-compact .fi-fo-field-wrp > div {
                row-gap: 0.2rem !important;
            }
            .laporan-pembelian-wrapper .fi-ta-filters-compact .fi-fo-field-wrp-label span {
                font-size: 0.6875rem !important; /* 11px */
                line-height: 0.95rem !important;
                font-weight: 600 !important;
                white-space: nowrap;
            }
            .laporan-pembelian-wrapper .fi-ta-filters-compact .fi-input-wrp,
            .laporan-pembelian-wrapper .fi-ta-compact-toolbar .fi-input-wrp {
                min-height: 1.875rem !important; /* 30px */
                border-radius: 0.425rem !important;
            }
            .laporan-pembelian-wrapper .fi-ta-filters-compact .fi-input,
            .laporan-pembelian-wrapper .fi-ta-compact-toolbar .fi-input {
                height: 1.875rem !important;
                padding-top: 0.2rem !important;
                padding-bottom: 0.2rem !important;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
                font-size: 0.75rem !important; /* 12px */
                line-height: 1.125rem !important;
            }
            .laporan-pembelian-wrapper .fi-ta-filters-compact select.fi-select-input {
                height: 1.875rem !important;
                padding-top: 0.2rem !important;
                padding-bottom: 0.2rem !important;
                padding-inline-start: 0.5rem !important;
                padding-inline-end: 1.55rem !important;
                font-size: 0.75rem !important;
                line-height: 1.125rem !important;
            }
            .laporan-pembelian-wrapper .fi-ta-filters-compact .choices__inner {
                min-height: 1.875rem !important;
                padding: 0.175rem 1.55rem 0.175rem 0.5rem !important;
                font-size: 0.75rem !important;
                line-height: 1.125rem !important;
            }
            .laporan-pembelian-wrapper .fi-ta-filters-compact .choices__list--single {
                padding: 0 !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .laporan-pembelian-wrapper .fi-ta-filters-compact .choices__list--dropdown .choices__item,
            .laporan-pembelian-wrapper .fi-ta-filters-compact .choices__list[aria-expanded] .choices__item {
                padding: 0.35rem 0.6rem !important;
                font-size: 0.75rem !important;
            }
            .laporan-pembelian-wrapper .fi-ta-compact-toolbar .fi-btn {
                padding: 0.325rem 0.7rem !important;
                font-size: 0.75rem !important;
                line-height: 1.125rem !important;
            }
            .laporan-pembelian-wrapper .fi-ta-compact-toolbar .fi-btn-icon {
                width: 0.95rem !important;
                height: 0.95rem !important;
            }
            .laporan-pembelian-wrapper .fi-ta-header-ctn .fi-ta-filter-indicators {
                padding-top: 0.35rem !important;
                padding-bottom: 0.35rem !important;
                padding-left: 0.875rem !important;
                padding-right: 0.875rem !important;
            }

            /* Compact horizontal spreadsheet styling */
            .laporan-pembelian-wrapper .fi-ta-table {
                font-size: 0.8125rem; /* 13px */
            }
            .laporan-pembelian-wrapper .fi-ta-content {
                max-height: calc(100vh - 14.5rem);
                overflow-x: auto !important;
                overflow-y: auto !important;
            }
            .laporan-pembelian-wrapper .fi-ta-content > .fi-ta-table > thead {
                position: sticky;
                top: 0;
                z-index: 10;
            }
            .laporan-pembelian-wrapper .fi-ta-content > .fi-ta-table > thead > tr {
                background-color: #f8fafc !important;
                box-shadow: 0 1px 0 0 #e2e8f0;
            }
            .dark .laporan-pembelian-wrapper .fi-ta-content > .fi-ta-table > thead > tr {
                background-color: #1e293b !important;
                box-shadow: 0 1px 0 0 rgb(255 255 255 / 0.1);
            }
            .laporan-pembelian-wrapper .fi-ta-table th {
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
                padding-left: 0.6rem !important;
                padding-right: 0.6rem !important;
                white-space: nowrap !important;
                font-size: 0.72rem !important;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }
            .laporan-pembelian-wrapper .fi-ta-table td {
                padding-top: 0.35rem !important;
                padding-bottom: 0.35rem !important;
                padding-left: 0.6rem !important;
                padding-right: 0.6rem !important;
                white-space: nowrap !important;
            }
            .laporan-pembelian-wrapper .fi-ta-row {
                height: 2.35rem !important; /* Slim rows like desktop ERP */
            }
            .laporan-pembelian-wrapper .fi-ta-expanded-row {
                background-color: #F4F9F6 !important;
            }
            .dark .laporan-pembelian-wrapper .fi-ta-expanded-row {
                background-color: rgb(15 23 42 / 0.7) !important;
            }
            .laporan-pembelian-wrapper .fi-ta-expanded-row > td {
                padding-top: 0.6rem !important;
                padding-bottom: 0.6rem !important;
                padding-left: 2rem !important;
                padding-right: 1.5rem !important;
                white-space: normal !important;
            }
            .laporan-pembelian-wrapper .fi-ta-expanded-row table {
                box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.08);
            }
            .laporan-pembelian-wrapper .fi-ta-expanded-row table th {
                background-color: #E7F4EC !important;
                color: #1F4E34 !important;
                font-size: 0.7rem !important;
                padding: 0.4rem 0.6rem !important;
                font-weight: 600 !important;
            }
            .dark .laporan-pembelian-wrapper .fi-ta-expanded-row table th {
                background-color: #1e293b !important;
                color: #e2e8f0 !important;
            }
            .laporan-pembelian-wrapper .fi-ta-expanded-row table td {
                padding: 0.35rem 0.6rem !important;
                font-size: 0.75rem !important;
                white-space: nowrap !important;
            }
        </style>

        <script>
            window.toggleInvoiceDetail = function (id) {
                const row = document.getElementById('invoice-detail-' + id);
                const icon = document.getElementById('invoice-toggle-icon-' + id);
                if (!row) {
                    console.error('Row not found: invoice-detail-' + id);
                    return;
                }
                const isHidden = row.classList.contains('hidden') || row.style.display === 'none';
                if (isHidden) {
                    row.classList.remove('hidden');
                    row.style.display = 'table-row';
                    if (icon) icon.innerHTML = '&minus;';
                } else {
                    row.classList.add('hidden');
                    row.style.display = 'none';
                    if (icon) icon.innerHTML = '+';
                }
            };
        </script>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
