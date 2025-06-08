@php
    $configData = Helper::appClasses();
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

    @if (!isset($navbarFull))
        <div class="app-brand demo">
            <a href="{{ url('/app/ecommerce/dashboard') }}" class="app-brand-link">
                <span class="app-brand-logo demo">
                    @include('_partials.macros', ['height' => 20])
                </span>
                <span class="app-brand-text demo menu-text fw-bold">{{ config('variables.templateName') }}</span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
                <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
            </a>
        </div>
    @endif

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        @foreach ($menuData[0]->menu as $menu)
            @if (
                (isset($menu->adminOnly) && Auth::check() && Auth::user()->role_id == 1) ||
                    (isset($menu->masterOnly) && Auth::check() && Auth::user()->role_id == 2) ||
                    (isset($menu->revededorOnly) && Auth::check() && Auth::user()->role_id == 4) ||
                    (isset($menu->clientOnly) &&
                        Auth::guard('cliente')->check() &&
                        Auth::guard('cliente')->user()->role_id == 3 &&
                        $menu->clientOnly) ||
                    (!isset($menu->adminOnly) &&
                        !isset($menu->masterOnly) &&
                        !isset($menu->revededorOnly) &&
                        !isset($menu->clientOnly)))
                @if (isset($menu->menuHeader))
                    <li class="menu-header small text-uppercase">
                        <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
                    </li>
                @else
                    @php
                        $activeClass = null;
                        $currentRouteName = Route::currentRouteName();

                        if (isset($menu->slug) && $currentRouteName === $menu->slug) {
                            $activeClass = 'active';
                        } elseif (isset($menu->submenu)) {
                            if (is_array($menu->slug)) {
                                foreach ($menu->slug as $slug) {
                                    if (
                                        str_contains($currentRouteName, $slug) &&
                                        strpos($currentRouteName, $slug) === 0
                                    ) {
                                        $activeClass = 'active open';
                                    }
                                }
                            } else {
                                if (
                                    str_contains($currentRouteName, $menu->slug) &&
                                    strpos($currentRouteName, $menu->slug) === 0
                                ) {
                                    $activeClass = 'active open';
                                }
                            }
                        }
                    @endphp

                    <li class="menu-item {{ $activeClass }}">
                        <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}"
                            class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
                            @if (isset($menu->target) && !empty($menu->target)) target="_blank" @endif>
                            @isset($menu->icon)
                                <i class="{{ $menu->icon }}"></i>
                            @endisset
                            <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
                            @isset($menu->badge)
                                @php
                                    $badgeText = is_object($menu->badge) ? ($menu->badge->text ?? 'New') : (is_array($menu->badge) ? ($menu->badge[1] ?? 'New') : 'New');
                                    $badgeClass = is_object($menu->badge) ? ($menu->badge->classes ?? 'badge bg-primary rounded-pill ms-auto') : 'badge bg-'.(is_array($menu->badge) ? ($menu->badge[0] ?? 'primary') : 'primary').' rounded-pill ms-auto';
                                @endphp
                                <div class="{{ $badgeClass }}">{{ $badgeText }}</div>
                            @endisset
                        </a>

                        @isset($menu->submenu)
                            @include('layouts.sections.menu.submenu', ['menu' => $menu->submenu])
                        @endisset
                    </li>
                @endif
            @endif
        @endforeach
    </ul>
</aside>