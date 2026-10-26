---
name: jira-scaffold
description: Scaffolds JIRA issues from a Figma design file. Triggered when the user pastes a Figma URL and wants to create JIRA tickets from it, scaffold a backlog from a design file, generate issues from Figma components/sections/templates, or says "maak jira issues van figma", "figma naar jira", "scaffold figma", "maak tickets van dit figma bestand", or "zet het figma design om naar jira taken".
---

# Webreact Jira Scaffold

This skill reads a Figma design file and creates JIRA issues from every component, section, and template it finds — following a fixed naming convention. It is not for general JIRA work; it is specifically for converting a Figma file structure into a scaffolded backlog. The output is always deterministic for the same Figma input.

## Phase 0 — Auth checks

Before doing anything else, verify both connectors are available.

**Figma.** The Figma MCP connects via the **Figma desktop app Dev Mode MCP server** — not via OAuth. Call `mcp__Figma__get_metadata` with no arguments. If it returns setup instructions instead of a page list, stop immediately and tell the user:

> The Figma connector is not available. Open the Figma desktop app, go to **Figma menu → Preferences → Enable Dev Mode MCP Server**, then fully restart Claude Code and try again.

Do not attempt any workaround. The file must be open in the Figma desktop app.

**Jira (Atlassian).** Call `mcp__<atlassian-connector>__getAccessibleAtlassianResources` to get the `cloudId`. Save it — every subsequent Jira call needs it. If this call fails, stop and tell the user:

> The Atlassian connector is not authorized. Go to your claude.ai connector settings, find Atlassian, and complete the OAuth flow. Then run this skill again.

## Phase 1 — Jira project selection

Call `mcp__<atlassian-connector>__getVisibleJiraProjects` with the `cloudId` from Phase 0 and `expandIssueTypes: true`.

Present the result as a numbered list:

```
Which Jira project should the issues be created in?

1. MY-PROJECT — My Project
2. WEB — Webreact Main
...
```

Wait for the user to pick one. Record the `projectKey` and the list of valid `issueTypeName` values for that project.

If "Task" is in the list, use it. If "Task" is not available, **do not silently pick the first type** — sub-tasks and Epics require fields this skill does not collect and will fail. Instead, show the available types and ask:

> "Task" is not available in this project. Which issue type should I use? (pick a top-level type — avoid Sub-task, Epic, or any type that requires a parent)
>
> Available: [list]

Wait for the user to choose before continuing.

**Do not proceed until the user has answered.**

## Phase 2 — Figma structure extraction

### Parse the URL

Extract the file key from the URL — the path segment that comes after `/design/` (before the next `/` or `?`). Example:

```
https://www.figma.com/design/AbCdEfGhIjKl/My-Design?node-id=1-2
                             ^^^^^^^^^^^^
                             file key
```

If `node-id=A-B` is present in the URL, convert it to `"A:B"` (replace hyphen with colon). Otherwise default to `"0:1"` (the root page).

### Verify the active file

The Figma MCP reads from whichever file is currently open and active in the Figma desktop app — it is not scoped to the URL you passed. Before calling `get_metadata`, tell the user:

> Please confirm that **[fileName from the URL]** is the active file in your Figma desktop app. The MCP reads whatever is open, so if another file is active, the results will be wrong.

Wait for the user to confirm before proceeding.

### Get the page list

Call `mcp__Figma__get_metadata` with no `nodeId` to list all top-level pages. Identify the page IDs for:
- **Components** page
- **Sections** page
- **Desktop** page

Then call `mcp__Figma__get_metadata` for each of those page IDs. The response is XML describing the layer hierarchy. Note: large pages may exceed the token limit and be saved to a file — read the file using a subagent.

### Identify design elements

The file uses a three-level structure: **page → section (grouping) → frames**. The items to extract are NOT the direct page children but the named frames found deeper in the hierarchy. This skill only processes the three pages below.

---

#### Components page

Structure: `<canvas>` → `<section>` (Navigation, Forms, Content & Cards, etc.) → wrapper `<frame>` → **component `<frame>`** (contains `<symbol>` elements)

Extract every named frame that directly contains `<symbol>` elements. Skip generic wrapper frames named: `col`, `row`, `Frame XX`, `overig`, `forms`, `cards`, `buttons`, `local navigation`, `global navigation`, `content`, `e-commerce` and any other purely organisational container.

Issue format: `Component / [frame name]`

Example: a frame `<frame id="602:54" name="button">` containing symbols → `Component / button`

---

#### Sections page

Structure: `<canvas>` → `<section>` (Menu, Footer, Header, Forms, Content, etc.) → wrapper frames → **section `<frame>`** (named `section-X` or `footer`)

Extract every named frame/symbol that represents an actual page section. These are typically named `section-[type]` or `footer`. Skip wrapper frames named `menu`, `forms`, `content`, `Header`, `col`, `row`, `Frame XX`, `-`.

Issue format: `Section / [frame name]`

Example: `<frame id="914:3322" name="section-menu">` → `Section / section-menu`

---

#### Desktop page (Templates)

Structure: `<canvas>` → `<section>` (page grouping, e.g. "Homepagina") → **template `<frame>`** (with numbered name, e.g. "1.0 Homepagina")

The template frames have names that start with a version number followed by a space and a descriptive name, e.g. `"1.0 Homepagina"`, `"9.1 Oplossingen"`, `"2.0.1 Artikel"`.

Extract every direct child frame of each section whose name matches the pattern `[number] [text]`. Split the name into:
- **Number part**: everything before the first space (e.g. `1.0`, `9.1.1`)
- **Text part**: everything after the first space (e.g. `Homepagina`, `Propositie detail`)

**Deduplicate by frame name** — the same numbered frame may appear multiple times across sections (different viewport sizes). Keep only the first occurrence.

Issue format: `Template / [number] / [text]`

Example: frame `"1.0 Homepagina"` → `Template / 1.0 / Homepagina`

---

If any of the three pages is missing from the file, note it to the user and continue with the ones that exist.

For each element, record its **node ID** from the XML. Build a Figma deep-link to that exact node:

```
https://www.figma.com/design/[fileKey]/[fileName]?node-id=[nodeId]
```

Convert the node ID back to URL format: replace `:` with `-` (e.g. `"1:2"` → `"1-2"`). The `[fileName]` segment is whatever slug appears in the original URL path — or omit it if not present.

## Phase 3 — Approval

After extracting all elements, stop and present the full planned issue list to the user for review before doing anything else:

```
Here are the [N] issues I'm about to create in [PROJECT-KEY]:

Component / Button
Component / Card
Section / Hero
Section / Features
Template / 1 / Home
Template / 2 / About
...

Approve this list to continue, or tell me what to change.
```

**Wait for explicit approval before calling `createJiraIssue` even once.** Do not start creating issues automatically, do not assume approval, do not proceed if the user only says "looks good" without a clear go-ahead — ask again. A clear "yes", "go ahead", "create them", "ja", "doe het" counts as approval.

If the user wants to remove or rename items, update the list and show it again for final confirmation before proceeding.

This step is mandatory and cannot be skipped.

## Phase 4 — Issue creation

Create issues one by one (sequentially, not in parallel) so any failure is immediately visible.

For each issue:

```
createJiraIssue(
  cloudId:        <from Phase 0>
  projectKey:     <from Phase 1>
  issueTypeName:  <from Phase 1, default "Task">
  summary:        <the formatted title>
  description:    {
                    "version": 1,
                    "type": "doc",
                    "content": [{
                      "type": "embedCard",
                      "attrs": {
                        "url": "<figma-deep-link for this node>",
                        "layout": "wide"
                      }
                    }]
                  }
  contentFormat:  "adf"
)
```

The `description` is an ADF `embedCard` pointing to the exact Figma node for this component, section, or template. This renders the Figma design as a live embedded preview directly inside the Jira issue when it is opened.

Do not include `assignee_account_id`. Note that Jira may still auto-assign if the project has a default assignee configured. After all issues are created, check the first created issue and warn the user if it was assigned:

> Warning: Jira auto-assigned these issues to [name] because the project has a default assignee. Unassign them manually in Jira, or ask your project admin to remove the default.

After each successful create, print the issue key and title on one line:
```
✓ WEB-123  Component / Button
✓ WEB-124  Component / Card
...
```

If any issue fails, print the error and continue with the rest. Report failed issues in a summary at the end.

---

## Working agreements

**Issues are always unassigned.** Never include `assignee_account_id`. If the user asks to assign during this skill, tell them to assign in Jira after scaffolding.

**The naming format is not negotiable.** `Component / X`, `Section / X`, `Template / N / X` — exactly this, including the spaces around the slashes. Do not reformat, shorten, or clean up the Figma names.

**Always confirm before writing.** Phase 3 is mandatory. Do not skip it even if the user says "just do it".

**Figma auth is a hard stop.** No workarounds, no manual fallbacks. The user must connect the MCP.

**issueTypeName fallback requires user input.** If "Task" is not available, show the list and ask the user to pick a compatible top-level type. Never silently select the first entry — it may be a Sub-task or Epic that will fail without a parent.
