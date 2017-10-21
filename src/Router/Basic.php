<?php
declare(strict_types=1);

namespace eXtalion\Component\Routing\Router;

use eXtalion\Component\Routing\Exception\RouteNotFound;
use eXtalion\Component\Routing\Exception\RouteConflict;
use eXtalion\Component\Routing\Exception\UnknownRequestMethod;
use eXtalion\Component\Routing\Route;
use eXtalion\Component\Routing\Router;
use Psr\Http\Message\RequestInterface;

/**
 * @author Damian Glinkowski <damianglinkowski@gmail.com>
 */
class Basic implements Router
{
    /**
     * Route tree
     *
     * @var array
     */
    private $tree;

    /**
     * @param \eXtalion\Component\Routing\Route[] $routes
     */
    public function __construct(Route ...$routes)
    {
        $this->tree = [];
        foreach ($routes as $route) {
            $method = $route->method();
            $routeIterator = $route->getIterator();
            $this->tree[$method] ?? $this->tree[$method] = [
                'route' => null,
                'constants' => [],
                'parameters' => null,
            ];
            $this->addRouteToMethodTree(
                $route,
                $routeIterator,
                $this->tree[$method]
            );
        }
    }

    private function addRouteToMethodTree(
        Route $route,
        \Iterator $routeIterator,
        array &$tree
    ): void {
        if (!$routeIterator->valid()) {
            if ($tree['route'] === null) {
                $tree['route'] = $route;
                return;
            }
            throw new RouteConflict(
                'There is a route conflict: ' .
                $route . ' and ' . $tree['route']
            );
        }

        $routePart = $routeIterator->current();
        if ($this->isRouteParameter($routePart)) {
            $tree['parameters'] ?? $tree['parameters'] = [
                'route' => null,
                'constants' => [],
                'parameters' => null,
            ];
            $tree = &$tree['parameters'];
        } else {
            $tree['constants'][$routePart] ??
                $tree['constants'][$routePart] = [
                    'route' => null,
                    'constants' => [],
                    'parameters' => null,
                ];
            $tree = &$tree['constants'][$routePart];
        }
        $routeIterator->next();
        $this->addRouteToMethodTree(
            $route,
            $routeIterator,
            $tree
        );
    }

    public function isRouteParameter(string $routePart): bool
    {
        return $routePart !== '' ?
            ($routePart[0] === '{' && $routePart[-1] === '}') :
            false;
    }

    public function __destruct()
    {
        $this->tree = null;
    }

    public function handle(RequestInterface $request): Route
    {
        $method = strtolower($request->getMethod());
        if (isset($this->tree[$method])) {
            $path = $request->getUri()->getPath();
            $pathIterator = new \ArrayIterator(explode('/', $path));
            $pathIterator->next();
            return $this->findRouteByPathIterator(
                $pathIterator,
                $this->tree[$method]
            ) ?? $this->routeNotFound($method, $path);
        }
        throw new UnknownRequestMethod(
            'Unknown request method: ' . strtoupper($method)
        );
    }

    private function findRouteByPathIterator(
        \Iterator $pathIterator,
        array $tree
    ): ?Route {
        if (!$pathIterator->valid()) {
            return $tree['route'];
        }

        $path = $pathIterator->current();
        $tree = $tree['constants'][$path] ??
            $tree['parameters'] ??
            null;

        $pathIterator->next();

        return $tree !== null ?
            $this->findRouteByPathIterator($pathIterator, $tree) :
            null;
    }

    private function routeNotFound(string $method, string $path): void
    {
        throw new RouteNotFound(
            'Could not handle ' . strtoupper($method) . ' ' . $path
        );
    }
}
