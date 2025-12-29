<?php

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Pyle\Webhooks\Tests\TestCase;

beforeEach(function () {
    $this->files = new Filesystem;
    $this->transformerPath = app_path('Webhooks/Transformers');
});

afterEach(function () {
    // Clean up generated files
    $files = new Filesystem;
    $transformerPath = app_path('Webhooks/Transformers');
    if ($files->isDirectory($transformerPath)) {
        $files->deleteDirectory($transformerPath);
    }
});

it('generates a transformer class', function () {
    $files = new Filesystem;
    $transformerPath = app_path('Webhooks/Transformers');

    Artisan::call('make:webhooks-transformer', [
        'name' => 'OrderCreatedTransformer',
        '--no-interaction' => true,
    ]);

    $filePath = $transformerPath . '/OrderCreatedTransformer.php';

    expect($files->exists($filePath))->toBeTrue();

    $content = $files->get($filePath);

    expect($content)
        ->toContain('namespace App\\Webhooks\\Transformers')
        ->toContain('class OrderCreatedTransformer')
        ->toContain('implements WebhookPayloadTransformer')
        ->toContain('public function transform(object $event): array');
});

it('generates a transformer with event type when event option is provided', function () {
    $files = new Filesystem;
    $transformerPath = app_path('Webhooks/Transformers');

    Artisan::call('make:webhooks-transformer', [
        'name' => 'OrderCreatedTransformer',
        '--event' => 'App\\Events\\OrderCreated',
        '--no-interaction' => true,
    ]);

    $filePath = $transformerPath . '/OrderCreatedTransformer.php';
    $content = $files->get($filePath);

    expect($content)
        ->toContain('use App\\Events\\OrderCreated')
        ->toContain('@param App\\Events\\OrderCreated $event')
        ->toContain("'type' => 'OrderCreated'");
});

it('refuses to overwrite existing transformer without force flag', function () {
    $files = new Filesystem;
    $transformerPath = app_path('Webhooks/Transformers');

    // Create the file first
    if (!$files->isDirectory($transformerPath)) {
        $files->makeDirectory($transformerPath, 0755, true);
    }

    $filePath = $transformerPath . '/OrderCreatedTransformer.php';
    $files->put($filePath, 'existing content');

    $result = Artisan::call('make:webhooks-transformer', [
        'name' => 'OrderCreatedTransformer',
        '--no-interaction' => true,
    ]);

    expect($result)->toBe(1); // Command::FAILURE
    expect($files->get($filePath))->toBe('existing content');
});

it('overwrites existing transformer with force flag', function () {
    $files = new Filesystem;
    $transformerPath = app_path('Webhooks/Transformers');

    // Create the file first
    if (!$files->isDirectory($transformerPath)) {
        $files->makeDirectory($transformerPath, 0755, true);
    }

    $filePath = $transformerPath . '/OrderCreatedTransformer.php';
    $files->put($filePath, 'existing content');

    Artisan::call('make:webhooks-transformer', [
        'name' => 'OrderCreatedTransformer',
        '--force' => true,
        '--no-interaction' => true,
    ]);

    $content = $files->get($filePath);

    expect($content)
        ->not->toBe('existing content')
        ->toContain('class OrderCreatedTransformer');
});

it('appends Transformer suffix if not provided', function () {
    $files = new Filesystem;
    $transformerPath = app_path('Webhooks/Transformers');

    Artisan::call('make:webhooks-transformer', [
        'name' => 'OrderCreated',
        '--no-interaction' => true,
    ]);

    $filePath = $transformerPath . '/OrderCreatedTransformer.php';

    expect($files->exists($filePath))->toBeTrue();

    $content = $files->get($filePath);

    expect($content)->toContain('class OrderCreatedTransformer');
});

it('handles nested namespace paths', function () {
    $files = new Filesystem;

    Artisan::call('make:webhooks-transformer', [
        'name' => 'Webhooks/Transformers/OrderCreatedTransformer',
        '--no-interaction' => true,
    ]);

    $filePath = app_path('Webhooks/Transformers/OrderCreatedTransformer.php');

    expect($files->exists($filePath))->toBeTrue();

    $content = $files->get($filePath);

    expect($content)->toContain('namespace App\\Webhooks\\Transformers');
});

