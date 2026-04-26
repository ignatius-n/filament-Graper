<?php

namespace Tests\Unit\ComponentTraits;

use CybertronianKelvin\Graper\ComponentTraits\ComponentTrait;

class TestComponentTraitStub extends ComponentTrait
{
    public static function getId(): string { return 'test-trait'; }
    public static function getName(): string { return 'Test Trait'; }
    public static function getLabel(): string { return 'Test Label'; }
    public static function getType(): string { return 'select'; }
}

class OrderedTraitStub extends ComponentTrait
{
    public static function getId(): string { return 'ordered-trait'; }
    public static function getName(): string { return 'Ordered'; }
    public static function getLabel(): string { return 'Ordered Trait'; }
    public static function getType(): string { return 'text'; }
    public static function getOrder(): int { return 50; }
}

class OptionsTraitStub extends ComponentTrait
{
    public static function getId(): string { return 'options-trait'; }
    public static function getName(): string { return 'Options'; }
    public static function getLabel(): string { return 'Options Trait'; }
    public static function getType(): string { return 'select'; }
    public static function getOptions(): array { return ['a' => 'A', 'b' => 'B']; }
}

test('component trait has required methods', function () {
    $trait = new TestComponentTraitStub();

    expect($trait::getId())->toBe('test-trait');
    expect($trait::getName())->toBe('Test Trait');
    expect($trait::getLabel())->toBe('Test Label');
    expect($trait::getType())->toBe('select');
    expect($trait::getOrder())->toBe(100);
    expect($trait::getOptions())->toBe([]);
    expect($trait->toArray())->toHaveKeys(['id', 'name', 'label', 'type', 'order', 'options']);
});

test('component trait can override order', function () {
    $trait = new OrderedTraitStub();
    expect($trait::getOrder())->toBe(50);
});

test('component trait can have options', function () {
    $trait = new OptionsTraitStub();
    expect($trait::getOptions())->toBe(['a' => 'A', 'b' => 'B']);
});