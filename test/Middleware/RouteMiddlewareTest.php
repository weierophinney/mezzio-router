<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Router\Middleware;

use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteMiddlewareTest extends TestCase
{
    /** @var RouterInterface|ObjectProphecy */
    private $router;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    /** @var RouteMiddleware */
    private $middleware;

    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    /** @var RequestHandlerInterface|ObjectProphecy */
    private $handler;

    protected function setUp() : void
    {
        $this->router     = $this->prophesize(RouterInterface::class);
        $this->request = $this->prophesize(ServerRequestInterface::class);
        $this->response   = $this->prophesize(ResponseInterface::class);
        $this->handler = $this->prophesize(RequestHandlerInterface::class);

        $this->middleware = new RouteMiddleware($this->router->reveal());
    }

    public function testRoutingFailureDueToHttpMethodCallsHandlerWithRequestComposingRouteResult()
    {
        $result = RouteResult::fromRouteFailure(['GET', 'POST']);

        $this->router->match($this->request->reveal())->willReturn($result);
        $this->handler->handle($this->request->reveal())->will([$this->response, 'reveal']);

        $this->request->withAttribute(RouteResult::class, $result)->will([$this->request, 'reveal']);
        $this->request
            ->withAttribute(\Zend\Expressive\Router\RouteResult::class, $result)
            ->will([$this->request, 'reveal']);

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());
        $this->assertSame($this->response->reveal(), $response);
    }

    public function testGeneralRoutingFailureInvokesHandlerWithRequestComposingRouteResult()
    {
        $result = RouteResult::fromRouteFailure(null);

        $this->router->match($this->request->reveal())->willReturn($result);
        $this->handler->handle($this->request->reveal())->will([$this->response, 'reveal']);

        $this->request->withAttribute(RouteResult::class, $result)->will([$this->request, 'reveal']);
        $this->request
            ->withAttribute(\Zend\Expressive\Router\RouteResult::class, $result)
            ->will([$this->request, 'reveal']);

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());
        $this->assertSame($this->response->reveal(), $response);
    }

    public function testRoutingSuccessInvokesHandlerWithRequestComposingRouteResultAndAttributes()
    {
        $middleware = $this->prophesize(MiddlewareInterface::class)->reveal();
        $parameters = ['foo' => 'bar', 'baz' => 'bat'];
        $result = RouteResult::fromRoute(
            new Route('/foo', $middleware),
            $parameters
        );

        $this->router->match($this->request->reveal())->willReturn($result);

        $this->request
            ->withAttribute(RouteResult::class, $result)
            ->will([$this->request, 'reveal']);
        $this->request
            ->withAttribute(\Zend\Expressive\Router\RouteResult::class, $result)
            ->will([$this->request, 'reveal']);
        foreach ($parameters as $key => $value) {
            $this->request
                ->withAttribute($key, $value)
                ->will([$this->request, 'reveal']);
        }

        $this->handler
            ->handle($this->request->reveal())
            ->will([$this->response, 'reveal']);

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());
        $this->assertSame($this->response->reveal(), $response);
    }
}
