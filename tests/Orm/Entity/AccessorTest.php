<?php
require_once 'lib/DoctrineTestInit.php';
 
class Orm_Entity_AccessorTest extends Doctrine_OrmTestCase
{
    public function testGetterSetterOverride()
    {        
        $entity1 = new CustomAccessorMutatorTestEntity();
        $entity1->username = 'romanb';
        $this->assertEquals('romanb?!', $entity1->username);
        
        $entity2 = new MagicAccessorMutatorTestEntity();
        $entity2->username = 'romanb';
        $this->assertEquals('romanb?!', $entity1->username);
    }
}


/* Local test classes */

class CustomAccessorMutatorTestEntity extends Doctrine_ORM_Entity
{
    public static function initMetadata($mapping) 
    {
        $mapping->mapField(array(
            'fieldName' => 'id',
            'type' => 'integer',
            'length' => 4,
            'id' => true
        ));
        $mapping->mapField(array(
            'fieldName' => 'username',
            'type' => 'string',
            'length' => 50,
            'accessor' => 'getUsernameCustom',
            'mutator' => 'setUsernameCustom'
        ));
    }
    
    public function getUsernameCustom()
    {
        return $this->_get('username') . "!";
    }
    
    public function setUsernameCustom($username)
    {
        $this->_set('username', $username . "?");
    }
}

class MagicAccessorMutatorTestEntity extends Doctrine_ORM_Entity
{
    public static function initMetadata($mapping) 
    {
        $mapping->mapField(array(
            'fieldName' => 'id',
            'type' => 'integer',
            'length' => 4,
            'id' => true
        ));
        $mapping->mapField(array(
            'fieldName' => 'username',
            'type' => 'string',
            'length' => 50
        ));
    }
    
    public function getUsername()
    {
        return $this->_get('username') . "!";
    }
    
    public function setUsername($username)
    {
        $this->_set('username', $username . "?");
    } 
}