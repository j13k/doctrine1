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
 * IdentificationVariableDeclaration ::= RangeVariableDeclaration [IndexBy] {JoinVariableDeclaration}*
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_Query_AST_IdentificationVariableDeclaration extends Doctrine_Query_AST
{
    protected $_rangeVariableDeclaration = null;
    
    protected $_indexBy = null;

    protected $_joinVariableDeclarations = array();
    

    /* Setters */
    public function setRangeVariableDeclaration($rangeVariableDeclaration)
    {
        $this->_rangeVariableDeclaration = $rangeVariableDeclaration;
    }


    public function setIndexBy($indexBy)
    {
        $this->_indexBy = $indexBy;
    }


    public function addJoinVariableDeclaration($joinVariableDeclaration)
    {
        $this->_joinVariableDeclarations[] = $joinVariableDeclaration;
    }


    public function setJoinVariableDeclarations($joinVariableDeclarations, $append = false)
    {
        $this->_joinVariableDeclarations = ($append === true)
            ? array_merge($this->_joinVariableDeclarations, $joinVariableDeclarations)
            : $joinVariableDeclarations;
    }

    
    /* Getters */
    public function getRangeVariableDeclaration()
    {
        return $this->_rangeVariableDeclaration;
    }


    public function getIndexBy()
    {
        return $this->_indexBy;
    }
    

    public function getJoinVariableDeclarations()
    {
        return $this->_joinVariableDeclarations;
    }


    /* REMOVE ME LATER. COPIED METHODS FROM SPLIT OF PRODUCTION INTO "AST" AND "PARSER" */
    
    public function buildSql()
    {
        $str = $this->_rangeVariableDeclaration->buildSql();

        for ($i = 0, $l = count($this->_joinVariableDeclarations); $i < $l; $i++) {
            $str .= ' ' . $this->_joinVariableDeclarations[$i]->buildSql();
        }

        return $str;
    }
}