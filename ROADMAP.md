# Singelkartan — Roadmap

## Phase 1 — Foundation ✅ mostly done
> Goal: working toplist on seeded data, all quality gates green

- [x] Tooling: PHPStan → level 8, composer script `stan` added *(rector not installed — skipped)*
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
- [ ] Pest tests: `SingleScoreServiceTest` (ratio, shrinkage, min-count, edge cases) + `LeaderboardTest`

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
