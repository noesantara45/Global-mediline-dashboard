<x-filament-panels::page class="fi-dashboard-page">
    <div class="gm-dashboard-wrapper">
        <style>
            .gm-dashboard-wrapper .fi-wi {
                gap: 1.125rem !important;
                align-items: stretch !important;
            }
            .gm-dashboard-wrapper .fi-wi > .fi-wi-widget {
                display: flex;
                flex-direction: column;
                height: 100%;
            }

            /* Actionable KPI stat cards subtle hover polish */
            .gm-dashboard-wrapper a.fi-wi-stats-overview-stat {
                transition: transform 150ms ease, box-shadow 150ms ease, border-color 150ms ease;
            }
            .gm-dashboard-wrapper a.fi-wi-stats-overview-stat:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 18px -6px rgb(31 78 52 / 0.14);
            }

            /* Equal-height, flush chart cards on Row 2 (zero vertical gap) */
            .gm-dashboard-wrapper .fi-wi-chart .fi-section {
                height: 100%;
                display: flex;
                flex-direction: column;
            }
            .gm-dashboard-wrapper .fi-wi-chart .fi-section-header {
                padding: 0.85rem 1.125rem !important;
            }
            .gm-dashboard-wrapper .fi-wi-chart .fi-section-header .fi-section-header-heading {
                font-size: 0.925rem !important;
                line-height: 1.25rem !important;
            }
            .gm-dashboard-wrapper .fi-wi-chart .fi-section-header .fi-section-header-description {
                font-size: 0.75rem !important;
                line-height: 1.1rem !important;
            }
            .gm-dashboard-wrapper .fi-wi-chart .fi-section-header .fi-input-wrp {
                flex-shrink: 0;
            }
            .gm-dashboard-wrapper .fi-wi-chart .fi-section-header select {
                font-size: 0.75rem !important;
                padding-top: 0.275rem !important;
                padding-bottom: 0.275rem !important;
                padding-left: 0.6rem !important;
                padding-right: 1.75rem !important;
            }
            .gm-dashboard-wrapper .fi-wi-chart .fi-section-content-ctn {
                flex: 1 1 auto;
                display: flex;
                flex-direction: column;
            }
            .gm-dashboard-wrapper .fi-wi-chart .fi-section-content {
                flex: 1 1 auto;
                display: flex;
                flex-direction: column;
                padding: 0.85rem 1.125rem 0.95rem !important;
            }
            .gm-dashboard-wrapper .fi-wi-chart .fi-section-content > div {
                flex: 1 1 auto;
                display: flex;
                flex-direction: column;
            }
            .gm-dashboard-wrapper .fi-wi-chart .fi-section-content > div > div[x-data] {
                position: relative;
                flex: 1 1 auto;
                min-height: 255px;
                height: 255px;
                width: 100%;
            }
            .gm-dashboard-wrapper .fi-wi-chart .fi-section-content canvas {
                width: 100% !important;
                height: 100% !important;
                max-height: none !important;
            }

            /* Compact ERP table widgets on Dashboard (no double cell padding or horizontal cutoff) */
            .gm-dashboard-wrapper .fi-wi-table .fi-ta-header {
                padding: 0.75rem 1rem !important;
            }
            .gm-dashboard-wrapper .fi-wi-table .fi-ta-header-heading {
                font-size: 0.9rem !important;
                line-height: 1.25rem !important;
            }
            .gm-dashboard-wrapper .fi-wi-table .fi-ta-header-description {
                font-size: 0.73rem !important;
                margin-top: 0.1rem !important;
            }
            .gm-dashboard-wrapper .fi-wi-table .fi-ta-table {
                font-size: 0.8rem;
            }
            .gm-dashboard-wrapper .fi-wi-table th.fi-ta-header-cell {
                padding: 0.45rem 0.55rem !important;
                white-space: nowrap !important;
            }
            .gm-dashboard-wrapper .fi-wi-table th.fi-ta-header-cell:first-of-type {
                padding-left: 1rem !important;
            }
            .gm-dashboard-wrapper .fi-wi-table th.fi-ta-header-cell:last-of-type {
                padding-right: 0.85rem !important;
            }
            .gm-dashboard-wrapper .fi-wi-table .fi-ta-header-cell-label {
                font-size: 0.68rem !important;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }
            .gm-dashboard-wrapper .fi-wi-table td.fi-ta-cell {
                padding: 0 !important;
                white-space: nowrap !important;
            }
            .gm-dashboard-wrapper .fi-wi-table td.fi-ta-cell:first-of-type .fi-ta-text {
                padding-left: 1rem !important;
            }
            .gm-dashboard-wrapper .fi-wi-table td.fi-ta-cell:last-of-type {
                padding-right: 0.65rem !important;
            }
            .gm-dashboard-wrapper .fi-wi-table .fi-ta-text {
                padding: 0.45rem 0.55rem !important;
                gap: 0.1rem !important;
            }
            .gm-dashboard-wrapper .fi-wi-table .fi-ta-text p {
                font-size: 0.72rem !important;
                line-height: 1rem !important;
            }
            .gm-dashboard-wrapper .fi-wi-table .fi-ta-actions {
                padding: 0.25rem 0.35rem !important;
                gap: 0.15rem !important;
            }

            /* Equal-height side-by-side Outbound & Inbound cards on Row 4 */
            .gm-dashboard-wrapper .gm-equal-table-widget {
                flex: 1 1 auto;
                display: flex;
                flex-direction: column;
                height: 100%;
            }
            .gm-dashboard-wrapper .gm-equal-table-card {
                flex: 1 1 auto;
                display: flex;
                flex-direction: column;
                height: 100%;
                background: #ffffff;
                border-radius: 0.75rem;
                border: 1px solid rgba(15, 23, 42, 0.07);
                box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.04);
                overflow: hidden;
            }
            .gm-dashboard-wrapper .gm-equal-table-card .fi-ta-ctn {
                border-radius: 0 !important;
                box-shadow: none !important;
                --tw-ring-shadow: 0 0 #0000 !important;
            }
            .gm-dashboard-wrapper .gm-table-spacer {
                flex: 1 1 auto;
            }
            .gm-dashboard-wrapper .gm-table-filler {
                flex: 1 1 auto;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0.875rem 1rem;
                border-top: 1px solid rgba(15, 23, 42, 0.06);
                background: linear-gradient(180deg, #fafdfb 0%, #f4faf6 100%);
            }
            .gm-dashboard-wrapper .gm-table-filler-box {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.875rem;
                padding: 0.75rem 0.95rem;
                border-radius: 0.625rem;
                border: 1px dashed rgba(45, 138, 86, 0.32);
                background: rgba(255, 255, 255, 0.85);
            }
            .gm-dashboard-wrapper .gm-table-filler-text {
                display: flex;
                flex-direction: column;
                gap: 0.15rem;
                min-width: 0;
            }
            .gm-dashboard-wrapper .gm-table-filler-title {
                font-size: 0.78rem;
                font-weight: 600;
                color: #1f4e34;
            }
            .gm-dashboard-wrapper .gm-table-filler-desc {
                font-size: 0.72rem;
                color: #64748b;
                line-height: 1.05rem;
            }
            .gm-dashboard-wrapper .gm-table-filler-btn {
                flex-shrink: 0;
                display: inline-flex;
                align-items: center;
                padding: 0.375rem 0.7rem;
                border-radius: 0.45rem;
                background: #2d8a56;
                color: #ffffff !important;
                font-size: 0.73rem;
                font-weight: 600;
                text-decoration: none;
                transition: background-color 150ms ease;
            }
            .gm-dashboard-wrapper .gm-table-filler-btn:hover {
                background: #1f4e34;
            }
            .gm-dashboard-wrapper .gm-table-summary-bar {
                margin-top: auto;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem 1rem;
                padding: 0.55rem 1rem;
                background: #f8faf9;
                border-top: 1px solid rgba(31, 78, 52, 0.1);
                font-size: 0.74rem;
                color: #475569;
            }
            .gm-dashboard-wrapper .gm-table-summary-item strong {
                font-weight: 700;
                color: #0f172a;
                margin-left: 0.2rem;
            }
            .gm-dashboard-wrapper .gm-table-summary-item strong.gm-text-danger {
                color: #dc2626;
            }
            .gm-dashboard-wrapper .gm-table-summary-item strong.gm-text-success {
                color: #2d8a56;
            }
        </style>

        <x-filament-widgets::widgets
            :columns="$this->getColumns()"
            :data="$this->getWidgetData()"
            :widgets="$this->getVisibleWidgets()"
        />
    </div>
</x-filament-panels::page>
