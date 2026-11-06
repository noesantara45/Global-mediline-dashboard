<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\LaporanDetailPenjualan;
use App\Filament\Pages\LaporanRekapBarang;
use App\Filament\Resources\ProductResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Tables\View\TablesRenderHook;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => [
                    50 => '243, 250, 246',  // #F3FAF6 - Soft Medical Mint
                    100 => '225, 242, 231', // #E1F2E7 - Active Sidebar Pill
                    200 => '194, 229, 209', // #C2E5D1 - Soft Border
                    300 => '148, 209, 174', // #94D1AE - Light Leaf
                    400 => '94, 184, 134',  // #5EB886 - Dark Mode Accent
                    500 => '56, 158, 102',  // #389E66 - Mid Leaf
                    600 => '45, 138, 86',   // #2D8A56 - Medical Emerald (Logo GM)
                    700 => '31, 78, 52',    // #1F4E34 - Forest Green (Logo GLOBAL)
                    800 => '24, 62, 41',    // #183E29 - Deep Forest
                    900 => '19, 49, 33',    // #133121 - Dark Botanical
                    950 => '10, 28, 18',    // #0A1C12 - Deepest Forest
                ],
                'gray' => Color::Slate,
            ])
            ->brandName('Global Mediline')
            ->brandLogo(fn (): View => view('filament.brand-logo'))
            ->brandLogoHeight('2.35rem')
            ->favicon(asset('images/logo-gm-clear.png'))
            ->sidebarFullyCollapsibleOnDesktop()
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString('
                    <style>
                        :root {
                            --gm-brand-dark: #1F4E34;
                            --gm-brand-emerald: #2D8A56;
                            --gm-brand-lime: #76B82A;
                            --gm-brand-lime-bright: #84C038;
                        }
                        .dark {
                            --gm-brand-dark: #E6F6EC;
                            --gm-brand-lime: #9BE14E;
                        }

                        /* Konsep 1: Clean Medical Light */
                        html:not(.dark) body,
                        html:not(.dark) main {
                            background-color: #F8FBF9 !important;
                        }

                        /* Sidebar Light Surface */
                        html:not(.dark) .fi-sidebar {
                            background-color: #F2F8F4 !important;
                            border-right: 1px solid #DCEBE1 !important;
                        }
                        html:not(.dark) .fi-sidebar-header {
                            background-color: #FFFFFF !important;
                            border-bottom: 1px solid #E2EFE7 !important;
                            box-shadow: none !important;
                        }
                        html:not(.dark) .fi-sidebar-group-label {
                            color: #3E6650 !important;
                            font-weight: 600 !important;
                            letter-spacing: 0.03em;
                        }
                        html:not(.dark) .fi-sidebar-item-button:hover {
                            background-color: #E5F3EA !important;
                        }
                        html:not(.dark) .fi-sidebar-item-button:hover .fi-sidebar-item-label {
                            color: #1F4E34 !important;
                        }
                        html:not(.dark) .fi-sidebar-item-button:hover .fi-sidebar-item-icon {
                            color: #2D8A56 !important;
                        }

                        /* Active Navigation Item with Lime Leaf Indicator */
                        html:not(.dark) .fi-sidebar-item.fi-active > .fi-sidebar-item-button {
                            background-color: #DFF1E6 !important;
                            box-shadow: inset 3.5px 0 0 0 #84C038 !important;
                        }
                        html:not(.dark) .fi-sidebar-item.fi-active > .fi-sidebar-item-button .fi-sidebar-item-label {
                            color: #1F4E34 !important;
                            font-weight: 700 !important;
                        }
                        html:not(.dark) .fi-sidebar-item.fi-active > .fi-sidebar-item-button .fi-sidebar-item-icon {
                            color: #2D8A56 !important;
                        }

                        /* Dark Mode Active Sidebar Accent */
                        .dark .fi-sidebar-item.fi-active > .fi-sidebar-item-button {
                            background-color: rgba(45, 138, 86, 0.18) !important;
                            box-shadow: inset 3.5px 0 0 0 #84C038 !important;
                        }

                        /* Topbar Brand Gradient Ribbon */
                        .fi-topbar > nav {
                            border-top: 2.5px solid transparent;
                            border-image: linear-gradient(90deg, #1F4E34 0%, #2D8A56 55%, #84C038 100%) 1;
                        }

                        /* Table Header Subtle Medical Tint */
                        html:not(.dark) .fi-ta-header-ctn,
                        html:not(.dark) .fi-ta-table thead tr {
                            background-color: #F4F9F6 !important;
                        }
                        html:not(.dark) .fi-ta-header-cell-label {
                            color: #1F4E34 !important;
                        }

                        /* Login Page Clean Medical Backdrop & Card Accent */
                        html:not(.dark) .fi-simple-layout {
                            background:
                                radial-gradient(circle at 85% 12%, rgba(132, 192, 56, 0.15), transparent 42%),
                                radial-gradient(circle at 12% 88%, rgba(45, 138, 86, 0.13), transparent 45%),
                                #F3F9F5 !important;
                        }
                        .fi-simple-main {
                            border-top: 4px solid #2D8A56 !important;
                        }
                    </style>
                ')
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): View => view('filament.sidebar-footer')
            )
            ->renderHook(
                TablesRenderHook::TOOLBAR_START,
                fn (): HtmlString => new HtmlString('
                    <div class="flex items-center gap-x-3">
                        <span class="inline-flex items-center gap-x-1.5 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-200">
                            <svg class="h-4 w-4 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 0 1 .628.74v2.288a2.25 2.25 0 0 1-.659 1.59l-4.682 4.683a2.25 2.25 0 0 0-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 0 1 8 18.25v-5.757a2.25 2.25 0 0 0-.659-1.591L2.659 6.22A2.25 2.25 0 0 1 2 4.629V2.34a.75.75 0 0 1 .628-.74Z" clip-rule="evenodd" />
                            </svg>
                            '.e(__('filament-tables::table.filters.heading')).'
                        </span>
                        <button
                            type="button"
                            wire:click="resetTableFiltersForm"
                            class="fi-link group/link relative inline-flex items-center justify-center outline-none text-xs font-medium text-danger-600 hover:underline focus-visible:underline dark:text-danger-400"
                        >
                            '.e(__('filament-tables::table.filters.actions.reset.label')).'
                        </button>
                    </div>
                '),
                scopes: LaporanDetailPenjualan::class,
            )
            ->renderHook(
                TablesRenderHook::TOOLBAR_START,
                fn (): HtmlString => new HtmlString('
                    <div class="flex items-center gap-x-3">
                        <span class="inline-flex items-center gap-x-1.5 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-200">
                            <svg class="h-4 w-4 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 0 1 .628.74v2.288a2.25 2.25 0 0 1-.659 1.59l-4.682 4.683a2.25 2.25 0 0 0-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 0 1 8 18.25v-5.757a2.25 2.25 0 0 0-.659-1.591L2.659 6.22A2.25 2.25 0 0 1 2 4.629V2.34a.75.75 0 0 1 .628-.74Z" clip-rule="evenodd" />
                            </svg>
                            '.e(__('filament-tables::table.filters.heading')).'
                        </span>
                        <button
                            type="button"
                            wire:click="resetAllFilters"
                            class="fi-link group/link relative inline-flex items-center justify-center outline-none text-xs font-medium text-danger-600 hover:underline focus-visible:underline dark:text-danger-400"
                        >
                            '.e(__('filament-tables::table.filters.actions.reset.label')).'
                        </button>
                    </div>
                '),
                scopes: LaporanRekapBarang::class,
            )
            ->renderHook(
                TablesRenderHook::TOOLBAR_SEARCH_AFTER,
                fn (): HtmlString => new HtmlString('
                    <a
                        href="'.e(ProductResource::getUrl('create')).'"
                        class="gm-rekap-create-btn shrink-0"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" />
                        </svg>
                        Tambah Produk
                    </a>
                '),
                scopes: LaporanRekapBarang::class,
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
