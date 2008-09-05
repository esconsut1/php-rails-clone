<?

class welcome_controller extends application_controller
{
	function _autorun() {
		#$this->cache = true;
	}

	function index() {
        $this->title = 'Welcome to the W3matter Framework';
		$this->description = 'This is my Meta Description';
		$this->keywords = 'These are my meta keywords, a, b, c';
	}
    
    function sorry() {
        $this->title = 'Don\'t be a douchebag';
    }
    
}

?>
