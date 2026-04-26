# Graper — Alpine-Embedded Page Builder for Filament

A visual page builder plugin for Filament that embeds GrapeJS directly in your admin forms.

## Installation

```bash
composer require graper/graper
```

Publish config (optional):

```bash
php artisan vendor:publish --tag=graper-config
```

Add to your Filament panel provider:

```php
use CybertronianKelvin\Graper\GraperPlugin;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(GraperPlugin::make());
}
```

Run migrations:

```bash
php artisan migrate
```

That's it! Visit `/admin/graper-pages` to create your first page.

---

## Usage

### The Pages Resource

Out of the box, Graper adds a **Pages** resource to your Filament admin with:

- Title field
- Slug field (unique)
- Published toggle
- Published at date/time
- Embedded GrapeJS editor

The editor is embedded directly in the edit form — no more separate "Open Editor" page. Everything saves with one click.

### GrapesJsField on Any Resource

The `GrapesJsField` is reusable on any Filament resource:

```php
use CybertronianKelvin\Graper\Forms\Components\GrapesJsField;

public static function form(Form $form): Form
{
    return $form->schema([
        // ... other fields
        GrapesJsField::make('content')
            ->loadDefaultBlocks()    // Load 62 built-in Tailwind blocks
            ->minHeight('70vh')      // Editor height (default: 600px)
            ->columnSpanFull(),
    ]);
}
```

**Methods:**

| Method | Default | Description |
|--------|---------|-------------|
| `loadDefaultBlocks(bool $load = true)` | `true` | Load the 62 built-in Tailwind blocks from `grapesjs-tailwind` |
| `minHeight(string $height)` | `'600px'` | Minimum height of the editor canvas |
| `columnSpanFull()` | — | Make the field span full width |

---

## Custom PHP Blocks

Create custom blocks that appear alongside the built-in blocks:

```php
// app/Blocks/HeroBlock.php
namespace App\Blocks;

use CybertronianKelvin\Graper\Blocks\Block;

class HeroBlock extends Block
{
    public static function getId(): string
    {
        return 'hero';
    }

    public static function getName(): string
    {
        return 'Hero Section';
    }

    public static function getCategory(): string
    {
        return 'Marketing';
    }

    public static function getOrder(): int
    {
        return 10;
    }

    public function getTemplate(): string
    {
        return <<<'HTML'
<section class="py-20 bg-gray-900 text-white">
  <div class="container mx-auto px-4 text-center">
    <h1 class="text-4xl font-bold mb-4">Your Title Here</h1>
    <p class="text-xl mb-8">Your subtitle here</p>
  </div>
</section>
HTML;
    }
}
```

Register in your `AppServiceProvider::boot()`:

```php
use CybertronianKelvin\Graper\Blocks\BlockRegistry;
use App\Blocks\HeroBlock;

BlockRegistry::make()->register(HeroBlock::class);
```

**Block methods:**

| Method | Required | Default |
|--------|----------|---------|
| `getId()` | Yes | — |
| `getName()` | Yes | — |
| `getCategory()` | Yes | — |
| `getTemplate()` | Yes | — |
| `getOrder()` | No | `100` |
| `getThumbnail()` | No | `null` |

---

## Custom JavaScript Blocks

Add blocks dynamically via JavaScript using the `graper:ready` event:

```js
window.addEventListener('graper:ready', ({ detail: { editor, id } }) => {
    editor.BlockManager.add('my-slider', {
        label: 'Image Slider',
        category: 'Interactive',
        content: '<div class="slider">...</div>',
        media: '<svg>...</svg>',
    });
});
```

---

## Accessing the Editor Instance

For advanced use cases, access the GrapeJS editor instance:

```js
// By field ID (default: 'graper-{fieldId}')
const editor = window.graperInstances['graper-content'];

// Or listen for the ready event
window.addEventListener('graper:ready', ({ detail: { editor, id } }) => {
    // Use the editor instance
    console.log('Editor ready:', editor);
});
```

---

## Public Page Display

Pages are publicly accessible at `/{slug}` (configurable). Only published pages are shown to visitors.

### Configuration

Edit `config/graper.php`:

```php
return [
    // URL prefix for public pages
    // '/' = /{slug} (e.g., /about, /contact)
    // 'pages' = /pages/{slug}
    'page_route_prefix' => '/',

    // Include Tailwind CDN on public pages
    'include_tailwind' => true,
];
```

### Published Gate

Unpublished pages return 404 for visitors. Authenticated users with `view` permission can preview unpublished pages.

---

## Config Reference

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `page_route_prefix` | string | `'/'` | URL prefix for public pages |
| `include_tailwind` | bool | `true` | Load Tailwind CDN on public pages |

---

## Testing Workflow

### PHP / Blade Changes

```bash
# Edit PHP or Blade files
# Just reload the browser — no build needed
```

### JavaScript / TypeScript Changes

```bash
# Edit resources/js/*
npm run build
# Then reload the browser
```

### Running Tests

```bash
# PHP tests
./vendor/bin/pest

# JS tests
npm test

# Format code
./vendor/bin/pint --dirty
```

---

## File Reference

| File | Description |
|------|-------------|
| `src/GraperPlugin.php` | Filament plugin integration |
| `src/GraperServiceProvider.php` | Package bootstrap, loads blocks, routes |
| `src/Forms/Components/GrapesJsField.php` | Filament form field |
| `src/Models/GraperPage.php` | Page model with `content` accessor |
| `src/Blocks/Block.php` | Abstract base for custom blocks |
| `src/Blocks/BlockRegistry.php` | Singleton for registering blocks |
| `resources/js/index.ts` | Alpine.js component |
| `resources/views/fields/grapesjs.blade.php` | Field Blade template |
| `routes/graper.php` | API routes |

---

## Upgrading from Older Versions

The redesign (v2) changed:

- Editor is now embedded in Filament forms (not separate page)
- Uses `grapesjs-tailwind` (62 blocks) instead of `grapesjs-tailwindcss-plugin`
- Single `content` field replaces three separate fields
- Flowbite/shadcn theme system removed

Existing data in `html`, `css`, `project_data` columns is preserved — the `content` accessor reads from those columns.