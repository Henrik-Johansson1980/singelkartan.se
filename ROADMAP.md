# Singelkartan — Roadmap

## Phase 1 — Foundation (current)
> Goal: working toplist on seeded data, all quality gates green

- [ ] Tooling: `rector.php`, PHPStan → level 8, composer scripts `stan` + `refactor`
- [ ] Enums: `Sex`, `HouseholdStatus`, `RegionLevel`
- [ ] Migrations: `regions`, `demographics`, `postal_lookups`, `score_cache`
- [ ] Models: `Region`, `Demographic`, `PostalLookup`, `ScoreCache` (typed, with casts)
- [ ] Factories + DatabaseSeeder (50 regions, realistic demographic data)
- [ ] DTOs: `JsonStatDataset`, `ScbObservation`, `ScbQueryPayload` (SCB PxWebApi 2 / JSON-stat2)
- [ ] `ScbClient` — typed HTTP client (retry, timeout, chunk pagination for 150k limit)
- [ ] `SingleScoreService` — single ratio, Bayesian shrinkage, min-count threshold
- [ ] `config/singelkartan.php` — SCB base URL, min_count, shrinkage K, sync year
- [ ] Artisan command `singelkartan:sync`
- [ ] Livewire `TargetForm` — sex toggle + dual-range age slider, wire:model.live
- [ ] Livewire `Leaderboard` — ranked top-10 list with score bars, medal badges
- [ ] Hero landing page (welcome.blade.php) wired to Livewire components
- [ ] Pest tests: `SingleScoreServiceTest` (ratio, shrinkage, min-count, edge cases) + `LeaderboardTest`
- [ ] `ROADMAP.md` (this file)

## Phase 2 — Interactive Map
> Goal: choropleth Sverigekarta synced with the toplist

- [ ] GeoJSON for Swedish kommuner (SCB geodata, CC0)
- [ ] Leaflet integration in Leaderboard (`wire:ignore` for the map DOM node)
- [ ] Choropleth colouring (YlOrRd / RdPu, 5–6 quantile classes)
- [ ] Min-count areas greyed out
- [ ] Hover/click: tooltip with score, single ratio, count
- [ ] Synced hover between toplist rows and map regions

## Phase 3 — Sharable Result Card
> Goal: viral sharing with auto-generated OG images

- [ ] Livewire `ResultCard` component
- [ ] OG-image generation (Browsershot or Blade → image route)
- [ ] Social share buttons (Facebook, X/Twitter, copy link)
- [ ] Auto-generated page title + meta tags per search

## Phase 4 — DeSO Granularity
> Goal: finer-grained map using DeSO areas

- [ ] Fetch `HushallDesoTyp` from SCB API (grov singel-andel per DeSO, no sex/age breakdowns)
- [ ] GeoJSON for DeSO boundaries
- [ ] Level toggle: Kommun ↔ DeSO in the UI

## Phase 5 — Premium / White-label Stub
> Goal: lay groundwork for future monetisation

- [ ] Feature flag `SINGELKARTAN_PREMIUM_MODE` in config
- [ ] White-label view: hide Singelkartan branding, show custom logo slot
- [ ] Route/middleware stub for premium access (no payment in MVP)

---

> **Detailed implementation plan (Phase 1):** `/home/henrik/.claude/plans/we-have-a-laravel-jazzy-feather.md`
