<?php

namespace Piovezanfernando\LaravelApiAutoDocs\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Throwable;
use Piovezanfernando\LaravelApiAutoDocs\Traits\Relations;
use ReflectionClass;
use ReflectionMethod;
use DB;

class ResponseGenerator
{
    use Relations;
    public function generate(Route $route): ?array
    {
        // Step 1: Static Override Check
        if ($staticResponse = $this->getStaticResponse($route)) {
            return $staticResponse;
        }

        // For now, we only focus on the 'resource' strategy.

        try {
            // Step 2: Get Resource Class
            $resourceClass = $this->getResourceClass($route);
            if (! $resourceClass) {
                return null;
            }

            // Step 3: Get Model Class
            $modelClass = $this->getModelFromResourceClass($resourceClass);
            if (! $modelClass || ! class_exists($modelClass) || ! method_exists($modelClass, 'factory')) {
                return null;
            }
            // Step 4: Generate Fake Model with Relationships
            $fakeModel = $this->generateFakeModel($modelClass);

            // Step 5: Instantiate Resource and Generate Response
            /** @var JsonResource $resourceInstance */
            $resourceInstance = new $resourceClass($fakeModel);

            return $resourceInstance->toArray(request());

        } catch (\Exception $e) {
            // In case of any reflection or instantiation error, return null.
            // We can log the error for debugging if needed.
            return null;
        }
    }

    private function getStaticResponse(Route $route): ?array
    {
        $appFile = resource_path('api-docs/custom-responses.php');
        $packageFile = __DIR__ . '/../../resources/stubs/custom-responses.stub.php';

        $responsesFile = file_exists($appFile)
            ? $appFile
            : $packageFile;

        $customResponses = require $responsesFile;
        $controllerAction = class_basename($route->getControllerClass()) . '@' . $route->getActionMethod();

        return $customResponses[$controllerAction] ?? null;
    }

    private function getResourceClass(Route $route): ?string
    {
        $controllerClass = $route->getControllerClass();
        if (! $controllerClass || ! class_exists($controllerClass)) {
            return null;
        }

        $reflectionClass = new ReflectionClass($controllerClass);

        if (! $reflectionClass->hasProperty('resourceClass')) {
            return null;
        }

        $controllerInstance = app($controllerClass);

        $property = $reflectionClass->getProperty('resourceClass');
        $resourceClass = $property->getValue($controllerInstance);

        if (! $resourceClass || ! class_exists($resourceClass)) {
            return null;
        }

        return $resourceClass;
    }

    private function getModelFromResourceClass(string $resourceClass): ?string
    {
        $resourceName = class_basename($resourceClass);
        $resourceNamespace = (new ReflectionClass($resourceClass))->getNamespaceName();

        $suffixes = ['Resource', 'ApiResource', 'JsonResource'];
        $modelName = $resourceName;

        foreach ($suffixes as $suffix) {
            if (Str::endsWith($resourceName, $suffix)) {
                $modelName = Str::replaceLast($suffix, '', $resourceName);
                break;
            }
        }

        // Possible model locations
        $possibleModelClasses = [
            $resourceNamespace . '\\' . $modelName, // Same namespace
            Str::replaceLast('Http\\Resources', 'Models', $resourceNamespace) . '\\' . $modelName, // In a parallel Models namespace
            'App\\Models\\' . $modelName, // Default Laravel location
        ];

        foreach ($possibleModelClasses as $modelClass) {
            if (class_exists($modelClass)) {
                return $modelClass;
            }
        }

        return null;
    }

    private function generateFakeModel(string $modelClass): Model
    {
        return $this->withInMemoryDatabase(function () use ($modelClass) {

            /** @var \Illuminate\Database\Eloquent\Factories\Factory $factory */
            $factory = $modelClass::factory();

            $relations = $this->getRelationShip($modelClass);
            $relationToSkip = ['audits'];

            // 1️⃣ cria o modelo base
            $model = $factory->create();

            // 2️⃣ cria relações manualmente (com ID disponível)
            foreach ($relations as $relationMeta) {

                $relationName = $relationMeta['name'];
                $relationType = $relationMeta['type'];     // Ex: HasMany
                $relatedClass = $relationMeta['related'];

                if (in_array($relationName, $relationToSkip)) {
                    continue;
                }

                if (! method_exists($model, $relationName)) {
                    continue;
                }

                if (! method_exists($relatedClass, 'factory')) {
                    continue;
                }

                try {
                    $relation = $model->{$relationName}();

                    match ($relationType) {

                        'HasOne', 'MorphOne' => $relation->save(
                            $relatedClass::factory()->make()
                        ),

                        'BelongsTo' => tap(
                            $relatedClass::factory()->create(),
                            fn ($related) => $model->{$relationName}()->associate($related)->save()
                        ),

                        'HasMany', 'MorphMany' => $relatedClass::factory()
                            ->count(3)
                            ->create([
                                $relation->getForeignKeyName() => $model->getKey(),
                            ]),

                        'BelongsToMany', 'MorphToMany' => $model->{$relationName}()
                            ->attach(
                                $relatedClass::factory()->count(3)->create()
                            ),

                        default => null,
                    };

                } catch (\Throwable $e) {
                    // relação não suportada → ignora
                    continue;
                }
            }

            // 3️⃣ carrega todas as relações válidas
            $model->load(
                $relations->pluck('name')->toArray()
            );

            return $model;
        });
    }

    private function withInMemoryDatabase(callable $callback)
    {
        $originalConnection = config('database.default');

        // troca conexão
        config(['database.default' => 'sqlite_testing']);
        config([
            'telescope.storage.database.connection' => 'sqlite_testing',
        ]);

        // reconecta
        DB::purge('pgsql');
        DB::purge('sqlite_testing');

        // Reconecta usando o novo default
        DB::reconnect('sqlite_testing');

        // Força o Eloquent a usar a nova conexão
        DB::setDefaultConnection('sqlite_testing');

        app()->forgetInstance(Migrator::class);

        // roda migrations
        \Artisan::call('migrate', [
            '--database' => 'sqlite_testing',
            '--force' => true,
        ]);

        try {
            return $callback();
        } finally {
            // restaura conexão original
            config(['database.default' => $originalConnection]);
            DB::purge();
            DB::reconnect();
        }
    }
}
