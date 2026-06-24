# Singelkartan — Roadmap

## Phase 1 — Foundation ✅
> Goal: working toplist on seeded data, all quality gates green

- [x] Tooling: PHPStan → level 8 via Larastan, Rector (PHP 8.5 ruleset), `format` + `format:check` composer scripts
- [x] `prompts/FORMAT.md` + `CLAUDE.md` Commits rule — format pipeline must be green before every commit
- [x] Enums: `Sex`, `HouseholdStatus`, `RegionLevel`
- [x] Migrations: `regions`, `demographics`, `postal_lookups`, `score_cache`
- [x] Models: `Region`, `Demographic`, `PostalLookup`, `ScoreCache` (typed, with casts)
- [x] Factories + DatabaseSeeder (30 regions, realistic demographic data + score cache)
- [x] DTOs: `JsonStatDataset`, `JsonStatDimension`, `ScbObservation`, `ScbQueryPayload`
- [x] `ScbClient` — typed HTTP client (retry, timeout, chunk pagination for 150k limit)
- [x] `SingleScoreService` — single ratio, Bayesian shrinkage, min-count threshold
- [x] `config/singelkartan.php` — SCB base URL, min_count, shrinkage K, sync year
- [x] Artisan command `singelkartan:sync` (with `--dry-run`)
- [x] Livewire `TargetForm` — sex toggle + dual-range age slider, wire:model.live
- [x] Livewire `Leaderboard` — ranked top-10 list with score bars, medal badges
- [x] Hero landing page (welcome.blade.php) wired to Livewire components
- [x] `ROADMAP.md` (this file)
- [x] Pest tests: `SingleScoreServiceTest` (ratio, shrinkage, min-count, edge cases) + `LeaderboardTest` + `TargetFormTest`
- [x] Hard age bounds (18–80) centralised in `config/singelkartan.php`
- [x] Commit

## Phase 2 — Interactive Map ✅
> Goal: choropleth Sverigekarta synced with the toplist

- [x] GeoJSON for Swedish kommuner (SCB geodata, CC0) — `public/geojson/kommuner.geojson`, 290 features
- [x] Leaflet integration in Leaderboard (`wire:ignore` for the map DOM node)
- [x] Choropleth colouring (6 quantile classes, pink palette)
- [x] Min-count areas greyed out
- [x] Hover/click: tooltip with score, single ratio, count
- [x] Synced hover between toplist rows and map regions
- [x] Replace all emoji icons with heroicons (`blade-heroicons`) and Unicode gender symbols (♀ ♂)
- [x] Commit

## Phase 3 — Sharable Result Card ✅
> Goal: viral sharing with auto-generated OG images

- [x] `#[Url]` on `TargetForm` properties — shareable URLs with browser history
- [x] `Leaderboard` + `ResultCard` read URL query params on mount
- [x] Livewire `ResultCard` component — top result summary + share buttons
- [x] Social share buttons (Facebook, X/Twitter, copy link via clipboard API)
- [x] OG-image route (`/og-image`) — PHP GD 1200×630 PNG, no extra dependencies
- [x] Dynamic `og:*` + `twitter:*` meta tags in `welcome.blade.php`
- [x] DejaVu Sans fonts bundled in `resources/fonts/` for portable OG image rendering
- [x] Commit

## Phase 4 — DeSO Granularity ✅
> Goal: finer-grained map using DeSO areas

- [x] Verified SCB API: table `TAB6568`, codelist `vs_DeSO2025`, household codes `ESMB`+`ESUB`=single, `TOTALT`=total
- [x] DeSO 2025 GeoJSON from SCB WFS (`geodata.scb.se`): reprojected SWEREF99→WGS84, simplified to 3%, 4.8 MB — `public/geojson/deso.geojson`
- [x] `DesoObservation` DTO (desoCode, kommunCode, singleCount, totalCount)
- [x] `ScbClient::fetchDesoData()` — chunks of 1 000, single request covers all 6 160 areas
- [x] `singelkartan:sync --deso` — upserts DeSO Region rows (name = municipality, code = desokod), computes Bayesian scores into `score_cache` with `sex=all, age_min=0, age_max=0`
- [x] `TargetForm` level toggle (Kommuner ↔ DeSO-områden) with `#[Url]`; sex/age controls hidden in DeSO mode
- [x] `Leaderboard` + `ResultCard` handle DeSO mode (different queries, toplist shows area code as subtitle)
- [x] `choropleth.js` lazy-loads `deso.geojson` on toggle; watches `$wire.level`, redraws with correct code property
- [x] Commit

## Phase 5 — Premium / White-label Stub
> Goal: lay groundwork for future monetisation

- [ ] Feature flag `SINGELKARTAN_PREMIUM_MODE` in config
- [ ] White-label view: hide Singelkartan branding, show custom logo slot
- [ ] Route/middleware stub for premium access (no payment in MVP)
- [ ] Commit

---

> **Detailed implementation plan (Phase 1):** `/home/henrik/.claude/plans/we-have-a-laravel-jazzy-feather.md`
