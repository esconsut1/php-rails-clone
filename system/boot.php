<?php

/*
error_reporting(0);
include_once('errors.php');
*/

// Loadup the config file
include_once('../config.php');

// Startup some things
session_save_path(ROOT . DS . 'tmp' . DS . 'sessions');
session_name(SESSION_USER);
session_start();

// Set include path
$path = array();
$path[] = APP_MODELS;
$path[] = APP_CONTROLLERS;
$path[] = APP_VIEWS;
$path[] = SYS;
$path[] = AR;
$path[] = LIB;
$path[] = get_include_path();

set_include_path(implode(PATH_SEPARATOR, $path));

// Setup autoloader
function __autoload($class_name) {
    require_once strtolower(trim($class_name)) . '.php';
}

// Load config files
$DEFAULT = array();

// Load helpers
#require_once SYS . DS . 'cache.php';		// Caching
require_once SYS . DS . 'utils.php';		// Systemwide utilities
require_once SYS . DS . 'helpers.php';		// HTML helpers

// Loadup active_record
require_once SYS . DS . 'ar' . DS . 'ar.php';

// Setup initial parameters list
// ignore $_REQUEST
$LOCAL_PARAMS = array_merge($_POST, $_GET, $_REQUEST);

// Load routing system
require_once CONFIG . DS . 'routes.php';

// Set controller and action to load
if (CURRENT_CONTROLLER) {
	define('CONTROLLER_FILE', APP_CONTROLLERS . DS . CURRENT_CONTROLLER . '.php');
}

// Finally autoload the files in autoload directory
$load_list = glob(ROOT . DS . 'autoload' . DS . '*.php');
foreach($load_list as $ll) {
	include_once $ll;
}

// Set some more defaults
$render_time = $db_time = 0;

// Set error handler
// set_error_handler("user_error_handler");

?>
