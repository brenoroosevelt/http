<?php
declare(strict_types=1);

namespace BrenoRoosevelt\Http;

use Habemus\Container;
use InvalidArgumentException;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\EmitterStack;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use League\Route\Route;
use League\Route\RouteCollectionTrait;
use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;
use Middlewares\Utils\CallableHandler;
use Middlewares\Utils\Dispatcher;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Http implements RequestHandlerInterface
{
    use RouteCollectionTrait;
    use Middlewares;

    private ContainerInterface $container;
    private Router $router;
    private array $providers = [];

    public function __construct(ContainerInterface $container = null, Router $router = null)
    {
        $this->container = $container ?? new Container();
        $this->router = $router ?? new Router();
        $this->router->setStrategy((new ApplicationStrategy())->setContainer($this->container));
    }

    /**
     * @param RouteProvider|string $provider
     */
    public function addRouteProvider($provider): void
    {
        if (!is_subclass_of($provider, RouteProvider::class, true)) {
            throw new InvalidArgumentException("Invalid route provider %s", $provider);
        }

        $this->providers[] = $provider;
    }

    private function registerProviders(): void
    {
        foreach ($this->providers as $provider) {
            if (is_string($provider) && !is_object($provider) && $this->container->has($provider)) {
                $provider = $this->container->get($provider);
            }

            if ($provider instanceof RouteProvider) {
                $provider->registerRoutes($this->router);
            }
        }
    }

    public function map(string $method, string $path, $handler): Route
    {
        return $this->router->map($method, $path, $handler);
    }

    public function router(): Router
    {
        return $this->router;
    }

    private function emitter(): EmitterInterface
    {
        return
            $this->container->has(EmitterInterface::class) ?
            $this->container->get(EmitterInterface::class) :
            $this->defaultEmitter();
    }

    public function run(ServerRequestInterface $request = null): void
    {
        $request = $request ?? ServerRequest::fromGlobals();
        $response = $this->handle($request);
        $this->emitter()->emit($response);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->registerProviders();
        $router = new CallableHandler(fn($r, $h) => $this->router->handle($r));
        return Dispatcher::run([...$this->resolvedMiddlewares(), $router], $request);
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    private function defaultEmitter(): EmitterInterface
    {
        $sapiStreamEmitter = new SapiStreamEmitter();
        $conditionalEmitter = new class ($sapiStreamEmitter) implements EmitterInterface {
            private EmitterInterface $emitter;

            public function __construct(EmitterInterface $emitter)
            {
                $this->emitter = $emitter;
            }

            public function emit(ResponseInterface $response) : bool
            {
                if (!$response->hasHeader('Content-Disposition') && !$response->hasHeader('Content-Range')) {
                    return false;
                }

                return $this->emitter->emit($response);
            }
        };

        $emitter = new EmitterStack();
        $emitter->push(new SapiEmitter());
        $emitter->push($conditionalEmitter);

        return $emitter;
    }
}
