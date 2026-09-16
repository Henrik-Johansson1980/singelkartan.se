# Singelkartan 💘

Singelkartan is a playful, shareable Swedish "singles map" — pick a target group (sex +
age range) and get a ranked leaderboard plus an interactive choropleth map of the Swedish
regions with the highest share of single people for that group, built on aggregated SCB
(Statistics Sweden) household statistics.

> "Här bor singlarna" — a lighthearted data-viz toy, not a dry statistics report. It
> shows **only aggregated area-level statistics** (municipality/DeSO) — never anything
> about individuals. No login, no tracking, no personal data.

## Tech stack

- **Laravel 13** (PHP 8.5) — single Laravel codebase, no separate API/frontend
- **TALL stack**: Tailwind CSS v4, Alpine.js, **Livewire 4**, Blade
- **Pest 4** for testing, **Larastan** (PHPStan level 8) for static analysis, **Rector**
  for automated refactoring, **Pint** for code style
- **SQLite** by default (swappable to Postgres/MySQL)
- **Leaflet** for the interactive map
- Data source: [SCB](https://scb.se) PxWebApi 2 (JSON-stat2), licensed CC0 1.0 Universal

## How it works

1. `php artisan singelkartan:sync` fetches household and population statistics from SCB
   (`TAB6689` for singles by region/age/sex, plus DeSO household-type data), normalizes
   them into the `demographics` table via typed DTOs (`app/Data/Scb/*`), and pre-computes
   scores for standard age/sex combinations.
2. `App\Services\SingleScoreService` turns a target group into a **Singelkartan score**
   per region: `single_ratio = single_count / total_count`, adjusted with Bayesian
   shrinkage so small regions can't hit 100% on a handful of people, and areas below a
   minimum-count threshold are excluded/greyed out (both a data-quality and a
   disclosure-protection measure).
3. Three Livewire components drive the whole page:
   - `TargetForm` — sex toggle + dual age-range slider, syncs to the URL via `#[Url]`
   - `Leaderboard` — ranked top-10 list + Leaflet choropleth map (kommun or DeSO level),
     synced hover/click between list and map
   - `ResultCard` — shareable summary card with social share buttons and an
     auto-generated OG image (`/og-image`, rendered with PHP GD)
4. A token-gated `/embed` route (`RequiresPremium` middleware) renders a white-label
   layout with no Singelkartan branding — a stub for future white-label/premium use,
   feature-flagged and unpaid in the MVP.

"Single" here means *ensamboende* (living alone) per SCB's household statistics — an
estimate for fun, not an exact measurement (e.g. non-cohabiting partners count as living
alone). See `PROJECT.md` for the full product/data spec.

## Getting started

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed   # sample data so the app works without live SCB calls
composer run dev      # server + queue worker + logs + vite, all in one
```

Then visit `http://localhost:8000`.

## Syncing real SCB data

```bash
php artisan singelkartan:sync              # kommun-level demographics + score cache
php artisan singelkartan:sync --deso        # also sync DeSO-level areas
php artisan singelkartan:sync --dry-run     # fetch & parse only, no writes
php artisan singelkartan:sync --cache-scores # recompute score cache without re-fetching
```

Sync settings (SCB base URL, sync year, thresholds, age bounds) live in
`config/singelkartan.php`, backed by these `.env` variables:

| Variable | Purpose | Default |
|---|---|---|
| `SCB_BASE_URL` | PxWebApi 2 base URL | `https://api.scb.se/OV0104/v2beta/api/v2` |
| `SINGELKARTAN_SYNC_YEAR` | Reference year to fetch | `2025` |
| `SINGELKARTAN_MIN_COUNT` | Minimum group size to show a region | `20` |
| `SINGELKARTAN_SHRINKAGE_K` | Bayesian shrinkage strength | `50` |
| `SINGELKARTAN_AGE_MIN` / `SINGELKARTAN_AGE_MAX` | Hard age bounds for the slider | `18` / `80` |
| `SINGELKARTAN_PREMIUM_MODE` | Enable the token-gated `/embed` white-label route | `false` |
| `SINGELKARTAN_PREMIUM_TOKEN` | Bearer/query token required when premium mode is on | — |

## Switching kommun ↔ DeSO

The level toggle lives on `TargetForm` (`#[Url]`-backed). Kommun mode gives full
resolution by sex/age; DeSO mode gives finer-grained areas but only a coarse single-share
(DeSO source data doesn't break down by sex/age). `Leaderboard`/`ResultCard` and the
frontend `choropleth.js` (which lazy-loads the right GeoJSON) both branch on this level.

## Quality gates

Run the full pipeline before every commit — it must be green:

```bash
composer run format       # Pint + Rector, auto-fix
php artisan test --compact
```

Individual checks:

```bash
composer lint          # Pint (style)
composer lint:check    # Pint --test
composer stan           # Larastan / PHPStan, level 8
composer rector          # Rector --dry-run
```

CI (`.github/workflows/`) runs the same checks on every push/PR.

## Project structure

```
app/Console/Commands/SingelkartanSync.php   SCB sync command
app/Data/Scb/                                Typed DTOs for SCB JSON-stat2 responses
app/Enums/                                   Sex, HouseholdStatus, RegionLevel
app/Livewire/                                TargetForm, Leaderboard, ResultCard
app/Models/                                  Region, Demographic, PostalLookup, ScoreCache
app/Services/ScbClient.php                   Typed SCB PxWebApi 2 HTTP client
app/Services/SingleScoreService.php          Ratio + Bayesian shrinkage scoring
resources/js/choropleth.js                   Leaflet map rendering/interaction
public/geojson/                              Kommun + DeSO boundary data (CC0)
```

See `ROADMAP.md` for delivered phases and `PROJECT.md` for the original product/data
specification (in Swedish).

## Legal & compliance notes

The app only ever shows aggregated, already disclosure-protected SCB statistics — no
personal data about the individuals behind the numbers, no login, no unnecessary
tracking or cookies. See `PROJECT.md` for the full GDPR/statistical-disclosure rationale.
