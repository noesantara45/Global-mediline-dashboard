<div class="fi-sidebar-footer px-6 py-4 border-t border-emerald-900/10 dark:border-white/10">
    <form action="{{ filament()->getLogoutUrl() }}" method="post" class="w-full">
        @csrf
        <button
            type="submit"
            class="group flex items-center w-full gap-x-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-emerald-900/5 hover:text-[#1F4E34] dark:text-gray-200 dark:hover:bg-white/5 transition duration-75 text-start cursor-pointer"
        >
            <x-filament::icon
                icon="heroicon-m-arrow-left-on-rectangle"
                class="h-5 w-5 text-slate-500 group-hover:text-[#2D8A56] dark:text-gray-400 dark:group-hover:text-gray-200 transition duration-75"
            />
            <span class="truncate">
                {{ __('filament-panels::widgets/account-widget.actions.logout.label') }}
            </span>
        </button>
    </form>
</div>
