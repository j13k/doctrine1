<?php
/**
 * Doctrine_Relation
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Relation {
    /**
     * RELATION CONSTANTS
     */

    /**
     * constant for ONE_TO_ONE and MANY_TO_ONE aggregate relationships
     */
    const ONE_AGGREGATE         = 0;
    /**
     * constant for ONE_TO_ONE and MANY_TO_ONE composite relationships
     */
    const ONE_COMPOSITE         = 1;
    /**
     * constant for MANY_TO_MANY and ONE_TO_MANY aggregate relationships
     */
    const MANY_AGGREGATE        = 2;
    /**
     * constant for MANY_TO_MANY and ONE_TO_MANY composite relationships
     */
    const MANY_COMPOSITE        = 3;
    

    /**
     * @var Doctrine_Table $table   foreign factory
     */
    private $table;
    /**
     * @var string $local           local field
     */
    private $local;
    /**
     * @var string $foreign         foreign field
     */
    private $foreign;
    /**
     * @var integer $type           bind type
     */
    private $type;
    /**
     * @var string $alias           relation alias
     */
    private $alias;

    /**
     * @param Doctrine_Table $table
     * @param string $local
     * @param string $foreign
     * @param integer $type
     * @param string $alias
     */
    public function __construct(Doctrine_Table $table, $local, $foreign, $type, $alias) {
        $this->table    = $table;
        $this->local    = $local;
        $this->foreign  = $foreign;
        $this->type     = $type;
        $this->alias    = $alias;
    }
    /**
     * @return string                   the relation alias
     */
    public function getAlias() {
        return $this->alias;
    }
    /**
     * @return integer                  the relation type, either 0 or 1
     */
    public function getType() {
        return $this->type;
    }
    /**
     * @return object Doctrine_Table    foreign factory object
     */
    public function getTable() {
        return $this->table;
    }
    /**
     * @return string                   the name of the local column
     */
    public function getLocal() {
        return $this->local;
    }
    /**
     * @return string                   the name of the foreignkey column where
     *                                  the localkey column is pointing at
     */
    public function getForeign() {
        return $this->foreign;
    }
    /**
     * getDeleteOperations
     *
     * get the records that need to be deleted in order to change the old collection
     * to the new one
     *
     * The algorithm here is very simple and definitely not
     * the fastest one, since we have to iterate through the collections twice.
     * the complexity of this algorithm is O(n^2)
     *
     * We iterate through the old collection and get the records
     * that do not exists in the new collection (Doctrine_Records that need to be deleted).
     */
    final public static function getDeleteOperations(Doctrine_Collection $old, Doctrine_Collection $new) {
        $r = array();

        foreach($old as $k => $record) {
            $id = $record->getIncremented();

            if(empty($id))
                continue;

            $found = false;
            foreach($new as $k2 => $record2) {
                if($record2->getIncremented() === $record->getIncremented()) {
                    $found = true;
                    break;
                }
            }

            if( ! $found)  {
                $r[] = $record;
                unset($old[$k]);
            }
        }

        return $r;
    }
    /**
     * getInsertOperations
     *
     * get the records that need to be added in order to change the old collection
     * to the new one
     *
     * The algorithm here is very simple and definitely not
     * the fastest one, since we have to iterate through the collections twice.
     * the complexity of this algorithm is O(n^2)
     *
     * We iterate through the old collection and get the records
     * that exists only in the new collection (Doctrine_Records that need to be added).
     */
    final public static function getInsertOperations(Doctrine_Collection $old, Doctrine_Collection $new) {
        $r = array();

        foreach($new as $k => $record) {
            $found = false;

            $id = $record->getIncremented();
            if( ! empty($id)) {
                foreach($old as $k2 => $record2) {
                    if($record2->getIncremented() === $record->getIncremented()) {
                        $found = true;
                        break;
                    }
                }
            }
            if( ! $found) {
                $old[] = $record;
                $r[] = $record;
            }
        }

        return $r;
    }
    /**
     * __toString
     */
    public function __toString() {
        $r[] = "<pre>";
        $r[] = "Class       : ".get_class($this);
        $r[] = "Component   : ".$this->table->getComponentName();
        $r[] = "Table       : ".$this->table->getTableName();
        $r[] = "Local key   : ".$this->local;
        $r[] = "Foreign key : ".$this->foreign;
        $r[] = "Type        : ".$this->type;
        $r[] = "</pre>";
        return implode("\n", $r);
    }
}

?>
