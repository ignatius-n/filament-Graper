# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Run all tests
./vendor/bin/pest

# Run a specific test file
./vendor/bin/pest tests/Unit/Blocks/BlockRegistryTest.php

# Run tests matching a filter
./vendor/bin/pest --filter="block registry"

# Format PHP files (run after any PHP change)
./vendor/bin/pint --dirty --format agent

# Compile frontend assets
npm run build
```

## Architecture

### Package Structure

This is a Filament v5 / Laravel 13 plugin that provides a GrapeJS visual page editor. The root namespace is `CybertronianKelvin\Graper`.

```
src/
├── GraperServiceProvider.php   ← Boots everything: views, routes, FilamentAsset, block registration
├── GraperPlugin.php            ← Filament plugin: registers GraperPageResource into the panel
├── Blocks/
│   ├── Block.php               ← Abstract base — getId, getName, getCategory, getTemplate, getOrder
│   ├── BlockRegistry.php       ← Singleton: register(class|instance), all(), byCategory(), toArray()
│   └── Samples/                ← 9 ready-made Tailwind blocks (Hero, CTA, Stats, Testimonials, etc.)
├── ComponentTraits/
│   ├── ComponentTrait.php      ← Abstract base for GrapeJS traits (sidebar controls)
│   └── ComponentTraitRegistry.php
├── Http/Controllers/
│   ├── GraperBlockController.php  ← GET /graper/api/blocks → JSON block registry
│   └── GraperPageController.php   ← edit/show/update/display for GraperPage
├── Models/GraperPage.php       ← Eloquent model (graper_pages table), keyed by id
├── Resources/GraperPageResource.php  ← Filament resource for managing pages
└── Storage/EloquentDriver.php  ← Saves/loads page content via the API
resources/js/
├── editor.ts                   ← initEditor() — bootstraps GrapeJS, loads blocks, wires save
└── blocks/registry.ts          ← loadBlocks(editor) — fetches /graper/api/blocks, registers them
routes/graper.php               ← All package routes (loaded via loadRoutesFrom in service provider)
```

### Data Flow

1. **Admin creates page** via `GraperPageResource` (Filament CRUD).
2. **Editor opens** via `ViewGraperPage` → `route('graper.edit', $record)` → `GraperPageController::edit()` → renders `editor.blade.php`.
3. **Editor initialises** — `editor.ts` calls `loadBlocks()` (fetches `/graper/api/blocks`), then loads existing page content from `/graper/api/page/{id}`.
4. **Blocks served** — `GraperBlockController::index()` returns `BlockRegistry::make()->toArray()`. Default blocks are registered in `GraperServiceProvider::boot()`.
5. **Save** — editor JS PUTs to `/graper/api/page/{id}` with `{html, css, project_data}`.
6. **Public display** — `/{prefix}/{slug}` hits `GraperPageController::display()` which queries by slug explicitly.

### Key Design Decisions

- **BlockRegistry is a singleton** (`BlockRegistry::make()`). Register custom blocks in your app's service provider via `BlockRegistry::make()->register(MyBlock::class)`.
- **Model binding uses `id`** (default). The public display route queries by `slug` explicitly in the controller — do not add `getRouteKeyName()` to `GraperPage`.
- **Routes are loaded from `routes/graper.php`** via `loadRoutesFrom()` in the service provider — do not duplicate them inline.
- **`GraperServiceProvider::boot()` does not call `parent::boot()`** — migrations, views, and assets are registered manually to support the workbench dev path (`packages/graper/`).

### Adding a Block

1. Create `src/Blocks/Samples/MyBlock.php` extending `Block`.
2. Implement `getId()`, `getName()`, `getCategory()`, `getTemplate()`, optionally `getOrder()`.
3. Register it in `GraperServiceProvider::boot()` or in the consuming app's service provider.

### Testing

Tests use **Pest** with Orchestra Testbench. The `TestCase` manually runs migrations (bypassing package tools) and defines routes without `auth` middleware for controller tests. Block and trait registry tests are unit tests with no HTTP layer.
