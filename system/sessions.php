<?
//
// PHP Cookie based session handlers
// This does not work, I don't know why
//

function css_open($save_path, $session_name) {
	return(true);
}

function css_close() {
	return(true);
}

function css_read($id) {
	$data = base64_decode($_COOKIE['css']);
	// print_r($data);
	return($data);
}

function css_write($id, $sess_data) {
	setcookie('css', base64_encode($sess_data));
	setcookie('css_gc', time());
	return(true);
}

function css_destroy($id) {
	setcookie('css', '', time() - 86400);
	return(true);
}

function css_gc($maxlifetime) {
	if ($_COOKIE['css_gc'] + $maxlifetime < time()) {
		css_destroy(1);
	}
	return(true);
}

session_set_save_handler('css_open', 'css_close', 'css_read', 'css_write', 'css_destroy', 'css_gc');

?>
