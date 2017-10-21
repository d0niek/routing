<?php
declare(strict_types=1);

namespace spec\eXtalion\Component\Routing\Router;

use eXtalion\Component\Routing\Route;
use eXtalion\Component\Routing\Router;
use eXtalion\Component\Routing\Exception\RouteNotFound;
use eXtalion\Component\Routing\Exception\RouteConflict;
use eXtalion\Component\Routing\Exception\UnknownRequestMethod;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;

class BasicSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Router::class);
    }

    function let(
        Route $routeRoot,
        Route $routePost,
        Route $routePostPut,
        Route $routePostId,
        Route $routePostAll
    ) {
        $routeRoot->method()
            ->willReturn('get')
            ->shouldBeCalled();
        $routeRoot->getIterator()
            ->willReturn(new \ArrayIterator(['']))
            ->shouldBeCalled();

        $routePost->method()
            ->willReturn('get')
            ->shouldBeCalled();
        $routePost->getIterator()
            ->willReturn(new \ArrayIterator(['post']))
            ->shouldBeCalled();

        $routePostPut->method()
            ->willReturn('put')
            ->shouldBeCalled();
        $routePostPut->getIterator()
            ->willReturn(new \ArrayIterator(['post']))
            ->shouldBeCalled();

        $routePostId->method()
            ->willReturn('get')
            ->shouldBeCalled();
        $routePostId->getIterator()
            ->willReturn(new \ArrayIterator(['post', '{id}']))
            ->shouldBeCalled();

        $routePostAll->method()
            ->willReturn('get')
            ->shouldBeCalled();
        $routePostAll->getIterator()
            ->willReturn(new \ArrayIterator(['post', 'all']))
            ->shouldBeCalled();

        $this->beConstructedWith(
            $routeRoot,
            $routePost,
            $routePostPut,
            $routePostId,
            $routePostAll
        );
    }

    function it_should_throw_exception_when_two_routes_resolve_the_same_path(
        Route $routeRoot,
        Route $routePost,
        Route $routePostPut,
        Route $routePostId,
        Route $routePostAll,
        Route $routePostLogin
    ) {
        $routePostId->__toString()
            ->willReturn('/post/{id}')
            ->shouldBeCalled();

        $routePostLogin->method()
            ->willReturn('get')
            ->shouldBeCalled();
        $routePostLogin->getIterator()
            ->willReturn(new \ArrayIterator(['post', '{login}']))
            ->shouldBeCalled();
        $routePostLogin->__toString()
            ->willReturn('/post/{login}')
            ->shouldBeCalled();

        $this->beConstructedWith(
            $routeRoot,
            $routePost,
            $routePostPut,
            $routePostId,
            $routePostAll,
            $routePostLogin
        );

        $this->shouldThrow(RouteConflict::class)
            ->duringInstantiation();
    }

    function it_should_return_root_route(
        RequestInterface $request,
        UriInterface $uri,
        Route $routeRoot
    ) {
        $request->getMethod()
            ->willReturn('GET')
            ->shouldBeCalled();
        $request->getUri()
            ->willReturn($uri)
            ->shouldBeCalled();

        $uri->getPath()
            ->willReturn('/')
            ->shouldBeCalled();

        $this->handle($request)
            ->shouldReturn($routeRoot);
    }

    function it_should_return_post_route(
        RequestInterface $request,
        UriInterface $uri,
        Route $routePost
    ) {
        $request->getMethod()
            ->willReturn('GET')
            ->shouldBeCalled();
        $request->getUri()
            ->willReturn($uri)
            ->shouldBeCalled();

        $uri->getPath()
            ->willReturn('/post')
            ->shouldBeCalled();

        $this->handle($request)
            ->shouldReturn($routePost);
    }

    function it_should_return_postPut_route(
        RequestInterface $request,
        UriInterface $uri,
        Route $routePostPut
    ) {
        $request->getMethod()
            ->willReturn('PUT')
            ->shouldBeCalled();
        $request->getUri()
            ->willReturn($uri)
            ->shouldBeCalled();

        $uri->getPath()
            ->willReturn('/post')
            ->shouldBeCalled();

        $this->handle($request)
            ->shouldReturn($routePostPut);
    }

    function it_should_return_postId_route(
        RequestInterface $request,
        UriInterface $uri,
        Route $routePostId
    ) {
        $request->getMethod()
            ->willReturn('GET')
            ->shouldBeCalled();
        $request->getUri()
            ->willReturn($uri)
            ->shouldBeCalled();

        $uri->getPath()
            ->willReturn('/post/4')
            ->shouldBeCalled();

        $this->handle($request)
            ->shouldReturn($routePostId);
    }

    function it_should_return_postAll_route(
        RequestInterface $request,
        UriInterface $uri,
        Route $routePostAll
    ) {
        $request->getMethod()
            ->willReturn('GET')
            ->shouldBeCalled();
        $request->getUri()
            ->willReturn($uri)
            ->shouldBeCalled();

        $uri->getPath()
            ->willReturn('/post/all')
            ->shouldBeCalled();

        $this->handle($request)
            ->shouldReturn($routePostAll);
    }

    function it_should_throw_exception_when_unknown_request_method(
        RequestInterface $request
    ) {
        $request->getMethod()
            ->willReturn('UNKNOWN')
            ->shouldBeCalled();

        $this->shouldThrow(UnknownRequestMethod::class)
            ->during('handle', [$request]);
    }

    function it_should_throw_exception_when_route_not_found(
        RequestInterface $request,
        UriInterface $uri
    ) {
        $request->getMethod()
            ->willReturn('GET')
            ->shouldBeCalled();
        $request->getUri()
            ->willReturn($uri)
            ->shouldBeCalled();

        $uri->getPath()
            ->willReturn('/page/not/found')
            ->shouldBeCalled();

        $this->shouldThrow(RouteNotFound::class)
            ->during('handle', [$request]);
    }
}
