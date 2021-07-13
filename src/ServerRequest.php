<?php
declare(strict_types=1);

namespace BrenoRoosevelt\Http;

use Middlewares\Utils\Factory;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class ServerRequest
{
    public static function fromGlobals(): ServerRequestInterface
    {
        $factory = Factory::getServerRequestFactory();
        $className = get_class($factory);

        if ($className === 'Slim\Psr7\Factory\ServerRequestFactory') {
            return call_user_func([$className, 'createFromGlobals']);
        }

        if ($className === 'Laminas\Diactoros\ServerRequestFactory') {
            return call_user_func([$className, 'fromGlobals']);
        }

        if ($className === 'Sunrise\Http\ServerRequest\ServerRequestFactory') {
            return call_user_func([$className, 'fromGlobals']);
        }

        if ($className === 'Nyholm\Psr7\Factory\Psr17Factory' &&
            class_exists($creator = '\Nyholm\Psr7Server\ServerRequestCreator')) {
            return (new $creator($factory, $factory,$factory, $factory))->fromGlobals();
        }

        throw new RuntimeException(sprintf('No factory detected to create a server request from globals'));
    }
}
