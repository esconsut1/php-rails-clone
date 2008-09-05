<?php

/**
* Basic MySQL driver
*
*/
class Ar_Mysql
{
    public $db;
    public $statement_handle;
    public $tablename;
    public $primary_key_name;  
	public $sequence_name;
	public $schema;
	public $db_conn;

    public $default;  
    public $readonly;
    
    public $record;
    public $alive;
    public $binds;
    public $sql;    
	public $duration;
    
	public $db_connection;

    /**
    * Connect to the db
    */
    function __construct()
    {
        // Set some more defaults
		$this->db_connection = false;
        $this->readonly = false;
		$this->record = array();
        
        return(true);
    }
    
	// Connect to the DB
	function db_connect($connection_name=false) {

        if ($this->db) {
            return(true);
        }
        
        // Choose what to connect to
        if ($connection_name && $GLOBALS['AR_DB_CONFIG'][$connection_name]) {
            $this->default = $GLOBALS['AR_DB_CONFIG'][$connection_name];
            $this->db_connection = $connection_name;            
        } elseif ($this->db_connection && $GLOBALS['AR_DB_CONFIG'][$this->db_connection]) {
            $this->default = $GLOBALS['AR_DB_CONFIG'][$this->db_connection];
        } else {
            $this->default = $GLOBALS['AR_DB_CONFIG'][AR_DEFAULT_DB];
            $this->db_connection = AR_DEFAULT_DB;
        }
        
        // Get outta here if we have no good setting
		if (!$this->default['hostname']) {
			die('Please setup database.ini');
		}
        
		// Connect
		if ($this->db = @mysql_connect($this->default['hostname'], $this->default['login'], $this->default['password'])) {
			if (!@mysql_select_db($this->default['database'], $this->db)) {
				// die('Unable to connect to database: ' . $this->default['database']);
			}
		} else {
			// die('Unable to connect to database: ' . $this->default['database']);
		}

		return(true);
	}

    function db_escape($str="")
    {
        return(mysql_real_escape_string($str, $this->db));
    }
    
    function db_last_insert_id()
    {	
		return(mysql_insert_id($this->db));
    }

	// gets the sequence
	function db_next_insert_id()
	{
		return(false);
	}
    
    /**
    * Does a query with placeholders
    */
    function db_query($q)
    {
		$this->db_connect();
        $this->statement_handle = null;
        
		$this->sql = $this->db_merge($q);
		$this->last_sql = $this->sql;

		$start = microtime(true);
		$this->statement_handle = mysql_query($this->sql, $this->db);
		$this->duration = microtime(true) - $start;
		ar_logger('SQL: [' . number_format($this->duration,6) . '] ' . $this->sql, $this->db_connection);
		
		$GLOBALS['db_time'] += $this->duration;
		
		if ($this->statement_handle === false) {
			$errorstr = 'SQL ERROR: ' . mysql_error($this->db) . "\nSTATEMENT: " . $this->sql;
			//debug($errorstr);
			ar_logger($errorstr, $this->db_connection);
			throw_error($errorstr);
		}
		$this->sql = array();
    }
	
	/**
	* Load the schema for this table
	*/
	function db_schema()
	{
        // Loadup from the cache
        $cache_file = '/tmp/ar_schema-' . $this->db_connection . '-' . $this->tablename . '.cache';
        if (file_exists($cache_file)) {
            $a = unserialize(file_get_contents($cache_file));
            $this->primary_key_name = $a['primary_key_name'];
            $this->schema = $a['schema'];
            
            if (!$t = @filectime($cache_file)) {
                if ($t > time() - 3600) {
                    @unlink($cache_file);
                }
            }
            
            return(true);
        }
        
		$this->db_query(array("SHOW COLUMNS FROM {$this->tablename}"));
		$fields = $this->db_all();
		
		$this->schema = array();
		foreach ($fields as $rec) {
			$this->schema[$rec['Field']] = array();
			$this->schema[$rec['Field']]['type'] = preg_replace('/\(.*?\).*?$/', '', $rec['Type']);
			$this->schema[$rec['Field']]['default'] = $rec['Default'];
			$this->schema[$rec['Field']]['key'] = $rec['Key'];
			$this->schema[$rec['Field']]['length'] = preg_replace('/^.*?\(|\).*?$/', '', $rec['Type']);
			$this->schema[$rec['Field']]['type'] = preg_match('/int/i', $rec['Type']) ? 'integer' : $this->schema[$rec['Field']]['type'];
			if ($rec['Key'] == 'PRI' && !$this->primary_key_name) {
				$this->primary_key_name = $rec['Field'];
			}
		}
		
		// Set default primary_key name
		if (!$this->primary_key_name) {
			$this->primary_key_name = 'id';
		}
        
        // Write to the cache
        $cache = array('schema' => $this->schema, 'primary_key_name' => $this->primary_key_name);
        file_put_contents($cache_file, serialize($cache), LOCK_EX);
        
        return(true);
	}
    
    /**
    * Get the next record from a statement handle
    *
    */
    function db_next()
    {
        if ($this->statement_handle) {
            return(mysql_fetch_assoc($this->statement_handle));
        }
    }

    /** 
    * Get all the records
    *
    */
    function db_all()
    {
        if ($this->statement_handle) {
			$ret = array();
			
			while($rec = mysql_fetch_assoc($this->statement_handle)) {
				$ret[] = $rec;
			}
            return($ret);
        }
        
        return(false);
    }
    
    /** 
    * Get name of primary key -- we need to cache this info
    */
    function db_get_primary_key_name()
    {
		return($this->primary_key_name);
		
        // Could me manually set
		/*
        if($this->primary_key_name)
            return($this->primary_key_name);
        
        $sql = "SELECT column_name FROM information_schema.key_column_usage WHERE table_name=? AND constraint_name='PRIMARY'";
        $this->db_query(array($sql, $this->tablename));
        
        if ($this->statement_handle) {
            $pkey = $this->db_next();
            $this->primary_key_name = $pkey['column_name'];
        } else {
            $this->primary_key_name = "id";
        }
        $this->statement_handle = null;        

		return(true);
		*/
    }
    
    /**
    * Build a query -- this is driver specific
    */
    function build_query($params=false, $mode='SELECT')
    {
        // Setup SELECT clause... we can make this more complex later
		if ($mode == 'SELECT') {
			$this->sql[] = $params['select'] ? 'SELECT ' . $params['select'] : 'SELECT *';
		}  elseif ($mode == 'DELETE') {
			$this->sql[] = 'DELETE';
		}
        
        // Setup from clause
        $this->sql[] = $params['from'] ? 'FROM ' . $params['from'] : 'FROM ' . $this->tablename;
        
        // Setup conditions and bind variables
        if ($params['conditions']) {
            if (is_array($params['conditions'])) {
               $this->sql[] = 'WHERE ' . $params['conditions'][0];
               $num = count($params['conditions']);
               if($num > 1) {
                   $this->push_binds(array_slice($params['conditions'], 1));
               }
            } else {
               $this->sql[] = 'WHERE ' . trim($params['conditions']); 
            }
        }        

        // Setup grouping
        $this->sql[] = $params['group'] ? 'GROUP ON ' . $params['group'] : '';        
        
        // Setup order
        $this->sql[] = $params['order'] ? 'ORDER BY ' . $params['order'] : '';

        // Limits
        $this->sql[] = $params['limit'] ? 'LIMIT ' . intval($params['limit']) : '';

        // Offset
        $this->sql[] = $params['offset'] ? 'OFFSET ' . intval($params['offset']) : '';
        
        // Readonly
        if ($params['readonly']) {
            $this->readonly = true;
        }
        
        return(implode(' ', $this->sql));           
    }
    
    // Push more binds
    function push_binds($more_binds=false) 
    {
        foreach($more_binds as $bind)
        {
            $this->binds[] = $bind;
        }
    }    

	// Setup a conditions type string
	function db_merge($params = array())
	{
		if(!is_array($params))
			return($params);

		$n = count($params);

		if($n == 1)
			return($params[0]);

		if($n > 0)
		{
			$this->binds = array_slice($params,1);
			$parts = explode('?', $params[0]);
			$c = count($this->binds);
			for($x = 0; $x < $c; $x++)
			{
				$parts[$x] .= trim("'" . $this->db_escape($this->binds[$x]) . "'");
			}

			return( implode('', $parts));
		}
	
		return($params);
	}


}

?>
