<?php

namespace MarwanOsama\BaseCrudGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;


class GenerateCrudCommand extends Command
{
    protected $signature = 'make:crud {name} {namespace}';
    protected $description = 'Generate complete CRUD operations';

    protected $filesystem;

    protected $namespace;
    protected $modelName;
    protected $singular;
    protected $plural;
    protected $snakePlural;

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
    }

    public function handle()
    {
        $this->modelName = $this->argument('name');
        $this->namespace = $this->argument('namespace');
        $this->singular = Str::singular($this->modelName);
        $this->plural = Str::plural($this->modelName);
        $this->snakePlural = Str::snake($this->plural, '-');

        $this->createDirectories($this->namespace);
        $this->generateFiles($this->namespace);
        $this->addRoutes();
        $this->updateComposerAutoload();

        $this->info("CRUD for {$this->singular} generated successfully!");
        $this->line("Run migrations: php artisan migrate");
    }

    protected function createDirectories($namespace)
    {
        $capitalFirstLetterNamespace = ucfirst(strtolower($namespace));
        $smallFirstLetterNamespace = lcfirst(strtolower($namespace));
        $directories = [
            app_path("Http/Controllers/{$smallFirstLetterNamespace}"),
            app_path("Repositories"),
            app_path("Http/Requests"),
            app_path("Models"),
            resource_path("views/{$smallFirstLetterNamespace}/{$this->plural}"),
        ];

        foreach ($directories as $dir) {
            if (!$this->filesystem->isDirectory($dir)) {
                $this->filesystem->makeDirectory($dir, 0755, true);
            }
        }
    }

    protected function generateFiles($namespace)
    {
        $capitalFirstLetterNamespace = ucfirst(strtolower($namespace));
        $smallFirstLetterNamespace = lcfirst(strtolower($namespace));

        // Define all directories that need to exist
        $directories = [
            app_path("Http/Controllers/{$smallFirstLetterNamespace}"),
            app_path("Repositories/Interfaces"),
            app_path("Models"),
            app_path("Http/Requests"),
            database_path("migrations"),
            resource_path("views/{$smallFirstLetterNamespace}/{$this->snakePlural}"),
        ];

        // Create directories if they don't exist
        foreach ($directories as $directory) {
            if (!$this->filesystem->isDirectory($directory)) {
                $this->filesystem->makeDirectory($directory, 0755, true);
            }
        }

        $files = [
            'Controller' => app_path("Http/Controllers/{$smallFirstLetterNamespace}/{$this->singular}Controller.php"),
            'Repository' => app_path("Repositories/{$this->singular}Repository.php"),
            'RepositoryInterface' => app_path("Repositories/Interfaces/{$this->singular}RepositoryInterface.php"),
            'Model' => app_path("Models/{$this->singular}.php"),
            'Migration' => database_path("migrations/" . date('Y_m_d_His') . "_create_{$this->snakePlural}_table.php"),
            'StoreRequest' => app_path("Http/Requests/{$this->singular}/Store{$this->singular}Request.php"),
            'UpdateRequest' => app_path("Http/Requests/{$this->singular}/Update{$this->singular}Request.php"),
            'IndexView' => resource_path("views/{$smallFirstLetterNamespace}/{$this->snakePlural}/index.blade.php"),
            'CreateView' => resource_path("views/{$smallFirstLetterNamespace}/{$this->snakePlural}/create.blade.php"),
            'EditView' => resource_path("views/{$smallFirstLetterNamespace}/{$this->snakePlural}/edit.blade.php"),
            'ShowView' => resource_path("views/{$smallFirstLetterNamespace}/{$this->snakePlural}/show.blade.php"),
            'ArchiveView' => resource_path("views/{$smallFirstLetterNamespace}/{$this->snakePlural}/archive.blade.php"),
        ];

        foreach ($files as $stubType => $filePath) {
            $stubContent = $this->getStubContent($stubType);
            $this->filesystem->ensureDirectoryExists(dirname($filePath), 0755, true);
            $this->filesystem->put($filePath, $stubContent);
        }
    }

    protected function getStubContent($stubType)
    {
        $stubPath = __DIR__ . "/../Stubs/" . Str::snake($stubType) . ".stub";
        $content = $this->filesystem->get($stubPath);

        return str_replace(
            [
                '{{singular}}',
                '{{plural}}',
                '{{snakePlural}}',
                '{{model}}',
                '{{variable}}',
                '{{namespace}}',
                '{{base_namespace_capital}}',
                '{{base_namespace_small}}'
            ],
            [
                $this->singular,
                $this->plural,
                $this->snakePlural,
                $this->modelName,
                lcfirst($this->singular),
                $this->laravel->getNamespace(),
                ucfirst(strtolower($this->namespace)),
                lcfirst(strtolower($this->namespace)),
            ],
            $content
        );
    }

    protected function addRoutes()
    {
        $namespace = $this->namespace;
        $controllerNamespace = "App\\Http\\Controllers\\{$namespace}\\{$this->singular}Controller";
        $repositoryInterface = "App\\Repositories\\Interfaces\\{$this->singular}RepositoryInterface";
        $repository = "App\\Repositories\\{$this->singular}Repository";

        $routes = <<<EOT
        \n
        // {$this->singular} Routes
        use {$controllerNamespace};
        use {$repositoryInterface};
        use {$repository};

        // Binding (uncomment to use):
        // app()->bind({$repositoryInterface}::class, {$repository}::class);

        Route::resource('{$this->snakePlural}', {$this->singular}Controller::class)->except('show');
        Route::get('{$this->snakePlural}-archive', [{$this->singular}Controller::class, 'archive'])->name('{$this->snakePlural}.archive');
        Route::post('{$this->snakePlural}-restore/{uuid}', [{$this->singular}Controller::class, 'restore'])->name('{$this->snakePlural}.restore');
        Route::delete('{$this->snakePlural}-force-delete/{uuid}', [{$this->singular}Controller::class, 'forceDelete'])->name('{$this->snakePlural}.forceDelete');
        Route::patch('{$this->snakePlural}-change-status/{id}', [{$this->singular}Controller::class, 'changeActive'])->name('{$this->snakePlural}.change.active');\n
        EOT;

        $this->info("\nGenerated Routes:");
        $this->line($routes);
        $this->comment("\nCopy these routes to your routes file.");
    }

    protected function updateComposerAutoload()
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        // Add namespaces to composer autoload
        $composer['autoload']['psr-4']["App\\Repositories\\"] = "app/Repositories/";
        $composer['autoload']['psr-4']["App\\Http\\Requests\\"] = "app/Http/Requests/";

        file_put_contents(
            base_path('composer.json'),
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        shell_exec('composer dump-autoload -q');
    }
}
