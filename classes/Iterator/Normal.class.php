<?php
require_once(Doctrine::getPath().DIRECTORY_SEPARATOR."Iterator.class.php");

class Doctrine_Iterator_Normal extends Doctrine_Iterator {
    /**
     * @return boolean                          whether or not the iteration will continue
     */
    public function valid() {
        return ($this->index < $this->count);
    }
}
?>
