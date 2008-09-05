<?

return;

// Deals with cached documents
$filename = getcwd() . '/cache' . $_SERVER['REQUEST_URI'] . '.html';
if (file_exists($filename)) {

	// Spit it out
	readfile($filename);
	echo '<!-- cached -->';

	// Should we delete it>
	$c = filemtime($filename);
	if ($c < time() - 86400) {
		unlink($filename);
	}
	exit;
}

?>
