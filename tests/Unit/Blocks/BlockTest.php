<?php

use CybertronianKelvin\Graper\Blocks\Block;

test('block has required methods', function () {
    $block = new class extends Block {
        public static function getId(): string { return 'test-block'; }
        public static function getName(): string { return 'Test Block'; }
        public static function getCategory(): string { return 'Test'; }
        public function getTemplate(): string { return '<div>Test</div>'; }
    };

    expect($block::getId())->toBe('test-block');
    expect($block::getName())->toBe('Test Block');
    expect($block::getCategory())->toBe('Test');
    expect($block->getTemplate())->toBe('<div>Test</div>');
    expect($block::getOrder())->toBe(100);
    expect($block::getThumbnail())->toBeNull();
    expect($block->toArray())->toHaveKeys(['id', 'name', 'category', 'template', 'order', 'thumbnail']);
});

test('block can override order', function () {
    $block = new class extends Block {
        public static function getId(): string { return 'ordered-block'; }
        public static function getName(): string { return 'Ordered Block'; }
        public static function getCategory(): string { return 'Test'; }
        public function getTemplate(): string { return '<div>Test</div>'; }
        public static function getOrder(): int { return 50; }
    };

    expect($block::getOrder())->toBe(50);
});

test('block can override thumbnail', function () {
    $block = new class extends Block {
        public static function getId(): string { return 'thumb-block'; }
        public static function getName(): string { return 'Thumb Block'; }
        public static function getCategory(): string { return 'Test'; }
        public function getTemplate(): string { return '<div>Test</div>'; }
        public static function getThumbnail(): ?string { return '/blocks/thumb.png'; }
    };

    expect($block::getThumbnail())->toBe('/blocks/thumb.png');
});

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