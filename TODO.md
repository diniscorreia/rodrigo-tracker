# Roadmap

## Auto-refresh
Periodically re-fetch data from the API without requiring a manual page reload — useful when the page is left open on a screen (e.g. at the gym). Probably a simple `setInterval` calling `loadStatus()` every N minutes, with a visual indicator if data is stale.

## Days off
Allow marking individual days or entire periods (e.g. a vacation week) as excused rest. Those days would be neutral — not logged, but not penalised either. PIN-protected, same as logging a day. Needs a `rest_days` table with a date range, a UI action to set a period, and balance/projection logic that skips excused days when calculating missed sessions.

## Unlog a day
Allow correcting a mistaken log entry by removing it — PIN-protected, limited to the past 7 days (matching the existing log window). The soft-delete mechanism is already in place (`deleted_at`); this is mostly a UI action to surface logged days and let you undo one.
