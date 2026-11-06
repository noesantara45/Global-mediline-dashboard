@php
    $summary = $this->getSummaryFooterData();
@endphp

<x-filament-widgets::widget class="fi-wi-table gm-equal-table-widget">
    <div class="gm-equal-table-card">
        <div class="gm-equal-table-main">
            {{ $this->table }}
        </div>

        @if ($summary['visibleCount'] > 0 && $summary['visibleCount'] <= 3)
            <div class="gm-table-filler">
                <div class="gm-table-filler-box">
                    <div class="gm-table-filler-text">
                        <span class="gm-table-filler-title">{{ $summary['fillerTitle'] }}</span>
                        <span class="gm-table-filler-desc">{{ $summary['fillerDescription'] }}</span>
                    </div>
                    <a href="{{ $summary['ctaUrl'] }}" class="gm-table-filler-btn">
                        {{ $summary['ctaLabel'] }} &rarr;
                    </a>
                </div>
            </div>
        @else
            <div class="gm-table-spacer"></div>
        @endif

        <div class="gm-table-summary-bar">
            <span class="gm-table-summary-item">
                Total Aktif ({{ $summary['activeCount'] }} Faktur):
                <strong>{{ $summary['activeAmount'] }}</strong>
            </span>
            <span class="gm-table-summary-item">
                {{ $summary['unpaidLabel'] }}:
                <strong class="{{ $summary['hasUnpaid'] ? 'gm-text-danger' : 'gm-text-success' }}">
                    {{ $summary['unpaidAmount'] }}
                </strong>
            </span>
        </div>
    </div>
</x-filament-widgets::widget>

