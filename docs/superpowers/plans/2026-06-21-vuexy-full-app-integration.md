# Vuexy Full App Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (\`- [ ]\`) syntax for tracking.

**Goal:** Replace the SmartQua web interface with Vuexy Laravel styling while retaining all existing Laravel routes, controller contracts, forms, AJAX endpoints, exports, and ESP32 API behavior.

**Architecture:** SmartQua remains a server-rendered Laravel Blade application. A small, local Vuexy asset layer supports two Blade shells: a vertical layout for authenticated users and a guest layout for authentication. The existing page scripts and data stay in place; only their presentational structure is converted. Theme preference is a standalone browser module that follows the operating system unless a user chooses a mode.

**Tech Stack:** Laravel Blade, Vite 8, Bootstrap 5, selected Vuexy Laravel starter assets, Chart.js, DataTables, React/goey-toast, PHPUnit.

---

## Locked File Structure

- \`resources/vendor/vuexy/\` — selected Vuexy asset copies; never load assets from \`starter-kit/\` at runtime.
- \`resources/css/vuexy.css\` — Vuexy core imports plus focused SmartQua overrides.
- \`resources/js/theme.js\` — system/light/dark preference handling.
- \`resources/js/app.js\` — existing Bootstrap, chart, table, and toast startup; imports Vuexy runtime and theme module.
- \`resources/views/layouts/app.blade.php\` — authenticated Vuexy vertical shell.
- \`resources/views/layouts/guest.blade.php\` — Vuexy guest/auth shell.
- \`resources/views/layouts/partials/sidebar.blade.php\` — SmartQua menu and route-active state.
- \`resources/views/layouts/partials/navbar.blade.php\` — menu toggle, theme selector, user menu, logout.
- \`resources/views/{dashboard,history,settings}.blade.php\`, \`resources/views/api-docs/index.blade.php\`, \`resources/views/api-tokens/index.blade.php\` — feature views with their existing behavior preserved.
- \`resources/views/auth/{login,register}.blade.php\` — guest Vuexy cards retaining Laravel form contracts.
- \`tests/Feature/VuexyLayoutTest.php\` — route-rendering and layout-contract tests.

### Task 1: Add regression coverage for layout contracts

**Files:**

- Create: \`tests/Feature/VuexyLayoutTest.php\`
- Modify: \`tests/Feature/ExampleTest.php\`

- [ ] **Step 1: Write the failing authenticated and guest layout tests**

~~~php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VuexyLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_pages_render_the_vuexy_vertical_shell(): void
    {
        $user = User::factory()->create();

        foreach (['dashboard', 'history', 'settings', 'api-docs', 'api-tokens.index'] as $route) {
            $this->actingAs($user)->get(route($route))
                ->assertOk()
                ->assertSee('id="layout-menu"', false)
                ->assertSee('data-theme-preference="system"', false);
        }
    }

    public function test_guest_pages_render_the_vuexy_auth_shell_without_admin_menu(): void
    {
        foreach (['login', 'register'] as $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertDontSee('id="layout-menu"', false)
                ->assertSee('data-theme-preference="system"', false);
        }
    }
}
~~~

- [ ] **Step 2: Run it before implementation**

Run: \`php artisan test tests/Feature/VuexyLayoutTest.php\`

Expected: FAIL because the current navbar shell does not provide the required Vuexy menu anchor or system-preference marker.

- [ ] **Step 3: Remove the duplicated generic root-page check**

Delete only \`test_the_application_returns_a_successful_response()\` from \`tests/Feature/ExampleTest.php\`. Keep the guest redirect and API-token flash tests.

- [ ] **Step 4: Commit the red test**

~~~powershell
git add tests/Feature/VuexyLayoutTest.php tests/Feature/ExampleTest.php
git commit -m "test: cover Vuexy layout contracts"
~~~

### Task 2: Add the selected Vuexy runtime asset boundary

**Files:**

- Create: \`resources/vendor/vuexy/css/demo.css\`
- Create: \`resources/vendor/vuexy/js/{helpers,config,menu,main}.js\`
- Create: \`resources/vendor/vuexy/scss/\` (complete core SCSS tree and required partials)
- Create: \`resources/vendor/vuexy/fonts/\` (icon styles and referenced font files)
- Create: \`resources/vendor/vuexy/libs/{perfect-scrollbar,node-waves}/\`
- Create: \`resources/css/vuexy.css\`
- Modify: \`package.json\`, \`package-lock.json\`, \`vite.config.js\`, \`resources/js/app.js\`

- [ ] **Step 1: Copy only the assets used by the vertical Vuexy layout**

~~~text
starter-kit/resources/assets/vendor/scss/                    -> resources/vendor/vuexy/scss/
starter-kit/resources/assets/vendor/fonts/                   -> resources/vendor/vuexy/fonts/
starter-kit/resources/assets/vendor/libs/perfect-scrollbar/  -> resources/vendor/vuexy/libs/perfect-scrollbar/
starter-kit/resources/assets/vendor/libs/node-waves/         -> resources/vendor/vuexy/libs/node-waves/
starter-kit/resources/assets/vendor/js/helpers.js            -> resources/vendor/vuexy/js/helpers.js
starter-kit/resources/assets/vendor/js/menu.js               -> resources/vendor/vuexy/js/menu.js
starter-kit/resources/assets/js/config.js                    -> resources/vendor/vuexy/js/config.js
starter-kit/resources/assets/js/main.js                      -> resources/vendor/vuexy/js/main.js
starter-kit/resources/assets/css/demo.css                    -> resources/vendor/vuexy/css/demo.css
~~~

Never copy starter routes, controllers, demo views, Jetstream code, or \`starter-kit/package.json\`.

- [ ] **Step 2: Add only packages missing from SmartQua**

Run: \`npm install perfect-scrollbar node-waves\`

Do not overwrite SmartQua dependencies with the starter-kit package manifest. Existing Bootstrap, Chart.js, DataTables, React, and goey-toast versions remain authoritative.

- [ ] **Step 3: Create the Vuexy stylesheet entry**

~~~css
@import '../vendor/vuexy/scss/core.scss';
@import '../vendor/vuexy/css/demo.css';
@import '../vendor/vuexy/libs/perfect-scrollbar/perfect-scrollbar.scss';
@import '../vendor/vuexy/libs/node-waves/node-waves.scss';

:root {
    --bs-primary: #696cff;
    --smartqua-water: #0ea5e9;
}

.app-brand-text { color: var(--bs-heading-color); }
.menu-vertical .app-brand-logo { color: var(--smartqua-water); }
~~~

- [ ] **Step 4: Register the stylesheet and Vuexy runtime**

Change the Laravel Vite input to:

~~~js
input: [
    'resources/css/app.css',
    'resources/css/vuexy.css',
    'resources/js/app.js',
],
~~~

At the top of \`resources/js/app.js\`, before the current app setup, import:

~~~js
import '../vendor/vuexy/js/helpers.js';
import '../vendor/vuexy/js/config.js';
import '../vendor/vuexy/libs/node-waves/node-waves.js';
import '../vendor/vuexy/libs/perfect-scrollbar/perfect-scrollbar.js';
import '../vendor/vuexy/js/menu.js';
import '../vendor/vuexy/js/main.js';
~~~

Keep every existing SmartQua import and global assignment below these new imports.

- [ ] **Step 5: Verify Vite asset resolution**

Run: \`npm run build\`

Expected: exits 0 and the manifest contains an entry for \`resources/css/vuexy.css\`.

- [ ] **Step 6: Commit the asset foundation**

~~~powershell
git add package.json package-lock.json vite.config.js resources/css/vuexy.css resources/js/app.js resources/vendor/vuexy
git commit -m "feat: add Vuexy asset foundation"
~~~

### Task 3: Implement theme handling and the shared Vuexy shells

**Files:**

- Create: \`resources/js/theme.js\`
- Create: \`resources/views/layouts/partials/sidebar.blade.php\`
- Create: \`resources/views/layouts/partials/navbar.blade.php\`
- Create: \`resources/views/layouts/guest.blade.php\`
- Modify: \`resources/views/layouts/app.blade.php\`
- Modify: \`resources/js/app.js\`

- [ ] **Step 1: Write the system-aware theme module**

~~~js
const storageKey = 'smartqua-theme-preference';
const systemQuery = window.matchMedia('(prefers-color-scheme: dark)');

export function effectiveTheme(preference = localStorage.getItem(storageKey) ?? 'system') {
    return preference === 'system' ? (systemQuery.matches ? 'dark' : 'light') : preference;
}

export function applyTheme(preference = localStorage.getItem(storageKey) ?? 'system') {
    const theme = effectiveTheme(preference);

    document.documentElement.dataset.themePreference = preference;
    document.documentElement.dataset.bsTheme = theme;
    document.documentElement.classList.toggle('dark-style', theme === 'dark');
    document.documentElement.classList.toggle('light-style', theme === 'light');
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { preference, theme } }));
}

export function setThemePreference(preference) {
    localStorage.setItem(storageKey, preference);
    applyTheme(preference);
}

export function initializeTheme() {
    applyTheme();
    systemQuery.addEventListener('change', () => {
        if ((localStorage.getItem(storageKey) ?? 'system') === 'system') applyTheme('system');
    });
}
~~~

Import \`initializeTheme\` and \`setThemePreference\` in \`app.js\`, call \`initializeTheme()\` once, and expose \`window.setThemePreference = setThemePreference\`. Update the existing toast theme listener to prefer \`event.detail.theme\`, preserving its cleanup.

- [ ] **Step 2: Replace the authenticated shell with the vertical Vuexy structure**

\`layouts/app.blade.php\` must provide these anchors while retaining CSRF meta, Vite, session toast dispatch, \`#app-toast-root\`, stacks, and a pre-paint theme script:

~~~blade
<html lang="id" data-theme-preference="system" data-bs-theme="light">
<body>
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
      @include('layouts.partials.sidebar')
      <div class="layout-page">
        @include('layouts.partials.navbar')
        <div class="content-wrapper">
          <main class="container-xxl flex-grow-1 container-p-y">@yield('content')</main>
        </div>
      </div>
    </div>
    <div class="layout-overlay layout-menu-toggle"></div>
  </div>
</body>
</html>
~~~

- [ ] **Step 3: Implement navigation without importing Vuexy demo data**

The sidebar must use \`request()->routeIs()\` and contain:

~~~text
Dashboard                route('dashboard')          active: dashboard
Histori Pembacaan        route('history')            active: history
Sistem > Pengaturan      route('settings')           parent active: settings
API > Dokumentasi API    route('api-docs')           parent active: api-docs
API > Token API          route('api-tokens.index')   parent active: api-tokens.*
~~~

The navbar has \`data-theme-preference="light"\`, \`"dark"\`, and \`"system"\` buttons, shows \`auth()->user()->name\`, and posts logout to \`route('logout')\` with \`@csrf\`.

- [ ] **Step 4: Create the guest shell**

\`layouts/guest.blade.php\` uses the same head, Vite entries, and pre-paint theme script but wraps content only in \`authentication-wrapper authentication-basic container-p-y\`. It never includes a menu, app navbar, or logout form.

- [ ] **Step 5: Prove the shell test passes**

Run: \`php artisan test tests/Feature/VuexyLayoutTest.php\`

Expected: PASS for five authenticated pages and two guest pages.

- [ ] **Step 6: Commit shared layout work**

~~~powershell
git add resources/js/theme.js resources/js/app.js resources/views/layouts
git commit -m "feat: add Vuexy layouts and theme preference"
~~~

### Task 4: Convert all authenticated feature pages

**Files:**

- Modify: \`resources/views/dashboard.blade.php\`
- Modify: \`resources/views/history.blade.php\`
- Modify: \`resources/views/settings.blade.php\`
- Modify: \`resources/views/api-docs/index.blade.php\`
- Modify: \`resources/views/api-tokens/index.blade.php\`
- Modify: \`resources/css/vuexy.css\`

- [ ] **Step 1: Convert dashboard presentation only**

Keep its opening \`@php\` calculation block, IDs consumed by dashboard JavaScript, chart canvas, and existing script block. Replace wrappers with a Vuexy grid:

~~~blade
<div class="row gy-6 mb-6">
  <div class="col-xl-8">{{-- metric cards, trend chart, alerts --}}</div>
  <div class="col-xl-4">{{-- device status and recommendations --}}</div>
</div>
~~~

Use Vuexy \`card\`, \`card-header\`, \`card-body\`, \`avatar\`, \`badge\`, \`btn\`, and \`table\` classes. Map existing \`normal\`, \`warning\`, and \`critical\` values to \`text-success/bg-label-success\`, \`text-warning/bg-label-warning\`, and \`text-danger/bg-label-danger\` via aliases in \`vuexy.css\`.

- [ ] **Step 2: Convert one remaining feature page at a time**

Do not change these contracts:

~~~text
history.blade.php          filter field names, DataTable ID, chart ID, export URLs, modal IDs
settings.blade.php         settings.device.update, thresholds.update, settings.telegram.test, settings.telegram.toggle
api-docs/index.blade.php   API samples and endpoint text
api-tokens/index.blade.php api-tokens.store, plain_token session display, api-tokens.destroy DELETE forms
~~~

Use the Vuexy page-header flex row, \`card\` sections, \`nav nav-pills\` where sections exist, \`form-control\`/ \`form-select\`, and responsive table wrappers. Preserve every existing JavaScript selector and \`data-*\` attribute.

- [ ] **Step 3: Move presentational CSS out of Blade**

Move static per-page rules into named \`vuexy.css\` sections:

~~~css
/* SmartQua dashboard */
.smartqua-metric-value { font-variant-numeric: tabular-nums; }
.smartqua-chart { min-height: 22rem; position: relative; }

/* SmartQua severity aliases */
.smartqua-severity-warning { color: var(--bs-warning); }
.smartqua-severity-critical { color: var(--bs-danger); }
~~~

Leave only data-dependent values inline.

- [ ] **Step 4: Run page regression tests and compile assets**

~~~powershell
php artisan test tests/Feature/VuexyLayoutTest.php tests/Feature/ExampleTest.php
npm run build
~~~

Expected: PHP tests pass and Vite exits 0.

- [ ] **Step 5: Commit page conversion**

~~~powershell
git add resources/views/dashboard.blade.php resources/views/history.blade.php resources/views/settings.blade.php resources/views/api-docs/index.blade.php resources/views/api-tokens/index.blade.php resources/css/vuexy.css
git commit -m "feat: apply Vuexy styling to SmartQua pages"
~~~

### Task 5: Convert login and register without changing authentication behavior

**Files:**

- Modify: \`resources/views/auth/login.blade.php\`
- Modify: \`resources/views/auth/register.blade.php\`
- Modify: \`tests/Feature/VuexyLayoutTest.php\`

- [ ] **Step 1: Add field-contract coverage**

~~~php
public function test_vuexy_auth_pages_preserve_authentication_field_contracts(): void
{
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="remember"', false);

    $this->get(route('register'))
        ->assertOk()
        ->assertSee('name="name"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="password_confirmation"', false);
}
~~~

- [ ] **Step 2: Establish the test baseline**

Run: \`php artisan test tests/Feature/VuexyLayoutTest.php --filter=authentication_field_contracts\`

Expected: PASS before and after conversion; if it fails before conversion, correct the test to match an existing controller-required field rather than changing backend validation.

- [ ] **Step 3: Use the Vuexy guest card markup**

Each auth view begins with \`@extends('layouts.guest')\` and retains its action, \`@csrf\`, exact field names, old input, validation, and HTTP method. Use this field pattern:

~~~blade
<div class="mb-6">
  <label class="form-label" for="email">Email</label>
  <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus>
  @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
~~~

Do not include starter demo social login, password-reset, profile, or Jetstream controls because SmartQua has no such routes.

- [ ] **Step 4: Run all relevant tests**

Run: \`php artisan test tests/Feature/VuexyLayoutTest.php tests/Feature/ExampleTest.php\`

Expected: PASS, including redirects, API-token flash behavior, layout contracts, and auth field contracts.

- [ ] **Step 5: Commit auth pages**

~~~powershell
git add resources/views/auth/login.blade.php resources/views/auth/register.blade.php tests/Feature/VuexyLayoutTest.php
git commit -m "feat: apply Vuexy authentication screens"
~~~

### Task 6: Verify every workflow and document asset provenance

**Files:**

- Modify: \`README.md\`
- Modify: \`DOKUMENTASI_TEKNIS_SMARTQUA.md\`

- [ ] **Step 1: Add maintenance notes**

Document that selected assets from the locally licensed Vuexy Laravel starter live at \`resources/vendor/vuexy\`; that SmartQua does not execute the starter-kit; and that demo routes, controllers, and Jetstream integrations must not be copied into the application.

- [ ] **Step 2: Run the complete automated suite**

~~~powershell
php artisan test
npm run build
~~~

Expected: PHPUnit exits 0; Vite exits 0 and the production manifest includes \`resources/css/vuexy.css\` and \`resources/js/app.js\`.

- [ ] **Step 3: Perform the manual browser acceptance pass**

~~~text
1. A first visit follows operating-system color preference.
2. Navbar light, dark, and system choices update the shell and survive reload.
3. The sidebar opens on small screens and marks the active route.
4. Dashboard refresh, chart controls, and toasts work.
5. History filtering, DataTable, chart, CSV/Excel export, and printable report work.
6. Device settings, threshold save, and Telegram test/toggle work.
7. API-token creation/revocation, login, register, and logout work.
8. API documentation is readable in each theme.
~~~

- [ ] **Step 4: Commit documentation**

~~~powershell
git add README.md DOKUMENTASI_TEKNIS_SMARTQUA.md
git commit -m "docs: describe Vuexy integration maintenance"
~~~

## Plan Self-Review

- **Spec coverage:** Tasks 2–3 establish selected Vuexy assets, a vertical authenticated shell, a guest shell, and system-first theme behavior. Tasks 4–5 cover all seven user-facing pages without altering their backend contracts. Task 6 covers build, full tests, all agreed workflows, exports, and asset maintenance.
- **No-placeholder check:** Every task names files, commands, expected outcomes, data contracts, and the exact non-demo asset boundary.
- **Consistency check:** All theme controls write \`smartqua-theme-preference\`; both layouts expose \`data-theme-preference="system"\`; \`app.js\` owns the single \`theme-changed\` event used by the existing toast root.

