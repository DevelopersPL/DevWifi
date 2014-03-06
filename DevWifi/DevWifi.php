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

use  DevWifi\Entry;

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
    new Twig_Extension_Debug()
);

$view->parserOptions = array(
    'debug' => ENABLE_DEBUG
);

$view->appendData(array(
    'ROUTE_PREFIX' => ROUTE_PREFIX
));

/////////////////////////// SETUP LOGGER //////////////////////////////
// Create monolog logger and store logger in container as singleton
// (Singleton resources retrieve the same log resource definition each time)
$DevWifi->container->singleton('log', function () {
    $log = new \Monolog\Logger('DevWifi');
    $log->pushHandler(new \Monolog\Handler\StreamHandler(APP_ROOT . '/logs/error.log', \Monolog\Logger::ERROR));
    $log->pushHandler(new \Monolog\Handler\RotatingFileHandler(APP_ROOT . '/logs/events.log', \Monolog\Logger::INFO));
    return $log;
});

//////////////// DEFINE AUTHENTICATION MIDDLEWARE /////////////////////
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

/////////////////////////// HANDLE ERRORS /////////////////////////////
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

/////////////////////////// ENTRY FACTORY ////////////////////////////
// Define entry resource
$DevWifi->container->singleton('entries', function () {
    return new DevWifi\EntryFactory(DB_FILENAME);
});

$DevWifi->container->singleton('blacklist', function () {
    return new DevWifi\EntryFactory(DB_BLACKLIST_FILENAME);
});

//////////////////////////// ROUTES //////////////////////////////////
$DevWifi->map(ROUTE_PREFIX.'/', function() use($DevWifi) {
    $req = $DevWifi->request;
    if($req->isPost())
    {
        try {
            if($req->post('action') == 'add')
            {
                $entry = new Entry();
                $entry->firstName = $req->post('firstName');
                $entry->lastName = $req->post('lastName');
                if( $req->post('type') == 'u' )
                    $entry->grade = $req->post('grade');
                $entry->mac = $req->post('mac');
                $entry->device = $req->post('device');

                if($req->post('rules') != 'on')
                    throw new InputErrorException('You need to accept Terms & Conditions!', 400);

                if($DevWifi->entries->get($entry->mac) instanceof Entry)
                    throw new InputErrorException('This device already has access!', 400);

                if($DevWifi->blacklist->get($entry->mac) instanceof Entry)
                    throw new InputErrorException('This device is blacklisted!', 400);

                $DevWifi->entries->set($entry);
                $DevWifi->entries->save();
                $DevWifi->log->addInfo('New device added', $entry->toArray());

                // TODO: send email

                $DevWifi->view->appendData(array(
                    'key' => $entry->key
                ));
            }
            elseif($req->post('action') == 'key')
            {
                $entry = $DevWifi->entries->get($req->post('mac'));
                if(!($entry instanceof Entry))
                    throw new InputErrorException('This device is not registered!', 400);

                if($entry->firstName != $req->post('firstName'))
                    throw new InputErrorException('First name does not match!', 400);

                if($entry->lastName != $req->post('lastName'))
                    throw new InputErrorException('Last name does not match!', 400);

                if($req->post('rules') != 'on')
                    throw new InputErrorException('You need to accept Terms & Conditions!', 400);

                $entry->generateKey();

                $DevWifi->entries->set($entry);
                $DevWifi->entries->save();
                $DevWifi->log->addInfo('Device key modified', $entry->toArray());

                // TODO: send email

                $DevWifi->view->appendData(array(
                    'new_key' => $entry->key
                ));
            }
            elseif($req->post('action') == 'delete')
            {
                $entry = $DevWifi->entries->get($req->post('mac'));
                if(!($entry instanceof Entry))
                    throw new InputErrorException('This device is not registered!', 400);

                if($entry->firstName != $req->post('firstName'))
                    throw new InputErrorException('First name does not match!', 400);

                if($entry->lastName != $req->post('lastName'))
                    throw new InputErrorException('Last name does not match!', 400);

                $DevWifi->entries->delete($entry);
                $DevWifi->entries->save();
                $DevWifi->log->addInfo('Device deleted', $entry->toArray());

                $DevWifi->view->appendData(array(
                    'deleted' => true
                ));
            }
        } catch(InputErrorException $e)
        {
            $DevWifi->view->appendData(array(
                'error' => $e->getMessage()
            ));
        }
        $DevWifi->view->appendData(array(
            'pane' => $req->post('action')
        ));
    }
    $DevWifi->render('form.html', array(
        'type' => $req->post('type'),
        'firstName' => $req->post('firstName'),
        'lastName' => $req->post('lastName'),
        'grade' => $req->post('grade'),
        'mac' => $req->post('mac'),
        'device' => $req->post('device'),
        'email' => $req->post('email'),
        'rules' => $req->post('rules')
    ));
})->via('GET', 'POST')->name('home');

$DevWifi->get(ROUTE_PREFIX.'/regulamin', function() use($DevWifi) {
    $DevWifi->render('rules.html');
})->name('rules');

$DevWifi->map(ROUTE_PREFIX.'/manager', $authenticate(), function() use($DevWifi) {
    $req = $DevWifi->request;
    if($req->isPost())
    {
        if($req->post('scope') == 'entries')
            $entry = $DevWifi->entries->get($req->post('mac'));
        else
            $entry = $DevWifi->blacklist->get($req->post('mac'));

        if(!($entry instanceof Entry))
            $DevWifi->view->appendData(array(
                'error' => 'Entry with MAC '.$req->post('mac').' does not exist!'
            ));
        else
        {
            if($req->post('action') == 'delete' && $req->post('scope') == 'entries')
            {
                $DevWifi->entries->delete($entry);
                $DevWifi->entries->save();
            }
            elseif($req->post('action') == 'blacklist' && $req->post('scope') == 'entries')
            {
                $DevWifi->blacklist->set($entry);
                $DevWifi->blacklist->save();
                $DevWifi->entries->delete($entry);
                $DevWifi->entries->save();
            }
            elseif($req->post('action') == 'readd' && $req->post('scope') == 'blacklist')
            {
                $DevWifi->entries->set($entry);
                $DevWifi->entries->save();
                $DevWifi->blacklist->delete($entry);
                $DevWifi->blacklist->save();
            }
            elseif($req->post('action') == 'delete' && $req->post('scope') == 'blacklist')
            {
                $DevWifi->blacklist->delete($entry);
                $DevWifi->blacklist->save();
            }
        }
    }
    $DevWifi->render('manager.html', array(
        'entries' => $DevWifi->entries->getAll(),
        'blacklist' => $DevWifi->blacklist->getAll()
    ));
})->via('GET', 'POST')->name('manager');

if(ENABLE_DEBUG)
    $DevWifi->get(ROUTE_PREFIX.'/debug', function() use($DevWifi) {
        var_dump($DevWifi->entries->getAll());
        //echo $DevWifi->entries->getAll()['a8:26:d9:ca:22:58']->mac;
    });

//////////////////////////////////////////////////////////////////////
// all done, any code after this call will not matter to the request
$DevWifi->run();
