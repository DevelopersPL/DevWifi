<?php
/**
 * DevWifi
 *
 * A simple web application to register Wifi access point users
 *
 *
 * LICENSE: Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
 * FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package    DevWifi
 * @author     Daniel Speichert <daniel@speichert.pl>
 * @author     Wojciech Guziak <wojciech@guziak.net>
 * @copyright  2014 Developers.pl
 * @license    http://opensource.org/licenses/MIT MIT
 * @version    master
 * @link       https://github.com/DevelopersPL/DevWifi
 */

// Autoload our dependencies with Composer
$loader = require APP_ROOT . '/vendor/autoload.php';
$loader->setPsr4('DevWifi\\', APP_ROOT . '/DevWifi');


//////////////////////// CREATE Slim APPLICATION //////////////////////////////////
$DevWifi = new \Slim\Slim(array(
    'debug' => ENABLE_DEBUG,
    'templates.path' => APP_ROOT . '/templates',
));

///////////////////////////////// SETUP VIEW /////////////////////////////////////
// https://github.com/codeguy/Slim-Views
$DevWifi->view(new \Slim\Views\Twig());
$view = $DevWifi->view();
$view->parserOptions = array(
    'debug' => ENABLE_DEBUG,
    'cache' => APP_ROOT . '/cache',
    'charset' => 'utf-8',
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);
$view->parserExtensions = array(
    new \Slim\Views\TwigExtension(),
);

$view->appendData(array(
    'ROUTE_PREFIX' => ROUTE_PREFIX
));

/////////////////////////////// SETUP LOGGER ////////////////////////////////////
// Create monolog logger and store logger in container as singleton
// (Singleton resources retrieve the same log resource definition each time)
$DevWifi->container->singleton('log', function () {
    $log = new \Monolog\Logger('DevWifi');
    $log->pushHandler(new \Monolog\Handler\RotatingFileHandler(APP_ROOT . '/logs/events.log', \Monolog\Logger::INFO));
    return $log;
});

//////////////////// DEFINE AUTHENTICATION MIDDLEWARE ////////////////////////////
// http://docs.slimframework.com/#Middleware-Overview
$authenticate = function() use ( $DevWifi ) {
    return function () use ($DevWifi) {
        $req = $DevWifi->request();
        $res = $DevWifi->response();
        $auth_user = $req->headers('PHP_AUTH_USER');
        $auth_pass = $req->headers('PHP_AUTH_PW');

        if($auth_user != MANAGER_USER || $auth_pass != MANAGER_PASS)
        {
            $res->header('WWW-Authenticate', sprintf('Basic realm="%s"', 'DevWifi'));
            $DevWifi->halt(401, $DevWifi->view()->render('denied.html'));
        }
    };
};

////////////////////////////// HANDLE ERRORS ////////////////////////////////////
class InputErrorException extends \Exception {};
$DevWifi->error(function ($e) use ($DevWifi) {
    if($e instanceof InputErrorException)
    {
        $DevWifi->response->headers->set('Content-Type', 'application/json');
        $DevWifi->render('error.html', array(
            'code' => $e->getCode(),
            'message' => $e->getMessage()
        ));
    }
    else
    {
        $DevWifi->response->setStatus(500);
        $DevWifi->render('error.html', array(
            'code' => $e->getCode(),
            'message' => 'Fatal error occured: ' . $e->getMessage() . ' at line ' . $e->getLine() . ' in file ' . $e->getFile()
        ));
    }
});

//////////////////////////// ROUTES //////////////////////////////////
$DevWifi->get(ROUTE_PREFIX.'/', function() use($DevWifi) {
    $DevWifi->render('form.html');
    $DevWifi->log->addInfo('Something worth logging just happened!');
})->name('home');

$DevWifi->get(ROUTE_PREFIX.'/regulamin', function() use($DevWifi) {
    $DevWifi->render('rules.html');
})->name('rules');

$DevWifi->get(ROUTE_PREFIX.'/manager', $authenticate(), function() use($DevWifi) {
    $DevWifi->render('manager.html');
})->name('manager');

//////////////////////////////////////////////////////////////////////
// all done, any code after this call will not matter to the request
$DevWifi->run();
