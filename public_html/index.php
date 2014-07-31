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
// LOAD CONFIG IF IT EXISTS
if(is_file('./config.php'))
    include './config.php';

// DEFAULT CONFIG - DO NOT EDIT - PUT YOUR CUSTOMIZATIONS IN config.php
defined('APP_ROOT') or define('APP_ROOT', realpath('..'));
defined('ENABLE_DEBUG') or define('ENABLE_DEBUG', false);
defined('DB_FILENAME') or define('DB_FILENAME', './client.cfg');
defined('DB_BLACKLIST_FILENAME') or define('DB_BLACKLIST_FILENAME', './blacklist.cfg');
defined('ROUTE_PREFIX') or define('ROUTE_PREFIX', '');

// AUTHENTICATION - DO NOT EDIT - PUT YOUR CUSTOMIZATIONS IN config.php
defined('MANAGER_USER') or define('MANAGER_USER', 'admin');
defined('MANAGER_PASS') or define('MANAGER_PASS', 'demo'); // you might want to change this

// MAILER - DO NOT EDIT - PUT YOUR CUSTOMIZATIONS IN config.php
defined('MAILER_FROM') or define('MAILER_FROM', false); // this is your email address (or false)
defined('MAILER_BCC') or define('MAILER_BCC', false); // this email gets all messages BCC'ed (or false)

// APP SETTINGS - DO NOT EDIT - PUT YOUR CUSTOMIZATIONS IN config.php
defined('APP_DISABLED') or define('APP_DISABLED', false); // enter maintenance message to disable app (or false to enable)
defined('MSG_ENABLED') or define('MSG_ENABLED', true); // enable admin-contact page?
defined('APP_TITLE') or define('APP_TITLE', 'DevWifi'); // your web-app title
defined('STATIC_WEP') or define('STATIC_WEP', false); // set WEP key to disable auto-generate (or false to static)
defined('SHOW_WEP') or define('SHOW_WEP', true); // show a WEP key after submit the form?
// DO NOT EDIT
define('PUBLIC_HTML_PATH', realpath('.'));

// IF YOU INSTALL PUBLIC_HTML IN A SUBDIRECTORY, FOR EXAMPLE: http://example.com/some/dir/index.php
// THEN YOU NEED TO SET APP_ROOT ACCORDINGLY. IN THIS CASE TO '../../../DevWifi'
chdir(APP_ROOT);
require './DevWifi/DevWifi.php';
