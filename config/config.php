<?php
/**
* Loads System configuration and prepares
* default variables and so forth
*/

// ------ NOTE ------ There is almost no reason to edit stuff in here unless you know what you're doing ------

// Optionali -- setting to false will auto-detect
define('APPLICATION_PATH', false);

// Default environment
define('ENVIRONMENT', 'production');

// What is the encoding here?
define('ENCODING', 'UTF-8');

// Setup other defaults
define('SESSION_USER', 'user');

// Set this to be your home page controller
define('DEFAULT_CONTROLLER','welcome');

// Set this to be the default action for any controller
define('DEFAULT_ACTION', 'index');

define('DEFAULT_COOKIE', 'myuser');

// Set application encoding
if (defined(ENCODING)) {
	ini_set('mbstring.internal_encoding', ENCODING);
	ini_set('mbstring.http_output', ENCODING);
	ini_set('mbstring.detect_order', ENCODING);
	ini_set('mbstring.substitute_character', '12307');
}

// When did we start?
define('START_TIME', microtime(true));

// Futz our path
if (APPLICATION_PATH === false) {
	$sys_path = str_replace('/system', '', dirname(__FILE__));
} else {
	$sys_path = APPLICATION_PATH;
}

// Setup paths
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', $sys_path);
define('APP', ROOT . DS . 'app');
define('APP_MODELS', APP . DS . 'models');
define('APP_CONTROLLERS', APP . DS . 'controllers');
define('APP_VIEWS', APP . DS . 'views');
define('SYS', ROOT . DS . 'system');
define('LIB', ROOT . DS . 'lib');
define('CONFIG', ROOT . DS . 'config');
define('AR', ROOT . DS . 'system' . DS . 'ar');
define('AR_DEFAULT_DB', ENVIRONMENT);

?>
