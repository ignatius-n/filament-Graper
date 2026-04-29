<?php

declare(strict_types=1);

use CybertronianKelvin\Graper\Models\GraperPage;
use CybertronianKelvin\Graper\Storage\EloquentDriver;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('loads a page by id', function () {
    $page = GraperPage::factory()->withContent()->create();
    $driver = new EloquentDriver;

    $data = $driver->load($page->id);

    expect($data)
        ->toHaveKey('html')
        ->toHaveKey('css')
        ->toHaveKey('project_data')
        ->and($data['html'])->toBe('<div>Hello world</div>')
        ->and($data['css'])->toBe('div { color: red; }');
});

it('returns empty strings for a page with no content', function () {
    $page = GraperPage::factory()->create();
    $driver = new EloquentDriver;

    $data = $driver->load($page->id);

    expect($data['html'])->toBe('');
    expect($data['css'])->toBe('');
    expect($data['project_data'])->toBeArray()->toBeEmpty();
});

it('saves html and css to a page', function () {
    $page = GraperPage::factory()->create();
    $driver = new EloquentDriver;

    $driver->save($page->id, [
        'html' => '<p>Updated</p>',
        'css' => 'p { font-size: 16px; }',
        'project_data' => [],
    ]);

    expect($page->fresh())
        ->html->toBe('<p>Updated</p>')
        ->css->toBe('p { font-size: 16px; }');
});

it('saves project_data to a page', function () {
    $page = GraperPage::factory()->create();
    $driver = new EloquentDriver;
    $projectData = ['pages' => [['frames' => [['component' => ['type' => 'text']]]]]];

    $driver->save($page->id, ['html' => '', 'css' => '', 'project_data' => $projectData]);

    expect($page->fresh()->project_data)->toEqual($projectData);
});

it('throws an exception for a non-existent page', function () {
    $driver = new EloquentDriver;

    expect(fn () => $driver->load(99999))
        ->toThrow(ModelNotFoundException::class);
});
