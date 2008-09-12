<?php

#namespace Doctrine::Tests::ORM::Models::Forum;

#use Doctrine::ORM::Entity;

class ForumUser extends Doctrine_ORM_Entity
{
    #protected $id;
    #protected $username;
    #protected $avatar;
    
    public static function initMetadata($mapping) 
    {
        // inheritance mapping
        $mapping->setInheritanceType('joined', array(
                'discriminatorColumn' => 'dtype',
                'discriminatorMap' => array(
                        'user' => 'ForumUser',
                        'admin' => 'ForumAdministrator')
                ));
        // register subclasses
        $mapping->setSubclasses(array('ForumAdministrator'));
        
        // column-to-field mapping
        $mapping->mapField(array(
            'fieldName' => 'id',
            'type' => 'integer',
            'length' => 4,
            'id' => true,
            'idGenerator' => 'auto'
        ));
        $mapping->mapField(array(
            'fieldName' => 'username',
            'type' => 'string',
            'length' => 50
        ));
        
        $mapping->mapOneToOne(array(
            'fieldName' => 'avatar',
            'targetEntity' => 'ForumAvatar',
            'joinColumns' => array('avatar_id' => 'id'),
            'cascade' => array('save')
        ));
        
    }
    
}