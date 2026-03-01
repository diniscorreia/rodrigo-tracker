# O Rodrigo Foi Treinar?

A personal gym accountability tracker with a monetary jar system. Every week Rodrigo either earns or loses money based on how many days he goes to the gym.

## How it works

| Days / Week | Result   |
|-------------|----------|
| 0–3 dias    | −1,00 €  |
| 4 dias      | 0,00 €   |
| 5 dias      | +0,75 €  |
| 6+ dias     | +1,00 €  |

A streak bonus of +0,50 € applies every 4 consecutive good weeks (5+ days).

## Stack

- **Backend:** PHP 8.2, SQLite (via PDO)
- **Frontend:** Vanilla JS, CSS (no frameworks)
- **Font:** Inter (Google Fonts)

## Running locally

```bash
php -S 0.0.0.0:8000
```

Then open `http://localhost:8000`.

The SQLite database is auto-created at `data/rodrigo.db` on first request. The default PIN is `1234` — change it via the database directly after setup.

## Data

All data lives in `data/` which is gitignored:

- `rodrigo.db` — SQLite database (gym logs, withdrawals, settings)
- `rate_*.json` — IP-based rate limiting for PIN attempts

## Security

- PIN is stored as a bcrypt hash (cost 12) in the database — never in source code
- All write operations require PIN verification
- Rate limiting: 10 PIN attempts per 15 minutes per IP
- Soft deletes: gym logs are never permanently removed
