<?php
/*
 * $Id: Schema.php 1838 2007-06-26 00:58:21Z nicobn $
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

/**
 * class Doctrine_Import_Schema
 *
 * Different methods to import a XML schema. The logic behind using two different
 * methods is simple. Some people will like the idea of producing Doctrine_Record
 * objects directly, which is totally fine. But in fast and growing application,
 * table definitions tend to be a little bit more volatile. importArr() can be used
 * to output a table definition in a PHP file. This file can then be stored 
 * independantly from the object itself.
 *
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 1838 $
 * @author      Nicolas Bérard-Nault <nicobn@gmail.com>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Doctrine_Import_Schema
{
    public $relations = array();
    
    
    /**
     * importSchema
     *
     * A method to import a Schema and translate it into a Doctrine_Record object
     *
     * @param  string $schema       The file containing the XML schema
     * @param  string $directory    The directory where the Doctrine_Record class will be written
     * @param  array $models        Optional array of models to import
     * 
     * @access public
     */
    public function importSchema($schema, $format, $directory, $models = array())
    {
        $builder = new Doctrine_Import_Builder();
        $builder->setTargetPath($directory);
        
        $array = array();
        foreach ((array) $schema AS $s) {
            $array = array_merge($array, $this->parseSchema($s, $format));
        }
        
        $this->buildRelationships($array);
        
        foreach ($array as $name => $properties) {
            if (!empty($models) && !in_array($properties['className'], $models)) {
                continue;
            }
            
            $options = array();
            $options['className'] = $properties['className'];
            $options['fileName'] = $directory.DIRECTORY_SEPARATOR.$properties['className'].'.class.php';
            $options['tableName'] = isset($properties['tableName'])?$properties['tableName']:null;
            $columns = $properties['columns'];
            
            $relations = isset($this->relations[$options['className']]) ? $this->relations[$options['className']]:array();
            
            $builder->buildRecord($options, $columns, $relations);
        }
    }
    
    /**
     * parseSchema
     *
     * A method to parse a Yml Schema and translate it into a property array. 
     * The function returns that property array.
     *
     * @param  string $schema   Path to the file containing the XML schema
     * @return array
     */
    public function parseSchema($schema, $type)
    {
        $array = Doctrine_Parser::load($schema, $type);
        
        $build = array();
        
        foreach ($array as $className => $table) {
            $columns = array();
            
            $className = isset($table['className']) ? (string) $table['className']:(string) $className;
            $tableName = isset($table['tableName']) ? (string) $table['tableName']:(string) $className;
            
            foreach ($table['columns'] as $columnName => $field) {
                
                $colDesc = array();
                $colDesc['name'] = isset($field['name']) ? (string) $field['name']:$columnName;
                $colDesc['type'] = isset($field['type']) ? (string) $field['type']:null;
                $colDesc['ptype'] = isset($field['ptype']) ? (string) $field['ptype']:(string) $colDesc['type'];
                $colDesc['length'] = isset($field['length']) ? (int) $field['length']:null;
                $colDesc['fixed'] = isset($field['fixed']) ? (int) $field['fixed']:null;
                $colDesc['unsigned'] = isset($field['unsigned']) ? (bool) $field['unsigned']:null;
                $colDesc['primary'] = isset($field['primary']) ? (bool) (isset($field['primary']) && $field['primary']):null;
                $colDesc['default'] = isset($field['default']) ? (string) $field['default']:null;
                $colDesc['notnull'] = isset($field['notnull']) ? (bool) (isset($field['notnull']) && $field['notnull']):null;
                $colDesc['autoinc'] = isset($field['autoinc']) ? (bool) (isset($field['autoinc']) && $field['autoinc']):null;
                $colDesc['values'] = isset($field['values']) ? (array) $field['values']: null;
                
                $columns[(string) $colDesc['name']] = $colDesc;
            }

            $build[$className]['tableName'] = $tableName;
            $build[$className]['className'] = $className;

            $build[$className]['columns'] = $columns;
            $build[$className]['relations'] = isset($table['relations']) ? $table['relations']:array();
        }
        
        return $build;
    }
    
    public function buildRelationships($array)
    {
        foreach ($array as $name => $properties) {
            $className = $properties['className'];     
            $relations = $properties['relations'];
            $columns = $properties['columns'];
            
            foreach ($relations as $alias => $relation) {
 
                $class = isset($relation['class']) ? $relation['class']:$alias;
                
                $relation['foreign'] = isset($relation['foreign'])?$relation['foreign']:'id';                
                $relation['alias'] = $alias;
                $relation['class'] = $class;
                
                if (isset($relation['type']) && $relation['type']) {
                    $relation['type'] = $relation['type'] === 'one' ? Doctrine_Relation::ONE:Doctrine_Relation::MANY;
                } else {
                    $relation['type'] = Doctrine_Relation::ONE;
                }
                
                $this->relations[$className][$class] = $relation;
            }
        }
    }
}