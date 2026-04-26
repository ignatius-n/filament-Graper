<?php

declare(strict_types=1);

use CybertronianKelvin\Graper\Models\GraperPage;

it('returns page data from the show endpoint', function () {
    $page = GraperPage::factory()->withContent()->create();

    $this->getJson("/graper/api/page/{$page->id}")
        ->assertSuccessful()
        ->assertJsonStructure(['html', 'css', 'project_data'])
        ->assertJson(['html' => '<div>Hello world</div>']);
});

it('returns empty strings for a page with no content', function () {
    $page = GraperPage::factory()->create();

    $this->getJson("/graper/api/page/{$page->id}")
        ->assertSuccessful()
        ->assertJson(['html' => '', 'css' => '', 'project_data' => []]);
});

it('returns 404 for a missing page on show', function () {
    $this->getJson('/graper/api/page/99999')
        ->assertNotFound();
});

it('updates html and css via the update endpoint', function () {
    $page = GraperPage::factory()->create();

    $this->putJson("/graper/api/page/{$page->id}", [
        'html' => '<section>Updated</section>',
        'css' => 'section { margin: 0; }',
        'project_data' => [],
    ])->assertSuccessful()
        ->assertJson(['success' => true]);

    expect($page->fresh())
        ->html->toBe('<section>Updated</section>')
        ->css->toBe('section { margin: 0; }');
});

it('updates project_data via the update endpoint', function () {
    $page = GraperPage::factory()->create();
    $data = ['pages' => [['frames' => []]]];

    $this->putJson("/graper/api/page/{$page->id}", [
        'html' => '',
        'css' => '',
        'project_data' => $data,
    ])->assertSuccessful();

    expect($page->fresh()->project_data)->toEqual($data);
});

it('returns 404 for a missing page on update', function () {
    $this->putJson('/graper/api/page/99999', ['html' => ''])
        ->assertNotFound();
});

it('rejects invalid project_data type', function () {
    $page = GraperPage::factory()->create();

    $this->putJson("/graper/api/page/{$page->id}", [
        'project_data' => 'not-an-array',
    ])->assertUnprocessable();
});
