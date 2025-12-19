<?php

namespace Piovezanfernando\LaravelApiAutoDocs\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Piovezanfernando\LaravelApiAutoDocs\LaravelApiAutoDocs;
use Piovezanfernando\LaravelApiAutoDocs\LaravelApiAutoDocsToOpenApi;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class LaravelApiAutoDocsController extends Controller
{
    private LaravelApiAutoDocs $laravelApiAutoDocs;
    private LaravelApiAutoDocsToOpenApi $laravelRequestDocsToOpenApi;

    public function __construct(LaravelApiAutoDocs $laravelRequestDoc, LaravelApiAutoDocsToOpenApi $laravelRequestDocsToOpenApi)
    {
        $this->laravelRequestDocsToOpenApi = $laravelRequestDocsToOpenApi;
        $this->laravelApiAutoDocs = $laravelRequestDoc;
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function api(Request $request): JsonResponse
    {
        $showGet    = !$request->has('showGet') || $request->input('showGet') === 'true';
        $showPost   = !$request->has('showPost') || $request->input('showPost') === 'true';
        $showPut    = !$request->has('showPut') || $request->input('showPut') === 'true';
        $showPatch  = !$request->has('showPatch') || $request->input('showPatch') === 'true';
        $showDelete = !$request->has('showDelete') || $request->input('showDelete') === 'true';
        $showHead   = !$request->has('showHead') || $request->input('showHead') === 'true';

        // Get a list of Doc with route and rules information.
        // If user defined `Route::match(['get', 'post'], 'uri', ...)`,
        // only a single Doc will be generated.
        $docs = $this->laravelApiAutoDocs->getDocs(
            $showGet,
            $showPost,
            $showPut,
            $showPatch,
            $showDelete,
            $showHead,
        );

        // Loop and split Doc by the `methods` property.
        // `Route::match([...n], 'uri', ...)` will generate n number of Doc.
        $docs = $this->laravelApiAutoDocs->splitByMethods($docs);
        $docs = $this->laravelApiAutoDocs->sortDocs($docs, $request->input('sort'));
        $docs = $this->laravelApiAutoDocs->groupDocs($docs, $request->input('groupby'));

        if ($request->input('openapi')) {
            return response()->json(
                $this->laravelRequestDocsToOpenApi->openApi($docs->all())->toArray(),
                Response::HTTP_OK,
                [
                    'Content-type' => 'application/json; charset=utf-8',
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
        }

        return response()->json(
            $docs,
            Response::HTTP_OK,
            [
                'Content-type' => 'application/json; charset=utf-8',
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public function config(): JsonResponse
    {
        $config = [
            'title' => config('api-auto-docs.title'),
            'default_headers' => config('api-auto-docs.default_headers'),
            'responses' => $this->laravelApiAutoDocs->replaceTranslate(),
        ];
        return response()->json($config);
    }

    /**
     * @throws \ReflectionException
     */
    public function getRouteDetails(string $id): JsonResponse
    {
        $routeDoc = $this->laravelApiAutoDocs->getDocById($id);

        if (! $routeDoc) {
            return response()->json(['message' => 'Route not found.'], 404);
        }

        $routeDoc['id'] = $id;

        return response()->json(
            $routeDoc,
            ResponseAlias::HTTP_OK,
            [
                'Content-type' => 'application/json; charset=utf-8',
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    /**
     * @throws \ReflectionException
     */
    public function getRoutesList(): JsonResponse
    {
        $docs = $this->laravelApiAutoDocs->getDocs(
            config('api-auto-docs.show_get', true),
            config('api-auto-docs.show_post', true),
            config('api-auto-docs.show_put', true),
            config('api-auto-docs.show_patch', true),
            config('api-auto-docs.show_delete', true),
            config('api-auto-docs.show_head', true),
        );

        // Split routes by method so each entry has a single http_method
        $splitDocs = $this->laravelApiAutoDocs->splitByMethods($docs);

        $routesList = collect($splitDocs)->map(function ($doc) {
            $docData = $doc->toArray();
            $id = md5($docData['uri'] . ':' . $docData['http_method']);

            $groupParts = explode('/', $docData['uri']);
            $group = $groupParts[0] === 'api' ? ($groupParts[1] ?? 'default') : $groupParts[0];
            $group = ucwords(str_replace(['-', '_'], ' ', $group));

            return [
                'id' => $id,
                'uri' => $docData['uri'],
                'methods' => [$docData['http_method']], // Now it's a single-element array
                'group' => $group,
            ];
        })
            ->groupBy('group')
            ->map(function ($routes, $groupName) {
                return [
                    'group' => $groupName,
                    'routes' => $routes->sortBy('uri')->values()->all(),
                ];
            })
            ->sortBy('group')
            ->values();

        return response()->json($routesList);
    }

    /**
     * @codeCoverageIgnore
     */
    public function index(): Response
    {
        return response()->view('api-auto-docs::index');
    }
}
