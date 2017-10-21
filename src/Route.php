<?php
declare(strict_types=1);

namespace eXtalion\Component\Routing;

use eXtalion\Component\Routing\Exception\ControllerClassNotFound;
use eXtalion\Component\Routing\Exception\RouteNotValid;

/**
 * @author Damian Glinkowski <damianglinkowski@gmail.com>
 */
abstract class Route implements \IteratorAggregate
{
    /**
     * Uri path with parameters (if needed)
     *
     * Example:
     *     /
     *     /some/uri/path
     *     /some/{uri}/pl - uri is a parameter
     *
     * @var string
     */
    private $route;

    /**
     * Full namespace controller class
     *
     * @var string
     */
    private $controllerClass;

    /**
     * Controller class dependencies
     *
     * @var mixed[]
     */
    private $controllerClassDependencies;

    /**
     * @var \Closure[]
     */
    private $routeParametersOptions;

    /**
     * Separate route parts
     *
     * @var string[]
     */
    private $routeArray;

    /**
     * @var string[]
     */
    private $routeParameters;

    /**
     * Route method
     *
     * @var string
     */
    private $method;

    /**
     * @param string $route Uri path with parameters (if needed)
     * @param string $controllerClass
     * @param mixed[] $controllerClassDependencies
     * @param \Closure[] $routeParametersOptions
     */
    public function __construct(
        string $route,
        string $controllerClass,
        array $controllerClassDependencies = [],
        array $routeParametersOptions = []
    ) {
        $this->validRouet($route);
        $this->validControllerClass($controllerClass);

        $this->route = explode('?', $route)[0];
        $this->controllerClass = $controllerClass;
        $this->controllerClassDependencies = $controllerClassDependencies;
        $this->routeParametersOptions = $routeParametersOptions;

        $this->routeArray = explode('/', $this->route);
        array_shift($this->routeArray);
        $this->buildRouteParametersArray();

        $class = get_class($this);
        $this->method = strtolower(substr($class, strrpos($class, '\\') + 1));
    }

    private function validRouet(string $route): void
    {
        if ($route !== '' && $route[0] === '/') {
            $isRouteValid = filter_var(
                'http://example.com' . $route,
                FILTER_VALIDATE_URL,
                FILTER_FLAG_PATH_REQUIRED
            );
            if ($isRouteValid) {
                return;
            }
        }
        throw new RouteNotValid(
            'Route "' . $route . '" is not valid'
        );
    }

    private function validControllerClass(string $controllerClass): void
    {
        if (class_exists($controllerClass) === false) {
            throw new ControllerClassNotFound(
                'Controller class "' . $controllerClass . '" not found'
            );
        }
    }

    private function buildRouteParametersArray(): void
    {
        $this->routeParameters = [];
        foreach ($this->routeArray as $index => $part) {
            if ($part && $part[0] === '{' && $part[-1] === '}') {
                $this->routeParameters[$index] = trim($part, '{}');
            }
        }
    }

    public function __destruct()
    {
        $this->controllerClassDependencies = null;
        $this->routeParametersOptions = null;
    }

    /**
     * Gets route as string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->route;
    }

    /**
     * @inheritDoc
     *
     * @return \Iterator of string
     */
    public function getIterator(): \Iterator
    {
        yield from $this->routeArray;
    }

    /**
     * Gets route method
     *
     * @return string
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Gets controller class object
     *
     * @return object New instance of $this->controllerClass
     */
    public function controller()
    {
        return new $this->controllerClass(
            ...$this->controllerClassDependencies
        );
    }

    /**
     * Extract parameters value from path
     *
     * @param string $path Uri path that match to route without query
     *
     * @return mixed[] Map parameter_name => value
     * @throws \Throwable If any route parameter option throw exception
     */
    public function extractParameters(string $path): array
    {
        if ($this->routeParameters === []) {
            return [];
        }
        $parameters = [];
        $pathArray = explode('/', $path);
        array_shift($pathArray);
        foreach ($this->routeParameters as $index => $parameter) {
            $parameterAction = $this->routeParametersOptions[$parameter] ??
                function (string $parameter): string {
                    return $parameter;
                };
            $parameters[$parameter] = $parameterAction($pathArray[$index]);
        }
        return $parameters;
    }
}
