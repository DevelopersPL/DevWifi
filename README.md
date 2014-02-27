DevWifi
=======
This software is designed to:

* be easy to understand and modify
* emphasize simplicity and security
* provide an example of good programming practices in PHP
* require zero configuration for simple and secure use
* follow [PSR-2 coding guidelines](http://www.php-fig.org/psr/psr-2/)

Requirements
=====
* PHP >= 5.3

Installation (dev release)
=====
* [Get composer](https://getcomposer.org/download) ```curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin```
* Clone this repo: ```git clone https://github.com/DevelopersPl/DevWifi.git```
* Install dependencies: ```composer.phar install```
* Set up your web server to serve ```public_html``` as document root
* Create ```config.php``` taking inspirations from ```index.php```

Hacking
=====
* [Slim](http://slimframework.com) framework [documentation](http://docs.slimframework.com/)
* [Slim Views](https://github.com/codeguy/Slim-Views)
* [Twig](http://twig.sensiolabs.org/) templating engine

License
=====
Check [LICENSE](LICENSE) file