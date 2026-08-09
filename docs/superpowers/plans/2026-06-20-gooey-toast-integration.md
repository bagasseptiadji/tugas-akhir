# Gooey Toast Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace all in-browser application notifications with `goey-toast` while preserving Laravel flash-message contracts and dashboard history behavior.

**Architecture:** A React root mounted by `resources/js/app.js` renders one `GooeyToaster` and exposes `window.appToast` as a JavaScript bridge. Blade pages invoke that bridge for flash messages, settings AJAX results, sensor alerts, and API-token confirmation.

**Tech Stack:** Laravel 13, Blade, Vite, React 19, Framer Motion, `goey-toast` 0.5.

---

### Task 1: Preserve server flash-message contracts

**Files:**
- Modify: `tests/Feature/ExampleTest.php`
- Verify: `app/Http/Controllers/ApiTokenController.php`

- [ ] **Step 1: Write the failing test**

```php
public function test_api_token_creation_flashes_a_success_status(): void
{
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('api-tokens.store'), ['name' => 'ESP32 Test'])
        ->assertRedirect()
        ->assertSessionHas('status', 'Token API berhasil dibuat.')
        ->assertSessionHas('plain_token');
}
```

- [ ] **Step 2: Run the focused test to verify its current expected contract**

Run: `php artisan test --filter=api_token_creation_flashes_a_success_status`

Expected: PASS after the existing controller contract is confirmed.

- [ ] **Step 3: Keep `ApiTokenController::store()` unchanged**

The browser bridge consumes the existing `status` and `plain_token` messages; no controller API changes are required.

- [ ] **Step 4: Re-run the focused test**

Run: `php artisan test --filter=api_token_creation_flashes_a_success_status`

Expected: PASS.

### Task 2: Add the global Gooey toast bridge

**Files:**
- Modify: `resources/js/app.js`
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Import the runtime and required stylesheet**

```js
import React from 'react';
import { createRoot } from 'react-dom/client';
import { GooeyToaster, gooeyToast } from 'goey-toast';
import 'goey-toast/styles.css';
```

- [ ] **Step 2: Mount one theme-aware toaster and expose `window.appToast`**

The helper maps success, error, warning, info, loading, update, dismiss, and confirm calls to `gooeyToast`. Its shared options use top-right, `subtle` animation, progress, close control, mobile swipe, and a maximum queue. `confirm()` displays a warning toast whose action invokes the provided callback.

- [ ] **Step 3: Add one root element and replace Bootstrap session-toast markup**

```blade
<div id="app-toast-root" aria-live="polite" aria-atomic="true"></div>
```

The layout serializes `session('status')` or `session('error')` as JSON and calls `window.appToast` once the Vite bundle is ready.

- [ ] **Step 4: Build the assets**

Run: `npm run build`

Expected: Vite completes successfully and emits built assets under `public/build`.

### Task 3: Move page-level notifications to the bridge

**Files:**
- Modify: `resources/views/settings.blade.php`
- Modify: `resources/views/dashboard.blade.php`
- Modify: `resources/views/api-tokens/index.blade.php`

- [ ] **Step 1: Replace the settings Bootstrap toast helper**

```js
function showSettingsToast(message, isError = false) {
    window.appToast?.[isError ? 'error' : 'success'](message);
}
```

For asynchronous operations, show a loading toast before fetch and update it to success or error after the response.

- [ ] **Step 2: Replace the dashboard DOM toast with a deduplicated Gooey alert**

```js
window.appToast?.[severity === 'critical' ? 'error' : 'warning'](title, {
    description: message,
    id: `sensor-alert-${latest.id}`,
});
```

Remove the old alert-toast markup, timer state, and mouse handlers. Do not change `updateAlertRows()` or the dashboard data payload.

- [ ] **Step 3: Replace token revocation confirmation and copy feedback**

```js
window.appToast?.confirm('Cabut token ini?', 'Token tidak dapat digunakan lagi.', () => form.submit());
```

On successful clipboard write show `appToast.success('Token berhasil disalin.')`; on failure show `appToast.error('Gagal menyalin token.')`.

- [ ] **Step 4: Build the assets again**

Run: `npm run build`

Expected: Vite completes successfully with no unresolved React or toast imports.

### Task 4: Verify the completed integration

**Files:**
- Verify: `tests/Feature/ExampleTest.php`
- Verify: `resources/js/app.js`
- Verify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Run the Laravel suite**

Run: `php artisan test`

Expected: all tests pass.

- [ ] **Step 2: Start the application on the LAN interface**

Run: `php artisan serve --host=0.0.0.0 --port=8000`

Expected: the app is accessible at `http://192.168.1.8:8000/login`.

- [ ] **Step 3: Verify the primary rendered pages**

Open login, dashboard, settings, and API-token pages. Confirm the JavaScript bundle loads and that no Bootstrap toast markup remains.
