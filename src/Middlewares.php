<?php
declare(strict_types=1);

namespace BrenoRoosevelt\Http;

use Closure;
use Middlewares\Utils\CallableHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;

trait Middlewares
{
    private array $middlewares = [];

    public abstract function getContainer(): ContainerInterface;

    /**
     * @param string|callable|MiddlewareInterface $middleware
     */
    public function append($middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @param string|callable|MiddlewareInterface $middleware
     */
    public function prepend($middleware): void
    {
        array_unshift($this->middlewares, $middleware);
    }

    public function middlewares(): array
    {
        return $this->middlewares;
    }

    protected function resolveMiddleware($middleware): MiddlewareInterface
    {
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        if ($middleware instanceof Closure) {
            return new CallableHandler($middleware);
        }

        $container = $this->getContainer();
        if (is_string($middleware) && $container->has($middleware)) {
            return $container->get($middleware);
        }

        throw new RuntimeException(
            sprintf("Cannot resolve middleware: %s.", (string) $middleware)
        );
    }

    protected function resolvedMiddlewares(): array
    {
        return array_map(fn($middleware) => $this->resolveMiddleware($middleware), $this->middlewares);
    }
}
