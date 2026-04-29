<?php

declare(strict_types=1);

namespace CybertronianKelvin\Graper\Tests;

use CybertronianKelvin\Graper\GraperServiceProvider;
use CybertronianKelvin\Graper\Http\Controllers\GraperPageController;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CybertronianKelvin\\Graper\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

    }

    protected function getPackageProviders($app): array
    {
        return [
            GraperServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        foreach (File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }
    }

    protected function defineRoutes($router): void
    {
        $router->middleware('web')->prefix('graper')->group(function () use ($router) {
            $router->get('/api/page/{page}', [GraperPageController::class, 'show']);
            $router->put('/api/page/{page}', [GraperPageController::class, 'update']);
            $router->get('/edit/{page}', [GraperPageController::class, 'edit']);
        });
    }
}
