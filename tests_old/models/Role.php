<?php
class Role extends Doctrine_Entity 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('name', 'string', 20, array('unique' => true));
        $class->hasMany('Auth', array('local' => 'id', 'foreign' => 'roleid'));
    }

}
