<x-filament-panels::page class="fi-dashboard-page">
    @if (method_exists($this, 'filtersForm'))
        <div class="mb-4">
            {{ $this->filtersForm }}
        </div>
    @endif

    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="[
            'filters' => $this->filters,
        ]"
        :widgets="$this->getVisibleWidgets()"
    />
</x-filament-panels::page>
