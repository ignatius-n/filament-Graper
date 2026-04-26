# Graper: Alpine-Embedded Redesign

**Date:** 2026-04-25
**Branch:** phase-2-block-library
**Status:** Approved — ready for implementation

---

## Problem Statement

The current Graper plugin opens GrapeJS in a standalone full-page blade template, separate from Filament's admin layout. This means:
- No Filament chrome, no sidebar, no breadcrumbs
- Page metadata (title, slug, status) edited in one place, content edited in another
- The block library has no thumbnails
- `grapesjs-tailwindcss-plugin` provides CSS compilation only — zero pre-built blocks
- Flowbite/shadcn theme scaffolding exists but is unimplemented and adds noise

The redesign embeds GrapeJS inside Filament as a proper form field, switches to a block library that actually provides blocks with thumbnails, and cleans up the dead code.

---

## Goals

1. GrapeJS embedded in Filament admin as an Alpine.js form field
2. `GraperPageResource` form: metadata fields on top, editor below — one save action
3. Reusable `GrapesJsField` usable on any Filament resource
4. 62 built-in Tailwind blocks with SVG thumbnails via `grapesjs-tailwind`
5. Custom PHP blocks (extending `Block`) register alongside built-ins
6. Custom JS blocks possible via `graper:ready` event
7. Block thumbnails (SVG), categories, and ordering all supported
8. Three DB columns preserved (`html`, `css`, `project_data`) — no migration needed
9. Flowbite/shadcn code removed
10. Developer guide + desktop explainer written

---

## Architecture

### Before

```
Admin clicks "Open Editor"
  → GraperPageController::edit()
  → editor.blade.php (full-page, no Filament layout)
  → editor.ts (vanilla JS, loads blocks from API)
  → Save button → PUT /graper/api/page/{id}
```

### After

```
Admin opens GraperPage edit form (Filament layout)
  → metadata fields (title, slug, status, published_at)
  → GrapesJsField (Alpine x-data component)
      → grapesjs.init() + grapesjs-tailwind plugin (62 built-in blocks)
      → fetch /graper/api/blocks → register custom PHP blocks
      → fires graper:ready for custom JS blocks
  → Livewire entangle → state: { html, css, project_data }
  → Filament Save button → GraperPage model accessor unpacks JSON → 3 DB columns
```

---

## Components

### 1. `GrapesJsField` (new)

**File:** `src/Forms/Components/GrapesJsField.php`

Filament form field. Fluent API:

```php
GrapesJsField::make('content')
    ->loadDefaultBlocks(true)   // grapesjs-tailwind blocks, default: true
    ->minHeight('70vh')         // default: '600px'
    ->columnSpanFull()
```

**View:** `resources/views/fields/grapesjs.blade.php`

Renders an Alpine `x-data="graperEditor({...})"` wrapper with `wire:ignore`.
Passes to Alpine:
- `container` — unique element ID (`#graper-{getId()}`)
- `state` — Livewire entangled with the field's state path (deferred)
- `customBlocksUrl` — `/graper/api/blocks`
- `loadDefaultBlocks` — bool
- `minHeight` — string

### 2. Alpine component

**File:** `resources/js/index.ts` (replaces `editor.ts`)

Registered on `alpine:init`:

```js
Alpine.data('graperEditor', ({ container, state, customBlocksUrl, loadDefaultBlocks, minHeight }) => ({
  instance: null,
  init() {
    const editor = grapesjs.init({
      container,
      height: minHeight,
      storageManager: false,
      plugins: loadDefaultBlocks ? [tailwindPlugin] : [],
    });

    // Load custom PHP blocks
    fetch(customBlocksUrl)
      .then(r => r.json())
      .then(({ blocks }) => {
        blocks.forEach(block => {
          editor.BlockManager.add(block.id, {
            label: block.name,
            category: { id: block.category, label: block.category },
            content: block.template,
            media: block.thumbnail ?? '',
            attributes: { 'data-block-id': block.id },
          });
        });
      });

    // Restore saved state
    if (state) {
      const data = JSON.parse(state);
      if (data.project_data && Object.keys(data.project_data).length > 0) {
        editor.loadProjectData(data.project_data);
      } else if (data.html) {
        editor.setComponents(data.html);
        editor.setStyle(data.css);
      }
    }

    // Sync to Livewire on every change
    editor.on('update', () => {
      this.$data.state = JSON.stringify({
        html: editor.getHtml(),
        css: editor.getCss(),
        project_data: editor.getProjectData(),
      });
    });

    // Expose for custom JS blocks
    const id = container.replace('#', '');
    window.graperInstances = window.graperInstances ?? {};
    window.graperInstances[id] = editor;
    window.dispatchEvent(new CustomEvent('graper:ready', { detail: { editor, id } }));

    this.instance = editor;
  },
}));
```

### 3. `Block.php` (modified)

**Removed:**
- `getFlowbiteTemplate()`
- `getShadcnTemplate()`
- `getThemeTemplate()`

**Kept/unchanged:**
- `getId()`, `getName()`, `getCategory()`, `getOrder()`, `getTemplate()`
- `getThumbnail(): ?string` — return inline SVG string or null

`toArray()` simplified — no `$theme` parameter:

```php
public function toArray(): array
{
    return [
        'id'        => static::getId(),
        'name'      => static::getName(),
        'category'  => static::getCategory(),
        'template'  => $this->getTemplate(),
        'order'     => static::getOrder(),
        'thumbnail' => static::getThumbnail(),
    ];
}
```

### 4. `BlockRegistry.php` (minor)

Remove `$theme` parameter from `toArray()` only — `byCategory()` has no theme param. No other changes.

### 5. `GraperBlockController.php` (simplified)

Remove `?theme=` query param handling. Always calls `BlockRegistry::make()->toArray()`.

### 6. `GraperPage` model (new accessor/mutator)

No DB migration. New virtual `content` attribute bridges the JSON envelope to the three real columns:

```php
public function getContentAttribute(): string
{
    return json_encode([
        'html'         => $this->html ?? '',
        'css'          => $this->css ?? '',
        'project_data' => $this->project_data ?? [],
    ]);
}

public function setContentAttribute(string $value): void
{
    $data = json_decode($value, true) ?? [];
    $this->html         = $data['html'] ?? '';
    $this->css          = $data['css'] ?? '';
    $this->project_data = $data['project_data'] ?? [];
}
```

### 7. `GraperPageResource` (modified)

```php
public static function form(Form $form): Form
{
    return $form->schema([
        TextInput::make('title')->required()->columnSpan(2),
        TextInput::make('slug')->required(),
        Select::make('status')
            ->options(['draft' => 'Draft', 'published' => 'Published']),
        DateTimePicker::make('published_at')->columnSpan(2),
        GrapesJsField::make('content')
            ->loadDefaultBlocks()
            ->minHeight('70vh')
            ->columnSpanFull(),
    ])->columns(3);
}
```

### 8. Custom JS blocks (developer extension point)

```js
window.addEventListener('graper:ready', ({ detail: { editor } }) => {
    editor.BlockManager.add('my-slider', {
        label: 'Slider',
        category: 'Interactive',
        content: '<div class="my-slider">...</div>',
        media: '<svg>...</svg>',
    });
});
```

---

## What Gets Removed

| File / symbol | Reason |
|---|---|
| `resources/views/editor.blade.php` | Replaced by Alpine field view |
| `GraperPageController::edit()` | No longer needed |
| `graper.edit` route | No longer needed |
| `ViewGraperPage.php` | Was the "Open Editor" redirect page |
| `resources/js/editor.ts` | Replaced by `resources/js/index.ts` |
| `Block::getFlowbiteTemplate()` | Flowbite removed |
| `Block::getShadcnTemplate()` | shadcn removed |
| `Block::getThemeTemplate()` | Theme dispatch removed |
| `grapesjs-tailwindcss-plugin` (npm) | Replaced by `grapesjs-tailwind` |
| `?theme=` param in block controller | Theme system removed |

---

## npm Changes

```diff
- "grapesjs-tailwindcss-plugin": "^0.1.10"
+ "grapesjs-tailwind": "^1.0.13"
```

---

## Data Flow: Save

```
Editor change
  → Alpine editor.on('update')
  → state = JSON.stringify({ html, css, project_data })
  → Livewire entangle syncs to $record->content
  → Filament Save button
  → GraperPage::setContentAttribute() unpacks JSON
  → $this->html / $this->css / $this->project_data set on model
  → Eloquent saves three columns to DB
```

## Data Flow: Load

```
EditGraperPage opens
  → GraperPage::getContentAttribute() packs three columns → JSON string
  → Filament loads form state
  → Alpine init() receives state JSON
  → project_data present → editor.loadProjectData()
  → editor renders saved page content
```

---

## Documentation Deliverables

1. **`docs/README.md`** — developer guide: installation, `GrapesJsField` usage, custom PHP blocks, custom JS blocks, public display, config reference
2. **`~/Desktop/graper-how-it-works.md`** — detailed explainer for first-time Filament plugin author: PackageServiceProvider, Plugin class, Alpine/Livewire integration, full request lifecycle, block system flow

---

## Testing Plan

- `BlockTest` — assert `toArray()` has no theme key, thumbnail included
- `BlockRegistryTest` — update assertions to remove theme params
- `GraperBlockControllerTest` — assert no `?theme=` handling, returns blocks array
- `GraperPageTest` — assert `content` accessor/mutator round-trips all three columns correctly
- `GrapesJsFieldTest` — assert field renders with correct Alpine data attributes
- Manual: open `GraperPageResource` edit, confirm editor loads with 62 built-in blocks + custom PHP blocks, save round-trips all three DB columns

---

## Build Order

1. npm: swap `grapesjs-tailwindcss-plugin` → `grapesjs-tailwind`, run `npm install`
2. `Block.php` — remove Flowbite/shadcn methods, simplify `toArray()`
3. `BlockRegistry.php` — remove theme param from `toArray()`
4. `GraperBlockController.php` — remove theme param
5. `GraperPage` model — add `content` accessor/mutator
6. `GrapesJsField.php` — new form field class
7. `resources/views/fields/grapesjs.blade.php` — Alpine field view
8. `resources/js/index.ts` — new Alpine component
9. `GraperPageResource` — update form schema
10. Delete: `editor.blade.php`, `ViewGraperPage.php`, `editor.ts`
11. Update routes: remove `graper.edit`
12. `GraperServiceProvider` — remove editor asset registration, wire new field asset
13. Write/update tests
14. Write docs (`docs/README.md`, `~/Desktop/graper-how-it-works.md`)
