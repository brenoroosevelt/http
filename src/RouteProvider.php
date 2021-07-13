<?php
declare(strict_types=1);

namespace BrenoRoosevelt\Http;

use League\Route\Router;

interface RouteProvider
{
    public function registerRoutes(Router $router): void;
}
