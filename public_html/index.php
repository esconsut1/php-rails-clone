<?php
include_once('../system/boot.php');

// Dispatch requests
if(defined('CURRENT_CONTROLLER')) {
	$class_name = CURRENT_CONTROLLER . '_controller';
	$file = "../app/controllers/{$class_name}.php";
	if (file_exists($file)) {
		$s = new $class_name(); 
	} else {
		$s = new Migrate_Controller;
		$s->index();
		exit;
	}
	$s->logger();
} else {
	include_once('404.php');
}


?>
