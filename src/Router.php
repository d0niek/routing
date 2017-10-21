<?php
declare(strict_types=1);

namespace eXtalion\Component\Routing;

use Psr\Http\Message\RequestInterface;

/**
 * @author Damian Glinkowski <damianglinkowski@gmail.com>
 */
interface Router
{
    /**
     * Handle requeset
     *
     * @param \Psr\Http\Message\RequestInterface $request
     *
     * @return \eXtalion\Component\Routing\Route
     * @throws \eXtalion\Component\Routing\Exception\UnknownRequestMethod
     * @throws \eXtalion\Component\Routing\Exception\RouteNotFound
     */
    public function handle(RequestInterface $request): Route;
}
