<?php

namespace Tests\Unit\Blocks;

use CybertronianKelvin\Graper\Blocks\Block;
use CybertronianKelvin\Graper\Blocks\BlockRegistry;

class TestBlockStub extends Block
{
    public static function getId(): string
    {
        return 'test-stub';
    }

    public static function getName(): string
    {
        return 'Test Stub';
    }

    public static function getCategory(): string
    {
        return 'Test';
    }

    public function getTemplate(): string
    {
        return '<div>Test</div>';
    }
}

class FirstBlockStub extends Block
{
    public static function getId(): string
    {
        return 'first';
    }

    public static function getName(): string
    {
        return 'First';
    }

    public static function getCategory(): string
    {
        return 'Test';
    }

    public function getTemplate(): string
    {
        return '<div>1</div>';
    }

    public static function getOrder(): int
    {
        return 20;
    }
}

class SecondBlockStub extends Block
{
    public static function getId(): string
    {
        return 'second';
    }

    public static function getName(): string
    {
        return 'Second';
    }

    public static function getCategory(): string
    {
        return 'Test';
    }

    public function getTemplate(): string
    {
        return '<div>2</div>';
    }

    public static function getOrder(): int
    {
        return 10;
    }
}

class MarketingBlockStub extends Block
{
    public static function getId(): string
    {
        return 'marketing';
    }

    public static function getName(): string
    {
        return 'Marketing';
    }

    public static function getCategory(): string
    {
        return 'Marketing';
    }

    public function getTemplate(): string
    {
        return '<div>1</div>';
    }
}

class ContentBlockStub extends Block
{
    public static function getId(): string
    {
        return 'content';
    }

    public static function getName(): string
    {
        return 'Content';
    }

    public static function getCategory(): string
    {
        return 'Content';
    }

    public function getTemplate(): string
    {
        return '<div>2</div>';
    }
}

class CategoryABlockStub extends Block
{
    public static function getId(): string
    {
        return 'block-a1';
    }

    public static function getName(): string
    {
        return 'Block A1';
    }

    public static function getCategory(): string
    {
        return 'A';
    }

    public function getTemplate(): string
    {
        return '<div>1</div>';
    }
}

class CategoryBBlockStub extends Block
{
    public static function getId(): string
    {
        return 'block-b';
    }

    public static function getName(): string
    {
        return 'Block B';
    }

    public static function getCategory(): string
    {
        return 'B';
    }

    public function getTemplate(): string
    {
        return '<div>2</div>';
    }
}

class CategoryA2BlockStub extends Block
{
    public static function getId(): string
    {
        return 'block-a2';
    }

    public static function getName(): string
    {
        return 'Block A2';
    }

    public static function getCategory(): string
    {
        return 'A';
    }

    public function getTemplate(): string
    {
        return '<div>3</div>';
    }
}

beforeEach(function () {
    BlockRegistry::reset();
});

test('registry starts empty', function () {
    $registry = BlockRegistry::make();
    expect($registry->all())->toBeEmpty();
    expect($registry->categories())->toBeEmpty();
});

test('registry can register block by instance', function () {
    $block = new TestBlockStub;

    $registry = BlockRegistry::make();
    $registry->register($block);

    expect($registry->has('test-stub'))->toBeTrue();
    expect($registry->get('test-stub'))->toBe($block);
});

test('registry can register block by class name', function () {
    $registry = BlockRegistry::make();
    $registry->register(TestBlockStub::class);

    expect($registry->has('test-stub'))->toBeTrue();
});

test('registry returns null for unknown block', function () {
    $registry = BlockRegistry::make();
    expect($registry->get('unknown'))->toBeNull();
});

test('registry sorts blocks by order', function () {
    $registry = BlockRegistry::make();
    $registry->register(new FirstBlockStub);
    $registry->register(new SecondBlockStub);

    $all = $registry->all();
    expect($all->first()::getId())->toBe('second');
    expect($all->last()::getId())->toBe('first');
});

test('registry filters by category', function () {
    $registry = BlockRegistry::make();
    $registry->register(new MarketingBlockStub);
    $registry->register(new ContentBlockStub);

    expect($registry->byCategory('Marketing'))->toHaveCount(1);
    expect($registry->byCategory('Marketing')->first()::getId())->toBe('marketing');
});

test('registry returns unique categories', function () {
    $registry = BlockRegistry::make();
    $registry->register(new CategoryABlockStub);
    $registry->register(new CategoryBBlockStub);
    $registry->register(new CategoryA2BlockStub);

    expect($registry->categories()->values()->all())->toBe(['A', 'B']);
});

test('registry serializes to array', function () {
    $registry = BlockRegistry::make();
    $registry->register(new class extends Block
    {
        public static function getId(): string
        {
            return 'test-block';
        }

        public static function getName(): string
        {
            return 'Test Block';
        }

        public static function getCategory(): string
        {
            return 'Test';
        }

        public function getTemplate(): string
        {
            return '<div>Test</div>';
        }

        public static function getOrder(): int
        {
            return 50;
        }
    });

    $arr = $registry->toArray();
    expect($arr['blocks'])->toHaveCount(1);
    expect($arr['blocks'][0]['id'])->toBe('test-block');
    expect($arr['categories'])->toBe(['Test']);
});

test('toArray block entries contain exactly the six expected keys', function () {
    $registry = BlockRegistry::make();
    $registry->register(new class extends Block
    {
        public static function getId(): string
        {
            return 'b';
        }

        public static function getName(): string
        {
            return 'B';
        }

        public static function getCategory(): string
        {
            return 'T';
        }

        public function getTemplate(): string
        {
            return '<div></div>';
        }
    });

    $block = $registry->toArray()['blocks'][0];

    expect(array_keys($block))->toBe(['id', 'name', 'category', 'template', 'order', 'thumbnail']);
});
