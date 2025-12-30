<?php

namespace Pyle\Webhooks\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class MakeWebhookTransformerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:webhooks-transformer {name : The name of the transformer class}
        {--event= : The fully qualified class name of the event this transformer handles}
        {--force : Overwrite the existing transformer if it exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new webhook payload transformer class';

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $files): int
    {
        $name = $this->argument('name');
        $event = $this->option('event');

        // Parse the name to extract namespace and class
        $name = str_replace('/', '\\', $name);
        $segments = explode('\\', $name);
        $className = array_pop($segments);

        // Ensure class name ends with Transformer
        if (!str_ends_with($className, 'Transformer')) {
            $className .= 'Transformer';
        }

        // Determine namespace
        if (empty($segments)) {
            $namespace = 'App\\Webhooks\\Transformers';
            $path = app_path('Webhooks/Transformers');
        } else {
            $namespace = 'App\\' . implode('\\', $segments);
            $path = app_path(implode('/', $segments));
        }

        // Ensure directory exists
        if (!$files->isDirectory($path)) {
            $files->makeDirectory($path, 0755, true);
        }

        $filePath = $path . '/' . $className . '.php';

        // Check if file exists
        if ($files->exists($filePath) && !$this->option('force')) {
            $this->error("Transformer already exists at {$filePath}");

            return SymfonyCommand::FAILURE;
        }

        // Prepare stub replacements
        $eventType = $event ?: 'object';
        $eventTypeDoc = $event ? "use {$event};\n" : '';
        $eventTypeName = $event ? class_basename($event) : 'event';

        // Read and replace stub
        $stub = $files->get(__DIR__ . '/../../../resources/stubs/webhook-transformer.stub');
        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ class }}', $className, $stub);
        $stub = str_replace('{{ eventType }}', $eventType, $stub);
        $stub = str_replace('{{ eventTypeDoc }}', $eventTypeDoc, $stub);
        $stub = str_replace('{{ eventTypeName }}', $eventTypeName, $stub);

        // Write the file
        $files->put($filePath, $stub);

        $fullClassName = $namespace . '\\' . $className;

        $this->info("Transformer created successfully: {$fullClassName}");

        // Show config snippet
        $this->newLine();
        $this->line('Add this to your <comment>config/webhooks.php</comment> file:');
        $this->newLine();
        $this->line("'transformer' => \\{$fullClassName}::class,");

        return SymfonyCommand::SUCCESS;
    }
}

