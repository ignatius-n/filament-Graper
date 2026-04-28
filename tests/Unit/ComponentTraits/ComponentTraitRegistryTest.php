<?php

namespace Tests\Unit\ComponentTraits;

use CybertronianKelvin\Graper\ComponentTraits\ComponentTrait;
use CybertronianKelvin\Graper\ComponentTraits\ComponentTraitRegistry;

class PaddingTraitStub extends ComponentTrait
{
    public static function getId(): string
    {
        return 'padding';
    }

    public static function getName(): string
    {
        return 'Padding';
    }

    public static function getLabel(): string
    {
        return 'Padding';
    }

    public static function getType(): string
    {
        return 'select';
    }

    public static function getOptions(): array
    {
        return ['none' => 'None', 'small' => 'Small', 'normal' => 'Normal', 'large' => 'Large'];
    }
}

class FirstTraitStub extends ComponentTrait
{
    public static function getId(): string
    {
        return 'first';
    }

    public static function getName(): string
    {
        return 'First';
    }

    public static function getLabel(): string
    {
        return 'First Trait';
    }

    public static function getType(): string
    {
        return 'text';
    }

    public static function getOrder(): int
    {
        return 20;
    }
}

class SecondTraitStub extends ComponentTrait
{
    public static function getId(): string
    {
        return 'second';
    }

    public static function getName(): string
    {
        return 'Second';
    }

    public static function getLabel(): string
    {
        return 'Second Trait';
    }

    public static function getType(): string
    {
        return 'text';
    }

    public static function getOrder(): int
    {
        return 10;
    }
}

beforeEach(function () {
    ComponentTraitRegistry::reset();
});

test('registry starts empty', function () {
    expect(ComponentTraitRegistry::make()->all())->toBeEmpty();
});

test('registry can register trait', function () {
    $registry = ComponentTraitRegistry::make();
    $registry->register(new PaddingTraitStub);

    expect($registry->has('padding'))->toBeTrue();
    expect($registry->get('padding'))->toBeInstanceOf(ComponentTrait::class);
});

test('registry can register by class name', function () {
    $registry = ComponentTraitRegistry::make();
    $registry->register(PaddingTraitStub::class);

    expect($registry->has('padding'))->toBeTrue();
});

test('registry sorts by order', function () {
    $registry = ComponentTraitRegistry::make();
    $registry->register(new FirstTraitStub);
    $registry->register(new SecondTraitStub);

    $all = $registry->all();
    expect($all->first()::getId())->toBe('second');
    expect($all->last()::getId())->toBe('first');
});

test('registry serializes to array', function () {
    $registry = ComponentTraitRegistry::make();
    $registry->register(new PaddingTraitStub);

    $arr = $registry->toArray();
    expect($arr)->toHaveCount(1);
    expect($arr[0]['id'])->toBe('padding');
});
