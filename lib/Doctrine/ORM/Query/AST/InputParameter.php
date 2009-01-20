<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of InputParameter
 *
 * @author robo
 */
class Doctrine_ORM_Query_AST_InputParameter extends Doctrine_ORM_Query_AST
{
    private $_isNamed;
    private $_position;
    private $_name;

    public function __construct($value)
    {
        $param = substr($value, 1);
        $this->_isNamed = ! is_numeric($param);
        if ($this->_isNamed) {
            $this->_name = $param;
        } else {
            $this->_position = $param;
        }
    }

    public function isNamed()
    {
        return $this->_isNamed;
    }

    public function isPositional()
    {
        return ! $this->_isNamed;
    }

    public function getName()
    {
        return $this->_name;
    }
    
    public function getPosition()
    {
        return $this->_position;
    }
}

