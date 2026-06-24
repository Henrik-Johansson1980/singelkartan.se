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

## Phase 5 — Premium / White-label Stub ✅
> Goal: lay groundwork for future monetisation

- [x] `SINGELKARTAN_PREMIUM_MODE` + `SINGELKARTAN_PREMIUM_TOKEN` in config and `.env.example`
- [x] `RequiresPremium` middleware — passes through when `premium_mode=false`; validates Bearer/query token when enabled
- [x] `resources/views/layouts/white-label.blade.php` — no Singelkartan branding, `$logo`/`$title`/`$head`/`$scripts` slots
- [x] `GET /embed` route guarded by `RequiresPremium`, renders white-label layout
- [x] Commit

---

> **Detailed implementation plan (Phase 1):** `/home/henrik/.claude/plans/we-have-a-laravel-jazzy-feather.md`

## Phase 6 — Layout & Theme Refactor ✅
> Goal: full-viewport no-scroll layout + themeable color system

- [x] Full-viewport layout: `html`/`body` → `h-full flex flex-col overflow-hidden`; compact header bar replaces tall hero
- [x] Sidebar layout: filters (`TargetForm` + `ResultCard`) in fixed left panel (`lg:w-72`); leaderboard fills remaining width
- [x] Map enlarged: `md:w-3/5` of leaderboard panel, fills full height via `md:h-full`; list scrolls internally with `overflow-y-auto`
- [x] Tailwind v4 `@theme` semantic aliases: `--color-brand-*` (rose) and `--color-surface-*` (slate) — swap theme in 4+10 lines
- [x] All views migrated from raw `rose-*`/`slate-*` to `brand-*`/`surface-*`; `<style>` blocks use `var(--color-*)` CSS variables
- [x] Contrast pass: light-mode muted text lifted from `surface-400` → `surface-600`; dark-mode from `surface-500` → `surface-400`/`300`
- [x] Commit

## Phase 7 — Dataset completeness + click-to-highlight
> Goal: full map coverage with real SCB data + persistent selection UX

- [x] Fix SCB sync: `HushallT08CKM` → `TAB6689`, new variable codes, corrected dimension-order parser
- [x] `upsertRegions()` reads names from `kommuner.geojson` (all 290 instead of 30 hardcoded)
- [x] Sync pre-computes score_cache for standard age/sex combos after demographics insert
- [x] `Leaderboard::mount()` warms score cache before `buildMapDataJson()` (fixes grey map on first load)
- [x] Age slider snaps to 5-year SCB bands (`step=5`, bounds 20–69)
- [x] Click list item → highlight region on map + persistent popup + flyToBounds
- [x] `singelkartan:map-select` event syncs selected state back to list row highlight
- [x] Commit

> **Plan:** `/home/henrik/.claude/plans/the-datasets-does-not-tranquil-river.md`

## Phase 8 — Map reactivity, UX improvements ✅
> Goal: live map updates on filter change + usability features

- [x] Fix map not updating on filter change: replace broken `$watch('$wire.*')` with `$wire.on('map-updated', ...)` (correct Livewire v4 API); `Leaderboard` dispatches `map-updated` event after each filter change
- [x] Fix "Alla" sex filter returning no data: `queryDemographics` now aggregates male + female rows when `Sex::All` is selected (no `sex='all'` rows exist in demographics)
- [x] Zoom-reset button: floats over map, appears only when zoomed past initial Sweden view, resets to full bounds
- [x] Region search field in filters panel (between level toggle and sex/age filters): client-side Alpine filter via `singelkartan:search` window event, no Livewire round-trip
- [x] DeSO info popover: ℹ icon in leaderboard header (DeSO mode only) explaining the code format (kommunkod + tätortstyp + löpnummer)
- [x] Commit

## Phase 9 — Slider map reactivity fix ✅
> Goal: age sliders reliably trigger map updates for all filter combinations

- [x] Fix slider `$wire.entangle` conflict: replace with PHP-initialised Alpine values + `$wire.$watch` for server correction feedback
- [x] Fix `buildMapDataJson` missing fallback: add on-demand `computeTopRegions` call when cache is empty (mirrors existing `topRegions()` logic)
- [x] Precompute all 135 slider-aligned score combinations (45 age ranges × 3 sexes) via `singelkartan:sync --cache-scores`; added `--cache-scores` flag for future re-seeding
- [x] Default `ageMax` 34 → 35 (aligned to slider step-5 grid); `Sex::All` now included in precomputed ranges
- [x] Commit
