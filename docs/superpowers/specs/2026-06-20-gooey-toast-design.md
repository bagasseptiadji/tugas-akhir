# Gooey Toast Integration Design

## Goal

Replace the application's browser toast notifications and the API-token revoke confirmation with one global `goey-toast` integration. The existing Laravel Blade application remains server rendered; React is used only to mount the toast provider.

## Architecture

- `resources/js/app.js` mounts one `GooeyToaster` into a dedicated element in the base layout.
- It exposes a small `window.appToast` bridge for Blade scripts. The bridge accepts `success`, `error`, `warning`, `info`, `loading`, `update`, and `confirm` calls.
- The bridge reads the active `data-theme` value before each toast so the toast matches the application light/dark theme.
- The bridge applies the shared configuration: top-right position, subtle animation, progress indicator, close control, keyboard dismissal, and mobile swipe dismissal.
- The base layout passes Laravel flash messages to the bridge after the page loads. The previous Bootstrap session toast markup and script are removed.

## Notification Coverage

- Laravel flash messages for authentication, API tokens, thresholds, and settings use `appToast.success` or `appToast.error`.
- Settings page AJAX actions use the bridge for pending, success, and failure states.
- Dashboard sensor alerts use `appToast.warning` or `appToast.error`; a stable toast id prevents repeat alerts for the same reading.
- API token revocation uses `appToast.confirm`, with a confirmation action that submits the existing form.
- The Telegram service continues delivering remote Telegram notifications. Dashboard history and detail modals remain unchanged.

## Error Handling

- Failed browser requests dismiss the pending toast and show an error toast with the response message or a safe fallback.
- A missing browser clipboard API retains the current visible token and reports a toast error rather than throwing.
- The global bridge safely no-ops only when the application bundle fails to load, avoiding JavaScript errors in Blade pages.

## Testing

- Add a Laravel feature test that verifies relevant server-side actions continue to emit the existing session flash messages.
- Build the Vite assets to confirm React, Framer Motion, and `goey-toast` bundle successfully.
- Run the Laravel test suite.
- Start the local server on `0.0.0.0` and verify the login page, dashboard, settings page, and API-token page load over the LAN URL.
