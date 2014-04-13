<?php
/*
 *--------------------------------------------------------------------------------------------------
 * FIRST THINGS FIRST
 *--------------------------------------------------------------------------------------------------
 * 
 * Encoding & timezone settings
 */
date_default_timezone_set('Europe/Paris');
ini_set('default_charset', 'UTF-8');

if (!extension_loaded('mbstring')) {
    die('mbstring is missing!');
} else {
    mb_internal_encoding('UTF-8');
    mb_http_input('UTF-8');
    mb_http_output('UTF-8');
}
// -------------------------------------------------------------------------------------------------



/*
 *--------------------------------------------------------------------------------------------------
 * ERROR REPORTING
 *--------------------------------------------------------------------------------------------------
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
// -------------------------------------------------------------------------------------------------



/*
 *--------------------------------------------------------------------------------------------------
 * PROJECT STRUCTURE
 *--------------------------------------------------------------------------------------------------
 * 
 * We define the project structure in order to customize it easily to fit your needs and is assumed 
 * to be fully compliant with the great Twitter Bootstrap :
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
#define('DIR_ASSETS', DIR_BASE. DIR_SEP . 'assets' . DIR_SEP);
// -------------------------------------------------------------------------------------------------



/*
 *--------------------------------------------------------------------------------------------------
 * WEBSITE CONSTANTS
 *--------------------------------------------------------------------------------------------------
 * 
 * We need to define several constants in order to use them with the template engine and get them 
 * updated quickly and easily if needed.
 */
define('APP_BASE', '/skeleton');        # no trailing slash
define('APP_NAME', 'Skeletin app');
define('APP_AUTH', '@bpoinsot');
// -------------------------------------------------------------------------------------------------



/*
 *--------------------------------------------------------------------------------------------------
 * PHP NATIVE SESSIONS
 *--------------------------------------------------------------------------------------------------
 * 
 * http://www.php.net/manual/en/reserved.variables.session.php
 */
session_cache_limiter(false);
session_start();
// -------------------------------------------------------------------------------------------------



/*
 *--------------------------------------------------------------------------------------------------
 * REDBEAN ORM
 *--------------------------------------------------------------------------------------------------
 * 
 * RedBeanPHP is a lightweight, configuration-less ORM library for PHP
 * Version : 3.5.7
 * Website : http://www.redbeanphp.com
 */
require DIR_VENDORS . 'Redbean/rb.php';

R::setup('sqlite:' . DIR_APP . 'db.sqlite');
R::freeze(true);
// -------------------------------------------------------------------------------------------------



/*
 *--------------------------------------------------------------------------------------------------
 * SLIM FRAMEWORK
 *--------------------------------------------------------------------------------------------------
 * 
 * Slim Framework is a small but powerfull PHP framework
 * Version : 2.4.2
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
// -------------------------------------------------------------------------------------------------



/*
 *--------------------------------------------------------------------------------------------------
 * TWIG TEMPLATE ENGINE
 *--------------------------------------------------------------------------------------------------
 * 
 * Twig is a modern template engine for PHP
 * Version : 1.15.1
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

$view->parserExtensions = array(
    new \Slim\Views\TwigExtension(),
);
// -------------------------------------------------------------------------------------------------



/*
 *--------------------------------------------------------------------------------------------------
 * DEFINE APPLICATION HOOKS
 *--------------------------------------------------------------------------------------------------
 * 
 * We use the default 'slim.before' hook in order to make our life easier. It's an easy and quick 
 * way to pass constants to the template engine. 
 */
$app->hook('slim.before', function () use ($app) {
    $app->view()->appendData(array(
        'siteBase' => APP_BASE,
        'siteName' => APP_NAME,
        'siteAuth' => APP_AUTH
    ));
});
// -------------------------------------------------------------------------------------------------



/*
 *--------------------------------------------------------------------------------------------------
 * HEADER SETTINGS
 *--------------------------------------------------------------------------------------------------
 *
 */
$app->response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
$app->expires('-1 week');
// -------------------------------------------------------------------------------------------------



/*
 *--------------------------------------------------------------------------------------------------
 * WEBSITE ROUTES
 *--------------------------------------------------------------------------------------------------
 * 
 * Routes are :
 *    /
 */
$app->map('/', function () use ($app) {
    $app->render('main.htm');
})->via('GET', 'POST')->name('index');
// -------------------------------------------------------------------------------------------------



/*
 *--------------------------------------------------------------------------------------------------
 * HANDLING ERRORS
 *--------------------------------------------------------------------------------------------------
 * 
 * Let's face it : it may be handy to intercept (some) errors. This is what we do here.
 */
$app->notFound(function () use ($app) {
    $app->redirect($app->urlFor('index'));
});

$app->error(function (\Exception $e) use ($app) {
    $app->redirect($app->urlFor('index'));
});
// -------------------------------------------------------------------------------------------------



/*
 *--------------------------------------------------------------------------------------------------
 * RUN THE APPLICATION
 *--------------------------------------------------------------------------------------------------
 * 
 * Let's get the party started!
 */
$app->run();
// -------------------------------------------------------------------------------------------------



/*
 *--------------------------------------------------------------------------------------------------
 * THE END
 *--------------------------------------------------------------------------------------------------
 */
 ?>