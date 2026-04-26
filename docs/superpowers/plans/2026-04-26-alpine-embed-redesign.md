# Alpine-Embedded Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the full-page GrapeJS editor with an Alpine.js Filament form field backed by `grapesjs-tailwind` (62 blocks, SVG thumbnails), embedded directly in the `GraperPageResource` edit form alongside metadata fields.

**Architecture:** `GrapesJsField` is a Filament form field whose Blade view renders an Alpine `x-data="graperEditor({...})"` component. The Alpine component boots GrapeJS with the `grapesjs-tailwind` plugin for built-in blocks, then fetches `/graper/api/blocks` to register custom PHP blocks alongside them. State syncs to Livewire via `$wire.entangle`, and a `content` accessor/mutator on `GraperPage` bridges the JSON envelope to the three existing DB columns (`html`, `css`, `project_data`).

**Tech Stack:** PHP 8.4, Filament v5, Livewire v4, Alpine.js (global via Filament), GrapeJS 0.22, grapesjs-tailwind 1.0.13, TypeScript, Vite, Pest v4

---

## File Map

**Modified:**
- `package.json` — swap `grapesjs-tailwindcss-plugin` → `grapesjs-tailwind`
- `vite.config.ts` — entry `editor.ts` → `index.ts`, output name `index`
- `src/Blocks/Block.php` — remove `getFlowbiteTemplate`, `getShadcnTemplate`, `getThemeTemplate`; simplify `toArray()`
- `src/Blocks/BlockRegistry.php` — remove `$theme` param from `toArray()`
- `src/Http/Controllers/GraperBlockController.php` — remove `?theme=` handling
- `src/Models/GraperPage.php` — add `content` getter/setter, add `content` to `$fillable`
- `src/Forms/Components/GrapesJsField.php` — replace CSS options with `$loadDefaultBlocks` / `$minHeight`; update view path
- `src/Resources/GraperPageResource.php` — add `GrapesJsField` to form; remove `ViewGraperPage` from pages
- `src/GraperServiceProvider.php` — update registered JS asset from `editor.js` → `index.js`
- `routes/graper.php` — remove `graper.edit` route, name blocks route `graper.blocks`
- `tests/Unit/Blocks/BlockTest.php` — add assertion that `toArray()` exposes no theme key
- `tests/Unit/Blocks/BlockRegistryTest.php` — confirm `toArray()` takes no `$theme` arg

**Created:**
- `resources/views/fields/grapesjs.blade.php` — Alpine component Blade view
- `resources/js/index.ts` — Alpine `graperEditor` data component
- `tests/Unit/Forms/Components/GrapesJsFieldTest.php` — unit tests for field config

**Deleted:**
- `resources/views/editor.blade.php`
- `src/Resources/GraperPageResource/Pages/ViewGraperPage.php`
- `resources/js/editor.ts`
- `resources/js/blocks/registry.ts`
- `resources/js/components/DevicePreview.ts`
- `resources/js/components/DirtyTracker.ts`
- `resources/js/components/DirtyTracker.test.ts`
- `resources/js/utils/storage.ts`
- `resources/js/utils/storage.test.ts`

---

## Task 1: Swap npm package and update Vite config

**Files:**
- Modify: `package.json`
- Modify: `vite.config.ts`

- [ ] **Step 1: Update `package.json` dependencies**

Replace the old plugin. Final `dependencies` block:

```json
{
  "dependencies": {
    "grapesjs": "^0.22.0",
    "grapesjs-tailwind": "^1.0.13"
  }
}
```

Remove `"grapesjs-tailwindcss-plugin": "^0.1.10"` entirely.

- [ ] **Step 2: Replace `vite.config.ts`**

```ts
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/index.ts',
            refresh: true,
        }),
    ],
    build: {
        lib: {
            entry: 'resources/js/index.ts',
            name: 'GraperEditor',
            fileName: 'index',
            formats: ['iife'],
        },
        rollupOptions: {
            external: [],
            output: {
                dir: '../../public/build/grapesjs',
                entryFileNames: '[name].js',
                chunkFileNames: '[name].js',
            },
        },
    },
});
```

- [ ] **Step 3: Install dependencies**

```bash
cd /Users/asad/Herd/Filamentplugin/packages/graper
npm install
```

Expected: `node_modules/grapesjs-tailwind` present, `grapesjs-tailwindcss-plugin` absent.

- [ ] **Step 4: Commit**

```bash
git add package.json package-lock.json vite.config.ts
git commit -m "chore: swap grapesjs-tailwindcss-plugin for grapesjs-tailwind"
```

---

## Task 2: Strip Flowbite/shadcn from Block.php

**Files:**
- Modify: `src/Blocks/Block.php`
- Modify: `tests/Unit/Blocks/BlockTest.php`

- [ ] **Step 1: Add failing tests to `tests/Unit/Blocks/BlockTest.php`**

```php
test('toArray only contains the six expected keys', function () {
    $block = new class extends Block {
        public static function getId(): string { return 'no-theme'; }
        public static function getName(): string { return 'No Theme'; }
        public static function getCategory(): string { return 'Test'; }
        public function getTemplate(): string { return '<div>test</div>'; }
    };

    expect(array_keys($block->toArray()))->toBe(['id', 'name', 'category', 'template', 'order', 'thumbnail']);
});

test('block has no Flowbite or shadcn template methods', function () {
    $block = new class extends Block {
        public static function getId(): string { return 'x'; }
        public static function getName(): string { return 'X'; }
        public static function getCategory(): string { return 'Test'; }
        public function getTemplate(): string { return '<div></div>'; }
    };

    expect(method_exists($block, 'getFlowbiteTemplate'))->toBeFalse();
    expect(method_exists($block, 'getShadcnTemplate'))->toBeFalse();
    expect(method_exists($block, 'getThemeTemplate'))->toBeFalse();
});
```

- [ ] **Step 2: Run tests — confirm they fail**

```bash
./vendor/bin/pest tests/Unit/Blocks/BlockTest.php --filter="toArray only contains"
```

Expected: FAIL — extra keys present or theme methods exist.

- [ ] **Step 3: Replace `src/Blocks/Block.php`**

```php
<?php

declare(strict_types=1);

namespace CybertronianKelvin\Graper\Blocks;

abstract class Block
{
    abstract public static function getId(): string;

    abstract public static function getName(): string;

    abstract public static function getCategory(): string;

    abstract public function getTemplate(): string;

    public static function getOrder(): int
    {
        return 100;
    }

    public static function getThumbnail(): ?string
    {
        return null;
    }

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
}
```

- [ ] **Step 4: Run all block tests**

```bash
./vendor/bin/pest tests/Unit/Blocks/BlockTest.php
```

Expected: All PASS.

- [ ] **Step 5: Run pint**

```bash
./vendor/bin/pint src/Blocks/Block.php --format agent
```

- [ ] **Step 6: Commit**

```bash
git add src/Blocks/Block.php tests/Unit/Blocks/BlockTest.php
git commit -m "refactor: remove Flowbite/shadcn theme system from Block"
```

---

## Task 3: Remove theme param from BlockRegistry and GraperBlockController

**Files:**
- Modify: `src/Blocks/BlockRegistry.php`
- Modify: `src/Http/Controllers/GraperBlockController.php`
- Modify: `tests/Unit/Blocks/BlockRegistryTest.php`

- [ ] **Step 1: Add a test to `tests/Unit/Blocks/BlockRegistryTest.php`**

```php
test('toArray block entries contain exactly the six expected keys', function () {
    $registry = BlockRegistry::make();
    $registry->register(new class extends Block {
        public static function getId(): string { return 'b'; }
        public static function getName(): string { return 'B'; }
        public static function getCategory(): string { return 'T'; }
        public function getTemplate(): string { return '<div></div>'; }
    });

    $block = $registry->toArray()['blocks'][0];

    expect(array_keys($block))->toBe(['id', 'name', 'category', 'template', 'order', 'thumbnail']);
});
```

- [ ] **Step 2: Run test — confirm it currently fails**

```bash
./vendor/bin/pest tests/Unit/Blocks/BlockRegistryTest.php --filter="toArray block entries"
```

Expected: FAIL — extra keys present.

- [ ] **Step 3: Update `toArray()` in `src/Blocks/BlockRegistry.php`**

Remove the `string $theme = 'tailwind'` parameter from the signature:

```php
public function toArray(): array
{
    return [
        'blocks'     => $this->all()->map(fn (Block $b) => $b->toArray())->values()->all(),
        'categories' => $this->categories()->all(),
    ];
}
```

- [ ] **Step 4: Replace `src/Http/Controllers/GraperBlockController.php`**

```php
<?php

declare(strict_types=1);

namespace CybertronianKelvin\Graper\Http\Controllers;

use CybertronianKelvin\Graper\Blocks\BlockRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class GraperBlockController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(BlockRegistry::make()->toArray());
    }
}
```

- [ ] **Step 5: Run all block and controller tests**

```bash
./vendor/bin/pest tests/Unit/Blocks/ tests/Feature/
```

Expected: All PASS.

- [ ] **Step 6: Run pint**

```bash
./vendor/bin/pint src/Blocks/BlockRegistry.php src/Http/Controllers/GraperBlockController.php --format agent
```

- [ ] **Step 7: Commit**

```bash
git add src/Blocks/BlockRegistry.php src/Http/Controllers/GraperBlockController.php tests/Unit/Blocks/BlockRegistryTest.php
git commit -m "refactor: remove theme param from BlockRegistry and block controller"
```

---

## Task 4: Add content accessor/mutator to GraperPage

**Files:**
- Modify: `src/Models/GraperPage.php`
- Modify: `tests/Unit/GraperPageTest.php`

- [ ] **Step 1: Add failing tests to `tests/Unit/GraperPageTest.php`**

```php
it('content getter returns json encoding all three columns', function () {
    $page = new GraperPage;
    $page->html         = '<div>hello</div>';
    $page->css          = 'body { color: red; }';
    $page->project_data = ['pages' => []];

    $decoded = json_decode($page->content, true);

    expect($decoded['html'])->toBe('<div>hello</div>');
    expect($decoded['css'])->toBe('body { color: red; }');
    expect($decoded['project_data'])->toBe(['pages' => []]);
});

it('content setter unpacks json into the three real columns', function () {
    $page = new GraperPage;
    $page->content = json_encode([
        'html'         => '<p>test</p>',
        'css'          => 'p { color: blue; }',
        'project_data' => ['version' => '0.22'],
    ]);

    expect($page->html)->toBe('<p>test</p>');
    expect($page->css)->toBe('p { color: blue; }');
    expect($page->project_data)->toBe(['version' => '0.22']);
});

it('content getter returns empty defaults when columns are null', function () {
    $page = new GraperPage;

    $decoded = json_decode($page->content, true);

    expect($decoded['html'])->toBe('');
    expect($decoded['css'])->toBe('');
    expect($decoded['project_data'])->toBe([]);
});
```

- [ ] **Step 2: Run tests — confirm they fail**

```bash
./vendor/bin/pest tests/Unit/GraperPageTest.php --filter="content getter"
```

Expected: FAIL — `content` attribute does not exist.

- [ ] **Step 3: Replace `src/Models/GraperPage.php`**

```php
<?php

declare(strict_types=1);

namespace CybertronianKelvin\Graper\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;

class GraperPage extends Model
{
    use HasFactory;

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
        'content',
    ];

    protected $casts = [
        'project_data' => 'array',
        'is_published' => 'boolean',
    ];

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

    public function creator(): BelongsTo
    {
        $userModel = config('filament.user_model', User::class);

        return $this->belongsTo($userModel, 'created_by');
    }
}
```

- [ ] **Step 4: Run all GraperPage tests**

```bash
./vendor/bin/pest tests/Unit/GraperPageTest.php
```

Expected: All PASS.

- [ ] **Step 5: Run pint**

```bash
./vendor/bin/pint src/Models/GraperPage.php --format agent
```

- [ ] **Step 6: Commit**

```bash
git add src/Models/GraperPage.php tests/Unit/GraperPageTest.php
git commit -m "feat: add content accessor/mutator to GraperPage"
```

---

## Task 5: Rebuild GrapesJsField PHP class

**Files:**
- Modify: `src/Forms/Components/GrapesJsField.php`
- Create: `tests/Unit/Forms/Components/GrapesJsFieldTest.php`

- [ ] **Step 1: Create the test directory and file**

```bash
mkdir -p /Users/asad/Herd/Filamentplugin/packages/graper/tests/Unit/Forms/Components
```

Create `tests/Unit/Forms/Components/GrapesJsFieldTest.php`:

```php
<?php

declare(strict_types=1);

use CybertronianKelvin\Graper\Forms\Components\GrapesJsField;

it('loads default blocks by default', function () {
    $field = GrapesJsField::make('content');

    expect($field->getLoadDefaultBlocks())->toBeTrue();
});

it('can disable default blocks', function () {
    $field = GrapesJsField::make('content')->loadDefaultBlocks(false);

    expect($field->getLoadDefaultBlocks())->toBeFalse();
});

it('defaults to 600px min height', function () {
    $field = GrapesJsField::make('content');

    expect($field->getMinHeight())->toBe('600px');
});

it('can override min height', function () {
    $field = GrapesJsField::make('content')->minHeight('70vh');

    expect($field->getMinHeight())->toBe('70vh');
});

it('returns itself for method chaining', function () {
    $field = GrapesJsField::make('content');

    expect($field->minHeight('500px'))->toBeInstanceOf(GrapesJsField::class);
    expect($field->loadDefaultBlocks())->toBeInstanceOf(GrapesJsField::class);
});
```

- [ ] **Step 2: Run tests — confirm they fail**

```bash
./vendor/bin/pest tests/Unit/Forms/Components/GrapesJsFieldTest.php
```

Expected: FAIL — `getLoadDefaultBlocks` and `getMinHeight` do not exist yet.

- [ ] **Step 3: Replace `src/Forms/Components/GrapesJsField.php`**

```php
<?php

declare(strict_types=1);

namespace CybertronianKelvin\Graper\Forms\Components;

use Filament\Forms\Components\Field;

class GrapesJsField extends Field
{
    protected string $view = 'graper::fields.grapesjs';

    protected bool $loadDefaultBlocks = true;

    protected string $minHeight = '600px';

    public function loadDefaultBlocks(bool $load = true): static
    {
        $this->loadDefaultBlocks = $load;

        return $this;
    }

    public function getLoadDefaultBlocks(): bool
    {
        return $this->loadDefaultBlocks;
    }

    public function minHeight(string $height): static
    {
        $this->minHeight = $height;

        return $this;
    }

    public function getMinHeight(): string
    {
        return $this->minHeight;
    }
}
```

- [ ] **Step 4: Run tests — confirm they pass**

```bash
./vendor/bin/pest tests/Unit/Forms/Components/GrapesJsFieldTest.php
```

Expected: All PASS.

- [ ] **Step 5: Run pint**

```bash
./vendor/bin/pint src/Forms/Components/GrapesJsField.php --format agent
```

- [ ] **Step 6: Commit**

```bash
git add src/Forms/Components/GrapesJsField.php tests/Unit/Forms/Components/GrapesJsFieldTest.php
git commit -m "feat: rebuild GrapesJsField with loadDefaultBlocks and minHeight"
```

---

## Task 6: Build the Alpine component (index.ts)

**Files:**
- Create: `resources/js/index.ts`

- [ ] **Step 1: Create `resources/js/index.ts`**

```ts
import grapesjs, { type Editor } from 'grapesjs';
// @ts-ignore — grapesjs-tailwind ships no TypeScript types
import tailwindPlugin from 'grapesjs-tailwind';

interface BlockDefinition {
    id: string;
    name: string;
    category: string;
    template: string;
    order: number;
    thumbnail: string | null;
}

declare global {
    interface Window {
        graperInstances: Record<string, Editor>;
    }
}

function registerGraperEditor(): void {
    // Alpine is provided globally by Filament — no import needed
    // @ts-ignore
    window.Alpine.data(
        'graperEditor',
        ({
            container,
            state: initialState,
            customBlocksUrl,
            loadDefaultBlocks,
            minHeight,
        }: {
            container: string;
            state: string;
            customBlocksUrl: string;
            loadDefaultBlocks: boolean;
            minHeight: string;
        }) => ({
            state: initialState,
            instance: null as Editor | null,

            init(): void {
                const editor: Editor = grapesjs.init({
                    container,
                    height: minHeight,
                    storageManager: false,
                    plugins: loadDefaultBlocks ? [tailwindPlugin] : [],
                });

                fetch(customBlocksUrl)
                    .then((r) => r.json())
                    .then(({ blocks }: { blocks: BlockDefinition[] }) => {
                        blocks.forEach((block) => {
                            editor.BlockManager.add(block.id, {
                                label: block.name,
                                category: { id: block.category, label: block.category },
                                content: block.template,
                                media: block.thumbnail ?? '',
                                attributes: { 'data-block-id': block.id },
                            });
                        });
                    })
                    .catch((err: Error) => {
                        console.error('[Graper] Failed to load custom blocks', err);
                    });

                if (initialState) {
                    try {
                        const data = JSON.parse(initialState) as {
                            html?: string;
                            css?: string;
                            project_data?: object;
                        };
                        if (data.project_data && Object.keys(data.project_data).length > 0) {
                            editor.loadProjectData(data.project_data);
                        } else if (data.html) {
                            editor.setComponents(data.html);
                            editor.setStyle(data.css ?? '');
                        }
                    } catch {
                        // empty canvas is fine
                    }
                }

                editor.on('update', () => {
                    this.state = JSON.stringify({
                        html: editor.getHtml(),
                        css: editor.getCss(),
                        project_data: editor.getProjectData(),
                    });
                });

                const instanceId = container.replace('#', '');
                window.graperInstances = window.graperInstances ?? {};
                window.graperInstances[instanceId] = editor;
                window.dispatchEvent(
                    new CustomEvent('graper:ready', { detail: { editor, id: instanceId } }),
                );

                this.instance = editor;
            },
        }),
    );
}

document.addEventListener('alpine:init', registerGraperEditor);
```

- [ ] **Step 2: Verify TypeScript compiles**

```bash
npx tsc --noEmit
```

Expected: No errors.

- [ ] **Step 3: Build the asset**

```bash
npm run build
```

Expected: `../../public/build/grapesjs/index.js` created with no errors.

- [ ] **Step 4: Commit**

```bash
git add resources/js/index.ts
git commit -m "feat: add Alpine graperEditor component"
```

---

## Task 7: Create the Blade field view and name the blocks route

**Files:**
- Create: `resources/views/fields/grapesjs.blade.php`
- Modify: `routes/graper.php`

- [ ] **Step 1: Replace `routes/graper.php`**

```php
<?php

use CybertronianKelvin\Graper\Http\Controllers\GraperBlockController;
use CybertronianKelvin\Graper\Http\Controllers\GraperPageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('graper')->group(function () {
    Route::put('/api/page/{page}', [GraperPageController::class, 'update'])->name('graper.update');
    Route::get('/api/page/{page}', [GraperPageController::class, 'show'])->name('graper.show');
});

Route::get('/graper/api/blocks', [GraperBlockController::class, 'index'])->name('graper.blocks');
```

- [ ] **Step 2: Create `resources/views/fields/grapesjs.blade.php`**

```blade
@php
    $statePath   = $getStatePath();
    $fieldId     = $getId();
    $minHeight   = $getMinHeight();
    $loadDefault = $getLoadDefaultBlocks() ? 'true' : 'false';
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="graperEditor({
            container: '#graper-{{ $fieldId }}',
            state: $wire.{{ $applyStateBindingModifiers("entangle('{$statePath}').defer") }},
            customBlocksUrl: '{{ route('graper.blocks') }}',
            loadDefaultBlocks: {{ $loadDefault }},
            minHeight: '{{ $minHeight }}',
        })"
        wire:ignore
    >
        <div id="graper-{{ $fieldId }}" style="min-height: {{ $minHeight }};"></div>
    </div>
</x-dynamic-component>
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/fields/grapesjs.blade.php routes/graper.php
git commit -m "feat: add GrapesJsField blade view with Alpine entangle binding"
```

---

## Task 8: Update GraperServiceProvider asset path

**Files:**
- Modify: `src/GraperServiceProvider.php`

- [ ] **Step 1: Update the JS asset from `editor.js` to `index.js`**

In `boot()`, change the `Js::make` call:

```php
FilamentAsset::register([
    Css::make('graper-editor', 'https://unpkg.com/grapesjs/dist/css/grapes.min.css'),
    Js::make('graper-editor', asset('build/grapesjs/index.js')),
], 'graper');
```

- [ ] **Step 2: Run pint**

```bash
./vendor/bin/pint src/GraperServiceProvider.php --format agent
```

- [ ] **Step 3: Commit**

```bash
git add src/GraperServiceProvider.php
git commit -m "chore: update registered JS asset path to index.js"
```

---

## Task 9: Update GraperPageResource form

**Files:**
- Modify: `src/Resources/GraperPageResource.php`

- [ ] **Step 1: Replace `src/Resources/GraperPageResource.php`**

```php
<?php

declare(strict_types=1);

namespace CybertronianKelvin\Graper\Resources;

use CybertronianKelvin\Graper\Forms\Components\GrapesJsField;
use CybertronianKelvin\Graper\Models\GraperPage;
use CybertronianKelvin\Graper\Resources\GraperPageResource\Pages\CreateGraperPage;
use CybertronianKelvin\Graper\Resources\GraperPageResource\Pages\EditGraperPage;
use CybertronianKelvin\Graper\Resources\GraperPageResource\Pages\ListGraperPages;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GraperPageResource extends Resource
{
    protected static ?string $model = GraperPage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pages';

    protected static ?string $slug = 'graper-pages';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required()
                ->columnSpan(2),
            TextInput::make('slug')
                ->unique(ignoreRecord: true)
                ->required(),
            Select::make('is_published')
                ->label('Published')
                ->options([true => 'Yes', false => 'No'])
                ->default(false)
                ->required(),
            DateTimePicker::make('published_at')
                ->columnSpan(2),
            GrapesJsField::make('content')
                ->loadDefaultBlocks()
                ->minHeight('70vh')
                ->columnSpanFull(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('title'),
                TextColumn::make('slug'),
                IconColumn::make('is_published'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListGraperPages::route('/'),
            'create' => CreateGraperPage::route('/create'),
            'edit'   => EditGraperPage::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 2: Run pint**

```bash
./vendor/bin/pint src/Resources/GraperPageResource.php --format agent
```

- [ ] **Step 3: Commit**

```bash
git add src/Resources/GraperPageResource.php
git commit -m "feat: embed GrapesJsField in GraperPageResource form"
```

---

## Task 10: Remove the full-page editor files

**Files:**
- Modify: `src/Http/Controllers/GraperPageController.php`
- Delete: see list above

- [ ] **Step 1: Replace `src/Http/Controllers/GraperPageController.php`**

```php
<?php

declare(strict_types=1);

namespace CybertronianKelvin\Graper\Http\Controllers;

use CybertronianKelvin\Graper\Models\GraperPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class GraperPageController extends Controller
{
    public function update(Request $request, GraperPage $page): JsonResponse
    {
        $data = $request->validate([
            'html'         => 'nullable|string',
            'css'          => 'nullable|string',
            'project_data' => 'nullable|array',
        ]);

        $page->update($data);

        return response()->json(['success' => true]);
    }

    public function show(GraperPage $page): JsonResponse
    {
        return response()->json([
            'html'         => $page->getAttributeValue('html') ?? '',
            'css'          => $page->getAttributeValue('css') ?? '',
            'project_data' => $page->getAttributeValue('project_data') ?? [],
        ]);
    }

    public function display(string $slug, Request $request): Response
    {
        $page = GraperPage::where('slug', $slug)->firstOrFail();

        if (! $page->is_published && ! $request->user()?->can('view', $page)) {
            abort(404);
        }

        return response()->view('graper::display', [
            'page'            => $page,
            'html'            => $page->html ?? '',
            'css'             => $page->css ?? '',
            'includeTailwind' => config('graper.include_tailwind', true),
        ]);
    }
}
```

- [ ] **Step 2: Delete unused files**

```bash
cd /Users/asad/Herd/Filamentplugin/packages/graper
rm resources/views/editor.blade.php
rm src/Resources/GraperPageResource/Pages/ViewGraperPage.php
rm resources/js/editor.ts
rm resources/js/blocks/registry.ts
rm resources/js/components/DevicePreview.ts
rm resources/js/components/DirtyTracker.ts
rm resources/js/components/DirtyTracker.test.ts
rm resources/js/utils/storage.ts
rm resources/js/utils/storage.test.ts
```

- [ ] **Step 3: Run all PHP tests**

```bash
./vendor/bin/pest --compact
```

Expected: All PASS. If any test references the deleted `graper.edit` route or `ViewGraperPage`, delete that test.

- [ ] **Step 4: Rebuild JS — confirm no dangling imports**

```bash
npm run build
```

Expected: Builds cleanly.

- [ ] **Step 5: Run pint**

```bash
./vendor/bin/pint src/Http/Controllers/GraperPageController.php --format agent
```

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "refactor: remove full-page editor, unused JS files, and ViewGraperPage"
```

---

## Task 11: Full test suite green check

- [ ] **Step 1: Run full Pest suite**

```bash
./vendor/bin/pest --compact
```

Expected: All PASS. Common failures to watch:
- Any test still calling `toArray('tailwind')` on `BlockRegistry` → remove the argument
- Any test referencing `route('graper.edit')` → delete that assertion

- [ ] **Step 2: Run JS tests**

```bash
npm test
```

Expected: All Vitest tests PASS. Deleted test files are gone so Vitest won't find them.

- [ ] **Step 3: Run pint across all dirty files**

```bash
./vendor/bin/pint --dirty --format agent
```

- [ ] **Step 4: Commit any fixes**

```bash
git add -A
git commit -m "fix: resolve post-redesign test regressions"
```

---

## Task 12: Write developer docs

**Files:**
- Create: `docs/README.md`

- [ ] **Step 1: Write `docs/README.md`**

See the spec at `docs/superpowers/specs/2026-04-25-alpine-embed-redesign.md` section "Documentation Deliverables" for the full content outline. The file must cover:

1. Installation (`composer require`, `graper:install`, `migrate`, panel provider setup)
2. The Pages resource out of the box
3. `GrapesJsField` on any resource (code example with `->loadDefaultBlocks()` and `->minHeight()`)
4. Adding custom PHP blocks (extend `Block`, implement all methods, register in `AppServiceProvider`)
5. Adding custom JS blocks (the `graper:ready` event, code example)
6. Accessing the editor instance via `window.graperInstances['graper-{fieldId}']`
7. Public page display (`/{prefix}/{slug}`, published gate)
8. Config reference table (`page_route_prefix`, `include_tailwind`)
9. Testing workflow table (PHP/Blade → reload; JS/TS → `npm run build` then reload)

- [ ] **Step 2: Force-add and commit**

```bash
git add -f docs/README.md
git commit -m "docs: add developer guide"
```

---

## Task 13: Write the desktop architecture explainer

**Files:**
- Create: `~/Desktop/graper-how-it-works.md`

- [ ] **Step 1: Write `~/Desktop/graper-how-it-works.md`**

The file must explain each of the following to a first-time Filament plugin author:

1. **What a Filament plugin is** — `PackageServiceProvider` vs `Plugin` class, what each one does
2. **How GrapeJS gets on the page** — `FilamentAsset::register()` → IIFE → `alpine:init` → `Alpine.data()` → `x-data` mount
3. **How Livewire and Alpine communicate** — `$wire.entangle`, `.defer`, `wire:ignore` and why each is needed
4. **The block system end to end** — PHP class → `BlockRegistry` singleton → `/graper/api/blocks` JSON → Alpine `fetch` → `editor.BlockManager.add()`
5. **The save/load cycle** — `editor.on('update')` → `this.state` → Livewire entangle → Filament Save → `setContentAttribute` → 3 DB columns; reverse for load
6. **Why both `project_data` and `html` are stored** — `project_data` for editable re-opening; `html` for fast public rendering
7. **Public page display** — the `display` route, published gate, Tailwind CDN injection
8. **How `FilamentAsset` works** — batching, deduplication, the `asset()` URL helper
9. **A file reference table** — every file, one-line description of its responsibility

- [ ] **Step 2: No git commit needed — desktop file is for personal reference only**

---

## Final Verification

- [ ] `./vendor/bin/pest --compact` → all green
- [ ] `npm run build` → no errors, `public/build/grapesjs/index.js` present
- [ ] Open the Graper Pages resource in the Herd admin panel
- [ ] Create a page — title/slug/status/published_at fields at top, editor below
- [ ] Block panel shows 62+ blocks with SVG thumbnails across categories
- [ ] Add a block, edit its content, click Filament Save — saves successfully
- [ ] Reopen the page — blocks reload in editable state (not frozen HTML)
- [ ] Visit `/{prefix}/{slug}` — public display renders the saved HTML correctly
