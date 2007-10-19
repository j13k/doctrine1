<?php
/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class AddContact extends Doctrine_Migration
{
	public function up()
	{
		$this->createTable('contact', array (
  'id' => 
  array (
    'primary' => true,
    'autoincrement' => true,
    'type' => 'integer',
    'length' => 11,
  ),
  'name' => 
  array (
    'type' => 'string',
    'length' => 255,
  ),
), array (
  'indexes' => 
  array (
  ),
  'primary' => 
  array (
    0 => 'id',
  ),
));
	}

	public function down()
	{
		$this->dropTable('contact');
	}
}