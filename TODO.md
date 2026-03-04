# Roadmap

## Auto-refresh
Periodically re-fetch data from the API without requiring a manual page reload — useful when the page is left open on a screen (e.g. at the gym). Probably a simple `setInterval` calling `loadStatus()` every N minutes, with a visual indicator if data is stale.

## Days off
Allow Rodrigo to mark a day as a planned rest day, so it doesn't count against him at the end of the week. Needs a new `rest_days` table (or a flag on `gym_logs`), a UI action to log a rest day, and balance/projection logic that treats rest days as neutral rather than missed.

## Un-logged past days
Surface a view of past days that were neither logged nor marked as rest, so it's easy to spot gaps that need to be corrected (or accepted). Likely a section in the Histórico panel showing missed days with a quick "log this day" action, respecting the existing 7-day edit window.
