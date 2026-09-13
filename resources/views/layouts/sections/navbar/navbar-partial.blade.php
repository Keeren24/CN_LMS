@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
@endphp

<!--  Brand demo (display only for navbar-full and hide on below xl) -->
@if (isset($navbarFull))
<div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4 ms-0">
  <a href="{{ url('/') }}" class="app-brand-link">
    <span class="app-brand-logo demo">@include('_partials.macros')</span>
    <span class="app-brand-text demo menu-text fw-bold">{{ config('variables.templateName') }}</span>
  </a>

  <!-- Display menu close icon only for horizontal-menu with navbar-full -->
  @if (isset($menuHorizontal))
  <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
    <i class="icon-base ti tabler-x icon-sm d-flex align-items-center justify-content-center"></i>
  </a>
  @endif
</div>
@endif

<!-- ! Not required for layout-without-menu -->
@if (!isset($navbarHideToggle))
<div
  class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ? ' d-xl-none ' : '' }}">
  <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
    <i class="icon-base ti tabler-menu-2 icon-md"></i>
  </a>
</div>
@endif

<div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">

  {{--
  @if (!isset($menuHorizontal))
  <!-- Search -->
  <div class="navbar-nav align-items-center">
    <div class="nav-item navbar-search-wrapper px-md-0 px-2 mb-0">
      <a class="nav-item nav-link search-toggler d-flex align-items-center px-0" href="javascript:void(0);">
        <span class="d-inline-block text-body-secondary fw-normal" id="autocomplete"></span>
      </a>
    </div>
  </div>
  <!-- /Search -->
  @endif
  --}}

  <ul class="navbar-nav flex-row align-items-center ms-md-auto">
    {{--
    @if (isset($menuHorizontal))
    <!-- Search -->
    <li class="nav-item navbar-search-wrapper btn btn-text-secondary btn-icon rounded-pill">
      <a class="nav-item nav-link search-toggler px-0" href="javascript:void(0);">
        <span class="d-inline-block text-body-secondary fw-normal" id="autocomplete"></span>
      </a>
    </li>
    <!-- /Search -->
    @endif

    <!-- Language -->
    <li class="nav-item dropdown-language dropdown">
      <a class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill"
        href="javascript:void(0);" data-bs-toggle="dropdown">
        <i class="icon-base ti tabler-language icon-22px text-heading"></i>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li>
          <a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}" href="{{ url('lang/en') }}"
            data-language="en" data-text-direction="ltr">
            <span>English</span>
          </a>
        </li>
        <li>
          <a class="dropdown-item {{ app()->getLocale() === 'fr' ? 'active' : '' }}" href="{{ url('lang/fr') }}"
            data-language="fr" data-text-direction="ltr">
            <span>French</span>
          </a>
        </li>
        <li>
          <a class="dropdown-item {{ app()->getLocale() === 'ar' ? 'active' : '' }}" href="{{ url('lang/ar') }}"
            data-language="ar" data-text-direction="rtl">
            <span>Arabic</span>
          </a>
        </li>
        <li>
          <a class="dropdown-item {{ app()->getLocale() === 'de' ? 'active' : '' }}" href="{{ url('lang/de') }}"
            data-language="de" data-text-direction="ltr">
            <span>German</span>
          </a>
        </li>
      </ul>
    </li>
    <!--/ Language -->

    @if ($configData['hasCustomizer'] == true)
    <!-- Style Switcher -->
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill" id="nav-theme"
        href="javascript:void(0);" data-bs-toggle="dropdown">
        <i class="icon-base ti tabler-sun icon-22px theme-icon-active text-heading"></i>
        <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
        <li>
          <button type="button" class="dropdown-item align-items-center active" data-bs-theme-value="light"
            aria-pressed="false">
            <span><i class="icon-base ti tabler-sun icon-22px me-3" data-icon="sun"></i>Light</span>
          </button>
        </li>
        <li>
          <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark" aria-pressed="true">
            <span><i class="icon-base ti tabler-moon-stars icon-22px me-3" data-icon="moon-stars"></i>Dark</span>
          </button>
        </li>
        <li>
          <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system"
            aria-pressed="false">
            <span><i class="icon-base ti tabler-device-desktop-analytics icon-22px me-3"
                data-icon="device-desktop-analytics"></i>System</span>
          </button>
        </li>
      </ul>
    </li>
    <!-- / Style Switcher-->
    @endif
    --}}

    <!-- User -->
    <li class="nav-item navbar-dropdown dropdown-user dropdown">
      <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
        <div class="avatar avatar-online">
          <img src="{{ Auth::user() ? Auth::user()->profile_photo_url : asset('assets/img/avatars/1.png') }}" alt
            class="rounded-circle" />
        </div>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li>
          <a class="dropdown-item mt-0"
            href="{{ Route::has('profile.show') ? route('profile.show') : url('pages/profile-user') }}">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0 me-2">
                <div class="avatar avatar-online">
                  <img src="{{ Auth::user() ? Auth::user()->profile_photo_url : asset('assets/img/avatars/1.png') }}"
                    alt class="rounded-circle" />
                </div>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-0">
                  @if (Auth::check())
                  {{ Auth::user()->name }}
                  @else
                  John Doe
                  @endif
                </h6>
                <small class="text-body-secondary">Student</small>
              </div>
            </div>
          </a>
        </li>
        <li>
          <div class="dropdown-divider my-1 mx-n2"></div>
        </li>
        <li>
          <a class="dropdown-item"
            href="{{ Route::has('profile.show') ? route('profile.show') : url('pages/profile-user') }}">
            <i class="icon-base ti tabler-user me-3 icon-md"></i><span class="align-middle">My Profile</span> </a>
        </li>
        <li>
          <div class="dropdown-divider my-1 mx-n2"></div>
        </li>
        @if (Auth::check())
        <li>
          <a class="dropdown-item" href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="icon-base bx bx-power-off icon-md me-3"></i><span>Logout</span>
          </a>
        </li>
        <form method="POST" id="logout-form" action="{{ route('logout') }}">
          @csrf
        </form>
        @else
        <li>
          <div class="d-grid px-2 pt-2 pb-1">
            <a class="btn btn-sm btn-danger d-flex"
              href="{{ Route::has('login') ? route('login') : url('auth/login-basic') }}" target="_blank">
              <small class="align-middle">Login</small>
              <i class="icon-base ti tabler-login ms-2 icon-14px"></i>
            </a>
          </div>
        </li>
        @endif
      </ul>
    </li>
    <!--/ User -->
  </ul>
</div>