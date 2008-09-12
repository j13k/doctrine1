<?php

/**
 * Connection interface.
 * Drivers must implement this interface.
 *
 * This resembles the PDO interface.
 * 
 * @since 2.0
 */
interface Doctrine_DBAL_Driver_Connection
{
    public function prepare($prepareString);
    public function query($queryString);
    public function quote($input);
    public function exec($statement);
    public function lastInsertId();
    public function beginTransaction();
    public function commit();
    public function rollBack();
    public function errorCode();
    public function errorInfo();
}

?>