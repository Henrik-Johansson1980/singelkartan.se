# Build: "Singelkartan" – en viral singel-demografi-app (Laravel 13 / TALL + Boost)

## Roll
Agera som en SENIOR Laravel-utvecklare. Skriv produktionsklar, idiomatisk och väl-typad
kod: tunna controllers/komponenter, logik i dedikerade service-/action-klasser, strict
types (`declare(strict_types=1);`), explicita return-typer och PHPDoc-generics där det
behövs. Följ SOLID, undvik premature abstraction, motivera kort viktiga designbeslut i
kommentarer/README. Tänk testbarhet, prestanda och säkerhet från start.

## Mål
Bygg en webbapp där användaren väljer en målgrupp (kön + åldersspann) och får en rolig,
delbar TOPPLISTA + karta över de områden i Sverige med högst andel singlar för den
målgruppen. Tonen ska vara lättsam och viral ("Här bor singlarna 💑"), inte en torr
statistikrapport.

VIKTIGT om scope: appen visar ENBART aggregerad områdesstatistik (kommun/DeSO) – ALDRIG
något om enskilda personer. Ingen inloggning, ingen tracking, inga personuppgifter i MVP.

## Teknisk stack (använd Boost-verktygen för versionskorrekt kod)
- Laravel 13 (PHP 8.3+)
- TALL: Tailwind CSS v4, Alpine.js, Livewire (senaste), Blade
- UI: använd ENBART Livewires egna komponenter och funktioner (Livewire-komponenter,
  wire:model / wire:click / wire:loading, wire:navigate för SPA-känsla) plus egna
  Blade-komponenter stylade med ren Tailwind. ANVÄND INTE Flux UI eller något annat
  betalt/externt komponentbibliotek.
- Pest för tester
- SQLite för MVP (lätt att byta till Postgres/MySQL senare)
- Använd Laravel Boost MCP-verktygen (search-docs m.fl.) för att verifiera Laravel 13-API:er
  innan du genererar kod.

## Arkitektur: single-page-känsla, ingen dashboard
- Bygg som en Livewire-driven app med SPA-känsla via wire:navigate — INTE en separat
  JS-SPA (ingen Vue/React-frontend, inget separat API-lager). Allt i en Laravel-kodbas.
- INGEN dashboard, ingen inloggning, inga konton. Appen är ett enda lekfullt flöde:
  hero + formulär → topplista + karta → delbart resultatkort. Allt ryms i princip på
  en sida (ev. en separat "Om/Metodik"-sida och en kort integritetspolicy).
- Artisan-syncen (singelkartan:sync) körs via CLI/scheduler, inte via något admin-UI.

## Kodkvalitet & statisk analys (obligatoriskt)
- Larastan (PHPStan för Laravel) på högsta rimliga nivå (sikta på level 8/max). All kod
  ska vara grön i `vendor/bin/phpstan analyse` utan baseline-fusk där det går att undvika.
- Rector (med set för PHP 8.3 + LaravelSetList) för automatiserade refactorings och
  uppgraderingsregler. Lägg en `rector.php` config.
- Laravel Pint för kodstil (PSR-12 + Laravel-preset).
- Pest för tester (unit + feature), sikta på meningsfull coverage av domänlogiken.
- composer-scripts: `composer lint` (Pint), `composer stan` (Larastan),
  `composer refactor` (Rector --dry-run) och `composer test` (Pest).
- En enkel GitHub Actions-workflow som kör Pint, Larastan, Rector --dry-run och Pest
  på varje push/PR.
- Strict types överallt + typed properties; undvik `mixed` där det går. Backed enums för
  kön och hushållsställning, castade i modellerna.
- Sätt upp Larastan, Rector och Pint TIDIGT så att all genererad kod hålls grön löpande.

## Datakällor & API (verifierade, färskaste årsdata per juni 2026)
### API: SCB Statistikdatabasen via PxWebApi 2
- Använd PxWebApi 2 (lanserad okt 2025, ersätter v1; v1 funkar bara t.o.m. årsskiftet
  2026/2027). Bygg mot v2 från början.
- Bas-URL (verifiera exakt path i SCB:s docs innan kodning):
  https://api.scb.se/OV0104/v2beta/api/v2 — hämta via GET (query i URL:en) eller POST.
  Max 150 000 värden per uttag → paginera/dela upp.
- Format: JSON-stat2 (rekommenderat för parsing).
- Licens: ALL SCB:s öppna statistik är CC0 1.0 Universal → fri även kommersiellt, INGEN
  attributionsplikt. Visa ändå "Källa: SCB" av god ton (frivilligt).
- Bygg HTTP-anropen med Laravels Http-klient (retry/timeout) och ett TYPAT DTO-lager för
  SCB-svaret — ingen array-soppa rakt in i logiken.

### Tabeller att använda
| Behov | Tabell | Referensår | Innehåll |
|---|---|---|---|
| Singel-täljare (kommun) | `HushallT08CKM` | 2025 (publ. 2026-03-24) | region + ålder + hushållsställning + kön |
| Nämnare + könsbalans | `BefolkningCKM` | 2025 (publ. 2026-02-24) | folkmängd per region/civilstånd/ålder/kön |
| Finkornig karta (DeSO) | `HushallDesoTyp` | 2011–2025 | hushållstyp per DeSO (grov) |
| "Live"-totaler (valfritt) | `MBefStatRegionCKM` | t.o.m. 2026M04 | region + kön, månadsvis (komplement) |

- VIKTIGT: `HushallT08CKM` är huvudkällan. Variabeln "hushållsställning" innehåller bl.a.
  "ensamboende person" och "person i samboförhållande". SINGEL = "ensamboende person".
  Sambos hamnar i "person i samboförhållande" och EXKLUDERAS därmed automatiskt — det är
  hela poängen och löser sambo-problemet utan att vi behöver civilstånd.
- DeSO-begränsning: `HushallDesoTyp` ger bara hushållstyp (Ensamstående/Sammanboende/
  Övriga) — inte kön/ålder. Så: kommun = full upplösning, DeSO = bara grov singel-andel.
- Hämta tabell-id/variabler dynamiskt via API:ets navigering där det går, istället för
  att hårdkoda struktur.

### Stödkällor (egna licenser — verifiera separat, CC0 gäller bara SCB-datan)
- Postnummer → kommun: SCB har INTE postnummer som geonivå. Seeda en lookup-tabell från
  öppet postnummer-kommun-register. Dokumentera källa + licens.
- Kommun/DeSO-GeoJSON för kartan: använd öppna kommungränser (t.ex. SCB:s geodata, CC0).

## Datamodell (migrations + Eloquent)
- regions: code (kommunkod/DeSO-kod), name, level (kommun|deso), lat, lng, geojson (nullable)
- demographics: region_id, year, sex (enum), age_min, age_max, household_status (enum), count
- postal_lookups: postal_code, region_code
- score_cache: region_id, sex, age_min, age_max, single_ratio, single_count, computed_at

## Kärnlogik
1. Artisan-kommando `php artisan singelkartan:sync`:
   - Hämtar `HushallT08CKM` + `BefolkningCKM` (+ ev. `HushallDesoTyp`) via PxWebApi 2,
     paginerat pga 150k-gränsen.
   - Normaliserar till `demographics` via typade DTO:er.
   - Cachar resultatet; schemaläggs (data är årsfärsk → kör t.ex. veckovis).
2. `app/Services/SingleScoreService.php` — för en målgrupp (kön + åldersspann) per region:
   - single_count = antal med hushållsställning = "ensamboende person"
   - total_count = befolkning i samma målgrupp (från `BefolkningCKM`)
   - single_ratio = single_count / total_count
   - "Singelkartan-score" 0–100: kombinera ratio och absolut antal (Bayesiansk shrinkage /
     viktning) så små områden inte får 100% av ett fåtal personer.
   - MIN-ANTAL-TRÖSKEL: visa bara områden med fler än N personer i målgruppen (kvalitet +
     skydd mot att peka ut pyttesmå områden).
3. Valfri viral vinkel (separat beräkning från `BefolkningCKM`): könsbalans per område
   ("X fler singelmän än singelkvinnor 30–39 här").
4. Metodik-ruta som tydligt förklarar uträkningen (transparens = trovärdighet).

## UI / Livewire-komponenter (endast Livewire + egna Blade-komponenter)
- `TargetForm` (Livewire): val av kön + åldersspann (slider) med wire:model.live; Alpine
  bara för ren klient-UX. wire:loading-state under sökning.
- `Leaderboard` (Livewire): topplista över de singeltätaste områdena för målgruppen +
  en Sverigekarta (Leaflet + kommun-GeoJSON) färgkodad efter score, via Alpine/wire:ignore
  där det behövs.
- `ResultCard` (Livewire/Blade): stort delbart kort ("Topp-5 singeltätaste områden för
  kvinnor 30–39") med auto-genererat OG-image (Browsershot eller Blade→image-route) för
  snygga delningar.
- Återanvändbara egna Blade-komponenter (knappar, kort, badges, slider) i ren Tailwind v4.
- wire:navigate mellan vyer för SPA-känsla. Allt mobilanpassat, snabbt, med glimten i ögat.

## Frontend-design & känsla (lekfullt men polerat)
Skapa ett snyggt, lekfullt och modernt gränssnitt – tänk "dejtingapp möter datavisualisering",
inte myndighetsrapport. Glimten i ögat ska synas i hela UX:en.

### Designspråk
- Mjuk, varm palett: rosa/korall + en djup kontrastfärg (t.ex. plommon/marinblå), mjuka
  rundade hörn (rounded-2xl), generös whitespace, subtila skuggor och gradienter.
- Lekfulla detaljer: emoji/ikoner (💘 📍 💑), mikrointeraktioner och små animationer
  (Alpine + Tailwind transitions) på hover/klick och när resultat laddas in.
- Tydlig typografi-hierarki, stora rubriker med personlighet. Mobile-first, känns snabbt
  och responsivt. Dark mode-stöd via Tailwind.
- wire:loading med en charmig laddnings-state ("Letar singlar... 🔍") istället för spinner.

### Hero / landning
- Stor playful hero med rubrik + underrubrik och formuläret direkt synligt
  ("Var bor singlarna? Hitta dem på Singelkartan 👇").

### Formulär (TargetForm – Livewire)
- Kön: snygga toggle-/segmenterade knappar (inte tråkig dropdown).
- Åldersspann: en dual-range-slider (Alpine för UX, wire:model för state) med live-uppdatering.
- Valfritt: region/län-filter. Allt med wire:model.live så resultatet uppdateras direkt.

### Karta (Leaderboard – Livewire + Leaflet)
- Interaktiv Sverigekarta med Leaflet + kommun/DeSO-GeoJSON, inkapslad med wire:ignore så
  Livewire inte stör kart-instansen; uppdatera lager via Alpine när data ändras.
- FÄRGKODNING (choropleth): sekventiell färgskala där högre singel-score = varmare/intensivare
  färg. Använd en perceptuellt jämn skala (t.ex. ColorBrewer "YlOrRd" eller "RdPu") och
  dela in i 5–6 klasser (jenks/kvantil). Tydlig färglegend med intervall.
- Gråa ut / mönstra områden under min-antal-tröskeln så de syns som "för lite data".
- Hover/klick på område → tooltip/popup med score, andel singlar och antal i målgruppen.

### Topplista & resultatkort
- Snygg rankad topplista (1–10) bredvid kartan, med medaljer/badges för topp-3, score-mätare
  (progress/donut) och "andel singlar"-procent.
- Delbart ResultCard med auto-genererat OG-image i samma lekfulla stil (med Singelkartan-logga).
- Synkad hover mellan listan och kartan (hovra rad → markera område, och tvärtom).

### Tillgänglighet trots lekfullheten
- Färg får aldrig vara enda informationsbäraren (lägg siffror/etiketter), kontrast enligt
  WCAG 2.1 AA, fokusbara kontroller och vettiga ARIA-labels på kartinteraktioner.

## Affärs-/tillväxthakar (bygg in från start)
- Delningsknappar + auto-genererat OG-image per resultat (viralitet).
- "Premiumläge"-stub: white-label-vy för mäklare/dejtingappar (dölj Singelkartan-branding,
  egen logga) — bara grund (feature flag), ingen betalning i MVP.

## Svensk lag & regelefterlevnad
### GDPR / dataskydd (kraftigt förenklat tack vare ren aggregerad design)
- Appen visar ENBART aggregerad, redan röjandeskyddad SCB-statistik → INGA personuppgifter
  om de personer statistiken beskriver. Försök ALDRIG bryta ned/berika datan så att
  individer kan identifieras.
- Ingen inloggning, ingen onödig tracking, inga icke-nödvändiga cookies i MVP → då krävs
  ingen cookie-banner. Om analytics läggs till senare: minimalt, anonymiserat, med samtycke.
- Kort, tydlig integritetspolicy (svenska) som säger att ingen persondata samlas in.
### Statistik- & röjandeskydd
- Från referensår 2025 har SCB infört en liten kontrollerad slumpmässig osäkerhet i alla
  uppgifter (röjandeskydd). Räkna aldrig på/visa inte maskerade småtal; håll min-antal-
  tröskeln så att inga slutsatser dras om enstaka personer.
- Håll dig på kommun/DeSO — aldrig adress- eller fastighetsnivå.
### Marknadsföringslagen (2008:486)
- Om annonser/leads/white-label: reklam och sponsrade notiser ska tydligt märkas och får
  inte vilseleda.
### Innehåll & ton
- Disclaimer: "singel" = ensamboende enligt SCB:s hushållsstatistik. Det är en
  uppskattning för skojs skull (särbo räknas t.ex. som ensamboende), inte en exakt mätning.
  Håll humorn på områdesnivå, aldrig utpekande av personer eller adresser.
### Tillgänglighet
- Sikta på WCAG 2.1 AA (särskilt viktigt för white-label mot kommuner/regioner).

## Generera dessa config-filer åt mig (komplett innehåll, inte bara instruktioner)
Skapa färdiga, kompletta filer:
1. `rector.php` – med PHP 8.3-set + Rector Laravel-set (LaravelSetList), inriktad på app/,
   config/, database/, tests/. Konfigurera så den samspelar med Pint (ingen stil-konflikt).
2. `phpstan.neon` (Larastan) – level 8 (max), paths app/, sökväg till bootstrap, rimliga
   excludes, ingen onödig baseline.
3. `pint.json` – Laravel-preset med ev. extra regler (declare_strict_types m.m.).
4. `.github/workflows/ci.yml` – GitHub Actions som på push/PR kör: composer install,
   Pint (--test), Larastan, Rector (--dry-run) och Pest. PHP 8.3, cache av composer.
5. composer-scripts (`lint`, `stan`, `refactor`, `test`) i composer.json.

## Verifiera & visa ett konkret API-anrop mot PxWebApi 2
- Använd Boost/SCB:s dokumentation för att verifiera den EXAKTA bas-pathen och query-strukturen
  för PxWebApi 2 (v2beta) innan du skriver sync-koden — gissa inte.
- Visa sedan ett komplett, körbart exempel mot tabell `HushallT08CKM`: en POST med JSON-query
  som väljer hushållsställning = "ensamboende person", ett par åldersgrupper, kön och några
  regioner, med response-format JSON-stat2. Inkludera ett exempel på hur JSON-stat2-svaret
  ser ut och hur ditt typade DTO-lager parsar det till `demographics`-rader.
- Implementera anropet i en dedikerad `ScbClient`-klass (Laravels Http-klient, retry + timeout),
  med hänsyn till 150 000-värdens-gränsen (paginering/uppdelning av uttaget).

## Leverans
- Seeders med exempeldata så appen funkar direkt utan live-API-anrop.
- Pest-tester för SingleScoreService (ratio, shrinkage, min-antal-tröskel, edge cases).
- README: hur man kör sync, var SCB-tabell-id:n konfigureras (.env), hur man byter nivå
  (kommun ↔ DeSO), samt hur man kör lint/stan/refactor/test.
- All kod ska passera Pint, Larastan (level 8) och Rector --dry-run innan den anses klar.
- Använd Boost för att hålla all kod idiomatisk för Laravel 13.

Börja med: datamodell + DTO-lager + SingleScoreService + en fungerande topplista på
exempeldata (med min-antal-tröskel). Lägg karta och OG-image-generering i ett andra steg.

