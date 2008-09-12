<?php

class Doctrine_Common_Exceptions_DoctrineException extends Exception
{
    private $_innerException;
    
    public function __construct($message = "", Exception $innerException = null)
    {
        parent::__construct($message);
        $this->_innerException = $innerException;
    }
    
    public function getInnerException()
    {
        return $this->_innerException;
    }
    
    public static function notImplemented($method, $class)
    {
        return new self("The method '$method' is not implemented in the class '$class'.");
    }
}

?>