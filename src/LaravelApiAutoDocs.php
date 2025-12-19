<?php

namespace Piovezanfernando\LaravelApiAutoDocs;

use Illuminate\Routing\RouteAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class LaravelApiAutoDocs
{
    private RoutePath $routePath;
    private DocBlockFactoryInterface $documentator;

    public function __construct(RoutePath $routePath)
    {
        $this->routePath = $routePath;
        $this->documentator = DocBlockFactory::createInstance();
    }

    /**
     * Get a collection of {@see \Piovezanfernando\LaravelApiAutoDocs\Doc} with route and rules information.
     *
     * @return \Illuminate\Support\Collection<int, \Piovezanfernando\LaravelApiAutoDocs\Doc>
     * @throws \ReflectionException
     */
    public function getDocs(
        bool $showGet,
        bool $showPost,
        bool $showPut,
        bool $showPatch,
        bool $showDelete,
        bool $showHead
    ): Collection {
        $filteredMethods = array_filter([
            Request::METHOD_GET => $showGet,
            Request::METHOD_POST => $showPost,
            Request::METHOD_PUT => $showPut,
            Request::METHOD_PATCH => $showPatch,
            Request::METHOD_DELETE => $showDelete,
            Request::METHOD_HEAD => $showHead,
        ], static fn(bool $shouldShow) => $shouldShow);

        $methods = array_keys($filteredMethods);

        $docs = $this->getControllersInfo($methods);
        $docs = $this->appendRequestRules($docs);

        return $docs->filter();
    }

    /**
     * Finds and processes documentation for a single route by its generated ID.
     * This avoids loading all application routes for a single detail view.
     *
     * @param string $id
     * @return array|null
     * @throws \ReflectionException
     */
    public function getDocById(string $id): ?array
    {
        $routes = Route::getRoutes()->getRoutes();
        $onlyRouteStartWith = config('api-auto-docs.only_route_uri_start_with') ?? '';
        $excludePatterns = config('api-auto-docs.hide_matching') ?? [];

        // We need to know which methods are allowed in general, to calculate the ID correctly
        $allowedMethods = array_keys(array_filter([
            Request::METHOD_GET => config('api-auto-docs.show_get', true),
            Request::METHOD_POST => config('api-auto-docs.show_post', true),
            Request::METHOD_PUT => config('api-auto-docs.show_put', true),
            Request::METHOD_PATCH => config('api-auto-docs.show_patch', true),
            Request::METHOD_DELETE => config('api-auto-docs.show_delete', true),
            Request::METHOD_HEAD => config('api-auto-docs.show_head', true),
        ], static fn(bool $shouldShow) => $shouldShow));

        foreach ($routes as $route) {
            // Apply the same filtering logic as in getControllersInfo
            if ($onlyRouteStartWith && !Str::startsWith($route->uri, $onlyRouteStartWith)) {
                continue;
            }

            foreach ($excludePatterns as $regex) {
                if (preg_match($regex, $route->uri)) {
                    continue 2;
                }
            }

            $routeMethods = array_intersect($route->methods, $allowedMethods);

            if (count($routeMethods) === 0) {
                continue;
            }

            // A single route can have multiple methods. We need to check each one.
            foreach ($routeMethods as $method) {
                $currentId = md5($route->uri . ':' . $method);
                if ($currentId === $id) {
                    // Found the specific route and method.
                    $doc = $this->createDocFromRoute($route, $routeMethods);

                    // Set the specific method for this doc, similar to splitByMethods()
                    $doc->setHttpMethod($method);
                    $doc->setMethods([$method]);

                    // The appendRequestRules method expects a collection and returns one.
                    $processedDoc = $this->appendRequestRules(collect([$doc]))->first();

                    return $processedDoc?->toArray();
                }
            }
        }

        return null;
    }

    /**
     * Loop and split {@see \Piovezanfernando\LaravelApiAutoDocs\Doc} by {@see \Piovezanfernando\LaravelApiAutoDocs\Doc::$methods}.
     *
     * @param  \Illuminate\Support\Collection<int, \Piovezanfernando\LaravelApiAutoDocs\Doc>  $docs
     * @return \Illuminate\Support\Collection<int, \Piovezanfernando\LaravelApiAutoDocs\Doc>
     */
    public function splitByMethods(Collection $docs): Collection
    {
        $splitDocs = collect();

        foreach ($docs as $doc) {
            foreach ($doc->getMethods() as $method) {
                $cloned = $doc->clone();
                $cloned->setMethods([$method]);
                $cloned->setHttpMethod($method);
                $splitDocs->push($cloned);
            }
        }

        return $splitDocs;
    }

    /**
     * Sort by `$sortBy`.
     *
     * @param  \Illuminate\Support\Collection<int, \Piovezanfernando\LaravelApiAutoDocs\Doc>  $docs
     * @return \Illuminate\Support\Collection<int, \Piovezanfernando\LaravelApiAutoDocs\Doc>
     */
    public function sortDocs(Collection $docs, ?string $sortBy = 'default'): Collection
    {
        if (!in_array($sortBy, ['route_names', 'method_names'])) {
            return $docs;
        }

        if ($sortBy === 'route_names') {
            return $docs->sort()->values();
        }

        // Sort by `method_names`.
        $methods = [
            Request::METHOD_GET,
            Request::METHOD_POST,
            Request::METHOD_PUT,
            Request::METHOD_PATCH,
            Request::METHOD_DELETE,
            Request::METHOD_HEAD,
        ];

        $sorted = $docs->sortBy(static fn(Doc $doc) => array_search($doc->getHttpMethod(), $methods), SORT_NUMERIC);

        return $sorted->values();
    }

    /**
     * Group by `$groupBy`. {@see \Piovezanfernando\LaravelApiAutoDocs\Doc::$group} and {@see \Piovezanfernando\LaravelApiAutoDocs\Doc::$groupIndex} will be set.
     * The return collection is always sorted by `group`, `group_index`.
     *
     * @param  \Illuminate\Support\Collection<int, \Piovezanfernando\LaravelApiAutoDocs\Doc>  $docs
     * @return \Illuminate\Support\Collection<int, \Piovezanfernando\LaravelApiAutoDocs\Doc>
     */
    public function groupDocs(Collection $docs, ?string $groupBy = 'default'): Collection
    {
        if (!in_array($groupBy, ['api_uri', 'controller_full_path'])) {
            return $docs;
        }

        if ($groupBy === 'api_uri') {
            $this->groupDocsByAPIURI($docs);
        }

        if ($groupBy === 'controller_full_path') {
            $this->groupDocsByFQController($docs);
        }

        return $docs
            ->sortBy(static fn(Doc $doc) => $doc->getGroup() . $doc->getGroupIndex(), SORT_NATURAL)
            ->values();
    }

    /**
     * Get controllers and routes information and return a list of {@see \Piovezanfernando\LaravelApiAutoDocs\Doc}
     *
     * @param  string[]  $onlyMethods
     * @return \Illuminate\Support\Collection<int, \Piovezanfernando\LaravelApiAutoDocs\Doc>
     * @throws \ReflectionException
     */
    // TODO Should reduce complexity
    // phpcs:ignore
    public function getControllersInfo(array $onlyMethods): Collection
    {
        $docs = collect();

        $routes = Route::getRoutes()->getRoutes();

        $onlyRouteStartWith = config('api-auto-docs.only_route_uri_start_with') ?? '';
        $excludePatterns = config('api-auto-docs.hide_matching') ?? [];

        foreach ($routes as $route) {
            if ($onlyRouteStartWith && !Str::startsWith($route->uri, $onlyRouteStartWith)) {
                continue;
            }

            foreach ($excludePatterns as $regex) {
                if (preg_match($regex, $route->uri)) {
                    continue 2;
                }
            }

            $routeMethods = array_intersect($route->methods, $onlyMethods);

            if (count($routeMethods) === 0) {
                continue;
            }

            $docs->push($this->createDocFromRoute($route, $routeMethods));
        }

        return $docs;
    }

    /**
     * Parse from request object and set into {@see \Piovezanfernando\LaravelApiAutoDocs\Doc}
     * This method also read docBlock and update into {@see \Piovezanfernando\LaravelApiAutoDocs\Doc}.
     *
     * @param  \Illuminate\Support\Collection<int, \Piovezanfernando\LaravelApiAutoDocs\Doc>  $docs
     * @return \Illuminate\Support\Collection<int, \Piovezanfernando\LaravelApiAutoDocs\Doc>
     * @throws \ReflectionException
     */
    // TODO Should reduce complexity
    // phpcs:ignore
    public function appendRequestRules(Collection $docs): Collection
    {
        /** @var Doc $doc */
        foreach ($docs as $doc) {
            if ($doc->isClosure()) {
                // Skip to next if controller is not exists.
                continue;
            }

            try {
                $controllerReflectionMethod = new ReflectionMethod($doc->getControllerFullPath(), $doc->getMethod());
                $controllerMethodDocComment = $this->getDocComment($controllerReflectionMethod);
                if ($controllerMethodDocComment) {
                    $docBlock = $this->documentator->create($controllerMethodDocComment);
                    $doc->setSummary($docBlock->getSummary());
                    $doc->setDescription($docBlock->getDescription()->render());
                }

                foreach ($controllerReflectionMethod->getParameters() as $param) {
                    /** @var \ReflectionNamedType|\ReflectionUnionType|\ReflectionIntersectionType|null $namedType */
                    $namedType = $param->getType();
                    if (!$namedType) {
                        continue;
                    }

                    try {
                        if (!method_exists($namedType, 'getName')) {
                            continue;
                        }

                        $requestClassName = $namedType->getName();

                        if (!class_exists($requestClassName)) {
                            continue;
                        }

                        $reflectionClass = new ReflectionClass($requestClassName);

                        try {
                            $requestObject = $reflectionClass->newInstance();
                        } catch (Throwable) {
                            $requestObject = $reflectionClass->newInstanceWithoutConstructor();
                        }

                        foreach (config('api-auto-docs.rules_methods') as $requestMethod) {
                            if (!method_exists($requestObject, $requestMethod)) {
                                continue;
                            }

                            try {
                                $doc->mergeRules($this->flattenRules($requestObject->$requestMethod()));
                            } catch (Throwable) {
                                $doc->mergeRules($this->rulesByRegex($requestClassName, $requestMethod));
                            }

                            if (config('api-auto-docs.use_factory')) {
                                $this->appendExample($doc);
                            }

                            if (method_exists($requestObject, 'fieldDescriptions')) {
                                $doc->setFieldInfo($requestObject->fieldDescriptions());
                            }
                        }
                    } catch (Throwable) {
                        // Do nothing.
                    }
                }
            } catch (Throwable) {
                // Do nothing.
            }
        }

        return $docs;
    }

    public function appendExample(Doc $doc): void
    {
        try {
            $modelName = preg_replace(
                config('api-auto-docs.pattern_model_from_controller_name'),
                '',
                class_basename($doc->getController())
            );
            $fullFactoryName = config('api-auto-docs.factory_path') . "\\{$modelName}Factory";

            if (!class_exists($fullFactoryName)) {
                return;
            }

            /** @var \Illuminate\Database\Eloquent\Factories\Factory $factory */
            $factory = app($fullFactoryName);
            $excludeFields = config('api-auto-docs.exclude_fields') ?? [];
            $example = $factory::new()->make()->toArray();
            $example = array_filter(
                $example,
                fn($key) => !in_array($key, $excludeFields),
                ARRAY_FILTER_USE_KEY
            );
            $doc->mergeExamples($example);
        } catch (Throwable) {
            // Do nothing.
        }
    }

    /**
     * Parse rules from the request.
     *
     * @param  array<string, \Illuminate\Contracts\Validation\Rule|array<\Illuminate\Contracts\Validation\Rule|string>|string>  $mixedRules
     * @return array<string, string[]>  Key is attribute, value is a list of rules.
     */
    public function flattenRules(array $mixedRules): array
    {
        $rules = [];

        foreach ($mixedRules as $attribute => $rule) {
            if (is_object($rule)) {
                $rules[$attribute][] = get_class($rule);
                continue;
            }

            if (is_array($rule)) {
                $rulesStrs = [];

                foreach ($rule as $ruleItem) {
                    $rulesStrs[] = is_object($ruleItem) ? get_class($ruleItem) : $ruleItem;
                }

                $rules[$attribute][] = implode("|", $rulesStrs);
                continue;
            }

            $rules[$attribute][] = $rule;
        }

        // Merge rules for array fields (e.g., attachments and attachments.*)
        $mergedRules = [];
        foreach ($rules as $attribute => $ruleList) {
            $targetAttribute = $attribute;
            if (str_contains($attribute, '.*')) {
                $targetAttribute = str_replace('.*', '', $attribute);
            }

            if (!isset($mergedRules[$targetAttribute])) {
                $mergedRules[$targetAttribute] = [];
            }

            foreach ($ruleList as $ruleStr) {
                $parts = explode('|', $ruleStr);
                $mergedRules[$targetAttribute] = array_merge($mergedRules[$targetAttribute], $parts);
            }
        }

        // Remove duplicates and implode back
        foreach ($mergedRules as $attribute => $ruleParts) {
            $uniqueParts = array_unique($ruleParts);
            $mergedRules[$attribute] = [implode('|', $uniqueParts)];
        }

        return $mergedRules;
    }

    /**
     * Read the source file and parse rules by regex.
     *
     * @return array<string, string[]> Key is attribute, value is a list of rules.
     * @throws \ReflectionException
     */
    public function rulesByRegex(string $requestClassName, string $methodName): array
    {
        $data = new ReflectionMethod($requestClassName, $methodName);
        $lines = file((string) $data->getFileName());

        if ($lines === false) {
            return [];
        }

        $rules = [];

        for ($i = $data->getStartLine() - 1; $i <= $data->getEndLine() - 1; $i++) {
            // check if line is a comment
            $trimmed = trim($lines[$i]);

            if (Str::startsWith($trimmed, '//') || Str::startsWith($trimmed, '#')) {
                continue; // @codeCoverageIgnore
            }

            // check if => in string, only pick up rules that are coded on single line
            if (!Str::contains($lines[$i], '=>')) {
                continue;
            }

            preg_match_all("/['\"].*?['\"]/", $lines[$i], $matches);
            $rules[] = $matches;
        }

        return collect($rules)
            ->filter(static fn($item) => count($item[0]) > 0)
            ->map(static function (array $item) {
                $fieldName = Str::of($item[0][0])->replace(['"', "'"], '');
                $definedFieldRules = collect(array_slice($item[0], 1))->transform(static fn($rule) => Str::of($rule)->replace(['"', "'"], '')->__toString())->toArray();

                return ['key' => $fieldName, 'rules' => $definedFieldRules];
            })
            ->keyBy('key')
            ->map(static fn($item) => $item['rules'])
            ->toArray();
    }

    /**
     * Group by {@see \Piovezanfernando\LaravelApiAutoDocs\Doc::$uri} and attach {@see \Piovezanfernando\LaravelApiAutoDocs\Doc::$group} and {@see \Piovezanfernando\LaravelApiAutoDocs\Doc::$groupIndex} details.
     *
     * @param  \Illuminate\Support\Collection<int, \Piovezanfernando\LaravelApiAutoDocs\Doc>  $docs
     */
    private function groupDocsByAPIURI(Collection $docs): void
    {
        $patterns = config('api-auto-docs.group_by.uri_patterns', []);

        $regex = count($patterns) > 0 ? '(' . implode('|', $patterns) . ')' : '';

        // A collection<string, int> to remember indexes with `group` => `index` pair.
        $groupIndexes = collect();

        foreach ($docs as $doc) {
            if ($regex !== '') {
                // If $regex    = '^api/v[\d]+/',
                // and $uri     = '/api/v1/users',
                // then $prefix = '/api/v1/'.
                $prefix = Str::match($regex, $doc->getUri());
            }

            $group = $this->getGroupByURI($prefix ?? '', $doc->getUri());
            $this->rememberGroupIndex($groupIndexes, $group);
            $this->setGroupInfo($doc, $group, (int) $groupIndexes->get($group));
        }
    }

    /**
     * Create and return group name by the {@see \Piovezanfernando\LaravelApiAutoDocs\Doc::$uri}.
     */
    private function getGroupByURI(string $prefix, string $uri): string
    {
        if ($prefix === '') {
            // No prefix, create group by the first path.
            $paths = explode('/', $uri);
            return $paths[0];
        }

        // Glue the prefix + "first path after prefix" to form a group.
        $after = Str::after($uri, $prefix);
        $paths = explode('/', $after);
        return $prefix . $paths[0];
    }

    /**
     * Group by {@see \Piovezanfernando\LaravelApiAutoDocs\Doc::$controllerFullPath} and attach {@see \Piovezanfernando\LaravelApiAutoDocs\Doc::$group} and {@see \Piovezanfernando\LaravelApiAutoDocs\Doc::$groupIndex} details.
     *
     * @param  \Illuminate\Support\Collection<int, \Piovezanfernando\LaravelApiAutoDocs\Doc>  $docs
     */
    private function groupDocsByFQController(Collection $docs): void
    {
        // To remember group indexes with group => index pair.
        $groupIndexes = collect();

        foreach ($docs as $doc) {
            $group = $doc->getControllerFullPath();
            $this->rememberGroupIndex($groupIndexes, $group);
            $this->setGroupInfo($doc, $group, (int) $groupIndexes->get($group));
        }
    }

    /**
     * Set the last index number into `$groupIndexes`
     *
     * @param  \Illuminate\Support\Collection<string, int>  $groupIndexes  [`group` => `index`]
     */
    private function rememberGroupIndex(Collection $groupIndexes, string $key): void
    {
        if (!$groupIndexes->has($key)) {
            $groupIndexes->put($key, 0);
            return;
        }

        $groupIndexes->put($key, $groupIndexes->get($key) + 1);
    }

    /**
     * Attach `group` and `group_index` into `$doc`.
     */
    private function setGroupInfo(Doc $doc, string $group, int $groupIndex): void
    {
        $doc->setGroup($group);
        $doc->setGroupIndex($groupIndex);
    }

    private function getDocComment(ReflectionMethod $reflectionMethod): string
    {
        $docComment = $reflectionMethod->getDocComment();

        if ($docComment === false) {
            return '';
        }

        return $docComment;
    }

    /**
     * Helper method to create a Doc object from a Route object.
     * Extracted from getControllersInfo to reduce duplication.
     *
     * @param IlluminateRoute $route
     * @param array $routeMethods
     * @return Doc
     * @throws ReflectionException
     */
    private function createDocFromRoute(IlluminateRoute $route, array $routeMethods): Doc
    {
        $controllerName = '';
        $controllerFullPath = '';
        $method = '';
        $tag = '';

        // `$route->action['uses']` value is either 'Class@method' string or Closure.
        if (is_string($route->action['uses']) && !RouteAction::containsSerializedClosure($route->action)) {
            /** @var array{0: class-string<\Illuminate\Routing\Controller>, 1: string} $controllerCallback */
            $controllerCallback = Str::parseCallback($route->action['uses']);
            $controllerFullPath = $controllerCallback[0];
            $method = $controllerCallback[1];
            $controllerName = (new ReflectionClass($controllerFullPath))->getShortName();
        }

        $pathParameters = [];
        $pp = $this->routePath->getPathParameters($route);

        // same format as rules
        foreach ($pp as $k => $v) {
            $pathParameters[$k] = [$v];
        }

        /** @var string[] $middlewares */
        $middlewares = $route->middleware();

        $doc = new Doc(
            $route->uri,
            $routeMethods,
            config('api-auto-docs.hide_meta_data') ? [] : $middlewares,
            config('api-auto-docs.hide_meta_data') ? '' : $controllerName,
            config('api-auto-docs.hide_meta_data') ? '' : $controllerFullPath,
            config('api-auto-docs.hide_meta_data') ? '' : $method,
            '',
            $pathParameters,
            [],
            '',
            [],
            [],
            config('api-auto-docs.rules_order') ?? [],
            '',
            '',
            $tag
        );

        $modelName = preg_replace(config('api-auto-docs.pattern_model_from_controller_name'), '', $controllerName);
        $translateName = Str::snake(Str::plural(class_basename($modelName)));

        $doc->setTranslatedModelSingular(__('models/' . $translateName . '.singular'));
        $doc->setTranslatedModelPlural(__('models/' . $translateName . '.plural'));

        return $doc;
    }

    public function replaceTranslate(?array $response = null): array
    {
        if ($response === null) {
            $response = config('api-auto-docs.open_api.responses', []);
        }

        $prefix = config('api-responses.translation_prefix', 'messages.');

        foreach ($response as $key => $value) {
            if (is_string($value) && str_starts_with($value, $prefix)) {
                $response[$key] = __($value);
            } elseif (is_array($value)) {
                $response[$key] = $this->replaceTranslate($value);
            }
        }
        return $response;
    }
}
