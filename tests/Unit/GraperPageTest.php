<?php

declare(strict_types=1);

use CybertronianKelvin\Graper\Models\GraperPage;
use Illuminate\Database\QueryException;

it('can create a graper page', function () {
    $page = GraperPage::factory()->create(['title' => 'My Page', 'slug' => 'my-page']);

    expect($page)
        ->title->toBe('My Page')
        ->slug->toBe('my-page')
        ->is_published->toBeFalse()
        ->project_data->toBeNull();
});

it('casts project_data as array', function () {
    $page = GraperPage::factory()->create([
        'project_data' => ['pages' => []],
    ]);

    expect($page->fresh()->project_data)->toBeArray();
});

it('casts is_published as boolean', function () {
    $page = GraperPage::factory()->published()->create();

    expect($page->fresh()->is_published)->toBeTrue();
});

it('enforces unique slugs', function () {
    GraperPage::factory()->create(['slug' => 'duplicate-slug']);

    expect(fn () => GraperPage::factory()->create(['slug' => 'duplicate-slug']))
        ->toThrow(QueryException::class);
});

it('has the correct fillable fields', function () {
    $fillable = (new GraperPage)->getFillable();

    expect($fillable)
        ->toContain('title')
        ->toContain('slug')
        ->toContain('project_data')
        ->toContain('html')
        ->toContain('css')
        ->toContain('is_published');
});

it('can store and retrieve html and css', function () {
    $page = GraperPage::factory()->withContent()->create();

    expect($page->fresh())
        ->html->toBe('<div>Hello world</div>')
        ->css->toBe('div { color: red; }');
});

it('content getter returns json encoding all three columns', function () {
    $page = new GraperPage;
    $page->html = '<div>hello</div>';
    $page->css = 'body { color: red; }';
    $page->project_data = ['pages' => []];

    $decoded = json_decode($page->content, true);

    expect($decoded['html'])->toBe('<div>hello</div>');
    expect($decoded['css'])->toBe('body { color: red; }');
    expect($decoded['project_data'])->toBe(['pages' => []]);
});

it('content setter unpacks json into the three real columns', function () {
    $page = new GraperPage;
    $page->content = json_encode([
        'html' => '<p>test</p>',
        'css' => 'p { color: blue; }',
        'project_data' => ['version' => '0.22'],
    ]);

    expect($page->html)->toBe('<p>test</p>');
    expect($page->css)->toBe('p { color: blue; }');
    expect($page->project_data)->toBe(['version' => '0.22']);
});

it('content getter returns null when all columns are null', function () {
    $page = new GraperPage;

    expect($page->content)->toBeNull();
});
