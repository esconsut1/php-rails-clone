<?
class user_controller extends application_controller
{
    function _autorun() {
        // We need to cache this one
    }

    function index() {
    }
    
	// Signup
    function signup() {
        $u = new Users;
        
        if ($this->params['name'] && $this->params['password'] && $_POST['name'] && $_POST['password']) {
            $name = strtolower(trim($this->params['name']));
            $email = @strtolower(@trim($this->params['email']));
            
            $dup = $u->find_by_name($name);
            if ($email) {
                $dup = $u->find_by_email($email);
            }
            
            if (!$dup) {
                $u = new Users;
                $u->name = $name;
                $u->email = $email;
                $u->password = strtolower(trim($this->params['password']));
                $u->ip = $_SERVER['REMOTE_ADDR'];
                $u->session_id = uniqid('');
                if ($u->save()) {
                    $_SESSION['user'] = $u->driver->record;
                    
                    if ($this->params['remember'] == 1) {
                        // Remember this user for 90 days
                        setcookie(DEFAULT_COOKIE, $u->session_id, time() + (86400 * 90));
                    }
                    
                    $url = '/';
                    if ($_SESSION['return_url']) {
                        $url = $_SESSION['return_url'];
                        unset($_SESSION['return_url']);
                    }
                    
                    redirect_to($url);
                    return;
                }
            } else {
                $this->flash('That username is already taken!');
            }
        }
        
        $this->title = 'Become a member';
        $this->crumbs = array('Signup');
    }
    
	// Login
    function login() {
        $u = new Users;
        
        if ($this->params['name'] && $this->params['password'] && $_POST['name'] && $_POST['password']) {
            $name = strtolower(trim($this->params['name']));
            $password = strtolower(trim($this->params['password']));
            if ($user = $u->find_by_name_and_password($name, $password)) {

				// Remmeber me forever
				if ($this->params['remember'] == 1) {
					setcookie(DEFAULT_COOKIE, $user['session_id'], time() + (86400 * 360), "/");
				}

                $_SESSION['user'] = $user;
                $url = '/';
                if ($_SESSION['return_url']) {
                    $url = $_SESSION['return_url'];
                    unset($_SESSION['return_url']);
                }
                
                redirect_to($url);
                return;                
            } else {
                $this->flash('Incorrect username or password!');
            }
            
        }
        
        $this->title = 'Login';
        $this->crumbs = array('Login');
    }
    
	// Logout
    function logout() {
        setcookie(DEFAULT_COOKIE, 'blah', time() - 86400);
        unset($_SESSION['user']);
        
        redirect_to('/');
        return;
    }

	// Resend a user's password
	function resend() {
		// to-do
	}
    
}
?>
