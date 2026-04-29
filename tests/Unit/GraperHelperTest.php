<?php

declare(strict_types=1);

use CybertronianKelvin\Graper\Graper;
use CybertronianKelvin\Graper\Models\GraperPage;
use Illuminate\Database\Eloquent\Builder;

it('returns a query builder from pages()', function () {
    expect((new Graper)->pages())
        ->toBeInstanceOf(Builder::class);
});

it('finds a page by id', function () {
    $page = GraperPage::factory()->create();

    expect((new Graper)->findPage($page->id))
        ->toBeInstanceOf(GraperPage::class)
        ->id->toBe($page->id);
});

it('returns null for a missing page id', function () {
    expect((new Graper)->findPage(99999))->toBeNull();
});

it('finds a page by slug', function () {
    $page = GraperPage::factory()->create(['slug' => 'my-unique-slug']);

    expect((new Graper)->findPageBySlug('my-unique-slug'))
        ->toBeInstanceOf(GraperPage::class)
        ->slug->toBe('my-unique-slug');
});

it('returns null for a missing slug', function () {
    expect((new Graper)->findPageBySlug('does-not-exist'))->toBeNull();
});
