<x-filament-panels::page class="fi-dashboard-page">
    <div class="grafik-penjualan-wrapper space-y-4">
        <style>
            /* Sticky compact analytics filter bar */
            .grafik-penjualan-wrapper .gm-analytics-filter-card {
                position: sticky;
                top: 4rem;
                z-index: 15;
                background-color: #F4F9F6;
                border: 1px solid #d7e8de;
                border-radius: 0.75rem;
                box-shadow: 0 2px 8px -2px rgb(15 23 42 / 0.06);
                overflow: visible;
            }
            .dark .grafik-penjualan-wrapper .gm-analytics-filter-card {
                background-color: #111827;
                border-color: rgb(255 255 255 / 0.1);
                box-shadow: 0 2px 10px -2px rgb(0 0 0 / 0.35);
            }
            .grafik-penjualan-wrapper .gm-analytics-filter-toolbar {
                background-color: #ffffff;
                border-bottom: 1px solid #e2e8f0;
                border-top-left-radius: 0.75rem;
                border-top-right-radius: 0.75rem;
            }
            .dark .grafik-penjualan-wrapper .gm-analytics-filter-toolbar {
                background-color: #111827;
                border-bottom-color: rgb(255 255 255 / 0.1);
            }
            .grafik-penjualan-wrapper .gm-period-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                padding: 0.15rem 0.55rem;
                border-radius: 9999px;
                font-size: 0.6875rem;
                font-weight: 600;
                background-color: #E5F3EA;
                color: #1F4E34;
                border: 1px solid #C2E5D1;
            }
            .dark .grafik-penjualan-wrapper .gm-period-pill {
                background-color: rgba(45, 138, 86, 0.2);
                color: #94D1AE;
                border-color: rgba(94, 184, 134, 0.3);
            }

            /* Uniform 30px single-row filter controls (prevents Choices.js 2-line wrapping) */
            .grafik-penjualan-wrapper .gm-analytics-filter-body .fi-fo-component-ctn {
                gap: 0.5rem !important;
            }
            .grafik-penjualan-wrapper .gm-analytics-filter-body .fi-fo-field-wrp > div {
                row-gap: 0.2rem !important;
            }
            .grafik-penjualan-wrapper .gm-analytics-filter-body .fi-fo-field-wrp-label span {
                font-size: 0.6875rem !important; /* 11px */
                line-height: 0.95rem !important;
                font-weight: 600 !important;
                white-space: nowrap;
            }
            .grafik-penjualan-wrapper .gm-analytics-filter-body .fi-input-wrp {
                height: 1.875rem !important;
                min-height: 1.875rem !important;
                max-height: 1.875rem !important;
                border-radius: 0.425rem !important;
                align-items: center;
            }
            .grafik-penjualan-wrapper .gm-analytics-filter-body .fi-input {
                height: 1.875rem !important;
                padding-top: 0.2rem !important;
                padding-bottom: 0.2rem !important;
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
                font-size: 0.75rem !important; /* 12px */
                line-height: 1.125rem !important;
            }
            .grafik-penjualan-wrapper .gm-analytics-filter-body select.fi-select-input {
                height: 1.875rem !important;
                padding-top: 0.2rem !important;
                padding-bottom: 0.2rem !important;
                padding-inline-start: 0.5rem !important;
                padding-inline-end: 1.55rem !important;
                font-size: 0.75rem !important;
                line-height: 1.125rem !important;
            }
            .grafik-penjualan-wrapper .gm-analytics-filter-body .choices {
                margin-bottom: 0 !important;
                width: 100%;
            }
            .grafik-penjualan-wrapper .gm-analytics-filter-body .choices__inner {
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
            .grafik-penjualan-wrapper .gm-analytics-filter-body .choices__list--single {
                padding: 0 !important;
                width: 100%;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
            .grafik-penjualan-wrapper .gm-analytics-filter-body .choices__list--single .choices__item {
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                word-break: normal !important;
            }
            .grafik-penjualan-wrapper .gm-analytics-filter-body .choices__list--dropdown .choices__item,
            .grafik-penjualan-wrapper .gm-analytics-filter-body .choices__list[aria-expanded] .choices__item {
                padding: 0.35rem 0.6rem !important;
                font-size: 0.75rem !important;
            }
        </style>

        @if (method_exists($this, 'filtersForm'))
            <div class="gm-analytics-filter-card">
                <div class="gm-analytics-filter-toolbar flex flex-wrap items-center justify-between gap-2 px-4 py-2">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span class="inline-flex items-center gap-x-1.5 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-200">
                            <x-filament::icon
                                icon="heroicon-m-funnel"
                                class="h-4 w-4 text-primary-600 dark:text-primary-400"
                            />
                            Filter Analitik Penjualan
                        </span>

                        <span class="gm-period-pill">
                            <x-filament::icon
                                icon="heroicon-m-calendar-days"
                                class="h-3 w-3"
                            />
                            {{ $this->getActivePeriodLabel() }}
                        </span>

                        @if (! empty($this->filters['division']))
                            <span class="gm-period-pill">
                                {{ $this->filters['division'] }}
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-x-3">
                        <x-filament::loading-indicator
                            wire:loading.delay
                            wire:target="filters,resetAnalyticsFilters"
                            class="h-4 w-4 text-gray-400 dark:text-gray-500"
                        />

                        <x-filament::link
                            color="danger"
                            size="sm"
                            tag="button"
                            wire:click="resetAnalyticsFilters"
                            class="text-xs font-medium"
                        >
                            Reset Filter
                        </x-filament::link>
                    </div>
                </div>

                <div class="gm-analytics-filter-body px-4 py-2.5">
                    {{ $this->filtersForm }}
                </div>
            </div>
        @endif

        <x-filament-widgets::widgets
            :columns="$this->getColumns()"
            :data="[
                'filters' => $this->filters,
            ]"
            :widgets="$this->getVisibleWidgets()"
        />
    </div>
</x-filament-panels::page>
