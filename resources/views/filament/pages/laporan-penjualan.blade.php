<x-filament-panels::page>
    <div class="laporan-penjualan-wrapper space-y-4">
        <style>
            /* Compact horizontal spreadsheet styling */
            .laporan-penjualan-wrapper .fi-ta-table {
                font-size: 0.8125rem; /* 13px */
            }
            .laporan-penjualan-wrapper .fi-ta-table th {
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
                padding-left: 0.6rem !important;
                padding-right: 0.6rem !important;
                white-space: nowrap !important;
                font-size: 0.72rem !important;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }
            .laporan-penjualan-wrapper .fi-ta-table td {
                padding-top: 0.35rem !important;
                padding-bottom: 0.35rem !important;
                padding-left: 0.6rem !important;
                padding-right: 0.6rem !important;
                white-space: nowrap !important;
            }
            .laporan-penjualan-wrapper .fi-ta-row {
                height: 2.35rem !important; /* Slim rows like desktop ERP */
            }
            .laporan-penjualan-wrapper .fi-ta-content {
                overflow-x: auto !important;
            }
            .laporan-penjualan-wrapper .fi-ta-expanded-row {
                background-color: rgb(248 250 252 / 0.9) !important;
            }
            .dark .laporan-penjualan-wrapper .fi-ta-expanded-row {
                background-color: rgb(15 23 42 / 0.7) !important;
            }
            .laporan-penjualan-wrapper .fi-ta-expanded-row > td {
                padding-top: 0.6rem !important;
                padding-bottom: 0.6rem !important;
                padding-left: 2rem !important;
                padding-right: 1.5rem !important;
                white-space: normal !important;
            }
            .laporan-penjualan-wrapper .fi-ta-expanded-row table {
                box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.08);
            }
            .laporan-penjualan-wrapper .fi-ta-expanded-row table th {
                background-color: #f1f5f9 !important;
                color: #334155 !important;
                font-size: 0.7rem !important;
                padding: 0.4rem 0.6rem !important;
                font-weight: 600 !important;
            }
            .dark .laporan-penjualan-wrapper .fi-ta-expanded-row table th {
                background-color: #1e293b !important;
                color: #e2e8f0 !important;
            }
            .laporan-penjualan-wrapper .fi-ta-expanded-row table td {
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
