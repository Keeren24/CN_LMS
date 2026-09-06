@php
    use Illuminate\Support\Facades\Route;

    /*
     * Mobile bottom navigation.
     * Items come straight from the same menuData used by the sidebar
     * (resources/menu/verticalMenu.json). We take the first few top-level
     * items that link somewhere directly (skipping headers) and add a
     * "More" button that opens the full off-canvas sidebar (Vuexy
     * .layout-menu-toggle).
     */
    $bottomItems = [];
    if (! empty($menuData[0]->menu)) {
        foreach ($menuData[0]->menu as $m) {
            if (isset($m->menuHeader) || ! isset($m->url)) {
                continue;
            }
            $bottomItems[] = $m;
            if (count($bottomItems) >= 4) {
                break;
            }
        }
    }
    $currentRoute = Route::currentRouteName();
@endphp

@if (count($bottomItems))
    <nav class="app-bottom-nav d-xl-none" aria-label="Primary">
        @foreach ($bottomItems as $item)
            @php
                $icon = isset($item->icon) ? trim(preg_replace('/\s+/', ' ', str_replace(['menu-icon', 'icon-base'], '', $item->icon))) : 'ti ti-circle';
                $slug = $item->slug ?? null;
                $isActive = $slug && is_string($slug) && $currentRoute && $currentRoute === $slug;
            @endphp
            <a class="bottom-nav-item {{ $isActive ? 'active' : '' }}"
               href="{{ isset($item->url) ? url($item->url) : 'javascript:void(0);' }}">
                <span class="bottom-nav-icon"><i class="{{ $icon }}"></i></span>
                <span class="bottom-nav-label">{{ __($item->name ?? '') }}</span>
            </a>
        @endforeach

        <a class="bottom-nav-item layout-menu-toggle" href="javascript:void(0);">
            <span class="bottom-nav-icon"><i class="ti ti-dots"></i></span>
            <span class="bottom-nav-label">{{ __('More') }}</span>
        </a>
    </nav>
@endif
