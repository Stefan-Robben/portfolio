# Back-end

Een selectie van back-endcode uit projecten die ik bij Webreact heb gebouwd. De code komt uit echte projecten. Ik heb alleen stukken weggelaten die niet bij het onderwerp horen. Die plekken zijn gemarkeerd met `// … (ingekort)`. Bovenaan elk bestand staat uit welk project en welk pad het komt.

Gevoelige gegevens (sleutels, wachtwoorden, adressen van servers, persoonsgegevens) staan niet in deze map. Bij elk onderdeel staat wat mijn rol was. Dat heb ik gecontroleerd met `git log` en `git blame`.

| Map | Project | Onderwerp | Rol |
| --- | --- | --- | --- |
| [`tvk/form-mappers`](tvk/form-mappers) | TVK Groep | Refactor van een leadmapper naar losse mappers per formulier | Refactor solo; de oude mapper was van een collega |
| [`tvk/site-drivers`](tvk/site-drivers) | TVK Groep | Driver-architectuur voor een multisite + scaffold-command | Solo |
| [`tvk/rdw`](tvk/rdw) | TVK Groep | Eigen RDW-client in plaats van een verouderde package | Solo |
| [`vonders/bullhorn-sync`](vonders/bullhorn-sync) | Vonders | Bullhorn-koppeling: rollen en opleidingsniveaus | Command solo; mapper samen met een back-endcollega |
| [`intake/claude-document-parser`](intake/claude-document-parser) | Intake-tool | Laravel-app die klantdocumenten met Claude uitleest | Solo |
| [`broring/woocommerce-and-schema`](broring/woocommerce-and-schema) | Broring | WooCommerce-prijsfilter en FAQ-schema | Solo |

---

## 1. TVK Groep: formuliermappers (`tvk/form-mappers`)

**Project.** TVK Groep is een autodealer met meerdere merken (Volvo, Lynk & Co, Polestar, Geely). De websites draaien als WordPress-multisite op de Roots-stack. Formulieren op de sites (proefrit, inruil, lease, offerte) worden als lead naar het dealersysteem LEF gestuurd. Een deel gaat ook naar het marketingplatform Ternair.

**Probleem.** Alle formulieren werden gemapt in één klasse `LeadMapper` van ongeveer 1000 regels. Elk formulier had een eigen tak in die klasse. Nieuwe formulieren toevoegen was lastig en foutgevoelig.

**Wat ik heb gebouwd.** In één refactor (commit `6e0d32cc`) heb ik de mapper opgesplitst:

- Een interface per doelsysteem: `LefFormMapperInterface` en `TernairFormMapperInterface`.
- Een eigen mapperklasse per formulier: 18 LEF-mappers en 4 Ternair-mappers.
- Een enum `VolvoFormType` die per formulier de juiste mapper teruggeeft (`lefMapper()`, `ternairMapper()`).
- `LeadMapper` doet nu alleen nog twee dingen: het formuliertype bepalen en de mapping doorgeven aan de juiste mapper. De klasse ging van ongeveer 1000 naar ongeveer 50 regels.
- Gedeelde logica staat in kleine helpers, zoals `ExtraInfoBuilder` en `UtmDataHelper`.

**Technieken.** Strategy-pattern via een interface, een backed enum met `match` als fabriek, named arguments en DTO's, het `?? throw`-patroon voor duidelijke fouten.

**Bestanden.** Begin bij `LeadMapper.php` en `VolvoFormType.php`. Kijk daarna naar `TradeInFormMapper.php` en `RequestTestDriveFormMapper.php` als voorbeeld van een mapper. Ik heb 2 van de 22 mappers opgenomen; de rest volgt hetzelfde patroon.

**Rol.** De oorspronkelijke `LeadMapper` is door een back-endcollega gebouwd. De refactor, de interfaces, de enum, alle mappers en de helpers zijn van mij. De helper `AddressHelper` (gebruikt in `RequestTestDriveFormMapper`) is van een collega en staat hier niet bij.

---

## 2. TVK Groep: site-drivers en `make-site`-command (`tvk/site-drivers`)

**Probleem.** Informatie per merk-site (logo, stylesheet, merknaam, formuliersleutel) stond verspreid over `if`/`switch`-blokken in het thema. Een nieuwe merk-site toevoegen betekende op veel plekken code aanpassen.

**Wat ik heb gebouwd.**

- `SiteDriverInterface`: één contract voor alles wat per site verschilt.
- `AbstractSiteDriver`: standaardwaarden die worden afgeleid van de merknaam en het blog-ID. Een concrete driver overschrijft alleen wat anders is (zie `VolvoSiteDriver` en `GroupSiteDriver`).
- `SiteDriverManager`: een kleine registry die per blog-ID de juiste driver oplost en de instantie cachet.
- `MakeSiteCommand` (`wp tvk make-site`): een WP-CLI-command dat een nieuwe site in één stap opzet. Het maakt de driverklasse, voegt een case toe aan de `Site`-enum, registreert de driver, maakt de configmap en de CSS-bestanden aan en past de buildconfig aan. Er is een `--dry-run` en een `--copy-from` om de config van een bestaande site te kopiëren.

**Technieken.** Driver/registry-pattern, template-method via een abstracte basisklasse, code generation, validatie vóór schrijven, dry-run.

**Bestanden.** `SiteDriverInterface.php` → `AbstractSiteDriver.php` → `SiteDriverManager.php` → `bootstrap.php` → `MakeSiteCommand.php`.

**Ingekort.** In `MakeSiteCommand.php` heb ik twee methodes ingekort: `scaffoldEmptyConfig()` (lange heredocs met standaardconfig) en `addWebpackEntries()` (string-bewerking op de buildconfig). De hulpmethode `getEntryType()` is daarom ook weggelaten.

**Rol.** Solo. Alle bestanden in deze map zijn volgens `git blame` volledig van mij.

---

## 3. TVK Groep: RDW-service (`tvk/rdw`)

**Probleem.** Een kentekencheck op de site gebruikte een externe package voor de open data van de RDW. Die package werd niet meer onderhouden en zat vast aan Guzzle 6. Daardoor kon een andere package (Google Places) niet worden geüpgraded.

**Wat ik heb gebouwd.** Een eigen, kleine service in de plugin: `Rdw` normaliseert het kenteken, `RdwApi` doet de HTTP-aanroep met Guzzle en kiest het juiste dataset-endpoint. Het resultaat heeft dezelfde vorm als bij de oude package, zodat de bestaande REST-route niet hoefde te veranderen. De client kan via de constructor worden ingeschoven, wat testen makkelijker maakt.

**Technieken.** Dependency verwijderen om een upgrade te deblokkeren, dunne API-client, constructor injection, nette afhandeling van fouten (`null` bij een netwerkfout).

**Bestanden.** `Rdw.php`, `RdwApi.php`. De REST-route die de service gebruikt is van een collega (ik heb alleen de aanroep aangepast) en staat hier daarom niet bij.

**Rol.** Solo, gemaakt met hulp van Claude Code (staat zo in de commit).

---

## 4. Vonders: Bullhorn-synchronisatie (`vonders/bullhorn-sync`)

**Project.** Vonders is een detacheerder. De vacatures en opdrachten komen uit het ATS Bullhorn en worden met artisan-commands gesynchroniseerd naar WordPress (Radicle/Acorn). Op de site kun je ze filteren met FacetWP.

**Probleem.** Vacatures en opdrachten deelden één taxonomie voor specialismen (rollen). Die moest worden gesplitst in een aparte taxonomie per post type, zodat beide archieven hun eigen rollen en filters krijgen. Bestaande posts moesten daarbij worden overgezet. Daarnaast moest er gefilterd kunnen worden op opleidingsniveau (ticket VON-124).

**Wat ik heb gebouwd.**

- `BullhornSyncRolesCommand` (`bullhorn:sync-roles`): een migratie-command dat voor alle bestaande vacatures en opdrachten de opgeslagen specialismen leest en ze aan de nieuwe taxonomie per post type koppelt. Het command heeft een `--dry-run`, een progress bar en leegt de term-cache van FacetWP.
- In `JobOrderMapper`: de keuze voor de juiste specialisme-taxonomie per post type, en een nieuwe taxonomie voor opleidingsniveau. `syncEducationLevelTerms()` zoekt termen eerst op naam, dan op slug, en maakt ze anders aan. Het vangt ook het geval op dat een term tegelijk al bestaat.

**Technieken.** Laravel-artisan-command met constructor injection, enums voor post types en taxonomieën, idempotente term-synchronisatie, dry-run.

**Bestanden.** `BullhornSyncRolesCommand.php` en `JobOrderMapper.php` (uittreksel).

**Rol.** `BullhornSyncRolesCommand` is volledig van mij. De Bullhorn-koppeling zelf (API-client, DTO's, de rest van `JobOrderMapper`) is door een back-endcollega gebouwd. In het uittreksel staan alleen mijn methodes. Het opzoeken van termen op Bullhorn-ID in het command volgt het patroon dat al in de mapper zat.

---

## 5. Intake-tool: documenten uitlezen met Claude (`intake/claude-document-parser`)

**Project.** Een interne Laravel-app (Laravel 13, PHP 8.3) voor de intake van nieuwe klanten. Een klant vult een vragenlijst in, en de app maakt daar een briefing, een design spec en een WordPress-starter van.

**Probleem.** Klanten hebben vaak al een briefing of projectomschrijving als document. Ze wilden de vragenlijst niet nog een keer handmatig invullen.

**Wat ik heb gebouwd.**

- `ClaudeDocumentParser`: stuurt de tekst van het document en de lijst met actieve vragen naar de Anthropic Messages API. Claude geeft een JSON-object terug met per vraag-key het antwoord. De parser haalt de JSON uit het antwoord en controleert die. Bij een fout gooit hij een duidelijke exception.
- `ImportIntakeFromDocument`: een action die de tekst uit het bestand haalt, de parser aanroept en een `Intake` met antwoorden opslaat. Alleen antwoorden met een bekende vraag-key worden bewaard.
- `GenerateDesignSpecBrandGuideTest`: een feature-test (PHPUnit) voor een andere Claude-aanroep in dezelfde app. De test gebruikt `Http::fake()` en `Storage::fake()` en controleert dat een brand guide (PDF) van de ingestelde disk wordt gelezen en als document naar de API gaat, en dat de flow ook werkt als het bestand ontbreekt.

**Technieken.** Laravel HTTP-client, action-klassen, repository-interface, constructor injection, prompt-ontwerp met een vast JSON-formaat, HTTP- en storage-fakes in tests.

**Bestanden.** `ClaudeDocumentParser.php` → `ImportIntakeFromDocument.php` → `GenerateDesignSpecBrandGuideTest.php`.

**Opmerking.** Er is (nog) geen aparte test voor `ClaudeDocumentParser` zelf. Daarom staat hier de test van de design spec, die dezelfde aanpak laat zien.

**Rol.** Solo: ik ben de enige auteur van de repository. Veel commits zijn gemaakt met hulp van Claude Code (staat zo in de commits).

---

## 6. Broring: WooCommerce-prijs en FAQ-schema (`broring/woocommerce-and-schema`)

**Project.** Broring is een webshop op WooCommerce (Radicle/Acorn).

**Wat ik heb gebouwd.**

- `AddFromPriceToVariableProducts`: een filter op `woocommerce_variable_price_html`. Bij producten met varianten toont het "Vanaf" met de laagste prijs. Is er korting, dan toont het een doorgestreepte prijs. De normale prijs en de actieprijs komen uit dezelfde variant. Eerder kon de doorgestreepte prijs van een andere variant komen, waardoor de korting niet klopte.
- FAQ-schema: het `Accordion`-component voegt de FAQ-items als schema.org `FAQPage` toe aan de schema-graph van Yoast. De opbouw zit in kleine builders (`FaqBuilder`, `AnswerBuilder`, `FaqPageBuilder`) en een `FaqService`.

**Technieken.** WordPress-filters als klassen, builder-pattern met een klein interface, schema.org/JSON-LD voor SEO, service via de container (`app(FaqService::class)`).

**Bestanden.** `AddFromPriceToVariableProducts.php`; voor het schema `Accordion.php` → `FaqService.php` → de builders.

**Rol.** Solo. De basisklasse `Filter` waar de prijsfilter van erft is van een collega en staat hier niet bij. De twee prijsfixes zijn gemaakt met hulp van Claude Code (staat zo in de commits).
