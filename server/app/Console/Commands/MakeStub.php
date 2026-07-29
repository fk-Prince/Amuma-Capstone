<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeStub extends Command
{
    protected $signature = 'make:stub {name}';

    protected $description = 'Create a new stub file';

    public function handle(): int
    {
        $name = str_replace('\\', '/', trim($this->argument('name'), '/'));

        $parts = collect(explode('/', $name))
            ->filter()
            ->values();

        if ($parts->isEmpty()) {
            $this->error('Invalid class name.');

            return self::FAILURE;
        }

        $className = Str::studly($parts->last());

        $directories = $parts
            ->slice(0, -1)
            ->map(fn($part) => Str::studly($part));

        $namespace = 'App';

        if ($directories->isNotEmpty()) {
            $namespace .= '\\' . $directories->implode('\\');
        }

        $relativePath = $directories
            ->push($className)
            ->implode('/');


        $path = app_path($relativePath . '.php');

        $filesystem = new Filesystem();

        $filesystem->ensureDirectoryExists(dirname($path));

        if ($filesystem->exists($path)) {
            $this->error('Stub already exists.');
            return self::FAILURE;
        }

        $content = <<<PHP
        <?php

        namespace {$namespace};

        class {$className}
        {
            public function __construct()
            {
            }
        }

        PHP;

        $filesystem->put($path, $content);

        $this->info("Stub created: {$path}");

        return self::SUCCESS;
    }
}
