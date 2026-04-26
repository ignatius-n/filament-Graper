<?php

declare(strict_types=1);

arch('no debugging functions in source')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->each->not->toBeUsed();

arch('models extend Eloquent Model')
    ->expect('CybertronianKelvin\Graper\Models')
    ->toExtend(\Illuminate\Database\Eloquent\Model::class);

arch('controllers extend Laravel Controller')
    ->expect('CybertronianKelvin\Graper\Http\Controllers')
    ->toExtend(\Illuminate\Routing\Controller::class);

arch('storage driver implements StorageDriver interface')
    ->expect('CybertronianKelvin\Graper\Storage\EloquentDriver')
    ->toImplement(\CybertronianKelvin\Graper\Storage\StorageDriver::class);

arch('plugin implements Filament Plugin contract')
    ->expect('CybertronianKelvin\Graper\GraperPlugin')
    ->toImplement(\Filament\Contracts\Plugin::class);

arch('commands extend Illuminate Command')
    ->expect('CybertronianKelvin\Graper\Commands')
    ->toExtend(\Illuminate\Console\Command::class);

arch('all source files use strict types')
    ->expect('CybertronianKelvin\Graper')
    ->toUseStrictTypes();
