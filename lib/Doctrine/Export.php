<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.com>.
 */
Doctrine::autoload('Doctrine_Connection_Module');
/**
 * Doctrine_Export
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Export extends Doctrine_Connection_Module
{
    /**
     * drop an existing database
     * (this method is implemented by the drivers)
     *
     * @param string $name name of the database that should be dropped
     * @return void
     */
    public function dropDatabase($database)
    {
        throw new Doctrine_Export_Exception('Drop database not supported by this driver.');
    }
    /**
     * dropTable
     * drop an existing table
     *
     * @param string $table           name of table that should be dropped from the database
     * @throws PDOException
     * @return void
     */
    public function dropTable($table)
    {
        $this->conn->execute('DROP TABLE ' . $table);
    }

    /**
     * drop existing index
     *
     * @param string    $table        name of table that should be used in method
     * @param string    $name         name of the index to be dropped
     * @return void
     */
    public function dropIndex($table, $name)
    {
        $name = $this->conn->quoteIdentifier($this->conn->getIndexName($name), true);
        return $this->conn->exec('DROP INDEX ' . $name);
    }
    /**
     * drop existing constraint
     *
     * @param string    $table        name of table that should be used in method
     * @param string    $name         name of the constraint to be dropped
     * @param string    $primary      hint if the constraint is primary
     * @return void
     */
    public function dropConstraint($table, $name, $primary = false)
    {
        $table = $this->conn->quoteIdentifier($table, true);
        $name  = $this->conn->quoteIdentifier($this->conn->getIndexName($name), true);
        return $this->conn->exec('ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $name);
    }
    /**
     * drop existing sequence
     * (this method is implemented by the drivers)
     *
     * @param string    $seq_name     name of the sequence to be dropped
     * @return void
     */
    public function dropSequence($name)
    {
        throw new Doctrine_Export_Exception('Drop sequence not supported by this driver.');
    }
    /**
     * create a new database
     * (this method is implemented by the drivers)
     *
     * @param string $name name of the database that should be created
     * @return void
     */
    public function createDatabase($database)
    {
        throw new Doctrine_Export_Exception('Create database not supported by this driver.');
    }
    /**
     * create a new table
     *
     * @param string $name   Name of the database that should be created
     * @param array $fields  Associative array that contains the definition of each field of the new table
     *                       The indexes of the array entries are the names of the fields of the table an
     *                       the array entry values are associative arrays like those that are meant to be
     *                       passed with the field definitions to get[Type]Declaration() functions.
     *                          array(
     *                              'id' => array(
     *                                  'type' => 'integer',
     *                                  'unsigned' => 1
     *                                  'notnull' => 1
     *                                  'default' => 0
     *                              ),
     *                              'name' => array(
     *                                  'type' => 'text',
     *                                  'length' => 12
     *                              ),
     *                              'password' => array(
     *                                  'type' => 'text',
     *                                  'length' => 12
     *                              )
     *                          );
     * @param array $options  An associative array of table options:
     *
     * @return string
     */
    public function createTableSql($name, array $fields, array $options = array())
    {
        if ( ! $name) {
            throw new Doctrine_Export_Exception('no valid table name specified');
        }
        
        if (empty($fields)) {
            throw new Doctrine_Export_Exception('no fields specified for table '.$name);
        }
        $queryFields = $this->getFieldDeclarationList($fields);

        if (isset($options['primary']) && ! empty($options['primary'])) {
            $queryFields .= ', PRIMARY KEY(' . implode(', ', array_values($options['primary'])) . ')';
        }
        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach($options['indexes'] as $index => $definition) {
                $queryFields .= ', ' . $this->getIndexDeclaration($index, $definition);
            }
        }

        $name  = $this->conn->quoteIdentifier($name, true);
        $query = 'CREATE TABLE ' . $name . ' (' . $queryFields . ')';

        return $query;
    }
    /**
     * create a new table
     *
     * @param string $name   Name of the database that should be created
     * @param array $fields  Associative array that contains the definition of each field of the new table
     * @param array $options  An associative array of table options:
     * @see Doctrine_Export::createTableSql()
     *
     * @return void
     */
    public function createTable($name, array $fields, array $options = array())
    {
        return $this->conn->execute($this->createTableSql($name, $fields, $options));
    }
    /**
     * create sequence
     *
     * @param string    $seqName        name of the sequence to be created
     * @param string    $start          start value of the sequence; default is 1
     * @return void
     */
    public function createSequence($seqName, $start = 1)
    {
        return $this->conn->execute($this->createSequenceSql($seqName, $start = 1));  
    }
    /**
     * return RDBMS specific create sequence statement
     * (this method is implemented by the drivers)
     *
     * @param string    $seqName        name of the sequence to be created
     * @param string    $start          start value of the sequence; default is 1
     * @return string
     */
    public function createSequenceSql($seqName, $start = 1)
    {
        throw new Doctrine_Export_Exception('Create sequence not supported by this driver.');
    }
    /**
     * create a constraint on a table
     *
     * @param string    $table         name of the table on which the constraint is to be created
     * @param string    $name          name of the constraint to be created
     * @param array     $definition    associative array that defines properties of the constraint to be created.
     *                                 Currently, only one property named FIELDS is supported. This property
     *                                 is also an associative with the names of the constraint fields as array
     *                                 constraints. Each entry of this array is set to another type of associative
     *                                 array that specifies properties of the constraint that are specific to
     *                                 each field.
     *
     *                                 Example
     *                                    array(
     *                                        'fields' => array(
     *                                            'user_name' => array(),
     *                                            'last_login' => array()
     *                                        )
     *                                    )
     * @return void
     */
    public function createConstraint($table, $name, $definition)
    {
        $table = $this->conn->quoteIdentifier($table, true);
        $name = $this->conn->quoteIdentifier($this->conn->getIndexName($name), true);
        $query = 'ALTER TABLE ' . $table . ' ADD CONSTRAINT ' . $name;
        if (!empty($definition['primary'])) {
            $query .= ' PRIMARY KEY';
        } elseif (!empty($definition['unique'])) {
            $query .= ' UNIQUE';
        }
        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $this->conn->quoteIdentifier($field, true);
        }
        $query .= ' ('. implode(', ', $fields) . ')';
        return $this->conn->exec($query);
    }
    /**
     * Get the stucture of a field into an array
     *
     * @param string    $table         name of the table on which the index is to be created
     * @param string    $name          name of the index to be created
     * @param array     $definition    associative array that defines properties of the index to be created.
     *                                 Currently, only one property named FIELDS is supported. This property
     *                                 is also an associative with the names of the index fields as array
     *                                 indexes. Each entry of this array is set to another type of associative
     *                                 array that specifies properties of the index that are specific to
     *                                 each field.
     *
     *                                 Currently, only the sorting property is supported. It should be used
     *                                 to define the sorting direction of the index. It may be set to either
     *                                 ascending or descending.
     *
     *                                 Not all DBMS support index sorting direction configuration. The DBMS
     *                                 drivers of those that do not support it ignore this property. Use the
     *                                 function supports() to determine whether the DBMS driver can manage indexes.
     *
     *                                 Example
     *                                    array(
     *                                        'fields' => array(
     *                                            'user_name' => array(
     *                                                'sorting' => 'ascending'
     *                                            ),
     *                                            'last_login' => array()
     *                                        )
     *                                    )
     * @return void
     */
    public function createIndex($table, $name, array $definition)
    {
        return $this->conn->execute($this->createIndexSql($table, $name, $definition));
    }
    /**
     * Get the stucture of a field into an array
     *
     * @param string    $table         name of the table on which the index is to be created
     * @param string    $name          name of the index to be created
     * @param array     $definition    associative array that defines properties of the index to be created.
     * @see Doctrine_Export::createIndex()
     * @return string
     */
    public function createIndexSql($table, $name, array $definition)
    {
        $table  = $this->conn->quoteIdentifier($table);
        $name   = $this->conn->quoteIdentifier($name);
        $type   = '';

        if(isset($definition['type'])) {
            switch (strtolower($definition['type'])) {
                case 'unique':
                    $type = strtoupper($definition['type']) . ' ';
                break;
                default:
                    throw new Doctrine_Export_Exception('Unknown index type ' . $definition['type']);
            }
        }

        $query = 'CREATE ' . $type . 'INDEX ' . $name . ' ON ' . $table;

        $fields = array();
        foreach (array_keys($definition['fields']) as $field) {
            $fields[] = $this->conn->quoteIdentifier($field);
        }
        $query .= ' (' . implode(', ', $fields) . ')';

        return $query;
    }
    /**
     * createForeignKey
     *
     * @param string    $table         name of the table on which the index is to be created
     * @param string    $name          name of the foreign key to be created
     * @param array     $definition    associative array that defines properties of the foreign key to be created.
     */
    public function createForeignKey($table, $name, array $definition)
    {

    }
    /**
     * alter an existing table
     * (this method is implemented by the drivers)
     *
     * @param string $name         name of the table that is intended to be changed.
     * @param array $changes     associative array that contains the details of each type
     *                             of change that is intended to be performed. The types of
     *                             changes that are currently supported are defined as follows:
     *
     *                             name
     *
     *                                New name for the table.
     *
     *                            add
     *
     *                                Associative array with the names of fields to be added as
     *                                 indexes of the array. The value of each entry of the array
     *                                 should be set to another associative array with the properties
     *                                 of the fields to be added. The properties of the fields should
     *                                 be the same as defined by the MDB2 parser.
     *
     *
     *                            remove
     *
     *                                Associative array with the names of fields to be removed as indexes
     *                                 of the array. Currently the values assigned to each entry are ignored.
     *                                 An empty array should be used for future compatibility.
     *
     *                            rename
     *
     *                                Associative array with the names of fields to be renamed as indexes
     *                                 of the array. The value of each entry of the array should be set to
     *                                 another associative array with the entry named name with the new
     *                                 field name and the entry named Declaration that is expected to contain
     *                                 the portion of the field declaration already in DBMS specific SQL code
     *                                 as it is used in the CREATE TABLE statement.
     *
     *                            change
     *
     *                                Associative array with the names of the fields to be changed as indexes
     *                                 of the array. Keep in mind that if it is intended to change either the
     *                                 name of a field and any other properties, the change array entries
     *                                 should have the new names of the fields as array indexes.
     *
     *                                The value of each entry of the array should be set to another associative
     *                                 array with the properties of the fields to that are meant to be changed as
     *                                 array entries. These entries should be assigned to the new values of the
     *                                 respective properties. The properties of the fields should be the same
     *                                 as defined by the MDB2 parser.
     *
     *                            Example
     *                                array(
     *                                    'name' => 'userlist',
     *                                    'add' => array(
     *                                        'quota' => array(
     *                                            'type' => 'integer',
     *                                            'unsigned' => 1
     *                                        )
     *                                    ),
     *                                    'remove' => array(
     *                                        'file_limit' => array(),
     *                                        'time_limit' => array()
     *                                    ),
     *                                    'change' => array(
     *                                        'name' => array(
     *                                            'length' => '20',
     *                                            'definition' => array(
     *                                                'type' => 'text',
     *                                                'length' => 20,
     *                                            ),
     *                                        )
     *                                    ),
     *                                    'rename' => array(
     *                                        'sex' => array(
     *                                            'name' => 'gender',
     *                                            'definition' => array(
     *                                                'type' => 'text',
     *                                                'length' => 1,
     *                                                'default' => 'M',
     *                                            ),
     *                                        )
     *                                    )
     *                                )
     *
     * @param boolean $check     indicates whether the function should just check if the DBMS driver
     *                             can perform the requested table alterations if the value is true or
     *                             actually perform them otherwise.
     * @return void
     */
    public function alterTable($name, array $changes, $check)
    {
        $this->conn->execute($this->alterTableSql($name, $changes, $check));
    }
    /**
     * generates the sql for altering an existing table
     * (this method is implemented by the drivers)
     *
     * @param string $name          name of the table that is intended to be changed.
     * @param array $changes        associative array that contains the details of each type      *
     * @param boolean $check        indicates whether the function should just check if the DBMS driver
     *                              can perform the requested table alterations if the value is true or
     *                              actually perform them otherwise.
     * @see Doctrine_Export::alterTable()
     * @return string
     */
    public function alterTableSql($name, array $changes, $check)
    {
        throw new Doctrine_Export_Exception('Alter table not supported by this driver.');
    }
    /**
     * Get declaration of a number of field in bulk
     *
     * @param array $fields  a multidimensional associative array.
     *      The first dimension determines the field name, while the second
     *      dimension is keyed with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      default
     *          Text value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     *      charset
     *          Text value with the default CHARACTER SET for this field.
     *      collation
     *          Text value with the default COLLATION for this field.
     *      unique
     *          unique constraint
     *
     * @return string
     */
    public function getFieldDeclarationList(array $fields)
    {
        foreach ($fields as $fieldName => $field) {
            $query = $this->getDeclaration($fieldName, $field);

            $queryFields[] = $query;
        }
        return implode(', ', $queryFields);
    }
    /**
     * Obtain DBMS specific SQL code portion needed to declare a generic type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param array  $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      default
     *          Text value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     *      charset
     *          Text value with the default CHARACTER SET for this field.
     *      collation
     *          Text value with the default COLLATION for this field.
     *      unique
     *          unique constraint
     *
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     */
    public function getDeclaration($name, array $field)
    {

        $default   = $this->getDefaultFieldDeclaration($field);

        $charset   = (isset($field['charset']) && $field['charset']) ?
                    ' ' . $this->getCharsetFieldDeclaration($field['charset']) : '';

        $collation = (isset($field['collation']) && $field['collation']) ?
                    ' ' . $this->getCollationFieldDeclaration($field['collation']) : '';

        $notnull   = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';

        $unique    = (isset($field['unique']) && $field['unique']) ?
                    ' ' . $this->getUniqueFieldDeclaration() : '';

        $method = 'get' . $field['type'] . 'Declaration';

        if (method_exists($this->conn->dataDict, $method)) {
            return $this->conn->dataDict->$method($name, $field);
        } else {
            $dec = $this->conn->dataDict->getNativeDeclaration($field);
        }
        return $this->conn->quoteIdentifier($name, true) . ' ' . $dec . $charset . $default . $notnull . $unique . $collation;
    }
    /**
     * getDefaultDeclaration
     * Obtain DBMS specific SQL code portion needed to set a default value
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param array $field      field definition array
     * @return string           DBMS specific SQL code portion needed to set a default value
     */
    public function getDefaultFieldDeclaration($field)
    {
        $default = '';
        if (isset($field['default'])) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull'])
                    ? null : $this->valid_default_values[$field['type']];

                if ($field['default'] === ''
                    && ($conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_EMPTY_TO_NULL)
                ) {
                    $field['default'] = ' ';
                }
            }
    
            $default = ' DEFAULT ' . $this->conn->quote($field['default'], $field['type']);
        }
        return $default;
    }
    /**
     * Obtain DBMS specific SQL code portion needed to set an index 
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param string $charset       name of the index
     * @param array $definition     index definition
     * @return string               DBMS specific SQL code portion needed to set an index
     */
    public function getIndexDeclaration($name, array $definition)
    {
        $name   = $this->conn->quoteIdentifier($name);
        $type   = '';

        if (isset($definition['type'])) {
            if (strtolower($definition['type']) == 'unique') {
                $type = strtoupper($definition['type']) . ' ';
            } else {
                throw new Doctrine_Export_Exception('Unknown index type ' . $definition['type']);
            }
        }
        
        if ( ! isset($definition['fields']) || ! is_array($definition['fields'])) {
            throw new Doctrine_Export_Exception('No index columns given.');
        }

        $query = $type . 'INDEX ' . $name;

        $query .= ' (' . $this->getIndexFieldDeclarationList($definition['fields']) . ')';
        
        return $query;
    }
    /**
     * getIndexFieldDeclarationList
     * Obtain DBMS specific SQL code portion needed to set an index
     * declaration to be used in statements like CREATE TABLE.
     *
     * @return string
     */
    public function getIndexFieldDeclarationList(array $fields)
    {
    	$ret = array();
        foreach ($fields as $field => $definition) {
            if(is_array($definition)) {
                $ret[] = $this->conn->quoteIdentifier($field);
            } else {
                $ret[] = $this->conn->quoteIdentifier($definition);
            }
        }
        return implode(', ', $ret);
    }
    /**
     * A method to return the required SQL string that fits between CREATE ... TABLE
     * to create the table as a temporary table.
     *
     * Should be overridden in driver classes to return the correct string for the
     * specific database type.
     *
     * The default is to return the string "TEMPORARY" - this will result in a
     * SQL error for any database that does not support temporary tables, or that
     * requires a different SQL command from "CREATE TEMPORARY TABLE".
     *
     * @return string The string required to be placed between "CREATE" and "TABLE"
     *                to generate a temporary table, if possible.
     */
    public function getTemporaryTableQuery()
    {
        return 'TEMPORARY';
    }
    /**
     * getForeignKeyDeclaration
     * Obtain DBMS specific SQL code portion needed to set the FOREIGN KEY constraint
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param array $definition         an associative array with the following structure:
     *          name                    optional constraint name
     * 
     *          local                   the local field(s)
     *
     *          foreign                 the foreign reference field(s)
     *
     *          foreignTable            the name of the foreign table
     *
     *          onDelete                referential delete action
     *  
     *          onUpdate                referential update action
     *
     *          deferred                deferred constraint checking
     *
     * The onDelete and onUpdate keys accept the following values:
     *
     * CASCADE: Delete or update the row from the parent table and automatically delete or 
     *          update the matching rows in the child table. Both ON DELETE CASCADE and ON UPDATE CASCADE are supported.
     *          Between two tables, you should not define several ON UPDATE CASCADE clauses that act on the same column
     *          in the parent table or in the child table.
     *
     * SET NULL: Delete or update the row from the parent table and set the foreign key column or columns in the
     *          child table to NULL. This is valid only if the foreign key columns do not have the NOT NULL qualifier 
     *          specified. Both ON DELETE SET NULL and ON UPDATE SET NULL clauses are supported.
     *
     * NO ACTION: In standard SQL, NO ACTION means no action in the sense that an attempt to delete or update a primary 
     *           key value is not allowed to proceed if there is a related foreign key value in the referenced table.
     *
     * RESTRICT: Rejects the delete or update operation for the parent table. NO ACTION and RESTRICT are the same as
     *           omitting the ON DELETE or ON UPDATE clause.
     *
     * SET DEFAULT
     *
     * @return string  DBMS specific SQL code portion needed to set the FOREIGN KEY constraint
     *                 of a field declaration.
     */
    public function getForeignKeyDeclaration(array $definition)
    {
        $sql  = $this->getForeignKeyBaseDeclaration($definition);
        
        if (isset($definition['deferred'])) {
            $sql .= ' ' . $this->getForeignKeyDeferredDeclaration();
        }

        $a = array('onUpdate', 'onDelete');
        foreach($a as $v) {
            $keyword = ($v == 'onUpdate') ? ' ON UPDATE ' : ' ON DELETE ';

            if (isset($definition[$v])) {
                switch ($definition[$v]) {
                    case 'CASCADE':
                    case 'SET NULL':
                    case 'NO ACTION':
                    case 'RESTRICT':
                    case 'SET DEFAULT':
                        $sql .= $keyword . $definition[$v];
                    break;
                    default:
                        throw new Doctrine_Export_Exception('Unknown foreign key referential action option given.');
                }
            }
        }
        return $sql;
    }
    /** 
     * getForeignKeyDeferredDeclaration
     *
     * @return string
     */
    public function getForeignKeyDeferredDeclaration($deferred)
    {
        return '';
    }
    /**
     * getForeignKeyBaseDeclaration
     * Obtain DBMS specific SQL code portion needed to set the FOREIGN KEY constraint
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param array $definition
     * @return string
     */
    public function getForeignKeyBaseDeclaration(array $definition)
    {
    	$sql = '';
        if (isset($definition['name'])) {
            $sql .= 'CONSTRAINT ' . $definition['name'] . ' ';
        }
        $sql .= 'FOREIGN KEY ';

        if ( ! isset($definition['local'])) {
            throw new Doctrine_Export_Exception('Local reference field missing from definition.');
        }
        if ( ! isset($definition['foreign'])) {
            throw new Doctrine_Export_Exception('Foreign reference field missing from definition.');
        }
        if ( ! isset($definition['foreignTable'])) {
            throw new Doctrine_Export_Exception('Foreign reference table missing from definition.');
        }

        if ( ! is_array($definition['local'])) {
            $definition['local'] = array($definition['local']);
        }
        if ( ! is_array($definition['foreign'])) {
            $definition['foreign'] = array($definition['foreign']);
        }
        $sql .= implode(', ', array_map(array($this->conn, 'quoteIdentifier'), $definition['local']))
              . ' REFERENCES '
              . $definition['foreignTable'] . '('
              . implode(', ', array_map(array($this->conn, 'quoteIdentifier'), $definition['foreign'])) . ')';
        
        return $sql;
    }
    /**
     * Obtain DBMS specific SQL code portion needed to set the UNIQUE constraint
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @return string  DBMS specific SQL code portion needed to set the UNIQUE constraint
     *                 of a field declaration.
     */
    public function getUniqueFieldDeclaration()
    {
        return 'UNIQUE';
    }
    /**
     * Obtain DBMS specific SQL code portion needed to set the CHARACTER SET
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $charset   name of the charset
     * @return string  DBMS specific SQL code portion needed to set the CHARACTER SET
     *                 of a field declaration.
     */
    public function getCharsetFieldDeclaration($charset)
    {
        return '';
    }
    /**
     * Obtain DBMS specific SQL code portion needed to set the COLLATION
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $collation   name of the collation
     * @return string  DBMS specific SQL code portion needed to set the COLLATION
     *                 of a field declaration.
     */
    public function getCollationFieldDeclaration($collation)
    {
        return '';
    }
    /**
     * export
     * method for exporting Doctrine_Record classes to a schema
     *
     * @return void
     */
    public static function exportAll()
    {
        $parent = new ReflectionClass('Doctrine_Record');
        $conn   = Doctrine_Manager::getInstance()->getCurrentConnection();
        $old    = $conn->getAttribute(Doctrine::ATTR_CREATE_TABLES);

        $conn->setAttribute(Doctrine::ATTR_CREATE_TABLES, true);

        foreach (get_declared_classes() as $name) {
            $class = new ReflectionClass($name);

            if ($class->isSubclassOf($parent) && ! $class->isAbstract()) {
                $obj = new $class();
            }
        }
        $conn->setAttribute(Doctrine::ATTR_CREATE_TABLES, $old);
    }
    public function export($record)
    {
        if ( ! $record instanceof Doctrine_Record)
            $record = new $record();

        $table = $record->getTable();

        $reporter = new Doctrine_Reporter();

        if ( ! Doctrine::isValidClassname($table->getComponentName())) {
            $reporter->add(E_WARNING, 'Badly named class.');
        }

        try {
            $columns = array();
            foreach ($table->getColumns() as $name => $column) {
                $definition = $column[2];
                $definition['type'] = $column[0];
                $definition['length'] = $column[1];

                if ($definition['type'] == 'enum' && isset($definition['default'])) {
                    $definition['default'] = $table->enumIndex($name, $definition['default']);
                }
                if ($definition['type'] == 'boolean' && isset($definition['default'])) {
                    $definition['default'] = (int) $definition['default'];
                }
                $columns[$name] = $definition;
            }

            $this->createTable($table->getTableName(), $columns);

        } catch(Doctrine_Connection_Exception $e) {
            $reporter->add(E_ERROR, $e->getMessage());
        }

        return $reporter;
    }
}
