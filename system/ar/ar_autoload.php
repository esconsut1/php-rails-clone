<?

// Autoloads the models
function __autoload($class_name) {
	// We assume paths were already set
    require_once strtolower(trim($class_name)) . '.php';
}


?>
