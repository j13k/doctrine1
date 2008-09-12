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
 * AbstractSchemaName ::= identifier
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_Query_Parser_AbstractSchemaName extends Doctrine_Query_ParserRule
{
    protected $_AST = null;
    
    
    public function syntax($paramHolder)
    {
        // AbstractSchemaName ::= identifier
        $this->_AST = $this->AST('AbstractSchemaName');

        $this->_parser->match(Doctrine_Query_Token::T_IDENTIFIER);
        $this->_AST->setComponentName($this->_parser->token['value']);
    }


    public function semantical($paramHolder)
    {
        $componentName = $this->_AST->getComponentName();

        // Check if we are dealing with a real Doctrine_Entity or not
        if ( ! $this->_isDoctrineEntity($componentName)) {
            $this->_parser->semanticalError(
                "Defined entity '" . $companyName . "' is not a valid Doctrine_Entity."
            );
        }

        // Return AST node
        return $this->_AST;
    }
    
    
    protected function _isDoctrineEntity($componentName)
    {
        if (class_exists($componentName)) {
            $reflectionClass = new ReflectionClass($componentName);
            $dctrnEntityReflectionClass = new ReflectionClass('Doctrine_Entity');

            return $reflectionClass->isSubclassOf($dctrnEntityReflectionClass);
        }

        return false;
    }
}
