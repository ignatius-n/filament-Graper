# Phase 1: GrapeJS MVP Implementation Plan

> **For agentic workers:** Use `subagent-driven-development` (recommended) or `executing-plans` to implement this plan task-by-task.

**Goal:** Create a working GrapeJS page builder for Filament with form field, GraperPage model, full-page builder, Eloquent storage, CSS injection, and device preview.

**Architecture:** Filament v3 plugin with GrapeJS 0.22.x, Vite/TypeScript frontend. Storage persists to GraperPage Eloquent model.

**Tech Stack:** Laravel 11+, Filament v3, GrapeJS 0.22.x, Vite, TypeScript, Pest

---

## File Structure

```
graper/
├── composer.json                           # Updated with deps
├── config/grapesjs.php                   # New config
├── package.json                        # NPM deps
├── vite.config.ts                     # Vite config
├── resources/
│   └── js/
│       ├── editor.ts                  # GrapeJS init
│       ├── components/
│       │   ├── DevicePreview.ts      # Device preview toggle
│       │   └── DirtyTracker.ts     # Unsaved changes
│       └── utils/
│           └── storage.ts            # Storage adapter
├── resources/views/
│   ├── editor.blade.php             # Editor view
│   ├── forms/components/
│   │   └── grapes-js-field.blade.php # Form field view
│   └── pages/
│       └── grapes-js-page.blade.php   # Page builder view
└── src/
    ├── Models/
    │   └── GraperPage.php          # Page model + migration
    ├── Forms/
    │   └── Components/
    │       └── GrapesJsField.php    # Form field
    ├── Pages/
    │       └── GrapesJsPage.php    # Full-page builder
    ├── Http/
    │   └── Controllers/
    │       └── GraperPageController.php # API controller
    ├── Storage/
    │   ├── StorageDriver.php      # Interface
    │   └── EloquentDriver.php     # Implementation
    ├── GraperPlugin.php           # Filament plugin
    ├── GraperServiceProvider.php  # Updated
    └── Commands/
        └── GraperInstallCommand.php # Install command
```

---

## Task 1: Update Dependencies

**Files:**
- Modify: `composer.json`
- Modify: `config/graper.php` → `config/grapesjs.php`

- [ ] **Step 1: Update composer.json**

```json
{
    "name": "graper/graper",
    "description": "GrapeJS page builder for Filament",
    "require": {
        "php": "^8.2",
        "filament/filament": "^3.2",
        "spatie/laravel-package-tools": "^1.16",
        "illuminate/contracts": "^11.0||^12.0"
    },
    "require-dev": {
        "laravel/pint": "^1.14",
        "nunomaduro/collision": "^8.8",
        "larastan/larastan": "^3.0",
        "orchestra/testbench": "^10.0",
        "pestphp/pest": "^4.0",
        "pestphp/pest-plugin-laravel": "^4.0",
        "phpstan/extension_installer": "^1.4",
        "phpstan/phpstan-deprecation-rules": "^2.0",
        "phpstan/phpstan-phpunit": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "graper\\Graper\\": "src/"
        }
    }
}
```

- [ ] **Step 2: Create config/grapesjs.php (replace graper.php)**

```php
<?php

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
    'default_height' => '500px',
];
```

- [ ] **Step 3: Install dependencies**

```bash
composer update
```

- [ ] **Step 4: Commit**

```bash
git add composer.json config/grapesjs.php
git commit -m "feat: add Filament and GrapeJS dependencies, create config"
```

---

## Task 2: Create GraperPage Model + Migration

**Files:**
- Create: `src/Models/GraperPage.php`
- Modify: `database/migrations/create_graper_table.php.stub` → rename to `create_graper_pages_table.php`

- [ ] **Step 1: Create migration rename**

```bash
mv database/migrations/create_graper_table.php.stub database/migrations/create_graper_pages_table.php
```

- [ ] **Step 2: Update migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graper_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->json('project_data')->nullable();
            $table->text('html')->nullable();
            $table->text('css')->nullable();
            $table->string('css_class')->nullable();
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graper_pages');
    }
};
```

- [ ] **Step 3: Create src/Models/GraperPage.php**

```php
<?php

namespace graper\Graper\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GraperPage extends Model
{
    protected $table = 'graper_pages';

    protected $fillable = [
        'title',
        'slug',
        'project_data',
        'html',
        'css',
        'css_class',
        'is_published',
        'created_by',
    ];

    protected $casts = [
        'project_data' => 'array',
        'is_published' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('filament.user_model', App\Models\User::class), 'created_by');
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add src/Models/GraperPage.php database/migrations/create_graper_pages_table.php
git commit -m "feat: add GraperPage model with project_data storage"
```

---

## Task 3: Create Storage Driver

**Files:**
- Create: `src/Storage/StorageDriver.php` (interface)
- Create: `src/Storage/EloquentDriver.php` (implementation)

- [ ] **Step 1: Create src/Storage/StorageDriver.php**

```php
<?php

namespace graper\Graper\Storage;

interface StorageDriver
{
    public function load(int|string $id): array;
    public function save(int|string $id, array $data): void;
}
```

- [ ] **Step 2: Create src/Storage/EloquentDriver.php**

```php
<?php

namespace graper\Graper\Storage;

use graper\Graper\Models\GraperPage;

class EloquentDriver implements StorageDriver
{
    public function load(int|string $id): array
    {
        $page = GraperPage::findOrFail($id);

        return [
            'html' => $page->html ?? '',
            'css' => $page->css ?? '',
            'project_data' => $page->project_data ?? [],
        ];
    }

    public function save(int|string $id, array $data): void
    {
        $page = GraperPage::findOrFail($id);

        $page->update([
            'html' => $data['html'] ?? $page->html,
            'css' => $data['css'] ?? $page->css,
            'project_data' => $data['project_data'] ?? $page->project_data,
        ]);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/Storage/StorageDriver.php src/Storage/EloquentDriver.php
git commit -m "feat: add Eloquent storage driver for page persistence"
```

---

## Task 4: Create Vite/JS Build Setup

**Files:**
- Create: `package.json`
- Create: `vite.config.ts`
- Create: `resources/js/editor.ts`
- Create: `resources/js/components/DevicePreview.ts`
- Create: `resources/js/components/DirtyTracker.ts`
- Create: `resources/js/utils/storage.ts`
- Create: `resources/views/editor.blade.php`

- [ ] **Step 1: Create package.json**

```json
{
    "name": "graper/graper",
    "private": true,
    "type": "module",
    "scripts": {
        "build": "tsc && vite build",
        "dev": "vite build",
        "preview": "vite preview"
    },
    "devDependencies": {
        "vite": "^6.0",
        "typescript": "^5.0"
    },
    "dependencies": {
        "grapesjs": "^0.22.0"
    }
}
```

- [ ] **Step 2: Create vite.config.ts**

```typescript
import { defineConfig } from 'vite';
import laravel from 'vite-plugin-laravel';

export default defineConfig({
    plugins: [
        laravel({
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            input: 'resources/js/editor.ts',
            output: {
                entryFileNames: 'grapesjs/[name].js',
                chunkFileNames: 'grapesjs/[name].js',
            },
        },
    },
});
```

- [ ] **Step 3: Create resources/js/utils/storage.ts**

```typescript
export interface StorageData {
    html: string;
    css: string;
    projectData: object;
}

export interface StorageDriver {
    load(id: string | number): Promise<StorageData>;
    save(id: string | number, data: StorageData): Promise<void>;
}

export const createApiDriver = (csrfToken: string): StorageDriver => {
    return {
        async load(id: string | number): Promise<StorageData> {
            const response = await fetch(`/graper/api/page/${id}`, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });
            return response.json();
        },
        async save(id: string | number, data: StorageData): Promise<void> {
            await fetch(`/graper/api/page/${id}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(data),
            });
        },
    };
};
```

- [ ] **Step 4: Create resources/js/components/DevicePreview.ts**

```typescript
import grapesjs from 'grapesjs';

export const DevicePreview = (editor: ReturnType<typeof grapesjs.init>) => {
    const dm = editor.DeviceManager;

    dm.add('desktop', 'Desktop', '100%');
    dm.add('tablet', 'Tablet', '768px');
    dm.add('mobile', 'Mobile', '375px');

    return {
        setDevice(device: string) {
            editor.setDevice(device);
        },
        getCurrentDevice() {
            return editor.getDevice();
        },
    };
};
```

- [ ] **Step 5: Create resources/js/components/DirtyTracker.ts**

```typescript
import grapesjs from 'grapesjs';

export const DirtyTracker = (editor: ReturnType<typeof grapesjs.init>) => {
    let isDirty = false;

    editor.on('change', () => {
        isDirty = true;
    });

    return {
        isDirty() {
            return isDirty;
        },
        clear() {
            isDirty = false;
        },
        warnIfDirty() {
            if (isDirty && !confirm('You have unsaved changes. Discard?')) {
                return false;
            }
            return true;
        },
    };
};
```

- [ ] **Step 6: Create resources/js/editor.ts**

```typescript
import grapesjs from 'grapesjs';
import { DevicePreview } from './components/DevicePreview';
import { DirtyTracker } from './components/DirtyTracker';
import { createApiDriver, StorageDriver } from './utils/storage';

interface EditorConfig {
    container: string;
    pageId: number | string;
    csrfToken: string;
    height?: string;
    cssExternal?: string[];
    cssInternal?: string;
}

export const initEditor = (config: EditorConfig) => {
    const { container, pageId, csrfToken, height = '500px', cssExternal = [], cssInternal = '' } = config;

    const storageDriver: StorageDriver = createApiDriver(csrfToken);

    const editor = grapesjs.init({
        container,
        height,
        storageManager: false,
        projectData: {},
        style: cssInternal,
    });

    const devicePreview = DevicePreview(editor);
    const dirtyTracker = DirtyTracker(editor);

    (editor as any)._graperStorage = storageDriver;
    (editor as any)._graperDirty = dirtyTracker;
    (editor as any)._graperDevice = devicePreview;

    editor.Panels.addPanel({
        id: 'graper-toolbar',
        buttons: [
            {
                id: 'device-desktop',
                label: 'Desktop',
                command: () => devicePreview.setDevice('desktop'),
                active: true,
            },
            {
                id: 'device-tablet',
                label: 'Tablet',
                command: () => devicePreview.setDevice('tablet'),
            },
            {
                id: 'device-mobile',
                label: 'Mobile',
                command: () => devicePreview.setDevice('mobile'),
            },
            {
                id: 'save-page',
                label: 'Save',
                command: async () => {
                    const html = editor.getHtml();
                    const css = editor.getCss();
                    const projectData = editor.getProjectData();

                    await storageDriver.save(pageId, { html, css, projectData });
                    dirtyTracker.clear();
                    editor.Notify?.success('Saved!');
                },
            },
        ],
    });

    return editor;
};

export type { EditorConfig, StorageDriver };
```

- [ ] **Step 7: Create resources/views/editor.blade.php**

```blade
<div id="grapesjs-editor" style="height: {{ $height }};"></div>

@vite(['resources/js/editor.ts'])

<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.graperEditor = initEditor({
            container: '#grapesjs-editor',
            pageId: {{ $pageId }},
            csrfToken: '{{ csrf_token() }}',
            height: '{{ $height }}',
            cssExternal: {!! json_encode($cssExternal ?? []) !!},
            cssInternal: {!! json_encode($cssInternal ?? '') !!},
        });
    });
</script>
```

- [ ] **Step 8: Build JS**

```bash
npm install && npm run build
```

- [ ] **Step 9: Commit**

```bash
git add package.json vite.config.ts resources/js/ resources/views/editor.blade.php
git commit -m "feat: setup Vite and GrapeJS editor with device preview"
```

---

## Task 5: Create GrapesJsField Form Component

**Files:**
- Create: `src/Forms/Components/GrapesJsField.php`
- Create: `resources/views/forms/components/grapes-js-field.blade.php`

- [ ] **Step 1: Create GrapesJsField.php**

```php
<?php

namespace graper\Graper\Forms\Components;

use Filament\Forms\Components\Field;

class GrapesJsField extends Field
{
    protected string $view = 'graper::forms.components.grapes-js-field';

    protected ?string $height = '500px';

    protected array $cssExternal = [];

    protected ?string $cssInternal = null;

    public function height(string $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function getHeight(): string
    {
        return $this->height;
    }

    public function cssExternal(array $css): static
    {
        $this->cssExternal = $css;
        return $this;
    }

    public function getCssExternal(): array
    {
        return $this->cssExternal;
    }

    public function cssInternal(?string $css): static
    {
        $this->cssInternal = $css;
        return $this;
    }

    public function getCssInternal(): ?string
    {
        return $this->cssInternal;
    }
}
```

- [ ] **Step 2: Create resources/views/forms/components/grapes-js-field.blade.php**

```blade
@php
    $getHeight = $getHeight();
    $getCssExternal = $getCssExternal();
    $getCssInternal = $getCssInternal();
@endphp

<div x-data="{
    pageId: null,
    initEditor() {
        if (!this.pageId) return;
        
        window.graperEditor = initEditor({
            container: '#{{ $getId() }}',
            pageId: this.pageId,
            csrfToken: '{{ csrf_token() }}',
            height: '{{ $getHeight }}',
            cssExternal: {{ json_encode($getCssExternal) }},
            cssInternal: {{ json_encode($getCssInternal) }},
        });
    }
}" x-init="initEditor()">
    <input type="hidden" name="{{ $getName() }}" :value="JSON.stringify(window.graperEditor?.getProjectData())">
    <div id="{{ $getId() }}" style="height: {{ $getHeight }}; border: 1px solid #e5e7eb; border-radius: 0.375rem;"></div>
</div>
```

- [ ] **Step 3: Commit**

```bash
git add src/Forms/Components/GrapesJsField.php resources/views/forms/components/grapes-js-field.blade.php
git commit -m "feat: add GrapesJsField form component"
```

---

## Task 6: Create GrapesJsPage Full-Page Builder

**Files:**
- Create: `src/Pages/GrapesJsPage.php`
- Create: `src/Http/Controllers/GraperPageController.php`
- Create: `routes/graper.php`
- Create: `resources/views/pages/grapes-js-page.blade.php`

- [ ] **Step 1: Create GraperPageController.php**

```php
<?php

namespace graper\Graper\Http\Controllers;

use graper\Graper\Models\GraperPage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GraperPageController extends Controller
{
    public function edit(GraperPage $page)
    {
        return view('graper::editor', [
            'page' => $page,
            'height' => config('grapesjs.default_height', '500px'),
            'cssExternal' => config('grapesjs.css.external', []),
            'cssInternal' => config('grapesjs.css.internal'),
        ]);
    }

    public function update(Request $request, GraperPage $page)
    {
        $data = $request->validate([
            'html' => 'nullable|string',
            'css' => 'nullable|string',
            'project_data' => 'nullable|array',
        ]);

        $page->update($data);

        return response()->json(['success' => true]);
    }

    public function show(GraperPage $page)
    {
        return response()->json([
            'html' => $page->html,
            'css' => $page->css,
            'project_data' => $page->project_data,
        ]);
    }
}
```

- [ ] **Step 2: Create GrapesJsPage.php**

```php
<?php

namespace graper\Graper\Pages;

use Filament\Pages\Page;
use graper\Graper\Models\GraperPage;
use Illuminate\Support\Facades\Route;

class GrapesJsPage extends Page
{
    protected static string $view = 'graper::pages.grapes-js-page';

    public ?GraperPage $page = null;

    public static function routes(Panel $panel): void
    {
        Route::get('/graper/edit/{page}', static::class)
            ->name('graper.edit')
            ->middleware(config('filament.auth.middleware'));
    }

    public function mount(GraperPage $page)
    {
        $this->page = $page;
    }

    protected function getViewData(): array
    {
        return [
            'page' => $this->page,
            'height' => config('grapesjs.default_height', '500px'),
            'cssExternal' => config('grapesjs.css.external', []),
            'cssInternal' => config('grapesjs.css.internal'),
        ];
    }
}
```

- [ ] **Step 3: Create routes/graper.php**

```php
<?php

use graper\Graper\Http\Controllers\GraperPageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('graper')->group(function () {
    Route::get('/edit/{page}', [GraperPageController::class, 'edit'])->name('graper.edit');
    Route::put('/api/page/{page}', [GraperPageController::class, 'update'])->name('graper.update');
    Route::get('/api/page/{page}', [GraperPageController::class, 'show'])->name('graper.show');
});
```

- [ ] **Step 4: Create resources/views/pages/grapes-js-page.blade.php**

```blade
<x-filament-panels::page>
    <div class="h-full">
        @include('graper::editor')
    </div>
</x-filament-panels::page>
```

- [ ] **Step 5: Commit**

```bash
git add src/Pages/GrapesJsPage.php src/Http/Controllers/GraperPageController.php routes/graper.php resources/views/pages/
git commit -m "feat: add GrapesJsPage full-page builder"
```

---

## Task 7: Create GraperPlugin Filament Integration

**Files:**
- Create: `src/GraperPlugin.php`
- Modify: `src/GraperServiceProvider.php`

- [ ] **Step 1: Create GraperPlugin.php**

```php
<?php

namespace graper\Graper;

use Filament\Plugin as BasePlugin;
use Filament\Panel;

class GraperPlugin extends BasePlugin
{
    public function register(Panel $panel): void
    {
        $panel
            ->routes([
                '/graper/edit/{page}' => Pages\GrapesJsPage::class,
            ])
            ->routes(__DIR__ . '/../../routes/graper.php');
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
```

- [ ] **Step 2: Update GraperServiceProvider.php**

```php
<?php

namespace graper\Graper;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use graper\Graper\Commands\GraperCommand;

class GraperServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('graper')
            ->hasConfigFile('grapesjs')
            ->hasViews()
            ->hasMigration('create_graper_pages_table')
            ->hasCommand(GraperCommand::class);
    }

    public function packageBooted(): void
    {
        //
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/GraperPlugin.php src/GraperServiceProvider.php
git commit -m "feat: add GraperPlugin Filament integration"
```

---

## Task 8: Create Artisan Install Command

**Files:**
- Create: `src/Commands/GraperInstallCommand.php`

- [ ] **Step 1: Create GraperInstallCommand.php**

```php
<?php

namespace graper\Graper\Commands;

use Illuminate\Console\Command;

class GraperInstallCommand extends Command
{
    protected $signature = 'graper:install';

    protected $description = 'Install Graper package';

    public function handle(): int
    {
        $this->info('Installing Graper...');

        $this->call('vendor:publish', [
            '--package' => 'graper/graper',
            '--tag' => 'grapesjs-config',
        ]);

        $this->call('vendor:publish', [
            '--package' => 'graper/graper',
            '--tag' => 'grapesjs-migrations',
        ]);

        $this->info('Graper installed successfully.');

        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Commands/GraperInstallCommand.php
git commit -m "feat: add graper:install command"
```

---

## Task 9: Run Tests + Verify

- [ ] **Step 1: Run PHP tests**

```bash
./vendor/bin/pest
```

- [ ] **Step 2: Run PHPStan**

```bash
./vendor/bin/phpstan analyse
```

- [ ] **Step 3: Run Pint**

```bash
./vendor/bin/pint --test
```

- [ ] **Step 4: Final commit**

```bash
git add .
git commit -m "chore: Phase 1 MVP complete"
```

---

## Plan Complete

**Saved to:** `docs/superpowers/plans/2026-04-24-grapesjs-mvp-plan.md`

**Two execution options:**

1. **Subagent-Driven (recommended)** - I dispatch a fresh subagent per task with code review between tasks
   
2. **Inline Execution** - Execute tasks in this session, batched with checkpoints for review

**Which approach?**