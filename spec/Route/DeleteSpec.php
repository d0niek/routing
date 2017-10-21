<?php
declare(strict_types=1);

namespace spec\eXtalion\Component\Routing\Route;

use eXtalion\Component\Routing\Exception\ControllerClassNotFound;
use eXtalion\Component\Routing\Exception\RouteNotValid;
use eXtalion\Component\Routing\Route;
use PhpSpec\ObjectBehavior;

class DeleteSpec extends ObjectBehavior
{
    private $controllerClass;

    function it_is_initializable()
    {
        $this->shouldHaveType(Route::class);
    }

    function it_is_traversable()
    {
        $this->shouldHaveType(\Traversable::class);
    }

    function let()
    {
        $route = '/some/{uri}/{path}';
        $this->controllerClass = new class {};
        $controllerClassDependencies = [];
        $uriParametersOptions = [
            'uri' => function (string $uri): string {
                return $uri;
            },
        ];

        $this->beConstructedWith(
            $route,
            get_class($this->controllerClass),
            $controllerClassDependencies,
            $uriParametersOptions
        );
    }

    function it_should_throw_exception_when_route_is_empty()
    {
        $this->beConstructedWith(
            '',
            get_class(new class {})
        );

        $this->shouldThrow(RouteNotValid::class)
            ->duringInstantiation();
    }

    function it_should_throw_exception_when_route_not_start_with_slash()
    {
        $this->beConstructedWith(
            'some/{uri}/{path}',
            get_class(new class {})
        );

        $this->shouldThrow(RouteNotValid::class)
            ->duringInstantiation();
    }

    function it_should_throw_exception_when_route_contain_host()
    {
        $this->beConstructedWith(
            'example.com/some/{uri}/{path}',
            get_class(new class {})
        );

        $this->shouldThrow(RouteNotValid::class)
            ->duringInstantiation();
    }

    function it_should_throw_exception_when_route_contain_schema()
    {
        $this->beConstructedWith(
            'http://example.com/some/{uri}/{path}',
            get_class(new class {})
        );

        $this->shouldThrow(RouteNotValid::class)
            ->duringInstantiation();
    }

    function it_should_throw_exception_when_controller_class_not_exists()
    {
        $this->beConstructedWith(
            '/some/{uri}/{path}',
            'controller\\class\\not\\exists'
        );

        $this->shouldThrow(ControllerClassNotFound::class)
            ->duringInstantiation();
    }

    function it_should_cast_to_string()
    {
        $this->__toString()
            ->shouldReturn('/some/{uri}/{path}');
    }

    function it_should_cut_get_query()
    {
        $this->beConstructedWith(
            '/some/uri?query&foo=bar',
            get_class(new class {})
        );

        $this->__toString()
            ->shouldReturn('/some/uri');
    }

    function it_should_iterate_throu_route_parts()
    {
        $routeIt = $this->getIterator();
        $routeIt->current()
            ->shouldReturn('some');
        $routeIt->next();
        $routeIt->current()
            ->shouldReturn('{uri}');
        $routeIt->next();
        $routeIt->current()
            ->shouldReturn('{path}');
        $routeIt->next();
        $routeIt->valid()
            ->shouldReturn(false);
    }

    function it_should_return_route_method()
    {
        $this->method()
            ->shouldReturn('delete');
    }

    function it_should_return_controller_class_object()
    {
        $this->controller()
            ->shouldHaveType(get_class($this->controllerClass));
    }

    function it_should_return_controller_class_object_with_his_dependencies()
    {
        $class = new class('j', 'w') {
            public function __construct(string $firstName, string $lastName)
            {
                $this->firstName = $firstName;
                $this->lastName = $lastName;
            }
            public function fullName(): string
            {
                return $this->firstName . ' ' . $this->lastName;
            }
        };

        $this->beConstructedWith(
            '/some/{uri}/{path}',
            get_class($class),
            ['John', 'Week']
        );

        $controller = $this->controller();
        $controller->fullName()
            ->shouldReturn('John Week');
    }

    function it_should_extract_parameters_value_from_path()
    {
        $this->extractParameters('/some/specific/url')
            ->shouldReturn([
                'uri' => 'specific',
                'path' => 'url',
            ]);
    }

    function it_should_return_empty_parameter_array()
    {
        $this->beConstructedWith(
            '/some/specific/url',
            get_class(new class {})
        );

        $this->extractParameters('/some/specific/url')
            ->shouldReturn([]);
    }

    function it_should_call_parameters_options_on_extracted_parameters()
    {
        $this->beConstructedWith(
            '/user/{id}/foo/{action}/{language}',
            get_class(new class {}),
            [],
            [
                'id' => function (string $id): int {
                    return (int) $id;
                },
                'action' => function (string $action): string {
                    return $action . '.php';
                },
            ]
        );

        $this->extractParameters('/user/4/foo/post/pl')
            ->shouldReturn([
                'id' => 4,
                'action' => 'post.php',
                'language' => 'pl',
            ]);
    }

    function it_should_throw_exception_from_parameter_option()
    {
        $this->beConstructedWith(
            '/user/{id}',
            get_class(new class {}),
            [],
            [
                'id' => function (string $id): void {
                    throw new \Exception('Exception message', (int) $id);
                },
            ]
        );

        $this->shouldThrow(new \Exception('Exception message', 3))
            ->during('extractParameters', ['/user/3']);
    }
}
