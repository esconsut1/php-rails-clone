<?php

// Setup active_record

// Load our database settings -- you can add as many as you want in the config file
$AR_DB_CONFIG = parse_ini_file(ROOT . DS . 'config' . DS . 'database.ini', true);

// Load some database drivers that we need
if (!$AR_DB_CONFIG) {
	die('Invalid database connection file "database.ini"');
}

include_once('activerecord.php');

while(list($c, $rec) = each($AR_DB_CONFIG)) {
	$driver = "ar_{$rec['driver']}.php";
	include_once($driver);
}

?>
