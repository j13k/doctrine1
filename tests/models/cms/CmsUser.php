<?php

#namespace Doctrine::Test::ORM::Models;

#use Doctrine::ORM::Entity;

class CmsUser extends Doctrine_Entity
{
    #protected $id;
    #protected $status;
    #protected $username;
    #protected $name;
    
    public static function initMetadata($mapping)
    {
        $mapping->mapField(array(
            'fieldName' => 'id',
            'type' => 'integer',
            'length' => 4,
            'id' => true,
            'idGenerator' => 'auto'
        ));
        $mapping->mapField(array(
            'fieldName' => 'status',
            'type' => 'string',
            'length' => 50
        ));
        $mapping->mapField(array(
            'fieldName' => 'username',
            'type' => 'string',
            'length' => 255
        ));
        $mapping->mapField(array(
            'fieldName' => 'name',
            'type' => 'string',
            'length' => 255
        ));

        /*$mapping->hasMany('CmsPhonenumber as phonenumbers', array(
              'local' => 'id', 'foreign' => 'user_id'));
        $mapping->hasMany('CmsArticle as articles', array(
              'local' => 'id', 'foreign' => 'user_id'));*/
        
        $mapping->mapOneToMany(array(
            'fieldName' => 'phonenumbers',
            'targetEntity' => 'CmsPhonenumber',
            
        ));
        
        $mapping->mapOneToMany(array(
            'fieldName' => 'articles',
            'targetEntity' => 'CmsArticle',
        ));
        
    }
}