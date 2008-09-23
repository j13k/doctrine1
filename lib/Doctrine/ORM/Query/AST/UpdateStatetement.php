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
 * UpdateStatement = UpdateClause [WhereClause]
 *
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Doctrine_ORM_Query_AST_UpdateStatement extends Doctrine_ORM_Query_AST
{
    protected $_updateClause;

    protected $_whereClause;
    
    
    /* Setters */
    public function setUpdateClause($updateClause)
    {
        $this->_updateClause = $updateClause;
    }

    
    public function setWhereClause($whereClause)
    {
        $this->_whereClause = $whereClause;
    }

    
    /* Getters */
    public function getUpdateClause()
    {
        return $this->_updateClause;
    }

    
    public function getWhereClause()
    {
        return $this->_whereClause;
    }
    
    
    /* REMOVE ME LATER. COPIED METHODS FROM SPLIT OF PRODUCTION INTO "AST" AND "PARSER" */
    
    public function buildSql()
    {
        // The 1=1 is needed to workaround the affected_rows in MySQL.
        // Simple "UPDATE table_name SET column_name = value" gives 0 affected rows.
        return $this->_updateClause->buildSql() . (($this->_whereClause !== null)
             ? ' ' . $this->_whereClause->buildSql() : ' WHERE 1 = 1');
    }
}    