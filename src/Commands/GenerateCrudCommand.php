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
        $files = [
            'Controller' => app_path("Http/Controllers/{$smallFirstLetterNamespace}/{$this->singular}Controller.php"),
            'Repository' => app_path("Repositories/{$this->singular}Repository.php"),
            'RepositoryInterface' => app_path("Repositories/Interfaces/{$this->singular}RepositoryInterface.php"),
            'Model' => app_path("Models/{$this->singular}.php"),
            'Migration' => database_path("migrations/" . date('Y_m_d_His') . "_create_{$this->plural}_table.php"),
            'StoreRequest' => app_path("Http/Requests/Store{$this->singular}Request.php"),
            'UpdateRequest' => app_path("Http/Requests/Update{$this->singular}Request.php"),
            'IndexView' => resource_path("views/{$smallFirstLetterNamespace}/{$this->plural}/index.blade.php"),
            'CreateView' => resource_path("views/{$smallFirstLetterNamespace}/{$this->plural}/create.blade.php"),
            'EditView' => resource_path("views/{$smallFirstLetterNamespace}/{$this->plural}/edit.blade.php"),
            'ShowView' => resource_path("views/{$smallFirstLetterNamespace}/{$this->plural}/show.blade.php"),
            'ArchiveView' => resource_path("views/{$smallFirstLetterNamespace}/{$this->plural}/archive.blade.php"),
        ];

        foreach ($files as $stubType => $filePath) {
            $stubContent = $this->getStubContent($stubType);
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
        $routeFile = base_path('routes/admin.php');

        if (!$this->filesystem->exists($routeFile)) {
            $this->filesystem->put($routeFile, "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n");
        }

        $routes = <<<EOT

        // {$this->singular} Routes
        Route::resource('{$this->snakePlural}', App\Http\Controllers\Admin\\{$this->singular}Controller::class)->except('show');
        Route::get('{$this->snakePlural}-archive', [App\Http\Controllers\Admin\\{$this->singular}Controller::class, 'archive'])->name('{$this->snakePlural}.archive');
        Route::post('{$this->snakePlural}-restore/{uuid}', [App\Http\Controllers\Admin\\{$this->singular}Controller::class, 'restore'])->name('{$this->snakePlural}.restore');
        Route::delete('{$this->snakePlural}-force-delete/{uuid}', [App\Http\Controllers\Admin\\{$this->singular}Controller::class, 'forceDelete'])->name('{$this->snakePlural}.forceDelete');
        Route::patch('{$this->snakePlural}-change-status/{id}', [App\Http\Controllers\Admin\\{$this->singular}Controller::class, 'changeActive'])->name('{$this->snakePlural}.change.active');
        EOT;

        $this->filesystem->append($routeFile, $routes);
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
