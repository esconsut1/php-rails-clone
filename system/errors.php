<?
function user_error_handler($errnum, $errmsg, $filename, $linenum, $vars) {

	ob_clean();

	$ts = date('r');

	echo '<h1>System Error</h1>';
	echo '<div style="border: 1px solid black; background-color: #FFFFCC; padding: 1em;">';
	echo "<h2 style=\"color: red;\">{$errmsg}</h2>";
	echo "<pre>";
	echo "<p>Program: {$filename}<br/>Line: {$linenum}<br/></p>";
	echo '</pre>';
	echo '<pre>';

	echo '<h3>GET</h3>';
	print_r($_GET);

	echo '<h3>POST</h3>';
	print_r($_POST);

	echo '<h3>System Variables</h3>';
	print_r($_SERVER);

	echo '<h3>COOKIES</h3>';
	print_r($_COOKIE);

	echo '</pre>';
	echo '</div>';
	die;
}
?>
