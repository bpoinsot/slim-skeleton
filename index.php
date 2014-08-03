<?php
/*
 *---------------------------------------------------------------------------------------------------------------------------
 * FIRST THINGS FIRST
 *---------------------------------------------------------------------------------------------------------------------------
 * 
 * Encoding & timezone settings
 */
ini_set('default_charset', 'UTF-8');
ini_set('session.cookie_lifetime', 1440);
ini_set('session.gc_maxlifetime', 180);

date_default_timezone_set('Europe/Paris');

if (!extension_loaded('mbstring')) {
    die('mbstring is missing!');
} else {
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');
    mb_http_input('UTF-8');
    mb_language('uni');
    mb_regex_encoding('UTF-8');
    mb_substitute_character('none');
}
// --------------------------------------------------------------------------------------------------------------------------



/*
 *---------------------------------------------------------------------------------------------------------------------------
 * ERROR REPORTING
 *---------------------------------------------------------------------------------------------------------------------------
 * 
 * We define two different environments (development & production) switching automatically from one 
 * to the other thanks to the IP address of the server.
 */
if (in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))) {
    error_reporting(-1);
    ini_set('display_errors', 1);
    $debug = true;
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    $debug = false;
}
// --------------------------------------------------------------------------------------------------------------------------



/*
 *---------------------------------------------------------------------------------------------------------------------------
 * PROJECT STRUCTURE
 *---------------------------------------------------------------------------------------------------------------------------
 * 
 * We define the project structure in order to customize it easily to fit your needs and is assumed 
 * to be fully compliant with the great Twitter Bootstrap (http://getbootstrap.com/) :
 * 
 *  /                   defined as DIR_BASE
 *  ├── app/            defined as DIR_APP
 *  │   ├── cache/      defined as DIR_CACHE
 *  │   ├── config/     defined as DIR_CONFIG
 *  │   ├── layouts/    defined as DIR_LAYOUTS
 *  │   └── vendors/    defined as DIR_VENDORS
 *  │       ├── Twig/
 *  │       ├── Slim/
 *  │       └── [...]
 *  └── assets/
 *      ├── css/
 *      ├── fonts/
 *      ├── ico/
 *      ├── js/
 *      └── [...]
 */
define('DIR_SEP', '/');
define('DIR_BASE', str_replace(DIRECTORY_SEPARATOR, '/', realpath(__DIR__)));
define('DIR_APP', DIR_BASE. DIR_SEP . 'app' . DIR_SEP);
define('DIR_CACHE',   DIR_APP . 'cache' . DIR_SEP);
define('DIR_CONFIG',  DIR_APP . 'config' . DIR_SEP);
define('DIR_LAYOUTS', DIR_APP . 'layouts' . DIR_SEP);
define('DIR_VENDORS', DIR_APP . 'vendors' . DIR_SEP);
// --------------------------------------------------------------------------------------------------------------------------



/*
 *---------------------------------------------------------------------------------------------------------------------------
 * WEBSITE CONSTANTS
 *---------------------------------------------------------------------------------------------------------------------------
 * 
 * We need to define several constants in order to use them with the template engine and get them 
 * updated quickly and easily if needed.
 */
define('APP_BASE', '/skeleton');    # no trailing slash
define('APP_NAME', 'skeleton"');
define('APP_DESC', 'skeleton app');
define('APP_AUTH', 'P. Benjamin');
// --------------------------------------------------------------------------------------------------------------------------



/*
 *---------------------------------------------------------------------------------------------------------------------------
 * PHP NATIVE SESSIONS
 *---------------------------------------------------------------------------------------------------------------------------
 * 
 * http://www.php.net/manual/en/reserved.variables.session.php
 */
session_cache_limiter(false);
session_start();
// --------------------------------------------------------------------------------------------------------------------------



/*
 *---------------------------------------------------------------------------------------------------------------------------
 * REDBEAN ORM
 *---------------------------------------------------------------------------------------------------------------------------
 * 
 * RedBeanPHP is a lightweight, configuration-less ORM library for PHP
 * Version : 4.0.8
 * Website : http://www.redbeanphp.com
 */
require DIR_VENDORS . 'Redbean/rb.php';

R::setup('sqlite:' . DIR_APP . 'db.sqlite');
R::freeze(true);
// --------------------------------------------------------------------------------------------------------------------------



/*
 *---------------------------------------------------------------------------------------------------------------------------
 * SLIM FRAMEWORK
 *---------------------------------------------------------------------------------------------------------------------------
 * 
 * Slim Framework is a small but powerful PHP framework
 * Version : 2.4.3
 * Website : http://slimframework.com
 */
require DIR_VENDORS . 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(array(
    'view' => new \Slim\Views\Twig(),
    'debug' => $debug,
    'templates.path' => DIR_LAYOUTS,
    'routes.case_sensitive' => true
));

# CSRF Token - https://github.com/codeguy/Slim-Extras/tree/develop/Middleware
# $app->add(new \Slim\Extras\Middleware\CsrfGuard());
// --------------------------------------------------------------------------------------------------------------------------



/*
 *---------------------------------------------------------------------------------------------------------------------------
 * TWIG TEMPLATE ENGINE
 *---------------------------------------------------------------------------------------------------------------------------
 * 
 * Twig is a modern template engine for PHP
 * Version : 1.16.0
 * Website : http://twig.sensiolabs.org
 */
$view = $app->view();

$view->parserOptions = array(
    'debug' => $debug,
    'charset' => 'utf-8',
    'autoescape' => true,
    'strict_variables' => true,
    //'cache' => DIR_CACHE
);
// --------------------------------------------------------------------------------------------------------------------------



/*
 *---------------------------------------------------------------------------------------------------------------------------
 * SLIM CUSTOM VIEW FOR TWIG
 *---------------------------------------------------------------------------------------------------------------------------
 * 
 * Slim Framework custom view for Twig by codeguy
 * Version : 0.1.2
 * Website : https://github.com/codeguy/Slim-Views
 */
$view->parserExtensions = array(
    new \Slim\Views\TwigExtension()
);
// --------------------------------------------------------------------------------------------------------------------------



/*
 *---------------------------------------------------------------------------------------------------------------------------
 * DEFINE APPLICATION HOOKS
 *---------------------------------------------------------------------------------------------------------------------------
 * 
 * We use the default 'slim.before' hook in order to make our life easier. It's an easy and quick 
 * way to pass constants to the template engine. 
 */
$app->hook('slim.before', function () use ($app) {
    $app->view()->appendData(array(
        'siteBase' => APP_BASE,
        'siteName' => APP_NAME,
        'siteDesc' => APP_DESC,
        'siteAuth' => APP_AUTH
    ));
});
// --------------------------------------------------------------------------------------------------------------------------



/*
 *---------------------------------------------------------------------------------------------------------------------------
 * HEADER SETTINGS
 *---------------------------------------------------------------------------------------------------------------------------
 *
 */
$app->response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0');
$app->expires('-1 week');
// --------------------------------------------------------------------------------------------------------------------------



/*
 *---------------------------------------------------------------------------------------------------------------------------
 * WEBSITE ROUTES
 *---------------------------------------------------------------------------------------------------------------------------
 */
$app->map('/', function () use ($app) {
    $app->render('index.html');
})->via('GET', 'POST')->name('index');

$app->group('/admin', function () use ($app) {
    $app->map('/', function () use ($app) {
        $app->redirect($app->urlFor('admin-index'));
    })->via('GET', 'POST');

    $app->map('/index.html', function () use ($app) {
        $app->render('index.html');
    })->via('GET', 'POST')->name('admin-index');
});
// --------------------------------------------------------------------------------------------------------------------------



/*
 *---------------------------------------------------------------------------------------------------------------------------
 * HANDLING ERRORS
 *---------------------------------------------------------------------------------------------------------------------------
 * 
 * Let's face it : it may be handy to intercept (some) errors. This is what we do here.
 */
$app->notFound(function () use ($app) {
    $app->render('404.html');
});

$app->error(function (\Exception $e) use ($app) {
    $app->render('50x.html');
});
// --------------------------------------------------------------------------------------------------------------------------



/*
 *---------------------------------------------------------------------------------------------------------------------------
 * RUN THE APPLICATION
 *---------------------------------------------------------------------------------------------------------------------------
 * 
 * Let's get the party started!
 */
$app->run();
// --------------------------------------------------------------------------------------------------------------------------



/*
 *---------------------------------------------------------------------------------------------------------------------------
 * THE END
 *---------------------------------------------------------------------------------------------------------------------------
 */
 ?>
