<?php

class Doctrine_DatabasePlatform_OraclePlatform extends Doctrine_DatabasePlatform
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_supported = array(
                          'sequences'            => true,
                          'indexes'              => true,
                          'summary_functions'    => true,
                          'order_by_text'        => true,
                          'current_id'           => true,
                          'affected_rows'        => true,
                          'transactions'         => true,
                          'savepoints'           => true,
                          'limit_queries'        => true,
                          'LOBs'                 => true,
                          'replace'              => 'emulated',
                          'sub_selects'          => true,
                          'auto_increment'       => false, // implementation is broken
                          'primary_key'          => true,
                          'result_introspection' => true,
                          'prepared_statements'  => true,
                          'identifier_quoting'   => true,
                          'pattern_escaping'     => true,
                          );
    }
    
    /**
     * Adds an driver-specific LIMIT clause to the query
     *
     * @param string $query         query to modify
     * @param integer $limit        limit the number of rows
     * @param integer $offset       start reading from given offset
     * @return string               the modified query
     * @override
     */
    public function writeLimitClause($query, $limit = false, $offset = false)
    {
        return $this->_createLimitSubquery($query, $limit, $offset);
    }
    
    private function _createLimitSubquery($query, $limit, $offset, $column = null)
    {
        $limit = (int) $limit;
        $offset = (int) $offset;
        if (preg_match('/^\s*SELECT/i', $query)) {
            if ( ! preg_match('/\sFROM\s/i', $query)) {
                $query .= " FROM dual";
            }
            if ($limit > 0) {
                $max = $offset + $limit;
                $column = $column === null ? '*' : $column;
                if ($offset > 0) {
                    $min = $offset + 1;
                    $query = 'SELECT b.'.$column.' FROM ('.
                                 'SELECT a.*, ROWNUM AS doctrine_rownum FROM ('
                                   . $query . ') a '.
                              ') b '.
                              'WHERE doctrine_rownum BETWEEN ' . $min .  ' AND ' . $max;
                } else {
                    $query = 'SELECT a.'.$column.' FROM (' . $query .') a WHERE ROWNUM <= ' . $max;
                }
            }
        }
        return $query;
    }
    
    /**
     * Creates the SQL for Oracle that can be used in the subquery for the limit-subquery
     * algorithm.
     * 
     * @override
     */
    public function writeLimitClauseInSubquery(Doctrine_ClassMetadata $rootClass,
            $query, $limit = false, $offset = false)
    {
        // NOTE: no composite key support
        $columnNames = $rootClass->getIdentifierColumnNames();
        if (count($columnNames) > 1) {
            throw new Doctrine_Connection_Exception("Composite keys in LIMIT queries are "
                    . "currently not supported.");
        }
        $column = $columnNames[0];
        
        return $this->_createLimitSubquery($query, $limit, $offset, $column);
    }
    
    /**
     * return string to call a function to get a substring inside an SQL statement
     *
     * Note: Not SQL92, but common functionality.
     *
     * @param string $value         an sql string literal or column name/alias
     * @param integer $position     where to start the substring portion
     * @param integer $length       the substring portion length
     * @return string               SQL substring function with given parameters
     * @override
     */
    public function getSubstringExpression($value, $position, $length = null)
    {
        if ($length !== null)
            return "SUBSTR($value, $position, $length)";

        return "SUBSTR($value, $position)";
    }

    /**
     * Return string to call a variable with the current timestamp inside an SQL statement
     * There are three special variables for current date and time:
     * - CURRENT_TIMESTAMP (date and time, TIMESTAMP type)
     * - CURRENT_DATE (date, DATE type)
     * - CURRENT_TIME (time, TIME type)
     *
     * @return string to call a variable with the current timestamp
     * @override
     */
    public function getNowExpression($type = 'timestamp')
    {
        switch ($type) {
            case 'date':
            case 'time':
            case 'timestamp':
            default:
                return 'TO_CHAR(CURRENT_TIMESTAMP, \'YYYY-MM-DD HH24:MI:SS\')';
        }
    }

    /**
     * random
     *
     * @return string           an oracle SQL string that generates a float between 0 and 1
     * @override
     */
    public function getRandomExpression()
    {
        return 'dbms_random.value';
    }

    /**
     * Returns global unique identifier
     *
     * @return string to get global unique identifier
     * @override
     */
    public function getGuidExpression()
    {
        return 'SYS_GUID()';
    }
    
    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param array $field  associative array with the name of the properties
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
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @override
     */
    public function getNativeDeclaration(array $field)
    {
        if ( ! isset($field['type'])) {
            throw new Doctrine_DataDict_Exception('Missing column type.');
        }
        switch ($field['type']) {
            case 'string':
            case 'array':
            case 'object':
            case 'gzip':
            case 'char':
            case 'varchar':
                $length = !empty($field['length'])
                    ? $field['length'] : 16777215; // TODO: $this->conn->options['default_text_field_length'];

                $fixed  = ((isset($field['fixed']) && $field['fixed']) || $field['type'] == 'char') ? true : false;

                return $fixed ? 'CHAR('.$length.')' : 'VARCHAR2('.$length.')';
            case 'clob':
                return 'CLOB';
            case 'blob':
                return 'BLOB';
            case 'integer':
            case 'enum':
            case 'int':
                if ( ! empty($field['length'])) {
                    return 'NUMBER('.$field['length'].')';
                }
                return 'INT';
            case 'boolean':
                return 'NUMBER(1)';
            case 'date':
            case 'time':
            case 'timestamp':
                return 'DATE';
            case 'float':
            case 'double':
                return 'NUMBER';
            case 'decimal':
                $scale = !empty($field['scale']) ? $field['scale'] : $this->conn->getAttribute(Doctrine::ATTR_DECIMAL_PLACES);
                return 'NUMBER(*,'.$scale.')';
            default:
        }
        throw new Doctrine_DataDict_Exception('Unknown field type \'' . $field['type'] .  '\'.');
    }

    /**
     * Maps a native array description of a field to a doctrine datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     * @throws Doctrine_DataDict_Oracle_Exception
     * @override
     */
    public function getPortableDeclaration(array $field)
    {
        if ( ! isset($field['data_type'])) {
            throw new Doctrine_DataDict_Exception('Native oracle definition must have a data_type key specified');
        }
        
        $dbType = strtolower($field['data_type']);
        $type = array();
        $length = $unsigned = $fixed = null;
        if ( ! empty($field['data_length'])) {
            $length = $field['data_length'];
        }

        if ( ! isset($field['column_name'])) {
            $field['column_name'] = '';
        }

        switch ($dbType) {
            case 'integer':
            case 'pls_integer':
            case 'binary_integer':
                $type[] = 'integer';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['column_name'])) {
                        $type = array_reverse($type);
                    }
                }
                break;
            case 'varchar':
            case 'varchar2':
            case 'nvarchar2':
                $fixed = false;
            case 'char':
            case 'nchar':
                $type[] = 'string';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['column_name'])) {
                        $type = array_reverse($type);
                    }
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
                break;
            case 'date':
            case 'timestamp':
                $type[] = 'timestamp';
                $length = null;
                break;
            case 'float':
                $type[] = 'float';
                break;
            case 'number':
                if ( ! empty($field['data_scale'])) {
                    $type[] = 'decimal';
                } else {
                    $type[] = 'integer';
                    if ($length == '1') {
                        $type[] = 'boolean';
                        if (preg_match('/^(is|has)/', $field['column_name'])) {
                            $type = array_reverse($type);
                        }
                    }
                }
                break;
            case 'long':
                $type[] = 'string';
            case 'clob':
            case 'nclob':
                $type[] = 'clob';
                break;
            case 'blob':
            case 'raw':
            case 'long raw':
            case 'bfile':
                $type[] = 'blob';
                $length = null;
            break;
            case 'rowid':
            case 'urowid':
            default:
                throw new Doctrine_DataDict_Exception('unknown database attribute type: ' . $dbType);
        }

        return array('type'     => $type,
                     'length'   => $length,
                     'unsigned' => $unsigned,
                     'fixed'    => $fixed);
    }
}

?>