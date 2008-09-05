<?php

// Put your controller wide methods in here

class Application_Controller extends Controller
{
	function _autoexec() {
		// Runs before anything in any controller
	}

	// This runs everytime we hit our controller
	function application_default() {
		$this->human = false;
		
		// Is this person a human? We do a few checks
		// only works if you have google analytics on your site
		if (!$_COOKIE['__utmb'] || !$_COOKIE['__utmc']) {
			$this->human = true;
		}
        
        // Lets get a form variable to prevent robots
        if (!$this->params['utform']) {
           $_SESSION['utform'] = md5(uniqid(rand(), true)); 
        }
        
        if ($this->params['utform'] && $this->params['utforms'] != $_SESSION['utform']) {
            // This is a forged form
            redirect_to('/welcome/sorry');
            return;
        }
        
        // Have a global variable for the cross site posting attack
        $this->utform = false;
        if ($_SESSION['utform']) {
            $this->utform = $_SESSION['utform'];
        }
		
        // User
        $this->user = $_SESSION['user'] ? $_SESSION['user'] : false; 
        
		// Automatically login old users
		if (!$this->user && $_COOKIE[DEFAULT_COOKIE] && strlen($_COOKIE[DEFAULT_COOKIE]) == 13) {
			$u = new Users;
			$this->user = $u->find_by_session_id($_COOKIE[DEFAULT_COOKIE]);
			if ($this->user) {
				$_SESSION['user'] = $this->user;
				$u->last_login = date('Y-m-d H:i:s');
				$u->ip = $_SERVER['REMOTE_ADDR'];
				$u->save();
			}
		}
	}
    
    // Reqire logins and save session
	// A transparent way to force someone to login, while saving
	// the submissions of a form in sessions
    function login_required($restore_params = false) {
        
        if (!$_SESSION['user']) {
            if ($restore_params) {
                $_SESSION['params'] = $this->params;
            }
            
            $_SESSION['return_url'] = $_SERVER['REQUEST_URI'];
            redirect_to('/user/signup');
            return;            
        }
        
        if ($_SESSION['user'] && $_SESSION['params']) {
            if ($restore_params) {
                $this->params = $_SESSION['params'];
            }
            unset($_SESSION['params']);          
        }
        
        return(true);
    }
}

?>
