<?php
/*
 *  $Id: GenerateMigrationsModels.php 2761 2007-10-07 23:42:29Z zYne $
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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Task_GenerateMigrationsModels
 *
 * @package     Doctrine
 * @subpackage  Task
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 2761 $
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
class Doctrine_Task_GenerateMigrationsModels extends Doctrine_Task
{
    public $description          =   'Generate migration classes for an existing set of models',
           $requiredArguments    =   array('migrations_path'  => 'Specify the path to your migration classes folder.',
                                           'yaml_schema_path' => 'Specify the path to your yaml schema files folder.'),
           $optionalArguments    =   array('models_path'      => 'Specify the path to your doctrine models folder.');

    public function execute()
    {   
        try {
            $migrationsPath = $this->getArgument('migrations_path');
            $modelsPath = $this->getArgument('models_path');
            $yamlSchemaPath = $this->getArgument('yaml_schema_path');
            $migration = new Doctrine_Migration($migrationsPath);
            $result1 = false;
            if ( ! count($migration->getMigrationClasses())) {
                $result1 = Doctrine::generateMigrationsFromModels($migrationsPath, $modelsPath);
            }
            $changes = Doctrine::generateMigrationsFromDiff($migrationsPath, $modelsPath, $yamlSchemaPath);
            $numChanges = count($changes, true) - count($changes);
            $result = ($result1 || $numChanges) ? true:false;
        } catch (Exception $e) {
            $result = false;
        }

        if ( ! $result) {
            throw new Doctrine_Task_Exception('Could not generate migration classes from models' . (isset($e) ? ': ' . $e->getMessage() : null));
        } else {
            $this->notify('Generated migration classes successfully from models');
        }
    }
}