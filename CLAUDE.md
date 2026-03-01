# CLAUDE.md — Project Reference

## Project: O Rodrigo Foi Treinar?

A single-user gym accountability tracker. One person (Rodrigo) logs gym visits; the balance goes up or down based on weekly performance.

## File structure

```
├── index.php          # HTML shell (all markup, no PHP logic)
├── api.php            # API router — all endpoints at api.php?action=X
├── init.php           # DB init, helpers, balance engine, projection
├── assets/
│   ├── css/styles.css # All styles — single file
│   ├── js/app.js      # All frontend logic — single IIFE
│   └── img/logo-bp.svg
├── data/              # Gitignored — auto-created on first run
│   └── rodrigo.db     # SQLite database
├── .htaccess          # Apache rewrite rules
├── README.md
└── CLAUDE.md
```

## Tech constraints

- **No build step** — vanilla JS/CSS, no npm, no bundler
- **No framework** — plain PHP, no Composer dependencies
- **Single SQLite file** — no MySQL, no Redis
- **PHP 8.2+** — uses `str_contains()`, named args, etc.

## Database schema

```sql
gym_logs    (id, log_date UNIQUE, logged_by, created_at, deleted_at)
withdrawals (id, amount, note, logged_by, created_at)
settings    (key, value)  -- pin_hash, challenge_end_date
```

Gym logs use soft deletes (`deleted_at`). `logged_by` is kept in schema for history display but is always stored as `''` (single-user mode).

## API endpoints

All at `api.php?action=X`. POST requires `Content-Type: application/json`.

| Action       | Method | PIN required | Notes                          |
|--------------|--------|-------------|--------------------------------|
| status       | GET    | No          | Balance, streak, current week  |
| history      | GET    | No          | Paginated past weeks           |
| log_day      | POST   | Yes         | Defaults to today, 7-day window|
| delete_day   | POST   | Yes         | Soft delete                    |
| withdraw     | POST   | Yes         | Deducts from balance           |
| verify_pin   | POST   | No          | Returns `{ok: true}` on success|

## Frontend architecture

Single IIFE in `app.js`. Key functions:

- `loadStatus()` — fetches API and calls all render functions
- `renderJar(balance)` — updates balance card number + colour class
- `renderCurrentWeek(week, today)` — updates ring, dots, label
- `renderStreak(streak)` — updates streak number
- `renderFab(week, today)` — enables/disables the log-today FAB
- `handleFabClick()` — PIN flow → `logToday()` if authenticated

## PIN flow

FAB → if authenticated, log directly. If not, `openPinModal(logToday)` sets `state.pinCallback`. On PIN success, callback fires instead of opening admin modal.

Gear icon (⚙) always opens PIN modal (if not auth'd) or admin modal (if auth'd).

## Euro formatting

Portuguese format: `0,00 €` (value, then non-breaking space, then sign). Use `formatEuro()` in JS. In PHP use `number_format($n, 2, ',', '.')` then append `\u{00a0}€`.

## CSS conventions

- CSS custom properties in `:root` for all colours, fonts, radii
- Desktop grid: `grid-template-columns: 280px 1fr 280px` at `min-width: 900px`
- Mobile: flex column, card order controlled via `order` property
- FAB is `position: fixed`, z-index 50
- Modals: `.modal-overlay.active { display: flex }`, each modal has `hidden` attribute

## Key IDs

- `#jar-amount` — balance number
- `#jar-label` — "Saldo" label
- `#week-ring-fill` — SVG circle for week ring (driven by `--week-ring-pct`)
- `#week-ring-label` — "X vezes / esta semana" text inside ring
- `#streak-count` — streak number
- `#fab-log` — floating action button
- `#modal-overlay` — single overlay for all modals

## Deployment

PHP built-in server for dev: `php -S 0.0.0.0:8000`
Production: any PHP 8.2+ host with SQLite support. Point webroot to project root.

## What NOT to do

- Don't add a build step or package.json
- Don't add Composer dependencies
- Don't split CSS/JS into multiple files
- Don't add user accounts — single-user only
- Don't commit `data/` directory
