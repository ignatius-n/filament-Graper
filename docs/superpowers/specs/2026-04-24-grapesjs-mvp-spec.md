# Phase 1: GrapeJS MVP Technical Specification

> **For agentic workers:** This spec defines what to build. Use `writing-plans` after this spec to create the implementation plan.

---

## 1. GrapesJsField Component

**Purpose:** Drop-in Filament form field for any form

**Location:** `src/Forms/Components/GrapesJsField.php`

**Database Model:** `src/Models/GraperPage.php`

**Required Columns:**
```php
$table->id();
$table->string('title');
$table->string('slug')->unique();
$table->json('project_data');  // GrapeJS JSON (blocks, styles, etc.)
$table->text('html');      // Rendered HTML
$table->text('css');      // Rendered CSS
$table->string('css_class')->nullable();
$table->boolean('is_published')->default(false);
$table->foreignId('created_by')->nullable();
$table->timestamps();
```

**API:**
```php
GrapesJsField::make('content')
    ->columnSpan(2)
    ->height('600px');
```

**Properties:**
- `height(string $height)` - Editor height (default: '500px')
- `cssExternal(array $urls)` - External CSS to inject into canvas
- `cssInternal(?string $css)` - Internal CSS string
- `jsExternal(array $urls)` - External JS to inject
- `toolbarButtons(array $buttons)` - Custom toolbar buttons

---

## 2. GrapesJsPage Component

**Purpose:** Full-page builder with routes

**Location:** `src/Pages/GrapesJsPage.php`

**Routes:**
- `GET /graper/edit/{page}` - Editor view (requires page ID)
- `PUT /graper/edit/{page}/save` - Save endpoint

**Middleware:** `auth`, `web`

---

## 3. Storage Driver

**Purpose:** Persist GrapeJS project data to database

**Interface:** `src/Storage/StorageDriver.php`

**Location:** `src/Storage/EloquentDriver.php`

**Methods:**
- `load(int|string $id): StorageData` - Load from DB
- `save(int|string $id, StorageData $data): void` - Save to DB

**StorageData Interface:**
```typescript
interface StorageData {
    html: string;
    css: string;
    projectData: object;
}
```

---

## 4. CSS Injection

**Purpose:** Inject styles into GrapeJS canvas iframe

**Config:** `config/grapesjs.php`

```php
return [
    'css' => [
        'external' => [
            'https://cdn.tailwindcss.com',
        ],
        'internal' => null,
    ],
    'js' => [
        'external' => [],
    ],
    'devices' => [
        'desktop' => ['width' => '100%', 'label' => 'Desktop'],
        'tablet' => ['width' => '768px', 'label' => 'Tablet'],
        'mobile' => ['width' => '375px', 'label' => 'Mobile'],
    ],
];
```

---

## 5. Device Preview

**Purpose:** Switch between viewport widths in editor

**Implementation:** CSS transform scale in iframe wrapper

**Default Breakpoints:**
- Desktop: 100%
- Tablet: 768px
- Mobile: 375px

---

## 6. JavaScript Editor

**Location:** `resources/js/editor.ts`

**Initialization:**
```typescript
import grapesjs from 'grapesjs';
import editorPlugin from 'grapesjs-plugin-export';

const editor = grapesjs.init({
    container: '#grapesjs-editor',
    height: '100%',
    storageManager: false,
    deviceManager: { devices: [...] },
});
```

**Feature Requirements:**
- Device preview toggle via toolbar
- Dirty state tracking (`editor.Canvas.getDirty()`)
- Custom save button in panels

---

## 7. Filament Plugin Registration

**Location:** `src/GraperPlugin.php`

```php
use Filament\Plugin as FilamentPlugin;

class GraperPlugin extends FilamentPlugin
{
    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                GrapesJsPage::class,
            ]);
    }
}
```

**Register in ServiceProvider:**
```php
public function boot(Panel $panel): void
{
    $panel->plugin(GraperPlugin::make());
}
```

---

## 8. Service Provider Updates

**Location:** `src/GraperServiceProvider.php`

```php
public function configurePackage(Package $package): void
{
    $package
        ->name('graper')
        ->hasConfigFile('grapesjs')
        ->hasMigrations([
            'create_graper_pages_table',
        ])
        ->hasCommands([
            GraperInstallCommand::class,
        ]);
}
```

---

## 9. Artisan Install Command

**Location:** `src/Commands/GraperInstallCommand.php`

**Purpose:** Publish config, migrations, run migrations