<?php
// Our base controller class
// Module\Controller classes inherit from this
class Controller 
{
    public $page;
    public $template;
    public $title;
    public $content;
    
    // Who am I
    public $current_controller;
    public $current_action;
	public $default;
	
	// Other stuff
	public $params;
	public $request;
	public $rendered;
	public $content_for_layout;
	public $flash_notice;
	
	// Actions
	public $before_action;
	public $after_action;
    
    function __construct()
    {
		global $LOCAL_PARAMS;

		// Measure how long this took
		$start_render = microtime(true);
		
		// Setup request
		$this->request = $_SERVER;
		$this->current_controller = CURRENT_CONTROLLER;
		$this->request_method = $_SERVER['REQUEST_METHOD'];
		$this->params = array_merge($_POST, $_GET, $_REQUEST, $LOCAL_PARAMS);

		// Setup some defaults
		$this->rendered = false;
		$this->flash_notice = $_SESSION['flash'];
		$this->default = $GLOBALS['DEFAULT'];
		
		// Run Application Default
		$this->application_default();
		
		// Lets create an _autorun action
		if (method_exists($this, '_autorun')) {
			$this->_autorun();
		}
		
		// Do the before actions
		if (is_array($this->before_action)) {
			foreach($this->before_action as $act) {
				if (method_exists($this, $act)) {
					$this->$act();
				} elseif (function_exists($act)) {
					$act();
				}
			}
		}
		
		// What method to call?
		if (defined('ACTION')) {
			$this->current_action = ACTION;
			$this->template = APP_VIEWS . DS . $this->current_controller . DS . $this->current_action . '.phtml';
		} else {
			$this->current_action = 'index';
			$this->template = APP_VIEWS . DS . $this->current_controller . DS . $this->current_action . '.phtml';
		}
		
		$default_action = '_default';
		if (method_exists($this, $this->current_action)) {
			$act = $this->current_action;
			$this->$act();
		} elseif (method_exists($this, $default_action)) {
			$this->$default_action();
		} else {
			throw_error('ACTION "' .$this->current_action. '" does not exist');
		}
		
		// Do the before actions
		if (is_array($this->after_action)) {
			foreach($this->after_action as $act) {
				if (method_exists($this, $act)) {
					$this->$act();
				}
			}
		}		
		
		// Render our template
		if (!$this->rendered)
			$this->output();
		
		$_SESSION['flash'] = '';
		$GLOBALS['render_time'] = microtime(true) - $start_render;
    }
    
    // Spit out our output using our template
    function output()
    {
		$this->rendered = true;
		
		if (file_exists($this->template)) {
			ob_start();
			include_once($this->template);
			$this->content_for_layout = ob_get_contents();
			ob_end_clean();
		}

		// Select and use a specified layout
		if (file_exists(APP_VIEWS . DS . 'layouts' . DS . $this->current_controller . '.phtml')) {
			// Use the specified layout
			ob_start();
			include_once(APP_VIEWS . DS . 'layouts' . DS . $this->current_controller . '.phtml');
			$output = ob_get_contents();
			ob_end_clean();
		} elseif(file_exists(APP_VIEWS . DS . 'layouts' . DS . 'application.phtml')) {
			// Use the application.php template
			ob_start();
			include_once(APP_VIEWS . DS . 'layouts' . DS . 'application.phtml');
			$output = ob_get_contents();
			ob_end_clean();
		} else {
			// Just spit it out
			$output =  $this->content_for_layout;
		}

		// echo our output

		if ($this->cache == true) {
			$uri = $_SERVER['REQUEST_URI'] != '/' ? $_SERVER['REQUEST_URI'] : '/index';
			$filename = getcwd() . '/cache' . $uri . '.shtml';
			if (!file_exists(dirname($filename))) {
				mkdir(dirname($filename), 0777, true);
			}

			// Append ATX to the output 
			$o = str_replace('<!-- atx -->', '<!--#include virtual="/cgi-bin/atx/in.cgi" -->', $output);
			$o .= '<!-- cached: ' . date('r') . ' -->';

			file_put_contents($filename, $o, LOCK_EX);
			logger('CACHED: ' . $filename);
		} else {
			if ($_SERVER['HTTP_HOST'] == 'www.sublimedirectory.com') {
				virtual("/cgi-bin/atx/in.cgi");	
			}
		}

		echo $output;

		return(true);
    }
    
    // Render a template to a string
    // useful for AJAXy goodness
    // We just pass in the partial template path like:
	// 'view/template'
    function render_to_string($name="")
    {
        $parts = explode('/', $name);
		$c = count($parts);
		
		if ($c > 1) {
			$parts[$c - 1] = "_" . $parts[$c -1];
			$path = APP_VIEWS . DS . implode(DS, $parts) . '.phtml';
		} else {
			$path = APP_VIEWS . DS . $this->current_controller . '/_' . $name . '.phtml';
		}
		
		$ret = "";
        if (file_exists($path)) {
			$rstart = microtime(true);
            ob_start();
            include($path);
            $ret = ob_get_contents();
            ob_end_clean();
			$rstop = microtime(true) - $rstart;
			logger('RENDER PARTIAL: [' . $path . '] ' . number_format($rstop,6));
        }
        
        return($ret);
    }
    
    // Render to the screen
    function render_partial($name="")
    {
		// $this->rendered = true;
        echo $this->render_to_string($name);
    }

	// Render Nothing
	function render_nothing()
	{
		$this->rendered = true;
	}
	
    // Render a different template
    function render_template($name) 
    {
        if (!preg_match('/\//', $name)) {
            $this->template = APP_VIEWS . DS . $this->current_controller . DS . $name . '.phtml';
        } else {
            $this->template = APP_VIEWS . DS . $name . '.phtml';
        }
    }
    
	// Log the output of all this
	function logger()
	{
		// $_SESSION['flash'] = false;	
		
		// Log the request
		logger($_SERVER['REQUEST_METHOD'] . ' REQUEST: ' . $_SERVER['QUERY_STRING']);
		
		$total_time = number_format(microtime(true) - START_TIME,6);

		$str  = 'SOURCE   : [' . $_SERVER['REMOTE_ADDR'] . ', ' . $_SERVER['HTTP_USER_AGENT'] . "]\n";
		$str .= 'REFERER  : [' . $_SERVER['HTTP_REFERER'] . "]\n";
		$str .= 'COMPLETED: [TOTAL: ' . $total_time . ' secs, DB: '. number_format($GLOBALS['db_time'],6) .' secs, RENDER: ' . 
				number_format($GLOBALS['render_time'],6) . '] with REQUEST: [ ' . $_SERVER['PATH_INFO'] . ' ]';
		logger($str);
	}
    
	// Call functions not in here
	// Possibly from autoload
	// So we can nicely extend this class
	function __call($m, $c)
	{
		$m($c);
	}
	
    function __destruct()
    {
		
    }
	
	//----- Form utilities that do not deserve to be here. We need to better design this later -----//
	
	// Input tag
	function input_tag($object="", $method="", $html=array())
	{
		$parts = array();
		$parts[] = 'type="text"';
		$parts[] = 'name="' . $object . '[' . $method . ']"';
		$parts[] = 'id="' . $object . '_' . $method . '"';
		
		if($this->$object->$method) {
			$parts[] = 'value="' . htmlspecialchars($this->$object->$method, ENT_QUOTES, 'UTF-8') . '"';
		} else {
			$parts[] = 'value=""';
		}
		
		while(list($key,$val) = each($html)) {
			$parts[] = $key . '="' . $val . '"';
		}
		
		return('<input ' . implode(' ', $parts) . ' />');
	}

	// Input tag
	function password_tag($object="", $method="", $html=array())
	{
		$parts = array();
		$parts[] = 'type="password"';
		$parts[] = 'name="' . $object . '[' . $method . ']"';
		$parts[] = 'id="' . $object . '_' . $method . '"';
		
		if($this->$object->$method) {
			$parts[] = 'value="' . htmlspecialchars($this->$object->$method, ENT_QUOTES, 'UTF-8') . '"';
		} else {
			$parts[] = 'value=""';
		}
		
		while(list($key,$val) = each($html)) {
			$parts[] = $key . '="' . $val . '"';
		}
		
		return('<input ' . implode(' ', $parts) . ' />');
	}	
	
	// Input tag
	function hidden_tag($object="", $method="")
	{
		$parts = array();
		$parts[] = 'type="hidden"';
		$parts[] = 'name="' . $object . '[' . $method . ']"';
		$parts[] = 'id="' . $object . '_' . $method . '"';
		
		if($this->$object->$method) {
			$parts[] = 'value="' . htmlspecialchars($this->$object->$method, ENT_QUOTES, 'UTF-8') . '"';
		} else {
			$parts[] = 'value=""';
		}
		
		return('<input ' . implode(' ', $parts) . ' />');
	}
	
	// Textarea
	function text_tag($object="", $method="", $html=array())
	{
		$parts = array();
		$parts[] = 'name="' . $object . '[' . $method . ']"';
		$parts[] = 'id="' . $object . '_' . $method . '"';
		while (list($key,$val) = each($html)) {
			$parts[] = $key . '="' . $val . '"';
		}
				
		$ret = '<textarea ' . implode(' ', $parts) . '>';
		if($this->$object->$method) {
			$ret .= htmlspecialchars($this->$object->$method, ENT_QUOTES, 'UTF-8') ;
		}
		$ret .= '</textarea>';
		
		return($ret);
	}

	// Checkbox
	function checkbox_tag($object, $method, $checked_value=1, $unchecked_value=0, $html=array())
	{
		$parts = array();
		$parts[] = 'name="' . $object . '[' . $method . ']"';
		$parts[] = 'id="' . $object . '_' . $method . '"';
		while (list($key,$val) = each($html)) {
			$parts[] = $key . '="' . $val . '"';
		}
		
		$parts[] = 'value="' . $checked_value . '"';
		$parts[] = $this->$object->$method == $checked_value ? 'CHECKED' : '';
		
		return('<input type="checkbox" ' . implode(' ', $parts) . ' />');
	}
	
	// Radio Button
	function radio_tag($object, $method, $checked_value=1, $unchecked_value=0, $html=array())
	{
		$parts = array();
		$parts[] = 'name="' . $object . '[' . $method . ']"';
		$parts[] = 'id="' . $object . '_' . $method . '"';
		while (list($key,$val) = each($html)) {
			$parts[] = $key . '="' . $val . '"';
		}
		
		$parts[] = 'value="' . $checked_value . '"';
		$parts[] = $this->$object->$method == $checked_value ? 'CHECKED' : '';
		
		return('<input type="radio" ' . implode(' ', $parts) . ' />');
	}	
	
	// Select box
	function select_tag($object, $method, $options = array(), $html=array())
	{
		$parts = array();
		$parts[] = 'name="' . $object . '[' . $method . ']"';
		$parts[] = 'id="' . $object . '_' . $method . '"';
		while (list($key,$val) = each($html)) {
			$parts[] = $key . '="' . $val . '"';
		}		
		
		return('<select ' . implode(' ', $parts) . '>' . select_options($options, "", "", $this->$object->$method) . '</select>'); 
	}
	
	// Flash
	function flash($msg="")
	{
		$_SESSION['flash'] = $this->flash_notice = $msg;
	}
	
}



?>
