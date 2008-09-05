<?php
// Utilities

function debug($var=false)
{
	print "<div style=\"text-align: left; padding: 10px; background-color: #FFFFCC; border: 1px solid #000;\"><pre>";
	print_r($var);
	print "</pre></div>";
}

// Write something to the proper log
function ar_logger($msg = '', $environment='default')
{
	if (!defined('AR_LOG_PATH')) {
		return(false);
	}

	if (!file_exists(AR_LOG_PATH)) {
		return(false);
	}

	$filename = AR_LOG_PATH . '/ar-' . $environment . '.log';

	$ts = date('r');
	$str = "{$ts}\t{$msg}\n";
	#file_put_contents($filename, $str, FILE_APPEND | LOCK_EX);

	return(true);
}

// Write something to the proper log
function logger($msg = '')
{
	$ts = date('r');
	$str = '[' . $ts . '] => "' . $msg . '"' . "\n"; 
	$filename = ROOT . DS . 'log' . DS . ENVIRONMENT . '.log';
	#file_put_contents($filename, $str, FILE_APPEND | LOCK_EX);
}

// Throw an error to the screen and exit
function throw_error($errormsg = "Unknown")
{
	$msg  = '<pre><h1>System Exception</h1>';
	$msg .= '<h2> ' . $errormsg . '</h2>';
	$msg .= 'LINE  : ' . __LINE__ . "\n";
	$msg .= 'FILE  : ' . __FILE__ . "\n";
	$msg .= 'CLASS : ' . __CLASS__ . "\n";
	$msg .= 'METHOD: ' . __METHOD__ . "\n";
	$msg .= '<hr size=1 />';
	
	echo $msg;
	exit(1);
}

function is_hash($var)
{
	if (!is_array($var))
		return false;

	return array_keys($var) !== range(0,sizeof($var)-1);
}

function now()
{
	return(date('Y-m-d H:i:s'));
}

function link_to($text="", $params=array(), $html=array())
{
	$options = build_html_options($html);
	$url = build_url($params);

	if($options)
		$url = "<a href=\"{$url}\" {$options}>{$text}</a>";
	else
		$url = "<a href=\"{$url}\">{$text}</a>";
	
	return($url);
}

// Create a url extended with the current parameters in GET or POST
function clink_to($text="", $params=array(), $html=array())
{
	// Setup params
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		$items = $_GET;
	} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$items = $_POST;
	} else {
		$items = $_REQUEST;
	}
	
	if (is_array($params)) {
		while(list($key,$val) = each($params)) {
			$items[$key] = $val;
		}
		$url = build_url($items);
	} else {
		$url = build_url($params);
	}
	
	$options = build_html_options($html);
	
	if($options)
		$url = "<a href=\"{$url}\" {$options}>{$name}</a>";
	else
		$url = "<a href=\"{$url}\">{$name}</a>";
		
	return($url);
}

function build_url($params=array())
{
	global $ROUTE;
	
	if (!is_array($params)) {
		return($params);
	}
	
	$path = array();
	$path[] = $params['controller'] ? $params['controller'] : CURRENT_CONTROLLER;
	
	// Only set if we have an action
	if ($params['action']) {
		$path[] = urlencode($params['action']);
	}
	
	if ($params['id']) {
		$path[] = urlencode($params['id']);
	}
	
	unset($params['controller']);
	unset($params['action']);
	unset($params['id']);
	
	$url = '/' . implode('/', $path);
	if (count($params) > 0) {
		$url .= '?' . http_build_query($params);
	}
	
	return($url);
}

// Build HTML options for a link or anything else
function build_html_options($params=array())
{
	$options = array();
		
	// Confirmation popup
	if($params['confirm'] && !$params['onclick'])
	{
		$options[] = "onClick=\"javascript:return confirm('{$params['confirm']}')\" ";
		unset($params['confirm']);
	}
	
	// Javascript popup of window
	if($params['popup'])
	{
		if($params['popup'] === true)
			$options[] = "onClick=\"window.open('{$url}', 'revsense_window', 'height=200,width=150')\" ";
		else
			$options[] = "onClick=\"window.open('{$url}', {$params['popup']} )\" ";

		unset($params['popup']);
	}

	@reset($params);
	while(list($key,$val) = each($params))
	{
		$options[] = "$key=\"{$val}\" ";
	}

	if(count($options))
		return(implode($options, " "));
	else
		return("");
}

/**
 * HTTP Protocol defined status codes
 * @param int $num
 */
function http_status($num=200) {
  
   $http = array (
       100 => "HTTP/1.1 100 Continue",
       101 => "HTTP/1.1 101 Switching Protocols",
       200 => "HTTP/1.1 200 OK",
       201 => "HTTP/1.1 201 Created",
       202 => "HTTP/1.1 202 Accepted",
       203 => "HTTP/1.1 203 Non-Authoritative Information",
       204 => "HTTP/1.1 204 No Content",
       205 => "HTTP/1.1 205 Reset Content",
       206 => "HTTP/1.1 206 Partial Content",
       300 => "HTTP/1.1 300 Multiple Choices",
       301 => "HTTP/1.1 301 Moved Permanently",
       302 => "HTTP/1.1 302 Found",
       303 => "HTTP/1.1 303 See Other",
       304 => "HTTP/1.1 304 Not Modified",
       305 => "HTTP/1.1 305 Use Proxy",
       307 => "HTTP/1.1 307 Temporary Redirect",
       400 => "HTTP/1.1 400 Bad Request",
       401 => "HTTP/1.1 401 Unauthorized",
       402 => "HTTP/1.1 402 Payment Required",
       403 => "HTTP/1.1 403 Forbidden",
       404 => "HTTP/1.1 404 Not Found",
       405 => "HTTP/1.1 405 Method Not Allowed",
       406 => "HTTP/1.1 406 Not Acceptable",
       407 => "HTTP/1.1 407 Proxy Authentication Required",
       408 => "HTTP/1.1 408 Request Time-out",
       409 => "HTTP/1.1 409 Conflict",
       410 => "HTTP/1.1 410 Gone",
       411 => "HTTP/1.1 411 Length Required",
       412 => "HTTP/1.1 412 Precondition Failed",
       413 => "HTTP/1.1 413 Request Entity Too Large",
       414 => "HTTP/1.1 414 Request-URI Too Large",
       415 => "HTTP/1.1 415 Unsupported Media Type",
       416 => "HTTP/1.1 416 Requested range not satisfiable",
       417 => "HTTP/1.1 417 Expectation Failed",
       500 => "HTTP/1.1 500 Internal Server Error",
       501 => "HTTP/1.1 501 Not Implemented",
       502 => "HTTP/1.1 502 Bad Gateway",
       503 => "HTTP/1.1 503 Service Unavailable",
       504 => "HTTP/1.1 504 Gateway Time-out"       
   );
  
   header($http[$num]);
}

// Redirect
function redirect_to($param=null, $header_code = 302)
{
	// Issue the header code
	http_status($header_code);
	
	// Redirect to where we came from
	if (is_array($param) && $param['back']) {
		header('Location: ' . $_SERVER['HTTP_REFERER']);
		exit;
	}
	
	// Redirect to a specific location
	if (is_array($param)) {
		header('Location: ' . build_url($param));
		exit;
	}
	
	// Its a string at this point
	header('Location: ' . $param);
	exit;
}

// Select options
function select_options($options=array(), $key="", $value="", $default_value="")
{
	$elem = array();
	$i = is_hash($options);
	
	while (list($key,$val) = each($options)) {
		$key = $i ? $key : $val;
		
		if ($default_value == $key)
			$elem[] = '<option value="' . $key . '" SELECTED>' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '</option>';
		else
			$elem[] = '<option value="' . $key . '">' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '</option>';
	}
	
	return(implode("\n", $elem)); 
}

// Shortcuts
function redirect_back()
{
	redirect_to(array('back'=>true));
}

// Stylesheet Link tags
function stylesheet_link_tag($tags = array())
{
	$t = array();
	foreach($tags as $tag) {
		$t[] = '<link href="/stylesheet/' . $tag . '.css" media="screen" rel="Stylesheet" type="text/css" />' . "\n";
	}
	
	return(implode("\n", $t));
}

// Javascript Link tags
function javascript_include_tag($tags = array())
{
	$t = array();
	foreach($tags as $tag) {
		$t[] = '<script src="/javascript/' . $tag . '.js" type="text/javascript"></script>' . "\n";
	}
	
	return(implode("\n", $t));
}

// Auto Discovery Link Tags
// Currently for RSS only
//auto_discovery_link_tag(array('type'=>'rss', array('controller'=>'rss', 'action'=>$this->rss_action, 'id'=>$this->rss_id))) 
function auto_discovery_link_tag($type='rss', $params=array())
{
	
}

// Loadup a file into a hash
// with unique lines
function load_hash($filename)
{
	$hash = array();
	$data = file($filename);
	foreach($data as $line) {
		$hash[trim($line)] = 1;
	}
	
	return($hash);
}

// Map a specific route
// and set parameters
function map_route($pattern="", $params=array(), $except=array())
{
	global $LOCAL_PARAMS;
	
	// We already matched something
	if (defined('CURRENT_CONTROLLER') || defined('ACTION')) {
		return(false);
	}
	
	$r = preg_split('/[\?\&]/u', str_replace('/index.php', '', $pattern), -1, PREG_SPLIT_NO_EMPTY);
	$path = preg_split('/[\/]+/u', $r[0], -1, PREG_SPLIT_NO_EMPTY);	
	
	$pieces = preg_split('/[\?\&]/u', str_replace('/index.php', '', $_SERVER['PATH_INFO']), -1, PREG_SPLIT_NO_EMPTY);
	$uri = preg_split('/[\/]+/u', $pieces[0], -1, PREG_SPLIT_NO_EMPTY);

	// Skip controllers that do not match
	if ($path[0] != $uri[0] && $path[0] != ':controller') {
		return;
	}
	
	// Do things match? And is this not the default?
	if (count($path) != count($uri) && $pattern != '/:controller/:action/:id')
		return(false);
	
	if (count($uri) == 0) {
		if (defined('DEFAULT_CONTROLLER')) {
			define('CURRENT_CONTROLLER', DEFAULT_CONTROLLER);
		}
		if (defined('DEFAULT_ACTION')) {
			define('ACTION', DEFAULT_ACTION);
		}
	}
	
	// Deal with the controller and action
	$fpath = array_flip($path);
	if (((isset($fpath[':controller']) && $uri[0]) || isset($fpath[$uri[0]])) && !$params['controller'])
		define('CURRENT_CONTROLLER', $uri[0]);
	if (((isset($fpath[':action']) && $uri[1]) || isset($fpath[$uri[1]])) && !$params['action'])
		define('ACTION', $uri[1]);
	if ($params['controller']) {
		define('CURRENT_CONTROLLER', $params['controller']);
	}
	if ($params['action']) {
		define('ACTION', $params['action']);
	}

	// Get any other vars from the params
	if (count($params) > 0 && $params) {
		reset($params);
		while(list($key, $val) = each($params)) {
			if ($key == 'controller' || $key == 'action') {
				continue;
			}
			$LOCAL_PARAMS[$key] = $val;
		}
	}
		
		
	// Override any other variables
	$c = count($uri);	
	for ($x = 0; $x < $c; $x++) {
		if ($path[$x] == ':controller' || $path[$x] == ':action') {
			continue;
		}
		$p = strtolower(str_replace(':', '', $path[$x]));
		if (strpos($path[$x], ':') === 0) {
			if ($uri[$x]) {
				$LOCAL_PARAMS[$p] = $uri[$x];
			}
		}
	}

	return(true);
}

function lib_pagelist($records=0, $limit=10)
{
	$request = $_GET;
	$page = $request['page'] ? $request['page'] : 1;
	unset($request['page']);

	if($records == 0)
		return('');

	$out = '';
	$r = number_format($records);

	if($records > 1)
		$out = "$r Results ";
	else
		$out = "$r Results ";

	$links = http_build_query($request);
	$pages = ceil($records / $limit);
	if($page > 1)
	{
		$request['page'] = 1;
		$links1 = http_build_query($request);
		$request['page'] = $page - 1;
		$links2 = http_build_query($request);
		$out .= "&nbsp;<a href=\"?$links1\">&laquo; First</a>&nbsp;";
		$out .= "&nbsp;<a href=\"?$links2\">&laquo; Prev</a>&nbsp;";
	}
	
	// Create floating pagelist
	if($pages > 1)
	{
		$out .= "&nbsp;Page:&nbsp;";

		if($page < 10)
			$showstart = 1;
		else
			$showstart = $page - 5;

		if($pages > 1)
		{
			$maxpages = $showstart+10 > $pages ? $pages : $showstart+10;
			for($x = $showstart; $x <= $maxpages; $x++)
			{
				$request['page'] = $x;
				$links = http_build_query($request);
				if($page == $x)
					$out .= "&nbsp;<strong>$x</strong>&nbsp;";
				else
					$out .= "&nbsp;<a href=\"?$links\">$x</a>&nbsp;";
			}
		}
	
		if($page < $pages)
		{
			$request['page'] = $page + 1;
			$links = http_build_query($request);
			$request['page'] = $pages;
			$links2 = http_build_query($request);
			$links = http_build_query($request);
			$out .= "&nbsp;<a href=\"?$links\">Next &raquo;</a>";
			$out .= "&nbsp;<a href=\"?$links2\">Last &raquo;</a>";
		
		}
	}
	return($out);
}

// Grab a summary of some text
function lib_summarize($paragraph="", $limit=15)
{
	$tok = strtok($paragraph, " ");
	while($tok)
	{
		$text .= " $tok";
		$words++;
		if(($words >= $limit) && ((substr($tok, -1) == "!") || (substr($tok,-1) == ".")) )
			break;
		$tok = strtok(" ");
	}
	return(ltrim($text));
}

// Lameness filter
function lib_lame($txt = "")
{
	$txt = trim($txt);
	if(strlen($txt) == 0)
		return(FALSE);

	$total_count = strlen($txt);
	
	//if text contains more than 33% of uppercase charcters.
	preg_match_all("/[A-Z]/",$txt,$matches);
	$upper_count = count($matches[0]);

	if($upper_count > (.33 * $total_count)){
		return(true);
	}

	//if Any words are more than 32 characters long
	$txt_array = explode(" ",$txt);
	foreach($txt_array as $word){
		if(strlen(trim($word)) > 32){
			preg_match("/(http|.com|.net|.org)/",$word,$matches);
			if(!$matches)
				return(true);
		}
	}
	
	if (preg_match('/http|www/im', $txt)) {
		return(true);
	}

	return false;
}

/**
 * Resize a photo to different sizes
 * example: resizer($filename="" array('small'=>array('width'=>100, 'height'=>100, 'name'=>$savename),
 *                      'med'=>array(...)) )
 * 
 */
function photo_resizer($sourcefile, $path="", $sizes=array())
{
    // Upload and resize various sizes
    if (count($sizes) > 0) {
        while(list($size, $data) = each($sizes)) {
            if (!file_exists($path)) {
                return(false);
            }
             
            // Is this a valid filetype
            list($width, $height, $type, $imgstr) = getimagesize($sourcefile);
            if ($type != 2 && $type != 3) {
                return(false);
            }
     
            if ($data['width'] == 0 && $data['height'] == 0) {
                // Copy original file size
                if (!move_uploaded_file($sourcefile, $path . DS . $data['name'])) {
                    return(false);
                }
            } else {
                $size = $data['width'] . 'x' . $data['height'];
                convert_photo($sourcefile,  $path . DS . $data['name'], $size, true);
            }
        }
    }
   
    return(true);
}


/**
 * A generic upload conversion function
 */
function convert_photo($infile,  $destfile="", $size="100x100", $square=false)
{
    $output = array();
    $safeinfile = escapeshellarg($infile);
    $destfile = escapeshellarg(trim($destfile));

    // Convert the file
    $errors = 0;
    if ($square == true) {
        // Small size
        list($width, $height) = getimagesize($infile);
        $gravity = $height > $width ? 'north' : 'center';
        $s = substr($size, 0, 3) * 2;
        $cmd = "convert {$safeinfile} -thumbnail x{$s} -resize '{$s}x<' -resize 50% -gravity {$gravity} -crop {$size}+0+0 +repage {$destfile}";
    } else {
        // Other sizes
        $cmd = "convert {$safeinfile} -thumbnail '{$size}>' {$destfile}";
    }

    $output[] = $cmd;
    exec($cmd, $output, $retval);
    $errors += $retval;

    if ($errors > 0) {
		return(false);
    }

    return(true);
}

function randomkeys($length)
{
    $pattern = "1234567890abcdefghijklmnopqrstuvwxyz";
    $key  = $pattern{rand(0,35)};
    for($i=1;$i<$length;$i++)
    {
        $key .= $pattern{rand(0,35)};
    }
    return $key;
}

// Sweep the cache of the current (or passed in) page
function sweep($page=false) {
    if (!$page) {
        $page = $_SERVER['REQUEST_URI'];
    }
    
    if (!preg_match('/\.html$/', $page)) {
        $page .= '.html';
    }
    
    $filename = 'cache/' . $page;
    if (file_exists($filename)) {
        unlink($filename);
        return(true);
    }
    
    return(false);
}

// Truncate a string
function truncate($str='', $len=30, $show='...') {
	if (strlen(trim($str)) <= $len) {
		return(trim($str));
	}
	
	if (strlen(trim($str)) > $len) {
		return(substr($str,0,$len - strlen($show)) . $show);
	}
}

// Make a permalink
function permalink($str='') {
    if (strlen($str) == 0) {
        return($str);
    }
    
	$str = str_replace("'", '', $str);
    return(preg_replace('/[\W]+/', '-', strtolower(trim($str))));
}

// highlight a word
function highlight($haystack='', $needle='') {
    $regex = "/({$needle})/i";
    return(preg_replace($regex, "<strong>$1</strong>", $haystack));
}

?>
