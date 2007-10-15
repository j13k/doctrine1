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

/**
 * Doctrine_Import_Builder
 * Import builder is responsible of building Doctrine ActiveRecord classes
 * based on a database schema.
 *
 * @package     Doctrine
 * @subpackage  Import
 * @link        www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
 * @author      Nicolas Bérard-Nault <nicobn@php.net>
 */
class Doctrine_Import_Builder
{
    /**
     * Path
     * 
     * the path where imported files are being generated
     *
     * @var string $path
     */
    private $path = '';

    /**
     * suffix
     * 
     * File suffix to use when writing class definitions
     *
     * @var string $suffix
     */
    private $suffix = '.class.php';
    
    /**
     * generateBaseClasses
     * 
     * Bool true/false for whether or not to generate base classes
     *
     * @var string $suffix
     */
    private $generateBaseClasses = false;
    
    /**
     * baseClassesDirectory
     * 
     * Directory to put the generate base classes in
     *
     * @var string $suffix
     */
    private $baseClassesDirectory = 'generated';
    
    /**
     * tpl
     *
     * Class template used for writing classes
     *
     * @var $tpl
     */
    private static $tpl;
    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        $this->loadTemplate();
    }

    /**
     * setTargetPath
     *
     * @param string path   the path where imported files are being generated
     * @return
     */
    public function setTargetPath($path)
    {
        if ( ! file_exists($path)) {
            mkdir($path, 0777);
        }

        $this->path = $path;
    }
    
    /**
     * generateBaseClasses
     *
     * Specify whether or not to generate classes which extend from generated base classes
     *
     * @param string $bool
     * @return void
     * @author Jonathan H. Wage
     */
    public function generateBaseClasses($bool = null)
    {
      if ($bool !== null) {
        $this->generateBaseClasses = $bool;
      }
      
      return $this->generateBaseClasses;
    }
    
    /**
     * getTargetPath
     *
     * @return string       the path where imported files are being generated
     */
    public function getTargetPath()
    {
        return $this->path;
    }

    /**
     * loadTemplate
     * 
     * Loads the class template used for generating classes
     *
     * @return void
     */
    public function loadTemplate() 
    {
        if (isset(self::$tpl)) {
            return;
        }

        self::$tpl =<<<END
/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
%sclass %s extends %s
{
%s
%s
%s
}
END;

    }

    /*
     * Build the accessors
     *
     * @param  string $table
     * @param  array  $columns
     */
    public function buildAccessors(array $options, array $columns)
    {
        $ret = '';
        foreach ($columns as $name => $column) {
            // getters
            $ret .= "\n\tpublic function get".Doctrine::classify($name)."(\$load = true)\n";
            $ret .= "\t{\n";
            $ret .= "\t\treturn \$this->get('{$name}', \$load);\n";
            $ret .= "\t}\n";

            // setters
            $ret .= "\n\tpublic function set".Doctrine::classify($name)."(\${$name}, \$load = true)\n";
            $ret .= "\t{\n";
            $ret .= "\t\treturn \$this->set('{$name}', \${$name}, \$load);\n";
            $ret .= "\t}\n";
        }

        return $ret;
    }

    /*
     * Build the table definition of a Doctrine_Record object
     *
     * @param  string $table
     * @param  array  $tableColumns
     */
    public function buildTableDefinition(array $options, array $columns, array $relations, array $indexes)
    {
        $ret = array();
        
        $i = 0;
        
        if (isset($options['inheritance']['extends']) && !isset($options['override_parent'])) {
            $ret[$i] = "\t\t\t\tparent::setTableDefinition();";
            $i++;
        }
        
        if (isset($options['tableName']) && !empty($options['tableName'])) {
            $ret[$i] = str_repeat(' ', 8) . '$this->setTableName(\''. $options['tableName'].'\');';
            
            $i++;
        }
        
        foreach ($columns as $name => $column) {
            $ret[$i] = '        $this->hasColumn(\'' . $name . '\', \'' . $column['type'] . '\'';
            
            if ($column['length']) {
                $ret[$i] .= ', ' . $column['length'];
            } else {
                $ret[$i] .= ', null';
            }

            $a = array();

            if (isset($column['default'])) {
                $a[] = '\'default\' => ' . var_export($column['default'], true);
            }
            if (isset($column['notnull']) && $column['notnull']) {
                $a[] = '\'notnull\' => true';
            }
            if (isset($column['primary']) && $column['primary']) {
                $a[] = '\'primary\' => true';
            }
            if ((isset($column['autoinc']) && $column['autoinc']) || isset($column['autoincrement']) && $column['autoincrement']) {
                $a[] = '\'autoincrement\' => true';
            }
            if (isset($column['unique']) && $column['unique']) {
                $a[] = '\'unique\' => true';
            }
            if (isset($column['unsigned']) && $column['unsigned']) {
                $a[] = '\'unsigned\' => true';
            }
            if ($column['type'] == 'enum' && isset($column['values']) ) {
                $a[] = '\'values\' => array(\'' . implode('\',\'', $column['values']) . '\')';
            }

            if ( ! empty($a)) {
                $ret[$i] .= ', ' . 'array(';
                $length = strlen($ret[$i]);
                $ret[$i] .= implode(',' . PHP_EOL . str_repeat(' ', $length), $a) . ')';
            }
            
            $ret[$i] .= ');';

            if ($i < (count($columns) - 1)) {
                $ret[$i] .= PHP_EOL;
            }
            $i++;
        }
        
        foreach ($indexes as $indexName => $definitions) {
            $ret[$i] = "\n".'        $this->index(\'' . $indexName . '\', array(';
            
            foreach ($definitions as $name => $value) {
              
              // parse fields
              if ($name === 'fields') {
                $ret[$i] .= '\'fields\' => array(';
                
                foreach ($value as $fieldName => $fieldValue) {
                  $ret[$i] .= '\'' . $fieldName . '\' => array( ';
                  
                  // parse options { sorting, length, primary }
                  if (isset($fieldValue) && $fieldValue) {
                    foreach ($fieldValue as $optionName => $optionValue) {
                      
                      $ret[$i] .= '\'' . $optionName . '\' => ';
                      
                      // check primary option, mark either as true or false
                      if ($optionName === 'primary') {
                      	$ret[$i] .= (($optionValue == 'true') ? 'true' : 'false') . ', ';
                      	continue;
                      }
                      
                      // convert sorting option to uppercase, for instance, asc -> ASC
                      if ($optionName === 'sorting') {
                      	$ret[$i] .= '\'' . strtoupper($optionValue) . '\', ';
                      	continue;
                      }
                      
                      // check the rest of the options
                      $ret[$i] .= '\'' . $optionValue . '\', ';
                    }
                  }
                                    
                  $ret[$i] .= '), ';
                }
              }
              
              // parse index type option, 4 choices { unique, fulltext, gist, gin }
              if ($name === 'type') {
              	$ret[$i] .= '), \'type\' => \'' . $value . '\'';
              }
              
              // add extra ) if type definition is not declared
              if (!isset($definitions['type'])) {
              	$ret[$i] .= ')';
              }
            }
            
            $ret[$i] .= '));';
            $i++;
        }
        
        if (!empty($ret)) {
          return "\n\tpublic function setTableDefinition()"."\n\t{\n".implode("\n", $ret)."\n\t}";
        }
    }
    
    public function buildSetUp(array $options, array $columns, array $relations)
    {
        $ret = array();
        $i = 0;
        
        if (! (isset($options['override_parent']) && $options['override_parent'] === true)) {
            $ret[$i] = "\t\t\t\tparent::setUp();";
            $i++;
        }
        
        foreach ($relations as $name => $relation) {
            $class = isset($relation['class']) ? $relation['class']:$name;
            $alias = (isset($relation['alias']) && $relation['alias'] !== $relation['class']) ? ' as ' . $relation['alias'] : '';

            if ( ! isset($relation['type'])) {
                $relation['type'] = Doctrine_Relation::ONE;
            }

            if ($relation['type'] === Doctrine_Relation::ONE || 
                $relation['type'] === Doctrine_Relation::ONE_COMPOSITE) {
                $ret[$i] = '        $this->hasOne(\'' . $class . $alias . '\'';
            } else {
                $ret[$i] = '        $this->hasMany(\'' . $class . $alias . '\'';
            }
            
            $a = array();

            if (isset($relation['refClass'])) {
                $a[] = '\'refClass\' => ' . var_export($relation['refClass'], true);
            }
            
            if (isset($relation['deferred']) && $relation['deferred']) {
                $a[] = '\'default\' => ' . var_export($relation['deferred'], true);
            }
            
            if (isset($relation['local']) && $relation['local']) {
                $a[] = '\'local\' => ' . var_export($relation['local'], true);
            }
            
            if (isset($relation['foreign']) && $relation['foreign']) {
                $a[] = '\'foreign\' => ' . var_export($relation['foreign'], true);
            }
            
            if (isset($relation['onDelete']) && $relation['onDelete']) {
                $a[] = '\'onDelete\' => ' . var_export($relation['onDelete'], true);
            }
            
            if (isset($relation['onUpdate']) && $relation['onUpdate']) {
                $a[] = '\'onUpdate\' => ' . var_export($relation['onUpdate'], true);
            }
            
            if ( ! empty($a)) {
                $ret[$i] .= ', ' . 'array(';
                $length = strlen($ret[$i]);
                $ret[$i] .= implode(',' . PHP_EOL . str_repeat(' ', $length), $a) . ')';
            }
            
            $ret[$i] .= ');';
            $i++;
        }
        
        if (isset($options['inheritance']['keyField']) && isset($options['inheritance']['keyValue'])) {
            $i++;
            $ret[$i] = "\t\t".'$this->setInheritanceMap(array(\''.$options['inheritance']['keyField'].'\' => '.$options['inheritance']['keyValue'].'));';
        }
        
        if (!empty($ret)) {
          return "\n\tpublic function setUp()\n\t{\n".implode("\n", $ret)."\n\t}";
        }
    }
    
    public function buildDefinition(array $options, array $columns, array $relations = array(), array $indexes = array())
    {
        if ( ! isset($options['className'])) {
            throw new Doctrine_Import_Builder_Exception('Missing class name.');
        }

        $abstract = isset($options['abstract']) && $options['abstract'] === true ? 'abstract ':null;
        $className = $options['className'];
        $extends = isset($options['inheritance']['extends']) ? $options['inheritance']['extends']:'Doctrine_Record';

        if (!(isset($options['no_definition']) && $options['no_definition'] === true)) {
            $definition = $this->buildTableDefinition($options, $columns, $relations, $indexes);
            $setUp = $this->buildSetUp($options, $columns, $relations);
        } else {
            $definition = null;
            $setUp = null;
        }
        
        $accessors = (isset($options['generate_accessors']) && $options['generate_accessors'] === true) ? $this->buildAccessors($options, $columns):null;
        
        $content = sprintf(self::$tpl, $abstract,
                                       $className,
                                       $extends,
                                       $definition,
                                       $setUp,
                                       $accessors);
        
        return $content;
    }

    public function buildRecord(array $options, array $columns, array $relations = array(), array $indexes = array())
    {
        if ( !isset($options['className'])) {
            throw new Doctrine_Import_Builder_Exception('Missing class name.');
        }

        if ( !isset($options['fileName'])) {
            if (empty($this->path)) {
                throw new Doctrine_Import_Builder_Exception('No build target directory set.');
            }
            

            if (is_writable($this->path) === false) {
                throw new Doctrine_Import_Builder_Exception('Build target directory ' . $this->path . ' is not writable.');
            }

            $options['fileName']  = $this->path . DIRECTORY_SEPARATOR . $options['className'] . $this->suffix;
        }
        
        if ($this->generateBaseClasses()) {
          
          // We only want to generate this one if it doesn't already exist
          if (!file_exists($options['fileName'])) {
            $optionsBak = $options;
            
            unset($options['tableName']);
            $options['inheritance']['extends'] = 'Base' . $options['className'];
            $options['requires'] = array($this->baseClassesDirectory . DIRECTORY_SEPARATOR  . $options['inheritance']['extends'] . $this->suffix);
            $options['no_definition'] = true;
            
            $this->writeDefinition($options, array(), array(), array());
            
            $options = $optionsBak;
          }
          
          $generatedPath = $this->path . DIRECTORY_SEPARATOR . $this->baseClassesDirectory;
          
          if (!file_exists($generatedPath)) {
            mkdir($generatedPath);
          }
          
          $options['className'] = 'Base' . $options['className'];
          $options['abstract'] = true;
          $options['fileName']  = $generatedPath . DIRECTORY_SEPARATOR . $options['className'] . $this->suffix;
          $options['override_parent'] = true;
          
          $this->writeDefinition($options, $columns, $relations, $indexes);
        } else {
          $this->writeDefinition($options, $columns, $relations, $indexes);
        }
    }
    
    public function writeDefinition(array $options, array $columns, array $relations = array(), array $indexes = array())
    {
        $content = $this->buildDefinition($options, $columns, $relations, $indexes);
        $code = "<?php\n";

        if (isset($options['requires'])) {
            if (!is_array($options['requires'])) {
                $options['requires'] = array($options['requires']);
            }

            foreach ($options['requires'] as $require) {
                $code .= "require_once('".$require."');\n";
            }
        }
        
        $code .= PHP_EOL . $content;

        $bytes = file_put_contents($options['fileName'], $code);

        if ($bytes === false) {
            throw new Doctrine_Import_Builder_Exception("Couldn't write file " . $options['fileName']);
        }
    }
}
