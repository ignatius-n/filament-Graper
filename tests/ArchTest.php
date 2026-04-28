<?php

declare(strict_types=1);
use CybertronianKelvin\Graper\Storage\StorageDriver;
use Filament\Contracts\Plugin;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Controller;

arch('no debugging functions in source')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->each->not->toBeUsed();

arch('models extend Eloquent Model')
    ->expect('CybertronianKelvin\Graper\Models')
    ->toExtend(Model::class);

arch('controllers extend Laravel Controller')
    ->expect('CybertronianKelvin\Graper\Http\Controllers')
    ->toExtend(Controller::class);

arch('storage driver implements StorageDriver interface')
    ->expect('CybertronianKelvin\Graper\Storage\EloquentDriver')
    ->toImplement(StorageDriver::class);

arch('plugin implements Filament Plugin contract')
    ->expect('CybertronianKelvin\Graper\GraperPlugin')
    ->toImplement(Plugin::class);

arch('commands extend Illuminate Command')
    ->expect('CybertronianKelvin\Graper\Commands')
    ->toExtend(Command::class);

arch('all source files use strict types')
    ->expect('CybertronianKelvin\Graper')
    ->toUseStrictTypes();
