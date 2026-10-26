# Front-end

Deze map bevat zes stukken front-end code uit klantprojecten van Webreact. Ik heb ze gekozen omdat ze goed laten zien hoe ik werk: herbruikbare Blade components, Tailwind CSS, Alpine.js, TypeScript, GSAP en aandacht voor toegankelijkheid.

Alle projecten draaien op WordPress met de Roots-stack (Radicle): Blade-templates, Laravel-achtige view components in PHP, Tailwind CSS v4, Alpine.js en TypeScript met Vite.

Over de code:

- Webreact heeft toestemming gegeven om deze code te delen.
- Elk bestand begint met een korte header: project, origineel pad en wat het doet.
- Soms heb ik een stuk weggelaten. Dat staat dan in de code als `(ingekort)`.
- Ik noem alleen werk dat ik zelf heb gebouwd. Dat heb ik gecontroleerd met `git log` en `git blame`. Waar ook collega's aan een bestand hebben gewerkt, staat dat erbij.
- De bestanden draaien niet los. Ze verwijzen naar andere components (`<x-button>`, `<x-heading>`, `<x-facet>`) en helpers uit het project die hier niet in staan.

## De projecten

**Niped** is een kennisplatform over de gezondheid van kinderen. De site heeft veel artikelen, cijfers en filters. Ik was lead developer: 229 van de ongeveer 300 commits (sept.–okt. 2026) zijn van mij.

**Vonders** is een recruitmentbureau met vacatures en opdrachten. Ik was lead developer: 319 van de 556 commits (zonder bots) zijn van mij (jan.–jul. 2026). Ik heb vooral de component library, de animaties en de UI kit gebouwd.

---

## 1. Grafiek-blok (Niped)

**Map:** `niped/chart-block/`

Redacteuren kunnen in de WordPress-editor een grafiek maken. Ze plakken een tabel uit Excel of typen een CSV. Het blok toont die data als staaf-, lijn-, cirkel- of donutgrafiek.

**Wat ik heb gebouwd:** het hele blok, van het ACF-veld tot de grafiek in de browser.

- `ChartBlockService.php` parset de geplakte tekst. Hij herkent tab, puntkomma of komma als scheidingsteken. Hij snapt Nederlandse getallen zoals `1.234,5`, `17.500` en `52%`. Een lege cel wordt een gat in de lijn, geen nul.
- `Chart.php` is de Blade view component. Hij controleert het type, houdt bij een cirkel alleen de eerste reeks over en formatteert waarden.
- `chart.blade.php` toont een `<canvas>` en een verborgen `<table>` met dezelfde cijfers. Het canvas is `aria-hidden`, zodat een screenreader de tabel leest.
- `chart.ts` laadt Chart.js pas met een dynamic `import()` als de grafiek bijna in beeld komt (IntersectionObserver). Pagina's zonder grafiek laden de library dus niet. De kleuren komen uit de CSS custom properties van het thema. Lange labels worden afgebroken. Met `prefers-reduced-motion` staat de animatie uit.
- `chart.css` geeft het canvas een vaste aspect-ratio. Zo verspringt de pagina niet als de grafiek later wordt getekend.

**Technieken:** Blade components, PHP 8 (constructor promotion, `match`), TypeScript, lazy loading, Chart.js tree-shaking, Tailwind v4, toegankelijkheid.

**Rol:** enige auteur van alle bestanden in deze map (100% volgens `git blame`).

## 2. Filterpaneel voor archiefpagina's (Niped)

**Map:** `niped/archive-filters/`

Op de overzichtspagina's kunnen bezoekers artikelen filteren. Het filteren zelf doet de plugin FacetWP. Ik heb de interface eromheen gebouwd.

**Wat ik heb gebouwd:**

- `archive-filters.blade.php` en `ArchiveFilters.php`: een knop "Filteren" die een paneel opent. De knop heeft `aria-expanded` en `aria-controls`. Elke filtergroep is een `role="group"` met een label. Met Escape sluit het paneel.
- `archive-filters.ts`: een Alpine.js-component. Op de knop staat hoeveel filters gekozen zijn, bijvoorbeeld "Filteren (3)". Het getal blijft gelijk met FacetWP na elke refresh.
- `loading.ts`: tijdens het laden ziet de bezoeker grijze "ghost cards" in plaats van een lege pagina. De lijst krijgt `aria-busy`.
- `reset.ts`: de link "Filters wissen" werkt ook zonder JavaScript. Met JavaScript reset hij alle filters zonder de pagina te herladen. Een Cmd/Ctrl-klik opent de link nog steeds in een nieuw tabblad.
- `archive-filters.css`: mobile-first layout. Lege filtergroepen worden verborgen met `:has()`.

**Technieken:** Alpine.js (`x-data`, `x-collapse`, `x-cloak`), TypeScript, progressive enhancement, ARIA, Tailwind v4.

**Rol:** enige auteur (100% volgens `git blame`).

## 3. Kerncijfers met count-up animatie (Niped)

**Map:** `niped/count-up-statistics/`

Niped toont veel cijfers, zoals "+23.879" of "8,4". Als die cijfers in beeld komen, tellen ze op vanaf nul.

**Wat ik heb gebouwd:**

- `card-stats.ts` leest de tekst van het cijfer en splitst die op in voorvoegsel, getal en achtervoegsel. Tijdens het tellen blijft de Nederlandse notatie (punt voor duizendtallen, komma voor decimalen) gelijk. GSAP animeert de waarde. Cijfers die samen in beeld komen, starten kort na elkaar (stagger).
- Ik gebruik een IntersectionObserver in plaats van ScrollTrigger. De reden staat in de code: lazy-loaded afbeeldingen schoven de cijfers naar beneden, waardoor ScrollTrigger te laat startte.
- Met `prefers-reduced-motion` toont de code direct het eindgetal.
- De functie werkt ook voor nieuwe HTML die FacetWP inlaadt, en ruimt observers op als die HTML weer verdwijnt.
- `section-statistics.blade.php` en `.css`: een rij kerncijfers. Het aantal items gaat als CSS-variabele naar de grid, zodat 2, 3 of 4 items altijd netjes de breedte vullen.

**Technieken:** GSAP, TypeScript, IntersectionObserver, `Intl.NumberFormat`, toegankelijkheid, CSS grid met custom properties.

**Rol:** enige auteur (100% volgens `git blame`).

## 4. GSAP-animatiemodules (Vonders)

**Map:** `vonders/gsap-animations/`

Een set kleine, herbruikbare animatiemodules. Een developer of redacteur zet een class op een element, zoals `.fx-parallax-up` of `.fx-zoom-in`, en de module doet de rest.

**Wat ik heb gebouwd:**

- `tiltedLoop.ts`: een schuine, oneindige carrousel met klantlogo's. Als je scrollt, gaat hij sneller en draait hij mee met de scrollrichting (GSAP Observer). Buiten beeld pauzeert de animatie (ScrollTrigger). Met `prefers-reduced-motion` beweegt hij niet.
- `parallax.ts` en `zoom.ts`: getypeerde modules met opties en standaardwaarden. Ze markeren elementen met `data-animated`, zodat ze niet twee keer starten.
- `horizontalLoop.ts`: de kern van deze loop is de openbare `horizontalLoop`-helper van GreenSock. Ik heb die naar TypeScript omgezet en ingekort weergegeven. `fillRowWithClones` en `setFixedRowWidth` heb ik zelf geschreven: ze vullen de rij met genoeg kopieën voor elke schermbreedte.

**Technieken:** GSAP (ScrollTrigger, Observer), TypeScript-interfaces.

**Rol:** volgens `git blame` ben ik enige auteur van deze vier bestanden. Let op: het weggelaten deel van `horizontalLoop.ts` is code van GreenSock.

## 5. UI kit (Vonders)

**Map:** `vonders/ui-kit/`

De UI kit is een pagina-template in WordPress. Het is een levende styleguide: hij toont typografie, kleuren en alle varianten van de componenten. Hij gebruikt de echte Blade components, dus wat je ziet is wat er op de site staat. Designers en developers checken hier of de code klopt met het design.

**Wat ik heb gebouwd:**

- `views/ui-kit.blade.php`: de pagina. Ik heb hier alleen typografie, kleuren en knoppen laten staan. De rest is ingekort.
- `views/components/ui-kit*.blade.php` en `Color.php`: kleine components (hoofdstuk, sectie, kleurstaal) met named slots.
- `ui-kit.css`: styling met lokale CSS custom properties.

**Technieken:** Blade components en slots, Tailwind v4, design tokens.

**Rol:** de components en de CSS zijn 100% van mij. Van de pagina zelf ben ik ongeveer 87% van de regels (699 van 806); een collega heeft ook secties toegevoegd.

## 6. Figma naar code controleren (Niped)

**Map:** `niped/figma-verification/`

Bij Niped wilde ik zeker weten dat componenten precies zo zijn als het Figma-design. Daarvoor heb ik twee scripts geschreven.

- `figma-mcp.sh` praat met de lokale Figma Dev Mode MCP-server. Het haalt de design context, metadata en screenshots van een Figma-node op.
- `render-check.mjs` opent de UI kit met Playwright op meerdere schermbreedtes. Het meet de echte computed styles (padding, gap, kleuren, radius, breedte en hoogte) en maakt screenshots. Die vergelijk ik met de waarden uit Figma.

Zo vind ik fouten die je in de broncode niet ziet. Bijvoorbeeld een Tailwind-class die niets doet, of een latere CSS-regel die een andere overschrijft.

**Technieken:** Playwright, Node.js, shell scripting, Figma Dev Mode MCP.

**Rol:** enige auteur van beide scripts. Ik gebruik ze samen met een AI-assistent (Claude Code), maar je kunt ze ook gewoon zelf draaien.
