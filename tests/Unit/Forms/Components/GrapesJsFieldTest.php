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
