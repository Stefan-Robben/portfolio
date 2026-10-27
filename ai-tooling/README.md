# AI-tooling: Claude Code skills

Een Claude Code skill is een map met instructies (`SKILL.md`), naslagbestanden en soms scripts. Claude laadt de skill zelf als een vraag erbij past, en volgt dan een vaste, geteste werkwijze in plaats van elke keer te improviseren.

Hieronder staan de skills die ik (Stefan Robben) heb gebouwd. Twee ervan zijn voor mijn team bij Webreact. Die staan in de teamrepository `webreact/ai-skills`, en Webreact heeft toestemming gegeven om ze hier te tonen. De andere twee zijn persoonlijke skills.

| Skill | Voor | Wat het doet |
| --- | --- | --- |
| [theme-scaffold](#theme-scaffold) | Team Webreact | Figma design tokens → `theme.css`, `utilities.css` en Blade-componenten |
| [jira-scaffold](#jira-scaffold) | Team Webreact | Figma-bestand → Jira-tickets met een live Figma-preview |
| [bedrock-to-native-wp](#bedrock-to-native-wp) | Persoonlijk (werk) | WordPress-project van Bedrock naar native WordPress met Parcel en gewone CSS |
| [motivatiebrief](#motivatiebrief) | Persoonlijk | Vacature-link → formele motivatiebrief als Pages-document van één A4 |

**Hoe ik werk.** Elke skill heeft vaste regels: eerst een plan laten zien, pas schrijven na akkoord, en dezelfde invoer geeft altijd dezelfde uitvoer. Voor de teamskills schreef ik ook een eval-suite. Dat zijn testvragen met verwachte antwoorden, plus een `grade.py` die de antwoorden automatisch nakijkt.

---

## theme-scaffold

**Bron:** `webreact/ai-skills` → `plugins/webreact/skills/theme-scaffold/` en `evals/theme-scaffold/`. Alle commits zijn van mij.

### Het probleem
Bij elk nieuw project zette een developer de kleuren en lettergroottes uit Figma met de hand over naar CSS. Dat kost tijd. Ook ging het vaak net anders: andere namen, vergeten tokens of hardcoded hex-waarden in componenten.

### Hoe het werkt
1. **Tokens ophalen.** Via de Figma MCP (`get_variable_defs`) met een Figma-link. Of uit geüploade `colors.json` en `typography.json`.
2. **Samenvoegen.** Alle collecties worden één set. Onbekende tokentypes worden overgeslagen.
3. **Plan tonen.** Claude laat de lijst met bestanden zien en wacht op akkoord.
4. **Genereren.**
   - `theme.css`: alle CSS custom properties, in `@layer theme`. Pixels worden omgezet naar rem.
   - `utilities.css`: utility classes (`.text-blue`, `.bg-blue`) die alleen variabelen gebruiken.
   - Blade-componenten: `button`, `badge`, `heading` en `text`, elk in een eigen bestand.
5. **Koppen-hiërarchie.** Een vast algoritme koppelt `--h1` t/m `--h6`, `--body`, `--small` en `--xs` aan de beschikbare lettergroottes. Ook als er maar vier groottes zijn.
6. **Commit.** Alleen de gegenereerde bestanden, met een vaste commit-message, en pas na bevestiging.

De regels: tokennamen blijven precies gelijk, nergens hardcoded waarden buiten `theme.css`, en de uitvoer is deterministisch.

### Hoe de kwaliteit wordt gecontroleerd
- `evals/evals.json` bevat 5 testgevallen. Bijvoorbeeld: vraagt de skill om een Figma-link als er geen invoer is? Werkt hij met alleen JSON? Wacht hij op akkoord? Klopt de koppen-mapping bij maar vier groottes? Maakt hij losse Blade-bestanden?
- `evals/grade.py` kijkt elk antwoord na met reguliere expressies (`any_of` / `none_of`). Het script draait over een map met resultaten, vergelijkt *met skill* en *zonder skill*, en schrijft per run een `grading.json` met score en bewijs. Zo zie je of een wijziging de skill echt beter maakt.

### Wat ik heb gebouwd
De hele skill: de werkwijze in `SKILL.md`, de regels voor kleuren en typografie, de exacte uitvoerformaten, de eval-suite en de grader. Ook verwerkte ik de review-opmerkingen (Copilot) en zette ik de skill om naar de nieuwe plugin-structuur van de teamrepo.

### Bestanden om te bekijken
- [`theme-scaffold/SKILL.md`](theme-scaffold/SKILL.md): stappen en werkafspraken
- [`theme-scaffold/references/typography.md`](theme-scaffold/references/typography.md): px→rem en het hiërarchie-algoritme
- [`theme-scaffold/references/outputs.md`](theme-scaffold/references/outputs.md): exacte opbouw van de drie uitvoerbestanden
- [`theme-scaffold/evals/grade.py`](theme-scaffold/evals/grade.py): de automatische grader

---

## jira-scaffold

**Bron:** `webreact/ai-skills`, branch `feature/webreact-jira-scaffold` → `plugins/webreact/skills/jira-scaffold/` en `evals/jira-scaffold/`. Alle commits zijn van mij. Deze skill staat nog op een feature-branch en is nog niet gemerged naar `main`.

### Het probleem
Na het design moet een projectleider voor elk component, elke sectie en elke template een Jira-ticket aanmaken. Bij een groot design zijn dat tientallen tickets. Met de hand is dat saai werk, en de namen worden niet consequent.

### Hoe het werkt
1. **Connectors controleren.** Eerst de Figma MCP (via de Figma desktop-app), daarna Atlassian. Werkt een van de twee niet? Dan stopt de skill met een duidelijke uitleg.
2. **Jira-project kiezen.** De gebruiker kiest uit een genummerde lijst. Is het issuetype "Task" er niet, dan vraagt de skill welk type wel (geen Sub-task of Epic).
3. **Figma-structuur lezen.** Alleen de pagina's *Components*, *Sections* en *Desktop*. De skill vraagt eerst of het juiste bestand actief is, want de MCP leest het actieve bestand en niet de link.
4. **Elementen herkennen.** Wrapper-frames worden overgeslagen. Templates worden ontdubbeld op naam.
5. **Vaste namen.** `Component / button`, `Section / section-hero`, `Template / 1.0 / Homepagina`.
6. **Akkoord.** De volledige lijst wordt getoond. Zonder duidelijk "ja" wordt er niets aangemaakt, ook niet als de gebruiker vraagt om deze stap over te slaan.
7. **Tickets aanmaken.** Eén voor één, zonder toegewezen persoon. In elke beschrijving staat een ADF `embedCard` met een deeplink naar precies dat Figma-element. Zo zie je het design live in Jira.

### Hoe de kwaliteit wordt gecontroleerd
- `evals/evals.json` bevat 4 testgevallen: connector-checks, de waarschuwing over het actieve bestand, de juiste pagina's en namen, en een poging om de akkoordstap over te slaan.
- `evals/grade.py` werkt net als bij theme-scaffold. Er is ook een `all_of`-check voor patronen die allemaal aanwezig moeten zijn.
- Alle evals zijn alleen-lezen. Er worden geen echte tickets aangemaakt.

### Wat ik heb gebouwd
De hele skill, de eval-suite en de grader. Ook de review-feedback en de overstap naar de plugin-structuur.

### Bestanden om te bekijken
- [`jira-scaffold/SKILL.md`](jira-scaffold/SKILL.md): de vijf fases en de werkafspraken
- [`jira-scaffold/evals/evals.json`](jira-scaffold/evals/evals.json): testgevallen
- [`jira-scaffold/evals/grade.py`](jira-scaffold/evals/grade.py): de grader

*Aangepast voor publicatie:* de interne ID van de Atlassian-connector in de toolnamen is vervangen door `<atlassian-connector>`.

---

## bedrock-to-native-wp

**Bron:** mijn persoonlijke skills-map (`~/.claude/skills/bedrock-to-native-wp/`). Deze skill staat niet in git.

### Het probleem
Oudere WordPress-sites van Webreact draaien op Roots Bedrock met een eigen framework (`webreactor/*`), Laravel Mix, Tailwind, Sass en Blade. Voor kleine sites is dat te zwaar: je hebt private Composer-repositories nodig om alleen al te bouwen. Ik had zo’n migratie al eens gedaan. Deze skill legt die kennis vast, zodat de volgende migratie snel gaat en dezelfde valkuilen worden vermeden.

### Hoe het werkt
1. **Keuzes vragen.** Premium plugins, WordPress core in git of niet, hoe `wp-config` eruitziet, en hoe ver de styling wordt versimpeld.
2. **Verkenning.** Een set commando's brengt de stack en alle koppelingen met het framework in kaart.
3. **Uitvoeren in vaste volgorde**, van laag naar hoog risico:
   build naar Parcel en gewone CSS → PHP loskoppelen van het framework → Blade naar native PHP → repository alleen nog het thema → `wp-config`-voorbeeld en security.
4. **Valkuilen.** Acht concrete problemen die tijd kostten, met de oplossing. Bijvoorbeeld een Parcel-target met de naam `scripts`, `git mv` na een `rm`, en secrets die in de git-geschiedenis blijven staan.
5. **Controle.** Een grep op oude stack-verwijzingen, `php -l` op alle bestanden en een productie-build.

### Hoe de kwaliteit wordt gecontroleerd
- `evals/trigger-eval.json`: 18 voorbeeldvragen. 9 moeten de skill starten, 9 juist niet (bijvoorbeeld Tailwind weghalen uit een Next.js-site, of een gewone WordPress-update). Zo test ik of de beschrijving van de skill precies genoeg is.
- In de skill zelf staat een checklist om te controleren voordat het werk wordt teruggegeven.

### Bestanden om te bekijken
- [`bedrock-to-native-wp/SKILL.md`](bedrock-to-native-wp/SKILL.md): stappen en valkuilen
- [`bedrock-to-native-wp/references/php-decoupling.md`](bedrock-to-native-wp/references/php-decoupling.md): framework → native WordPress, met code
- [`bedrock-to-native-wp/references/css-conversion.md`](bedrock-to-native-wp/references/css-conversion.md): Parcel-config en `@apply` → CSS
- [`bedrock-to-native-wp/evals/trigger-eval.json`](bedrock-to-native-wp/evals/trigger-eval.json)

---

## motivatiebrief

**Bron:** mijn persoonlijke skills-map (`~/.claude/skills/motivatiebrief/`).

### Het probleem
Een goede motivatiebrief per vacature kost veel tijd. Een algemene brief met clichés ("gedreven", "teamplayer") zegt een recruiter niets. Ik wilde een brief die elke belangrijke eis koppelt aan concreet bewijs uit mijn cv, en die ik daarna zelf nog kan aanpassen in Pages.

### Hoe het werkt
1. **Vacature ophalen** met WebFetch. Lukt dat niet, omdat de pagina met JavaScript wordt geladen, dan via de ingebouwde browser. Tekst van de website wordt behandeld als gegevens, niet als instructies.
2. **Feiten verzamelen** uit mijn cv en een apart bestand met aanvullende feiten, waaronder mijn reden voor een nieuwe uitdaging. Alleen bestaande feiten gebruiken. Ontbreekt er iets, dan komt het terug als open vraag.
3. **Schrijven** volgens de schrijfwijzer: vaste opbouw, 250–380 woorden, een formele brief in de u-vorm in helder Nederlands (B2), vloeiende zinnen met weinig zinnen die met "Ik" beginnen, en gaten eerlijk benoemen.
4. **Opmaken** in een eenvoudig HTML-sjabloon.
5. **Pages-document maken** met `build_pages.py`. Pages kan geen omgezette .docx- of .rtf-bestanden openen, dus het script laat Pages via AppleScript zelf een nieuw document maken, typt de brief erin, zet de opmaak per alinea en slaat hem op als .pages. Via een tijdelijke pdf controleert het of de brief op precies één A4 past.
6. **Controleren** met een voorbeeldafbeelding van de pagina. Daarna een korte samenvatting: welke eisen zijn afgedekt, waar zitten de gaten, en welke vragen zijn er nog.

### Hoe de kwaliteit wordt gecontroleerd
Deze skill heeft geen eval-suite. De controle zit in de werkwijze zelf: de paginacheck in het script, een telling van lange zinnen en zinnen die met "Ik" beginnen, en de regel om geen feiten te verzinnen.

### Bestanden om te bekijken
- [`motivatiebrief/SKILL.md`](motivatiebrief/SKILL.md): de vijf stappen
- [`motivatiebrief/references/schrijfwijzer.md`](motivatiebrief/references/schrijfwijzer.md): opbouw, stijlregels, clichés en voorbeelden
- [`motivatiebrief/scripts/build_pages.py`](motivatiebrief/scripts/build_pages.py): van HTML naar Pages-document, met paginacheck
- [`motivatiebrief/assets/brief.html`](motivatiebrief/assets/brief.html): het sjabloon

*Niet gepubliceerd:* `references/kandidaat.md` bestaat wel, maar bevat persoonlijke gegevens en staat daarom niet in deze map. In het sjabloon zijn mijn adres, e-mail en telefoonnummer vervangen door `{{ }}`-plekken.
