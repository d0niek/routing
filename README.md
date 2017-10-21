# Routing

Routing component to resolve Uri path

## Install
```bash
composer require extalion/routing
```

## Route
It's a class which represent the request method and Uri path. Also it store a knowledge what controller class create to handle the request.
```php
use eXtalion\Component\Routing\Route;

$route = new Route\{Method}(
    $uriPath,
    $fullnameControllerClass
);
```
where:
 *  **{Method}** is a class name which represent request method. Available classnames/request methods:
  *  `Get`,
  *  `Post`,
  *  `Put`,
  *  `Update`,
  *  `Delete`,
 *  **$uriPath** is a valid Uri path which can contain parameters (see example),
 * **$fullnameControllerClass** self explanatory name :)

### Example:
```php
new Route\Get('/', HomeController::class);
new Route\Get('/user', UserController::class);
new Route\Put('/user', User\SaveController::class);
new Route\Delete('/user/delete/{id}', User\DeleteController::class);
new Route\Delete('/user/delete/all', User\CleanUpController::class);
```
Route `DELETE:/user/delete/{id}` has a parameter `id` and that means this route will match to this requests (all of them has DELETE method request):
```bash
example.com/user/delete/1
example.com/user/delete/4
example.com/user/delete/nsgfg3
example.com/user/delete/...
```
but request `example.com/user/delete/all` will be match to route `DELETE:/user/delete/all`. You don't have to care about order of defining routes, constant path are always before "parameters" path.

If your controller class has any dependencies you can put them to array as third parameter:
```php
$postRepository = new Repository\Post(...);
$mailer = new Mailer\Swift(...);

new Route\Put(
    '/post',
    PostController::class,
    [
        $postRepository,
        'some_string_parameter',
        $mailer,
    ]
);
```

To extract parameters from Uri path call the method `Route::extractParameters($uriPath)`:
```php
$route = new Route\Get('/post/{id}/{action}', PostController::class);

$parameters = $route->extractParameters('/post/3/show');

var_dump($parameters);

// array(2) {
//   'id' => string(1)
//   'action' => string(4) "show"
// }
```
You can validate your parameters during extracting them from path:
```php
$route = new Route\Get(
    '/post/{id}/{action}/{language}',
    PostController::class,
    [],
    [
        'id' => function (string $id): int {
            return (int) $id;
        },
        'action' => function (string $action): string {
            return $action . '.php';
        }
    ]
);

$parameters = $route->extractParameters('/post/3/show/pl');

var_dump($parameters);

// array(2) {
//   'id' => int(3)
//   'action' => string(8) "show.php"
//   'language' => string(2) "pl"
// }
```

### New request method route
If you want to add new request method route you have to create new class and extends `\eXtalion\Component\Routing\Route`.

## Router (\\Basic)
It's a class which handle a `\Psr\Http\Message\RequestInterface` and return `Route` which match to requested method and Uri path.

### Example
```php
use eXtalion\Component\Routing\Router;

$router = new Router\Basic(
    new Route\Get(...),
    new Route\Get(...),
    new Route\Post(...),
    ...
);

$request = ... // Something what return \Psr\Http\Message\RequestInterface

$route = $router->handle($request);
```
If you try to add two routes which match exactly to the same Uri Path, `\eXtalion\Component\Routing\Exception\RouteConflict` will be thrown.
```php
try {
    $router = new Router\Basic(
        new Route\Get('/user/{id}', ...),
        new Route\Get('/user/{login}', ...)
    );
} catch (RouteConflict $ex) {
    echo $ex->getMessage();
}
```
