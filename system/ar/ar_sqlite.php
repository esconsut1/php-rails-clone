<?php

/**
* Basic SqLite driver
*
*/
class Ar_Sqlite
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
	function db_connect($connection_name = false) {
        
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
		if (!$this->default['database']) {
			die('Please setup database.ini');
		}
        
		// Connect
		if ($this->db = sqlite_open($this->default['database'], 0666, $sqlite_error)) {
            // We're ok
		} else {
			die('Unable to connect to database: ' . $this->default['database'] . ' : ' . $this->default['database']);
		}

		return(true);
	}

    function db_escape($str="")
    {
        $str = str_replace('\\', '\&\&', $str);
        $str = preg_replace("/'/", "''", $str);
        
        return($str);
    }
    
    function db_last_insert_id()
    {	
		return(sqlite_last_insert_rowid($this->db));
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

		$start = microtime(true);
		$this->statement_handle = sqlite_query($this->db, $this->sql);
		$this->duration = microtime(true) - $start;
		ar_logger('SQL: [' . number_format($this->duration,6) . '] ' . $this->sql);
		
		$GLOBALS['db_time'] += $this->duration;
		
		if ($this->statement_handle === false) {
			$errorstr = 'SQL ERROR: ' . @sqlite_error_string(@sqlite_last_error($this->db)) . "\nSTATEMENT: " . $this->sql;
			//debug($errorstr);
			ar_logger($errorstr);
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
        
		$this->db_query(array("PRAGMA table_info('{$this->tablename}')"));
		$fields = $this->db_all();
		
		$this->schema = array();
		foreach ($fields as $rec) {
			$this->schema[$rec['Field']] = array();
			$this->schema[$rec['Field']]['type'] = $rec['type'];
			$this->schema[$rec['Field']]['default'] = $rec['dflt_valye'];
			$this->schema[$rec['Field']]['key'] = false;
            if ($rec['type'] == 'varchar') {
			    $this->schema[$rec['Field']]['length'] = $rec;
            } else {
                $this->schema[$rec['Field']]['length'] = 0;
            }
			if ($rec['pk'] && !$this->primary_key_name) {
				$this->primary_key_name = $rec['name'];
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
            return(sqlite_fetch_array($this->statement_handle, SQLITE_ASSOC));
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
			
			while($rec = sqlite_fetch_array($this->statement_handle, SQLITE_ASSOC)) {
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
