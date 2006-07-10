<?php
require_once("Access.php");
/**
 * Doctrine_Query
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 * @version     1.0 alpha
 */
class Doctrine_Query extends Doctrine_Access {
    /**
     * @var array $fetchmodes               an array containing all fetchmodes
     */
    private $fetchModes  = array();
    /**
     * @var array $tables                   an array containing all the tables used in the query
     */
    private $tables      = array();
    /**
     * @var array $collections              an array containing all collections this parser has created/will create
     */
    private $collections = array();
    /**
     * @var array $joins                    an array containing all table joins
     */
    private $joins       = array();
    /**
     * @var array $data                     fetched data
     */
    private $data        = array();
    /**
     * @var Doctrine_Session $session       Doctrine_Session object
     */
    private $session;
    /**
     * @var Doctrine_View $view             Doctrine_View object
     */
    private $view;
    

    private $inheritanceApplied = false;

    private $aggregate  = false;
    /**
     * @var array $tableAliases
     */
    private $tableAliases = array();
    /**
     * @var array $tableIndexes
     */
    private $tableIndexes = array();
    /**
     * @var array $paths
     */
    private $paths        = array();
    /**
     * @var array $parts            SQL query string parts
     */
    protected $parts = array(
        "columns"   => array(),
        "from"      => array(),
        "join"      => array(),
        "where"     => array(),
        "groupby"   => array(),
        "having"    => array(),
        "orderby"   => array(),
        "limit"     => false,
        "offset"    => false,
        );
    /**
     * constructor
     *
     * @param Doctrine_Session $session
     */
    public function __construct(Doctrine_Session $session) {
        $this->session = $session;
    }
    /**
     * @return Doctrine_Session
     */
    public function getSession() {
        return $this->session;
    }
    /**
     * setView
     * sets a database view this query object uses
     * this method should only be called internally by doctrine
     *
     * @param Doctrine_View $view       database view
     * @return void
     */
    public function setView(Doctrine_View $view) {
        $this->view = $view;
    }
    /**
     * getView
     *
     * @return Doctrine_View
     */
    public function getView() {
        return $this->view;
    }

    /**
     * clear
     * resets all the variables
     * 
     * @return void
     */
    private function clear() {
        $this->fetchModes   = array();
        $this->tables       = array();

        $this->parts = array(
                  "columns"   => array(),
                  "from"      => array(),
                  "join"      => array(),
                  "where"     => array(),
                  "groupby"   => array(),
                  "having"    => array(),
                  "orderby"   => array(),
                  "limit"     => false,
                  "offset"    => false,
                );
        $this->inheritanceApplied = false;
        $this->aggregate    = false;
        $this->data         = array();
        $this->collections  = array();                  
        $this->joins            = array();
        $this->tableIndexes     = array();
        $this->tableAliases     = array();
    }
    /**
     * loadFields      
     * loads fields for a given table and
     * constructs a little bit of sql for every field
     *
     * fields of the tables become: [tablename].[fieldname] as [tablename]__[fieldname]
     *
     * @access private
     * @param object Doctrine_Table $table          a Doctrine_Table object
     * @param integer $fetchmode                    fetchmode the table is using eg. Doctrine::FETCH_LAZY
     * @param array $names                          fields to be loaded (only used in lazy property loading)
     * @return void
     */
    private function loadFields(Doctrine_Table $table, $fetchmode, array $names, $cpath) {
        $name = $table->getComponentName();

        switch($fetchmode):
            case Doctrine::FETCH_OFFSET:
                $this->limit = $table->getAttribute(Doctrine::ATTR_COLL_LIMIT);
            case Doctrine::FETCH_IMMEDIATE:
                if( ! empty($names))
                    $names = array_unique(array_merge($table->getPrimaryKeys(), $names));
                else
                    $names = $table->getColumnNames();
            break;
            case Doctrine::FETCH_LAZY_OFFSET:
                $this->limit = $table->getAttribute(Doctrine::ATTR_COLL_LIMIT);
            case Doctrine::FETCH_LAZY:
            case Doctrine::FETCH_BATCH:
                $names = array_unique(array_merge($table->getPrimaryKeys(), $names));
            break;
            default:
                throw new Doctrine_Exception("Unknown fetchmode.");
        endswitch;
        
        $component          = $table->getComponentName();
        $tablename          = $this->tableAliases[$cpath];

        $this->fetchModes[$tablename] = $fetchmode;

        $count = count($this->tables);

        foreach($names as $name) {
            if($count == 0) {
                $this->parts["columns"][] = $tablename.".".$name;
            } else {
                $this->parts["columns"][] = $tablename.".".$name." AS ".$tablename."__".$name;
            }
        }
    }
    /**
     * addFrom
     * 
     * @param strint $from
     */
    public function addFrom($from) {
        $class = "Doctrine_Query_From";
        $parser = new $class($this);
        $parser->parse($from);
    }
    /**
     * addWhere
     *
     * @param string $where
     */
    public function addWhere($where) {
        $class  = "Doctrine_Query_Where";
        $parser = new $class($this);
        $this->parts['where'][] = $parser->parse($where);
    }
    /**
     * sets a query part
     *
     * @param string $name
     * @param array $args
     * @return void
     */
    public function __call($name, $args) {
        $name = strtolower($name);
        
        if(isset($this->parts[$name])) {
            $method = "parse".ucwords($name);
            switch($name):
                case "from":
                    $this->parts['from']    = array();
                    $this->parts['columns'] = array();
                    $this->parts['join']    = array();
                    $this->joins            = array();
                    $this->tables           = array();
                    $this->fetchModes       = array();
                    $this->tableIndexes     = array();
                    $this->tableAliases     = array();
                    
                    $class = "Doctrine_Query_".ucwords($name);
                    $parser = new $class($this);
                    
                    $parser->parse($args[0]);
                break;
                case "where":
                case "having": 
                case "orderby":
                case "groupby":
                    $class = "Doctrine_Query_".ucwords($name);
                    $parser = new $class($this);

                    $this->parts[$name] = array($parser->parse($args[0]));
                break;
                case "limit":
                case "offset":
                    if($args[0] == null)
                        $args[0] = false;

                    $this->parts[$name] = $args[0];
                break;
                default:
                    $this->parts[$name] = array();
                    $this->$method($args[0]);
            endswitch;
        } else 
            throw new Doctrine_Query_Exception("Unknown overload method");

        return $this;
    }
    /**
     * returns a query part
     *
     * @param $name         query part name
     * @return mixed
     */
    public function get($name) {
        if( ! isset($this->parts[$name]))
            return false;

        return $this->parts[$name];
    }
    /**
     * sets a query part
     *
     * @param $name         query part name
     * @param $value        query part value
     * @return boolean
     */
    public function set($name, $value) {

        if(isset($this->parts[$name])) {
            $method = "parse".ucwords($name);
            switch($name):
                case "where":
                case "having":
                    $this->parts[$name] = array($this->$method($value));
                break;
                case "limit":
                case "offset": 
                    if($value == null)
                        $value = false;

                    $this->parts[$name] = $value;
                break;
                case "from":
                    $this->parts['columns'] = array();
                    $this->parts['join']    = array();
                    $this->joins            = array();
                    $this->tables           = array();
                    $this->fetchModes       = array();
                    $this->tableIndexes     = array();
                    $this->tableAliases     = array();
                default:
                    $this->parts[$name] = array();
                    $this->$method($value);
            endswitch;
            
            return true;
        }
        return false;
    }
    /**
     * returns the built sql query
     *
     * @return string
     */
    final public function getQuery() {
        if(empty($this->parts["columns"]) || empty($this->parts["from"]))
            return false;

        // build the basic query
        $q = "SELECT ".implode(", ",$this->parts["columns"]).
             " FROM ";
        
        foreach($this->parts["from"] as $tname => $bool) {
            $a[] = $tname;
        }
        $q .= implode(", ",$a);
        
        if( ! empty($this->parts['join'])) {
            foreach($this->parts['join'] as $part) {
                $q .= " ".implode(' ', $part);
            }
        }

        $string = $this->applyInheritance();

        if( ! empty($this->parts["where"])) {
            $q .= " WHERE ".implode(" ",$this->parts["where"]);
            if( ! empty($string))
                $q .= " AND (".$string.")";
        } else {
            if( ! empty($string))
                $q .= " WHERE (".$string.")";
        }


        $q .= ( ! empty($this->parts['groupby']))?" GROUP BY ".implode(", ",$this->parts["groupby"]):'';
        $q .= ( ! empty($this->parts['having']))?" HAVING ".implode(" ",$this->parts["having"]):'';
        $q .= ( ! empty($this->parts['orderby']))?" ORDER BY ".implode(" ",$this->parts["orderby"]):'';

        if( ! empty($this->parts["limit"]) || ! empty($this->offset))
            $q = $this->session->modifyLimitQuery($q,$this->parts["limit"],$this->offset);

        return $q;
    }

    /**
     * applyInheritance
     * applies column aggregation inheritance to DQL query
     *
     * @return string
     */
    final public function applyInheritance() {
        // get the inheritance maps
        $array = array();

        foreach($this->tables as $alias => $table):
            $array[$alias][] = $table->getInheritanceMap();
        endforeach;

        // apply inheritance maps
        $str = "";
        $c = array();

        $index = 0;
        foreach($array as $tname => $maps) {
            $a = array();
            foreach($maps as $map) {
                $b = array();
                foreach($map as $field => $value) {
                    if($index > 0)
                        $b[] = "(".$tname.".$field = $value OR $tname.$field IS NULL)";
                    else
                        $b[] = $tname.".$field = $value";
                }
                if( ! empty($b)) $a[] = implode(" AND ",$b);
            }
            if( ! empty($a)) $c[] = implode(" AND ",$a);
            $index++;
        }

        $str .= implode(" AND ",$c);

        return $str;
    }
    /**
     * getData
     * @param $key                      the component name
     * @return array                    the data row for the specified component
     */
    final public function getData($key) {
        if(isset($this->data[$key]) && is_array($this->data[$key]))
            return $this->data[$key];

        return array();
    }
    /**
     * execute
     * executes the dql query and populates all collections
     *
     * @param string $params
     * @return Doctrine_Collection            the root collection
     */
    public function execute($params = array()) {
        $this->data = array();
        $this->collections = array();
        
        if( ! $this->view)
            $query = $this->getQuery();
        else
            $query = $this->view->getSelectSql();

        switch(count($this->tables)):
            case 0:
                throw new DQLException();
            break;
            case 1:
                $keys  = array_keys($this->tables);

                $name  = $this->tables[$keys[0]]->getComponentName();
                $stmt  = $this->session->execute($query,$params);

                while($data = $stmt->fetch(PDO::FETCH_ASSOC)):
                    foreach($data as $key => $value):
                        $e = explode("__",$key);
                        if(count($e) > 1) {
                            $data[$e[1]] = $value;
                        } else {
                            $data[$e[0]] = $value;
                        }
                        unset($data[$key]);
                    endforeach;
                    $this->data[$name][] = $data;
                endwhile;

                return $this->getCollection($keys[0]);
            break;
            default:
                $keys  = array_keys($this->tables);
                $root  = $keys[0];

                $stmt  = $this->session->execute($query,$params);

                $previd = array();

                $coll        = $this->getCollection($root);
                $prev[$root] = $coll;

                $array = $this->parseData($stmt);


                $colls = array();
                
                foreach($array as $data) {
                    /**
                     * remove duplicated data rows and map data into objects
                     */
                    foreach($data as $key => $row) {
                        if(empty($row))
                            continue;
                        

                        $ids  = $this->tables[$key]->getIdentifier();
                        
                        $emptyID = false;
                        if(is_array($ids)) {
                            foreach($ids as $id) {
                                if($row[$id] == null) {
                                    $emptyID = true;
                                    break;
                                }
                            }
                        } else {
                            if($row[$ids] === null)
                                $emptyID = true;
                        }


                        $name    = $key;

                        if($emptyID) {


                            $pointer = $this->joins[$name];
                            $path    = array_search($name, $this->tableAliases);
                            $tmp     = explode(".", $path);
                            $alias   = end($tmp);
                            unset($tmp);
                            $fk      = $this->tables[$pointer]->getForeignKey($alias);

                            if( ! isset($prev[$pointer]) )
                                continue;

                            $last    = $prev[$pointer]->getLast();

                            switch($fk->getType()):
                                case Doctrine_Relation::ONE_COMPOSITE:
                                case Doctrine_Relation::ONE_AGGREGATE:
                                
                                break;
                                default:
                                    if($last instanceof Doctrine_Record) {
                                        if( ! $last->hasReference($alias)) {
                                            $prev[$name] = $this->getCollection($name);
                                            $last->initReference($prev[$name],$fk);
                                        }
                                    }
                            endswitch;

                            continue;
                        }


                        if( ! isset($previd[$name]))
                            $previd[$name] = array();

                        if($previd[$name] !== $row) {
                            // set internal data

                            $this->tables[$name]->setData($row);

                            // initialize a new record
                            $record = $this->tables[$name]->getRecord();

                            if($name == $root) {
                                // add record into root collection
                                $coll->add($record);
                                unset($previd);

                            } else {

                                $pointer = $this->joins[$name];
                                $path    = array_search($name, $this->tableAliases);
                                $tmp     = explode(".", $path);
                                $alias   = end($tmp);
                                unset($tmp);
                                $fk      = $this->tables[$pointer]->getForeignKey($alias);
                                $last    = $prev[$pointer]->getLast();

                                switch($fk->getType()):
                                    case Doctrine_Relation::ONE_COMPOSITE:
                                    case Doctrine_Relation::ONE_AGGREGATE:

                                        // one-to-one relation

                                        $last->internalSet($fk->getLocal(), $record->getIncremented());

                                        $last->initSingleReference($record, $fk);

                                        $prev[$name] = $record;
                                    break;
                                    default:

                                        // one-to-many relation or many-to-many relation

                                        if( ! $last->hasReference($alias)) {
                                            $prev[$name] = $this->getCollection($name);
                                            $last->initReference($prev[$name], $fk);
                                        } else {
                                            // previous entry found from identityMap
                                            $prev[$name] = $last->get($alias);
                                        }

                                        $last->addReference($record, $fk);
                                endswitch;
                            }
                        }

                        $previd[$name] = $row;
                    }
                }

                return $coll;
        endswitch;
    }
    /**
     * parseData
     * parses the data returned by PDOStatement
     *
     * @return array
     */
    public function parseData(PDOStatement $stmt) {
        $array = array();
        
        while($data = $stmt->fetch(PDO::FETCH_ASSOC)):
            /**
             * parse the data into two-dimensional array
             */
            foreach($data as $key => $value):
                $e = explode("__",$key);

                if(count($e) > 1) {
                    $data[$e[0]][$e[1]] = $value;
                } else {
                    $data[0][$e[0]] = $value;
                }
                unset($data[$key]);
            endforeach;
            $array[] = $data;
        endwhile;
        $stmt->closeCursor();
        return $array;
    }
    /**
     * returns a Doctrine_Table for given name
     *
     * @param string $name              component name
     * @return Doctrine_Table
     */
    public function getTable($name) {
        return $this->tables[$name];
    }
    /**
     * getCollection
     *
     * @parma string $name              component name
     * @param integer $index
     */
    private function getCollection($name) {
        $table = $this->tables[$name];
        switch($this->fetchModes[$name]):
            case Doctrine::FETCH_BATCH:
                $coll = new Doctrine_Collection_Batch($table);
            break;
            case Doctrine::FETCH_LAZY:
                $coll = new Doctrine_Collection_Lazy($table);
            break;
            case Doctrine::FETCH_OFFSET:
                $coll = new Doctrine_Collection_Offset($table);
            break;
            case Doctrine::FETCH_IMMEDIATE:
                $coll = new Doctrine_Collection_Immediate($table);
            break;
            case Doctrine::FETCH_LAZY_OFFSET:
                $coll = new Doctrine_Collection_LazyOffset($table);
            break;
            default:
                throw new Doctrine_Exception("Unknown fetchmode");
        endswitch;

        $coll->populate($this);
        return $coll;
    }
    /**
     * query the database with DQL (Doctrine Query Language)
     *
     * @param string $query                 DQL query
     * @param array $params                 parameters
     */
    public function query($query,$params = array()) {
        $this->parseQuery($query);

        if($this->aggregate) {
            $keys  = array_keys($this->tables);
            $query = $this->getQuery();
            $stmt  = $this->tables[$keys[0]]->getSession()->select($query,$this->parts["limit"],$this->offset);
            $data  = $stmt->fetch(PDO::FETCH_ASSOC);
            if(count($data) == 1) {
                return current($data);
            } else {
                return $data;
            }
        } else {
            return $this->execute($params);
        }
    }
    /**
     * DQL PARSER
     * parses a DQL query
     * first splits the query in parts and then uses individual
     * parsers for each part
     *
     * @param string $query         DQL query
     * @return void
     */
    final public function parseQuery($query) {
        $this->clear();
        $e = self::bracketExplode($query," ","(",")");


        $parts = array();
        foreach($e as $k=>$part):
            switch(strtolower($part)):
                case "select":
                case "from":
                case "where":
                case "limit":
                case "offset":
                case "having":
                    $p = $part;
                    $parts[$part] = array();
                break;
                case "order":
                case "group":
                    $i = ($k + 1);
                    if(isset($e[$i]) && strtolower($e[$i]) === "by") {
                        $p = $part;
                        $parts[$part] = array();
                    } else 
                        $parts[$p][] = $part;
                break;
                case "by":
                    continue;
                default:
                    $parts[$p][] = $part;
            endswitch;
        endforeach;

        foreach($parts as $k => $part) {
            $part = implode(" ",$part);
            switch(strtoupper($k)):
                case "SELECT":
                    $this->parseSelect($part);
                break;
                case "FROM":

                    $class  = "Doctrine_Query_".ucwords(strtolower($k));
                    $parser = new $class($this);
                    $parser->parse($part);
                break;
                case "GROUP":
                case "ORDER":
                    $k .= "by";
                case "WHERE":
                case "HAVING":
                    $class  = "Doctrine_Query_".ucwords(strtolower($k));
                    $parser = new $class($this);

                    $name = strtolower($k);
                    $this->parts[$name][] = $parser->parse($part);
                break;
                case "LIMIT":
                    $this->parts["limit"] = trim($part);
                break;
                case "OFFSET":
                    $this->offset = trim($part);
                break;
            endswitch;
        }
    }
    /**
     * DQL ORDER BY PARSER
     * parses the order by part of the query string
     *
     * @param string $str
     * @return void
     */
    final public function parseOrderBy($str) {
        $parser = new Doctrine_Query_Part_Orderby($this);
        return $parser->parse($str);
    }
    /**
     * returns Doctrine::FETCH_* constant
     *
     * @param string $mode
     * @return integer
     */
    final public function parseFetchMode($mode) {
        switch(strtolower($mode)):
            case "i":
            case "immediate":
                $fetchmode = Doctrine::FETCH_IMMEDIATE;
            break;
            case "b":
            case "batch":
                $fetchmode = Doctrine::FETCH_BATCH;
            break;
            case "l":
            case "lazy":
                $fetchmode = Doctrine::FETCH_LAZY;
            break;
            case "o":
            case "offset":
                $fetchmode = Doctrine::FETCH_OFFSET;
            break;
            case "lo":
            case "lazyoffset":
                $fetchmode = Doctrine::FETCH_LAZYOFFSET;
            default:
                throw new DQLException("Unknown fetchmode '$mode'. The availible fetchmodes are 'i', 'b' and 'l'.");
        endswitch;
        return $fetchmode;
    }
    /**
     * trims brackets
     *
     * @param string $str
     * @param string $e1        the first bracket, usually '('
     * @param string $e2        the second bracket, usually ')'
     */
    public static function bracketTrim($str,$e1,$e2) {
        if(substr($str,0,1) == $e1 && substr($str,-1) == $e2)
            return substr($str,1,-1);
        else
            return $str;
    }
    /**
     * bracketExplode
     * usage:
     * $str = (age < 20 AND age > 18) AND email LIKE 'John@example.com'
     * now exploding $str with parameters $d = ' AND ', $e1 = '(' and $e2 = ')'
     * would return an array:
     * array("(age < 20 AND age > 18)", "email LIKE 'John@example.com'")
     *
     * @param string $str
     * @param string $d         the delimeter which explodes the string
     * @param string $e1        the first bracket, usually '('
     * @param string $e2        the second bracket, usually ')'
     *
     */
    public static function bracketExplode($str,$d,$e1,$e2) {
        $str = explode("$d",$str);
        $i = 0;
        $term = array();
        foreach($str as $key=>$val) {
            if (empty($term[$i])) {
                $term[$i] = trim($val);
                $s1 = substr_count($term[$i],"$e1");
                $s2 = substr_count($term[$i],"$e2");
                    if($s1 == $s2) $i++;
            } else {
                $term[$i] .= "$d".trim($val);
                $c1 = substr_count($term[$i],"$e1");
                $c2 = substr_count($term[$i],"$e2");
                    if($c1 == $c2) $i++;
            }
        }
        return $term;
    }

    /**
     * generateAlias
     *
     * @param string $tableName
     * @return string
     */
    final public function generateAlias($tableName) {
        if(isset($this->tableIndexes[$tableName])) {
            return $tableName.++$this->tableIndexes[$tableName];
        } else {
            $this->tableIndexes[$tableName] = 1;
            return $tableName;
        }
    }
    /**
     * getTableAlias
     *
     * @param string $path
     * @return string
     */
    final public function getTableAlias($path) {
        if( ! isset($this->tableAliases[$path]))
            return false;

        return $this->tableAliases[$path];
    }
    /**
     * loads a component
     *
     * @param string $path              the path of the loadable component
     * @param integer $fetchmode        optional fetchmode, if not set the components default fetchmode will be used
     * @throws DQLException
     * @return Doctrine_Table
     */
    final public function load($path, $loadFields = true) {
        $e = preg_split("/[.:]/",$path);
        $index = 0;
        $currPath = '';

        foreach($e as $key => $fullname) {
            try {
                $copy  = $e;

                $e2    = preg_split("/[-(]/",$fullname);
                $name  = $e2[0];

                $currPath .= ".".$name;

                if($key == 0) {
                    $currPath = substr($currPath,1);

                    $table = $this->session->getTable($name);

                    $tname = $table->getTableName();
                    
                    if( ! isset($this->tableAliases[$currPath]))
                        $this->tableIndexes[$tname] = 1;
                    
                    $this->parts["from"][$tname] = true;

                    $this->tableAliases[$currPath] = $tname;
                    
                    $tableName = $tname;
                } else {

                    $index += strlen($e[($key - 1)]) + 1;
                    // the mark here is either '.' or ':'
                    $mark  = substr($path,($index - 1),1);

                    if(isset($this->tableAliases[$prevPath])) {
                        $tname = $this->tableAliases[$prevPath];
                    } else
                        $tname = $table->getTableName();


                    $fk       = $table->getForeignKey($name);
                    $name     = $fk->getTable()->getComponentName();
                    $original = $fk->getTable()->getTableName();
                    
                    if(isset($this->tableAliases[$currPath])) {
                        $tname2 = $this->tableAliases[$currPath];
                    } else
                        $tname2 = $this->generateAlias($original);

                    if($original !== $tname2) 
                        $aliasString = $original." AS ".$tname2;
                    else
                        $aliasString = $original;

                    switch($mark):
                        case ":":
                            $join = 'INNER JOIN ';
                        break;
                        case ".":
                            $join = 'LEFT JOIN ';
                        break;
                        default:
                            throw new Doctrine_Exception("Unknown operator '$mark'");
                    endswitch;


                    if($fk instanceof Doctrine_ForeignKey ||
                       $fk instanceof Doctrine_LocalKey) {

                        $this->parts["join"][$tname][$tname2]         = $join.$aliasString." ON ".$tname.".".$fk->getLocal()." = ".$tname2.".".$fk->getForeign();

                    } elseif($fk instanceof Doctrine_Association) {
                        $asf = $fk->getAssociationFactory();

                        $assocTableName = $asf->getTableName();

                        $this->parts["join"][$tname][$assocTableName] = $join.$assocTableName." ON ".$tname.".id = ".$assocTableName.".".$fk->getLocal();
                        $this->parts["join"][$tname][$tname2]         = $join.$aliasString." ON ".$tname2.".id = ".$assocTableName.".".$fk->getForeign();
                    }

                    $this->joins[$tname2] = $prevTable;


                    $table = $fk->getTable();
                    $this->tableAliases[$currPath] = $tname2;

                    $tableName = $tname2;
                }

                if( ! isset($this->tables[$tableName])) {
                    $this->tables[$tableName] = $table;

                    if($loadFields && ! $this->aggregate) {
                        $this->parseFields($fullname, $tableName, $e2, $currPath);
                    }
                }


                $prevPath  = $currPath;
                $prevTable = $tableName;
            } catch(Exception $e) {
                throw new DQLException($e->__toString());
            }
        }
        return $table;
    }
    /**
     * parseFields
     *
     * @param string $fullName
     * @param string $tableName
     * @param array $exploded
     * @param string $currPath
     * @return void
     */
    final public function parseFields($fullName, $tableName, $exploded, $currPath) {
        $table = $this->tables[$tableName];

        $fields = array();

        if(strpos($fullName, "-") === false) {
            $fetchmode = $table->getAttribute(Doctrine::ATTR_FETCHMODE);

            if(isset($exploded[1]))
                $fields = explode(",",substr($exploded[1],0,-1));

            } else {
                if(isset($exploded[1])) {
                    $fetchmode = $this->parseFetchMode($exploded[1]);
                } else
                    $fetchmode = $table->getAttribute(Doctrine::ATTR_FETCHMODE);

                if(isset($exploded[2]))
                    $fields = explode(",",substr($exploded[2],0,-1));
            }

        $this->loadFields($table, $fetchmode, $fields, $currPath);
    }
}

?>
