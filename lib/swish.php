<?

class Query
{
    var $results;
    var $hits;
    var $search;
    var $serps;
    var $offset;
    
    var $term;
    var $page;
    var $limit;
    var $images;
    var $exact_match;
    
    var $seconds;
    
    // The swish index
    private $db;
    
    function Query($index = '') {
        $this->hits = 0;
        $this->results = array();
        $this->search = false;
        $this->index($index);
        $this->offset = 1;
        $this->seconds = 0;
        $this->exact_match = false;
    }
    
    // Sets and cleans up the term
    function term($q = '', $or = false) {
        $this->term = trim(strtolower(preg_replace('/[^\w\- ]+/', ' ', $q)));
        
        // we make an or term out of multiple terms
        if ($or && strpos($q, ' ') >= 0 && !preg_match('/\bor\b|\band\b/', $q)) {
            $terms = preg_split('/[\s]+/', $q, -1, PREG_SPLIT_NO_EMPTY);
            $this->term = implode(' OR ', $terms);
        }
        
        if ($this->exact_match == true) {
            $this->term = '"' . $this->term . '"';
        }
    }
    
    function setpage($num = null) {
        $num = is_numeric($num) ? intval($num) : 1;
        $num = $num > 100 ? 100 : $num;       
        $num = $num < 1 ? 1 : $num;
        $this->page = $num;
    }
    
    function limit($num = null) {
        $num = intval($num) ? intval($num) : 20;
        $num = $num > 512 ? 512 : $num;
        $num = $num < 1 ? 20 : $num;
        $this->limit = $num;        
    }
    
    function images($img = false) {
        $this->images = $img ? true : false;
    }

    // Sets the index
    function index($index = false) {
        if (!$index) {
            return(false);
        }
        
        if (file_exists($index)) {
           $this->db = new Swish($index);
           $this->search = $this->db->prepare();
        }
    }
    
    // Just get the number of results found from an index
    function get_hits() {
        if (!$this->search) {
            return(false);
        }
        
        $st = microtime(true);
        try {
            $this->results = $this->search->execute($this->term);
        } catch(Exception $e) {
            return(false);
        }
        $this->seconds = microtime(true) - $st;
        
        $this->hits = $this->results->hits;
        return($this->results->hits);
    }
    
    // Set a filter
    function filter($key, $valstart, $valend) {
        $this->search->setLimit($key, $valstart, $valend);
    }
    
    // Set an result order
    function order($order) {   
        $this->search->setSort($order);
    }
    
    // Get us some results
    function get_results() {
        if (!$this->results) {
            $this->get_hits();
        }
        
        if (!$this->hits || !$this->results) {
            return(false);
        }
        
        // Seek to first position
        $seek = 0;
        if ($this->page > 1 && (($this->page-1) * $this->limit + 1) < $this->hits) {
            $seek = ($this->page-1) * $this->limit + 1;
            $this->results->seekResult($seek);
            $this->offset = $seek;
        }

        $x = 0;
        while($rec = $this->results->nextResult()) {
            if (!$rec) {
                continue;
            }
            
			$rec->sdate = date('Y-m-d', $rec->date);

            $this->serps[] = (array)$rec;
            $x++;
            if ($x >= $this->limit) {
                break;
            }
        }
       
        return($x);
    }
}

?>
