<?php
/**
 * Doctrine_View
 *
 * this class represents a database view
 */
class Doctrine_View {
    /**
     * SQL DROP constant
     */
    const DROP   = 'DROP VIEW %s';
    /**
     * SQL CREATE constant
     */
    const CREATE = 'CREATE VIEW %s AS %s';
    /**
     * SQL SELECT constant
     */
    const SELECT = 'SELECT * FROM %s';


    /**
     * @var string $name
     */
    protected $name;
    /**
     * @var Doctrine_Query $query
     */
    protected $query;
    /**
     * @var PDO $dbh
     */
    protected $dbh;

    /**
     * constructor
     *
     * @param Doctrine_Query $query
     */
    public function __construct(Doctrine_Query $query) {
        $this->name  = get_class($this);
        $this->query = $query;
        $this->query->setView($this);
        $this->dbh   = $query->getSession()->getDBH();
    }
    /**
     * simple get method for getting 
     * the associated query object
     *
     * @return Doctrine_Query
     */
    public function getQuery() {
        return $this->query;
    }
    /**
     * returns the name of this view
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }
    /**
     * returns the database handler
     *
     * @return PDO
     */
    public function getDBH() {
        return $this->dbh;
    }
    /**
     * creates this view
     *
     * @return void
     */
    public function create() {
        $sql = sprintf(self::CREATE, $this->name, $this->query->getQuery());
        $this->dbh->query($sql);
    }
    /**
     * drops this view
     *
     * @return void
     */
    public function drop() {
        $this->dbh->query(sprintf(self::DROP, $this->name));
    }
    /**
     * executes the view
     * 
     * @return Doctrine_Collection
     */
    public function execute() {
        return $this->query->execute();
    }
    /**
     * @return string
     */
    public function getSelectSql() {
        return sprintf(self::SELECT, $this->name);
    }
}
?>
