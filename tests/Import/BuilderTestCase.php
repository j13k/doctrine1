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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Import_Builder_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Import_Builder_TestCase extends Doctrine_UnitTestCase 
{
    public function testInheritanceGeneration()
    {
        $path = realpath(dirname(__FILE__) . '/../..') . '/models/test_generated';

        $import = new Doctrine_Import_Schema();
        $import->importSchema('schema.yml', 'yml', $path);

        $models = Doctrine::loadModels($path, Doctrine::MODEL_LOADING_CONSERVATIVE);

        $schemaTestInheritanceParent = new ReflectionClass('SchemaTestInheritanceParent');
        $schemaTestInheritanceChild1 = new ReflectionClass('SchemaTestInheritanceChild1');
        $schemaTestInheritanceChild2 = new ReflectionClass('SchemaTestInheritanceChild2');

        $schemaTestInheritanceParentTable = new ReflectionClass('SchemaTestInheritanceParentTable');
        $schemaTestInheritanceChild1Table = new ReflectionClass('SchemaTestInheritanceChild1Table');
        $schemaTestInheritanceChild2Table = new ReflectionClass('SchemaTestInheritanceChild2Table');

        $this->assertTrue($schemaTestInheritanceParent->isSubClassOf('Doctrine_Record'));
        $this->assertTrue($schemaTestInheritanceParent->isSubClassOf('BaseSchemaTestInheritanceParent'));
        $this->assertTrue($schemaTestInheritanceParent->isSubClassOf('PackageSchemaTestInheritanceParent'));
        $this->assertTrue($schemaTestInheritanceChild1->isSubClassOf('BaseSchemaTestInheritanceChild1'));
        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf('BaseSchemaTestInheritanceChild2'));
        
        $this->assertTrue($schemaTestInheritanceChild1->isSubClassOf('SchemaTestInheritanceParent'));
        $this->assertTrue($schemaTestInheritanceChild1->isSubClassOf('BaseSchemaTestInheritanceParent'));
        
        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf('SchemaTestInheritanceParent'));
        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf('BaseSchemaTestInheritanceParent'));
        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf('SchemaTestInheritanceChild1'));
        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf('BaseSchemaTestInheritanceChild1'));
        $this->assertTrue($schemaTestInheritanceChild2->isSubClassOf('PackageSchemaTestInheritanceParent'));

        $this->assertTrue($schemaTestInheritanceParentTable->isSubClassOf('Doctrine_Table'));
        $this->assertTrue($schemaTestInheritanceChild1Table->isSubClassOf('SchemaTestInheritanceParentTable'));
        $this->assertTrue($schemaTestInheritanceChild1Table->isSubClassOf('PackageSchemaTestInheritanceParentTable'));

        $this->assertTrue($schemaTestInheritanceChild2Table->isSubClassOf('SchemaTestInheritanceParentTable'));
        $this->assertTrue($schemaTestInheritanceChild2Table->isSubClassOf('PackageSchemaTestInheritanceParentTable'));
        $this->assertTrue($schemaTestInheritanceChild2Table->isSubClassOf('SchemaTestInheritanceChild1Table'));
        $this->assertTrue($schemaTestInheritanceChild2Table->isSubClassOf('PackageSchemaTestInheritanceChild1Table'));

        Doctrine_Lib::removeDirectories($path);
    }
}