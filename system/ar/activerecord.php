<?php
// Ericson Smith
// November 2007

// Something that mimics activeRecord
class ActiveRecord
{ 
    var $driver;
    var $default_connection;
    
	var $validated;
	var $errors;
	var $num_records;
	var $skip_relations;
	var $connection_name;
	
    function __construct($connection_name=false)
    {
        // parent::__construct();

        // Load the correct driver
        if ($connection_name && $GLOBALS['AR_DB_CONFIG'][$connection_name]) {
            $this->default_connection = $GLOBALS['AR_DB_CONFIG'][$connection_name];           
        } elseif ($this->driver->db_connection && $GLOBALS['AR_DB_CONFIG'][$this->driver->db_connection]) {
            $this->default_connection = $GLOBALS['AR_DB_CONFIG'][$this->driver->db_connection];
        } else {
            $this->default_connection = $GLOBALS['AR_DB_CONFIG'][AR_DEFAULT_DB];
            $connection_name = AR_DEFAULT_DB;
        }        
        
        // Load class
        $class_name = 'Ar_' . ucfirst($this->default_connection['driver']);
        
        $this->driver = new $class_name;
        
		// Connect & get the schema
		$this->driver->db_connect($connection_name);
        //$this->driver->tablename = str_replace('ar_', '', strtolower(get_class($this)));
        $this->driver->tablename = strtolower(get_class($this));
		$this->driver->db_schema();
        
		$this->validated = true;
		$this->num_records = 0;
		
		$this->skip_relations = false;

		$this->connection_name = $connection_name;

		// Setup the record
		while (list($key,$val) = @each($vars)) {
				$this->$key = $val;	
		}
    }
    
    // Simulates some super-simple actions
    // eg: find_by_user_id, find_by_name, find_or_create_by_name
    function __call($m = '', $a = '')
    {
		$m = trim(strtolower($m));
		
		if (preg_match('/^find_by_(.*?)$/iu', $m, $match)) {
			// Simulate find_by
			return($this->find(array('first'=>true, 'conditions'=>$this->build_generic_conditions($match, $a))));
		} elseif (preg_match('/^find_all_by_(.*?)$/iu', $m, $match)) {
			// Simulate find_all_by	
			return($this->find(array('conditions'=>$this->build_generic_conditions($match, $a))));

		} elseif (preg_match('/^find_or_create_by_(.*?)$/iu', $m, $match)) {
			// Simulate find_or_create_by
			$this->find(array('first'=>true, 'conditions'=>$this->build_generic_conditions($match, $a)));
			if (!$this->driver->record[$this->driver->primary_key_name]) {
				$parts = explode('_and_', $match[1]);
				$c = count($parts);
				$this->driver->record = array();
				for($x = 0; $x < $c; $x++) {
					$this->driver->record[$parts[$x]] = $a[$x];
				}
				$this->save();
				return($this->find(array('first'=>true, 'conditions'=>$this->build_generic_conditions($match, $a))));
			} else {
				return($this->driver->record);
			}
		} elseif (preg_match('/^find_first$/iu', $m, $match)) {
			// Find first
			if ($a) {
				// with conditions
				$ret = $this->find(array('conditions'=>$this->build_generic_conditions($match, $a), 'limit'=>1));
			} else {
				// without conditions
				$ret = $this->find(array('limit'=>1));
			}

			if ($ret) {
				return($ret[0]);
			}

			return(false);
		}
		
		// Get errors_for
		if (preg_match('/^errors_for_(.*?)$/', $u, $match)) {
			return($this->errors);
		}
    }
    
    function __get($key)
    {
		// We're going to get interesting here
		return($this->driver->schema[$key] ? $this->driver->record[$key] : null);
    }
    
    function __set($key, $val)
    {
		if($this->driver->schema[$key])
        	$this->driver->record[$key] = $val;
    }
 
    // Save a record in $record
    function save($data=array())
    {
		if (!$this->validated)
			return(false);
		
		// Did we pass in data?
		if (count($data) > 0) {
			while(list($key,$val) = each($data)) {
				$this->driver->record[$key] = $val;
			}
		}

        if (isset($this->driver->record[$this->driver->primary_key_name])) {
			// Run before action
			$this->before_update();
			$this->before_save();
			
            // Update
			if ($this->driver->schema['updated_on'] && !$this->driver->record['updated_on']) {
				$this->driver->record['updated_on'] = now();
			}

			$set = array();
			$query = array();
			$query[] = 'sql';
			reset($this->driver->record);
			while(list($key,$val) = each($this->driver->record)) {
				if (is_array($val)) {
					// Kill related stuff
					continue;
				}
				$set[] = $key . '=?';
				$query[] = $val;
			}
			 
			$query[0] = 'UPDATE ' . $this->driver->tablename . ' SET ' . implode(', ', $set) . ' WHERE ' . $this->driver->primary_key_name . '=?';
			$query[] = $this->driver->record[$this->driver->primary_key_name];
			
			$this->driver->db_query($query);
			$this->find($this->driver->record[$this->driver->primary_key_name]);
			
			// Run after action
			$this->after_update();
			$this->after_save();
        } else {
			// Run before action
			$this->before_create();
			$this->before_save();
			
			// Set created_on
			if ($this->driver->schema['created_on'] && !$this->driver->record['updated_on']) {
				$this->driver->record['created_on'] = now();
			}
			
			// Do we have an IP address?
			if ($this->driver->schema['ip'] && !$this->driver->record['ip']) {
				$this->driver->record['ip'] = substr($_SERVER['REMOTE_ADDR'],0,15);
			}
			
			// Do we have validation errors?
			if ($this->errors && count($this->errors) > 0) {
				return(false);
			}

            $sql  = 'INSERT INTO ' . $this->driver->tablename . ' (';
			$sql .= implode(', ', array_keys($this->driver->record)) . ') VALUES (' . implode(', ', array_fill(0, count($this->driver->record), '?')) . ')';
			
			$query = array();
			$query[] = $sql;
			reset($this->driver->record);
			foreach($this->driver->record as $r) {
				$query[] = $r;
			}
			
            // Save it and reload
			$this->driver->db_query($query);
			$this->find($this->driver->db_last_insert_id());
			
			// Run after action
			$this->after_create();
			$this->after_save();
        }
		
		return(true);
    }
    
	function delete($params=false)
	{
		if(is_bool($params) || !$params) {
			return(true);
		}
		
		// A direct delete
		if(is_numeric($params)) {
			$this->driver->db_query(array("DELETE FROM {$this->driver->tablename} WHERE {$this->driver->primary_key_name}=?", $params));
			return(true);
		}
		
		// With conditions
		if (is_array( $params ) && is_numeric( implode( array_keys( $params ) ) )) {
			$newhash = array();
			$newhash['conditions'] = $params;
			$query[] = $this->driver->build_query($newhash, 'DELETE');
			if (count($this->driver->binds) > 0) {
				foreach($this->driver->binds as $bind)
					$query[] = $bind;
			}
        
			$this->driver->db_query($query);			
			return(true);
		}
	}

	// Do a sql query with possible parameters
	function query($query=false) 
	{
		$this->driver->db_query($query);
		if (preg_match('/^SELECT/i', $this->driver->last_sql)) {
			return($this->driver->db_all());
		} else {
			return(true);
		}
	}
		
    // Find one or more records and place them in $record
    function find($params=false)
    {
		$this->driver->query = array();
		$this->driver->binds = array();
		
        // Get outta here if no params
        if (is_bool($params) || !$params) {
            return(true);
        }
        
        // Find one based on current primary key
        if (is_numeric($params)) {
            $this->driver->db_query(array("SELECT * FROM {$this->driver->tablename} WHERE {$this->driver->primary_key_name}=? LIMIT 1", $params));
            
            if($this->driver->statement_handle) {
                $this->driver->record = $this->driver->db_next();
				$this->num_records = 1;
			}
            
			$this->do_relations();	
				
            return(count($this->driver->record) > 0 ? $this->driver->record : false);
        }
        
        // Just submit a simple string and return multiple records
        if (is_string($params)) {
            $this->driver->db_query($params);
            
            if($this->driver->statement_handle) {
                $this->driver->record = $this->driver->db_all();
				$this->num_records = count($this->driver->record);
			}
            
			$this->do_relations();
				
            return(count($this->driver->record) > 0 ? $this->driver->record : false); 
        }

        // Could be a standard numeric based array, 
        // so we just have a basic search with conditions
        if (is_array( $params ) && is_numeric( implode( array_keys( $params ) ) )) {
			if (preg_match('/^SELECT|^UPDATE|^INSERT|^DESC|^CALL|^DELETE/ui', $params[0])) {
				$this->driver->db_query($params);
				if($this->driver->statement_handle && preg_match('/^SELECT|^CALL/ui', $params[0])) {
					$this->driver->record = $this->driver->db_all();
					return(count($this->driver->record) > 0 ? $this->driver->record : false);
				} else {
					return(true);
				}
			} else {
				$newhash = array();
				$newhash['conditions'] = $params;
				$params = $newhash;
			}
        }

        // at this point, if not an array, return
        if(!is_array($params)) {
            return(false);
        }

        // First record only?
        if ($params['first'] == 'true') {
            $params['limit'] = 1;
            if (!$params['order']) {
                $params['order'] = $this->driver->primary_key_name;
            }
        }

		// Override offset if we have :page
		if ($params['page']) {
			$params['offset'] = ($params['page'] -1) * $params['limit'];
		}

        $query = array();		

        $query[] = $this->driver->build_query($params);

        if (count($this->driver->binds) > 0) {
            foreach($this->driver->binds as $bind)
                $query[] = $bind;
        }
        
        $this->driver->db_query($query);
        
        if ($params['first']) {
            if($this->driver->statement_handle)
                $this->driver->record = $this->driver->db_next();
				$this->num_records = 1;
        } else {
            if($this->driver->statement_handle)
			{
                $this->driver->record = $this->driver->db_all();
				$this->num_records = count($this->driver->record);
			}
        }
		
		$this->do_relations();		
		
		return(count($this->driver->record) > 0 ? $this->driver->record : false);
    }

	// Work out relationships and load records
	// We need to do this by joins later on!
	function do_relations()
	{
		if ($this->skip_relations) {
			return(false);
		}

		if ($this->num_records == 0)
			return(false);
		
		if ($this->belongs_to) {
			reset($this->belongs_to);
			while(list($table, $rel_fieldname) = each($this->belongs_to)) {
				$obj = new $table($this->connection_name);
				if ($this->driver->record[$this->driver->primary_key_name]) {
					$id = $this->driver->record[$this->driver->primary_key_name];
					$this->driver->record[$table] = $obj->find($this->driver->record[$rel_fieldname]);  
				} else {
					for($x = 0; $x < $this->num_records; $x++) {
						$id = $this->driver->record[$this->driver->primary_key_name];
						$this->driver->record[$x][$table] = $obj->find($this->driver->record[$x][$rel_fieldname]);
					}
				}
				unset($obj);
			}
		}
		
		// Belongs to -- one-to-one
		if ($this->has_many) {
			reset($this->has_many);
			while(list($table, $rel_fieldname) = each($this->has_many)) {
				$obj = new $table($this->connection_name);
				if ($this->driver->record[$this->driver->primary_key_name]) {
					$id = $this->driver->record['id'];
					$this->driver->record[$table] = $obj->find(array('all' => true, 'conditions'=>array("{$rel_fieldname}=?", $id), 'order' => 'id'));  
				} else {
					for($x = 0; $x < $this->num_records; $x++) {
						$id = $this->driver->record[$x]['id'];
						$this->driver->record[$x][$table] = $obj->find(array('all' => true, 'conditions'=>array("{$rel_fieldname}=?", $id), 'order' => 'id'));
					}
				}
				unset($obj);
			}
		}
		
	}
	
	function to_serialize()
	{
		return(serialize($this->driver->record));
	}
	
	function to_xml()
	{
		return(false);
	}
	
	function to_xml_rpc()
	{
		if (function_exists('xmlrpc_encode')) {
			return(xmlrpc_encode($this->driver->record));
		}
		
		return(false);
	}
	
	// Build conditions
	private function build_generic_conditions($matches=array(), $params=array())
	{
		$parts = explode('_and_', $matches[1]);
		for($x = 0; $x < count($parts); $x++) {
			$parts[$x] .= '=?';
		}
		$conditions = array();
		$conditions[] = implode(' AND ', $parts);
		foreach($params as $param) {
			$conditions[] = $param;
		}
		
		return($conditions);
	}
	
	// Before update
	function before_update()
	{
	}
	
	// Before create
	function before_create()
	{
	}
	
	// Before save
	function before_save()
	{
	}

	// After save
	function after_save()
	{
	}

	// After update
	function after_update()
	{
	}
	
	// After create
	function after_create()
	{
	}
	
	// Before delete
	function before_delete()
	{
	}
	
	// After delete
	function after_delete()
	{
	}
	
	// ------ Validation Routines -------- //
	function valicates_acceptance_of()
	{
	}
	
	function validates_associated()
	{
	}
	
	function validates_confirmation_of()
	{
	}
	
	function validates_exclusion_of()
	{
	}
	
	function validates_format_of($fieldname='', $pattern='', $must_exist=false, $msg = '')
	{
		$fname = ucfirst($fieldname);

		if (!$msg) {
			$msg = ucfirst($fieldname) . ' must be between ' . $params['minimum'] . ' and ' . $params['maximum'] . ' characters';
		}

		if (!array_key_exists($fieldname, $this->driver->record)) {
			$this->errors[] = array($fieldname, "{$fname} does not exist");
			return(false);
		} elseif (!$this->driver->record[$fieldname] && $must_exist == false) {
			return(true);
		}

		if ($must_exist && strlen($this->driver->record[$fieldname]) == 0) {
			$this->errors[] =  array($fieldname, "{$fname} must be entered");
			return(false);
		}

		if (!preg_match($pattern, $this->driver->record[$fieldname])) {
			$this->errors[] = array($fieldname, "{$fname} has invalid format");
			return(false);
		}
	}
	
	function validates_inclusion_of()
	{
	}
	
	function validates_length_of($fieldname='', $params=array(), $msg='')
	{
		$fname = ucfirst($fieldname);

		if (!array_key_exists($fieldname, $this->driver->record)) {
			$this->errors[] = array($fieldname, "{$fname} does not exist");
			return(false);
		}
		
		if (!$msg) {
			$msg = ucfirst($fieldname) . ' must be between ' . $params['minimum'] . ' and ' . $params['maximum'] . ' characters';
		}

		if ($params['minimum'] && strlen($this->driver->record[$fieldname]) < $params['minimum'] ) {
			$this->errors[] = array($fieldname, $msg);
		}
		
		if ($params['maximum'] && strlen($this->driver->record[$fieldname]) > $params['maximum'] ) {
			$this->errors[] = array($fieldname, $msg);
		}	
	}

	function validates_numericality_of($fieldname='', $msg='')
	{
		if(!is_numeric($this->driver->record[$fieldname])) {
			$this->errors[] = array($fieldname, $msg);
		}
	}
	
	function validates_presence_of($fieldname='', $msg='')
	{
	}
	
	function validates_size_of()
	{
	}
	
	function validates_uniqueness_of($fieldname='', $msg='')
	{
	
	}
	
	function validates_on_create()
	{
	}
	
	function validates_on_update()
	{
	}
	
	// Show errors for this object
	function show_errors()
	{
		if(count($this->errors) == 0) {
			return(false);
		}

		$out = '<div id="error_messages"><p>The following errors prevented your information from being saved:</p><ul>';
		foreach ($this->errors as $error) {
			$out .= '<li>' . $error[1] . '</li>';
		}
			
		$out .= '</ul></div>';

		return($out);
	}

}

?>
