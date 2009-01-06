<?php

/**
 * @DoctrineEntity
 */
class CmsPhonenumber implements Doctrine_ORM_Entity
{
    /**
     * @DoctrineColumn(type="string", length=50)
     * @DoctrineId
     */
    public $phonenumber;
    /**
     * @DoctrineManyToOne(targetEntity="CmsUser", joinColumns={"user_id" = "id"})
     */
    public $user;
}