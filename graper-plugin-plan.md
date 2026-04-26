# Filament GrapeJS Plugin — Product Plan

> Goal: Build the definitive, production-ready GrapeJS page builder for Filament.
> Dotswan is the only visual canvas competitor — 10K installs, not actively marketed, broken responsive preview, no extension API.
> The block-based alternatives (Redberry, Fabricator) solve a different problem.
> This is a real gap in a 30K-star, 10M-download ecosystem.

---

## 0. Competitive Landscape (Research)

### Visual Canvas vs. Block-Based: Two Different Things

Most Filament "page builders" are **block-based** (pick a block card, fill in fields).
GrapeJS is a **visual canvas** (drag elements anywhere, see real HTML). They are not competing —
they serve different users. This is important: a new GrapeJS plugin isn't competing with
Redberry or Fabricator, it's replacing/succeeding dotswan.

### Filament GrapeJS Plugins

| Package | Installs | Stars | Last Update | Notes |
|---------|----------|-------|-------------|-------|
| **dotswan/filament-grapesjs-v3** | 10,151 | 58 | March 2025 | De facto standard. Actively maintained but not actively improved. |
| ekremogul/filament-grapesjs | Unknown | Unknown | Pre-v3 era | Abandoned. |
| vati, philippnies forks | Unknown | Unknown | Recent | Repackaged dotswan, no real changes. |

**30 forks** of dotswan exist — none have diverged meaningfully. No one has tried to build a better version yet.

### Other Filament Page Builder Plugins (Block-Based — Different Category)

| Plugin | Installs | Stars | Approach | Status |
|--------|----------|-------|----------|--------|
| Redberry Page Builder | 3,082 | — | Block cards + iframe preview | Active (v3 March 2026) |
| Filament Fabricator | Unknown | Unknown | Skeleton/starter kit | Active |
| Filamentor | Unknown | 30 | Grid drag-drop | Active |
| Sevendays-Digital | Unknown | 54 | Custom builder field | Pre-release (2+ yrs) |
| Layup, Threls | Unknown | Unknown | Various block approaches | Active |

None of these are visual canvas editors. They don't replace GrapeJS.

### Non-Filament Laravel + GrapeJS Packages

| Package | Installs | Status |
|---------|----------|--------|
| lamoud/laravel-grapesjs | 62 | Stalled |
| hansdeboeck/laravel-grapesjs | 9 | Abandoned |
| 3–4 others | <10 each | Abandoned |

**Total non-Filament adoption: under 200 installs.** The GrapeJS + Laravel market is
Filament-centric. Outside of Filament, developers use GrapeJS via CDN or in JS SPAs —
there's no demand for standalone Laravel wrappers.

### Market Size

| Metric | Value |
|--------|-------|
| Filament GitHub stars | 30,400 |
| Filament estimated downloads | 10M+ |
| Filament plugins available | 785+ |
| dotswan installs | 10,151 |
| Page builder plugins total | ~8–10 |
| GrapeJS market penetration in Filament | ~0.1% |

The ecosystem is large. Page builders are a micro-segment today — but that's because
there hasn't been a great option, not because the demand isn't there.

### What Developers Are Doing Without a Good Plugin

Real pain points from GitHub issues, Laracasts, Reddit, Discord:

1. **"There is nothing"** — Developers can't find documentation on how to customise
   the GrapeJS instance (87-reply thread on AnswerOverflow confirms this)
2. **DIY Repeater hacks** — Using Filament's native Repeater field with custom block
   definitions as a workaround for a proper page builder
3. **Raw CDN embedding** — Dropping GrapeJS script tags directly into custom Filament
   pages, with no Laravel integration at all
4. **Switching to no-code tools** — Some teams use Webflow or Framer and sync back to Laravel
5. **GrapesJS CMS request** — tomatophp/filament-cms has an open enhancement issue
   (11 comments) asking for GrapeJS integration — the existing plugin isn't good enough
   for full CMS use

### Risks to Acknowledge

| Risk | Mitigation |
|------|-----------|
| Dotswan is "good enough" for 80% of simple use cases | Phase 2 block library + Phase 4 code editor are the differentiators |
| Block-based builders gaining mindshare (simpler, faster) | GrapeJS fills a different niche — freeform canvas, not structured blocks |
| Filament team may eventually build a native page builder | Being established with installs and docs gives first-mover advantage |
| Performance ceiling in Filament at scale | Document known limits clearly; Phase 4 CSS tools address render bloat |

### Verdict

Dotswan has a **head start in installs but no moat**. It has not shipped meaningful features since
its initial release. There are zero well-maintained alternatives in the visual canvas space.
A plugin that ships Phase 1–2 (working device preview, block API, Eloquent storage, real docs)
immediately becomes the best option available. Phase 3+ (media, code editor, templates) makes
it genuinely hard to displace.

---

## 1. What Scooda Uses Today (Real-World Reference Implementation)

These are features proven in production on a multi-tenant charity platform.
Any plugin that covers these is immediately viable for serious projects.

### Editor Core
- GrapeJS 0.22.x initialised inside a Filament page (full-viewport, storage disabled)
- CSS injected into canvas iframe: Tailwind, app CSS, tenant CSS, Google/Adobe Fonts, CSS custom properties
- Custom dirty-state tracking with unsaved-changes warning
- CSS optimisation/deduplication on every save (prevents bloat over time)

### Blocks (43 core + 40+ tenant-specific)
- Hero, Hero Slider, Hero Video
- CTA, Simple CTA, CTA 50/50
- Accordion, FAQs, Tabbed Content
- Image, Images Grid, Video
- Stats, Quote, Testimonials, Team Members
- Blog/Events/Projects/Campaigns sliders and archive lists
- Quick Donate, Scheduled Giving
- Dynamic form blocks (built from API, field generator)
- **Tenant-specific block namespaces** — different block sets per client

### Custom Traits (Property Panel)
- `padding-top` / `padding-top-size` (Small, Normal, Extra) → CSS class
- `padding-bottom` / `padding-bottom-size`
- `rounded-top` / `rounded-bottom` (border radius toggles)
- `slider-button-colour` (Black / White)
- `items-per-page` (dependent on pagination checkbox, 3–50)

### Storage
- No localStorage — REST API only
- Save: `PUT /page-builder/{id}` → `{ content: {html, css}, project_data }`
- Load: `GET /page-builder/{id}` → `project_data` (GrapeJS native JSON), falls back to `html+css`
- Both structured data and rendered HTML/CSS persisted simultaneously

### Asset Manager
- Upload to `/front-api/v1/upload-file` via FormData + CSRF token
- Images fetched from API, combined from CDN (ImageKit) + local storage
- **Filament MediaPicker bridge** via Livewire events (`open-media-picker` → `filament-media-selected`)
- Background-image pending-target handling

### Toolbar Buttons
- **Save** (fa-save)
- **Media Library** (fa-image) → opens Filament MediaPicker
- **Clean CSS** (fa-broom) → deduplicates/optimises CSS rules
- **Clear Styles** (fa-eraser) → strips inline styles from selected component

### Multi-tenancy
- `window.___tenantName` passed from Filament page → loads tenant block set
- Tenant CSS injected into canvas
- Theme variables (brand colours, border radius, fonts, padding) generated from settings

### Email Template Editor
- Separate GrapeJS instance with `grapesjs-preset-newsletter`
- Brand-colour placeholder substitution
- Variables table for merge-tag interpolation

### Performance
- Request deduplication (in-flight Map + 15 s result cache)
- CSS rule deduplication on component update and save
- Async sequential plugin loader

---

## 2. What the Existing Filament Plugin is Missing (dotswan/filament-grapesjs-v3)

| Gap | Evidence | Impact |
|-----|----------|--------|
| Custom block API | Issues #5, #1 — "how do I add blocks?" | Locked to defaults |
| Code editor (view/edit HTML/CSS) | Issue #11 — "View Code Action" | Developers locked out |
| Responsive preview | Issue #10 — desktop layouts break | Unusable for real sites |
| GrapeJS version lag | Issue #4 — stuck on old version | Missing 2 years of fixes |
| Template/block library | None | Blank canvas only |
| Laravel storage patterns | localStorage default | Data lost on browser clear |
| Filament media library bridge | Not integrated | Manual asset workflow |
| Multi-tenancy | Not considered | Can't customise per client |
| i18n / locale config | Not configured | English UI only |
| Security (XSS, CSRF) | Not documented | Production risk |
| Documentation | README is a template | Nobody knows how to use it |

---

## 3. Community Research — Most Wanted Features

From GitHub issues, GrapeJS discussions, Reddit, Laravel forums:

1. **Pre-built block library** — hero, pricing table, testimonials, FAQ, CTA, footer
2. **Code editing mode** — switch between visual and HTML/CSS/JS editor
3. **Responsive preview that actually works** — real breakpoints, fix layout in-editor
4. **Laravel-native storage** — Eloquent model, S3, with versioning/rollback
5. **Drop-in Filament form field** — `GrapesJsField::make('content')` anywhere
6. **Asset manager with Laravel Storage** — spatie/laravel-medialibrary compatible
7. **Template manager** — save and reload designs as templates
8. **i18n** — multilingual UI and content
9. **Custom CSS injection** — Tailwind, brand tokens, app stylesheets in canvas
10. **Audit trail / version history** — who changed what, rollback

---

## 4. Plugin Architecture

```
filament-grapesjs/
├── src/
│   ├── Forms/Components/GrapesJsField.php      ← Drop-in Filament form field
│   ├── Filament/Pages/GrapesJsPage.php          ← Full-page builder page
│   ├── Storage/EloquentStorageDriver.php         ← DB persistence
│   ├── Storage/S3StorageDriver.php               ← Cloud storage
│   ├── Blocks/BlockRegistry.php                  ← Block registration API
│   ├── Traits/TraitRegistry.php                  ← Property panel trait API
│   ├── Assets/AssetManagerBridge.php             ← Laravel Storage bridge
│   └── GrapesJsPlugin.php                        ← Filament plugin class
├── resources/
│   ├── js/
│   │   ├── editor.ts                             ← Editor init
│   │   ├── blocks/                               ← Built-in block library
│   │   ├── traits/                               ← Built-in traits
│   │   └── storage/                              ← Storage adapters
│   └── views/
│       └── components/grapesjs-field.blade.php
└── config/grapesjs.php
```

---

## 5. Phased Delivery Plan

### Phase 1 — Solid Foundation (MVP) · Weeks 1–3
*Goal: Better than dotswan on day one.*

- [ ] `GrapesJsField` — drop-in Filament v3 form field
- [ ] `GrapesJsPage` — full-page builder Filament page
- [ ] Eloquent storage driver — save `project_data` + rendered `html`/`css` to any model column
- [ ] Configurable canvas CSS injection (pass your own stylesheets into the iframe)
- [ ] Device preview — desktop / tablet / mobile (working, not broken)
- [ ] Save button + dirty-state unsaved warning
- [ ] Proper documentation from day one

**Deliverable:** Installable via Composer, zero config needed for basic use, extensible from day one.

---

### Phase 2 — Block Library & Extension API · Weeks 4–6
*Goal: Make it extendable and immediately useful.*

- [ ] PHP Block registration API: `GrapesJs::registerBlock(MyHeroBlock::class)`
- [ ] PHP Trait registration API: `GrapesJs::registerTrait(PaddingTrait::class)`
- [ ] 15+ starter blocks: Hero (image + video), CTA, CTA 50/50, Testimonials,
      FAQ Accordion, Stats, Pricing Table, Team Members, Simple Text section - these have to configurable blocks that get pulled in via tailwind, flowbite or shadcn which they ca configure or select
- [ ] Block categories and ordering
- [ ] Block preview thumbnails

**Deliverable:** Devs add project-specific blocks in PHP. Non-devs have useful defaults.

---

### Phase 3 — Asset Manager & Media Library · Weeks 7–8
*Goal: Real file handling.*

- [ ] Laravel Storage upload endpoint (configurable disk: local, s3, etc.)
- [ ] `spatie/laravel-medialibrary` adapter (optional)
- [ ] Filament MediaPicker bridge (Scooda pattern, packaged cleanly)
- [ ] Image optimisation before upload (configurable)
- [ ] Bulk import from existing media library

**Deliverable:** Files go to Laravel storage. Works with whatever media setup the project already uses.

---

### Phase 4 — Code Editor & Developer Tools · Weeks 9–10
*Goal: Let developers use it too.*

- [ ] Code view: see generated HTML + CSS (read-only + editable mode)
- [ ] Custom CSS/JS injection per component
- [ ] CSS clean-up command (remove redundant rules)
- [ ] Clear component styles command
- [ ] Export: clean HTML file download
- [ ] Import: paste HTML → convert to editable blocks

**Deliverable:** Agency developers will actually use this. Designers and devs share the same tool.

---

### Phase 5 — Template Manager & Versioning · Weeks 11–13
*Goal: Team-level features.*

- [ ] Save current design as a named template (with thumbnail)
- [ ] Template library modal picker
- [ ] Version history per page (last 20 saves, with diff preview)
- [ ] Restore previous version
- [ ] Duplicate page / template
- [ ] Cross-tenant template sharing flag

**Deliverable:** Teams stop re-building the same layouts. Rollback removes fear of editing live pages.

---

### Phase 6 — i18n, Multi-tenancy & Production Polish · Weeks 14–16
*Goal: Enterprise/agency ready.*

- [ ] i18n: translate all UI labels via `lang/vendor/grapesjs/*.php`
- [ ] Multi-tenancy: per-tenant block sets, CSS, and theme variable injection
- [ ] Audit log: who saved what and when
- [ ] CSRF protection on all upload endpoints
- [ ] XSS sanitisation on HTML output (configurable policy)
- [ ] Rate limiting on upload and save endpoints
- [ ] `php artisan grapesjs:install` setup command

**Deliverable:** Safe to use in production SaaS with multiple clients on the same install.

---

## 6. Why This Plugin Makes You Employable

| Skill Demonstrated | How |
|---|---|
| Filament plugin architecture | Published, installable, properly structured package |
| GrapeJS internals | Custom blocks, traits, storage adapters, asset bridge |
| Laravel package development | Composer, service providers, config, artisan commands |
| TypeScript / modern JS | Editor init, block system, storage adapters |
| Multi-tenancy patterns | Per-tenant config, block namespacing |
| REST API design | Storage driver, upload endpoint |
| Open source discipline | Changelog, semver, documentation, tests |

The package fills a **genuine gap** — it's the only serious GrapeJS plugin for Filament,
and the existing one is broken. Stars and adoption will come quickly.
The techniques (plugin architecture, storage drivers, block registration) map directly
onto any SaaS or agency project on Laravel + Filament.

---

## 7. Key Technical Decisions

| Decision | Choice | Reason |
|---|---|---|
| GrapeJS version | Latest stable (0.22.x+) | Stay current; dotswan is lagging |
| Storage default | Eloquent (any model column) | Laravel-native, zero config |
| Block system | PHP class + JS template string | Familiar to Laravel devs |
| CSS injection | Configurable array of URLs/strings | Works with Vite, CDN, or inline |
| Media integration | Adapter pattern | Works with any media package |
| Field type | Filament `Field` subclass | Drops into any existing form |
| JS build | Vite + TypeScript | Matches Filament ecosystem |
| Testing | PHPUnit for PHP, Playwright for editor | Both layers covered |

---

*Created: 2026-04-24*
*Sources: Scooda production codebase analysis · dotswan plugin gap analysis · GrapeJS community research*
