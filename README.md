# Http router and dispatcher
Http router and dispatcher for development purpose.

## Install
Via composer 
```bash
$ composer require brenoroosevelt/http
```
## Usage

```php
<?php
declare(strict_types=1);

use BrenoRoosevelt\Http\Http;
use Laminas\Diactoros\Response\HtmlResponse;
use Middlewares\Whoops;

require "../vendor/autoload.php";

$app = new Http();
$app->append(new Whoops());

$app->get('/', function () {
    return new HtmlResponse("<h1>Olá, mundo!</h1>");
});

$app->run();
```
