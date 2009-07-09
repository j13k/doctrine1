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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM;

use Doctrine\ORM\Query\Expr;

/**
 * This class is responsible for building DQL query strings via a object oriented
 * PHP interface
 *
 * TODO: I don't like the API of using the Expr::*() syntax inside of the QueryBuilder
 * methods. What can we do to allow them to do it more fluently with the QueryBuilder.
 *
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class QueryBuilder
{
    const SELECT = 0;
    const DELETE = 1;
    const UPDATE = 2;

    const STATE_DIRTY = 0;
    const STATE_CLEAN = 1;

    protected $_entityManager;
    protected $_dqlParts = array(
        'select' => array(),
        'from' => array(),
        'where' => array(),
        'groupBy' => array(),
        'having' => array(),
        'orderBy' => array(),
        'limit' => array(), 
        'offset' => array()
    );
    protected $_type = self::SELECT;
    protected $_state = self::STATE_CLEAN;
    protected $_dql;

    public function __construct(EntityManager $entityManager)
    {
        $this->_entityManager = $entityManager;
    }

    public static function create(EntityManager $entityManager)
    {
        return new self($entityManager);
    }

    public function getType()
    {
        return $this->_type;
    }

    public function getState()
    {
        return $this->_state;
    }

    public function getDql()
    {
        if ($this->_dql !== null && self::STATE_CLEAN) {
            return $this->_dql;
        }

        $dql = '';

        switch ($this->_type) {
            case self::DELETE:
                $dql = $this->_getDqlForDelete();
                break;

            case self::UPDATE:
                $dql = $this->_getDqlForUpdate();
                break;

            case self::SELECT:
            default:
                $dql = $this->_getDqlForSelect();
                break;
        }

        $this->_dql = $dql;

        return $dql;
    }

    public function getQuery()
    {
        $q = new Query($this->_entityManager);
        $q->setDql($this->getDql());

        return $q;
    }

    public function select($select = null)
    {
        $this->_type = self::SELECT;

        if ( ! $select) {
            return $this;
        }

        return $this->_addDqlQueryPart('select', $select, true);
    }

    public function delete($delete = null, $alias = null)
    {
        $this->_type = self::DELETE;

        if ( ! $delete) {
            return $this;
        }

        return $this->_addDqlQueryPart('from', $delete . ' ' . $alias);
    }

    public function update($update = null, $alias = null)
    {
        $this->_type = self::UPDATE;

        if ( ! $update) {
            return $this;
        }

        return $this->_addDqlQueryPart('from', $update . ' ' . $alias);
    }

    public function set($key, $value = null)
    {
        return $this->_addDqlQueryPart('set', $key . ' = ' . $value, true);
    }

    public function from($from, $alias)
    {
        return $this->_addDqlQueryPart('from', $from . ' ' . $alias, true);
    }

    public function join($join, $alias)
    {
        return $this->_addDqlQueryPart('from', 'INNER JOIN ' . $join . ' ' . $alias, true);
    }

    public function innerJoin($join, $alias)
    {
        return $this->join($join, $alias);
    }

    public function leftJoin($join, $alias)
    {
        return $this->_addDqlQueryPart('from', 'LEFT JOIN ' . $join . ' ' . $alias, true);
    }

    public function where($where)
    {
        return $this->_addDqlQueryPart('where', $where, false);
    }

    public function andWhere($where)
    {
        if (count($this->getDqlQueryPart('where')) > 0) {
            $this->_addDqlQueryPart('where', 'AND', true);
        }

        return $this->_addDqlQueryPart('where', $where, true);
    }

    public function orWhere($where)
    {
        if (count($this->getDqlQueryPart('where')) > 0) {
            $this->_addDqlQueryPart('where', 'OR', true);
        }

        return $this->_addDqlQueryPart('where', $where, true);
    }

    public function groupBy($groupBy)
    {
        return $this->_addDqlQueryPart('groupBy', $groupBy, false);
    }

    public function having($having)
    {
        return $this->_addDqlQueryPart('having', $having, false);
    }

    public function andHaving($having)
    {
        if (count($this->getDqlQueryPart('having')) > 0) {
            $this->_addDqlQueryPart('having', 'AND', true);
        }

        return $this->_addDqlQueryPart('having', $having, true);
    }

    public function orHaving($having)
    {
        if (count($this->getDqlQueryPart('having')) > 0) {
            $this->_addDqlQueryPart('having', 'OR', true);
        }

        return $this->_addDqlQueryPart('having', $having, true);
    }

    public function orderBy($sort, $order)
    {
        return $this->_addDqlQueryPart('orderBy', $sort . ' ' . $order, false);
    }

    public function addOrderBy($sort, $order)
    {
        return $this->_addDqlQueryPart('orderBy', $sort . ' ' . $order, true);
    }

    public function limit($limit)
    {
        return $this->_addDqlQueryPart('limit', $limit);
    }

    public function offset($offset)
    {
        return $this->_addDqlQueryPart('offset', $offset);
    }

    /**
     * Get the DQL query string for DELETE queries
     *
     * BNF:
     *
     * DeleteStatement = DeleteClause [WhereClause] [OrderByClause] [LimitClause] [OffsetClause]
     * DeleteClause    = "DELETE" "FROM" RangeVariableDeclaration
     * WhereClause     = "WHERE" ConditionalExpression
     * OrderByClause   = "ORDER" "BY" OrderByItem {"," OrderByItem}
     * LimitClause     = "LIMIT" integer
     * OffsetClause    = "OFFSET" integer
     *
     * @return string $dql
     */
    private function _getDqlForDelete()
    {
         return 'DELETE'
              . $this->_getReducedDqlQueryPart('from', array('pre' => ' ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('where', array('pre' => ' WHERE ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('orderBy', array('pre' => ' ORDER BY ', 'separator' => ', '))
              . $this->_getReducedDqlQueryPart('limit', array('pre' => ' LIMIT ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('offset', array('pre' => ' OFFSET ', 'separator' => ' '));
    }

    /**
     * Get the DQL query string for UPDATE queries
     *
     * BNF:
     *
     * UpdateStatement = UpdateClause [WhereClause] [OrderByClause] [LimitClause] [OffsetClause]
     * UpdateClause    = "UPDATE" RangeVariableDeclaration "SET" UpdateItem {"," UpdateItem}
     * WhereClause     = "WHERE" ConditionalExpression
     * OrderByClause   = "ORDER" "BY" OrderByItem {"," OrderByItem}
     * LimitClause     = "LIMIT" integer
     * OffsetClause    = "OFFSET" integer
     *
     * @return string $dql
     */
    private function _getDqlForUpdate()
    {
         return 'UPDATE'
              . $this->_getReducedDqlQueryPart('from', array('pre' => ' ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('set', array('pre' => ' SET ', 'separator' => ', '))
              . $this->_getReducedDqlQueryPart('where', array('pre' => ' WHERE ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('orderBy', array('pre' => ' ORDER BY ', 'separator' => ', '))
              . $this->_getReducedDqlQueryPart('limit', array('pre' => ' LIMIT ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('offset', array('pre' => ' OFFSET ', 'separator' => ' '));
    }

    /**
     * Get the DQL query string for SELECT queries
     *
     * BNF:
     *
     * SelectStatement = [SelectClause] FromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause] [LimitClause] [OffsetClause]
     * SelectClause    = "SELECT" ["ALL" | "DISTINCT"] SelectExpression {"," SelectExpression}
     * FromClause      = "FROM" IdentificationVariableDeclaration {"," IdentificationVariableDeclaration}
     * WhereClause     = "WHERE" ConditionalExpression
     * GroupByClause   = "GROUP" "BY" GroupByItem {"," GroupByItem}
     * HavingClause    = "HAVING" ConditionalExpression
     * OrderByClause   = "ORDER" "BY" OrderByItem {"," OrderByItem}
     * LimitClause     = "LIMIT" integer
     * OffsetClause    = "OFFSET" integer
     *
     * @return string $dql
     */
    private function _getDqlForSelect()
    {
         return 'SELECT'
              . $this->_getReducedDqlQueryPart('select', array('pre' => ' ', 'separator' => ', '))
              . $this->_getReducedDqlQueryPart('from', array('pre' => ' FROM ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('where', array('pre' => ' WHERE ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('groupBy', array('pre' => ' GROUP BY ', 'separator' => ', '))
              . $this->_getReducedDqlQueryPart('having', array('pre' => ' HAVING ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('orderBy', array('pre' => ' ORDER BY ', 'separator' => ', '))
              . $this->_getReducedDqlQueryPart('limit', array('pre' => ' LIMIT ', 'separator' => ' '))
              . $this->_getReducedDqlQueryPart('offset', array('pre' => ' OFFSET ', 'separator' => ' '));
    }

    private function _getReducedDqlQueryPart($queryPartName, $options = array())
    {
        if (empty($this->_dqlParts[$queryPartName])) {
            return (isset($options['empty']) ? $options['empty'] : '');
        }

        $str  = (isset($options['pre']) ? $options['pre'] : '');
        $str .= implode($options['separator'], $this->getDqlQueryPart($queryPartName));
        $str .= (isset($options['post']) ? $options['post'] : '');

        return $str;
    }

    private function getDqlQueryPart($queryPartName)
    {
        return $this->_dqlParts[$queryPartName];
    }

    private function _addDqlQueryPart($queryPartName, $queryPart, $append = false)
    {
        if ($append) {
            $this->_dqlParts[$queryPartName][] = $queryPart;
        } else {
            $this->_dqlParts[$queryPartName] = array($queryPart);
        }

        $this->_state = self::STATE_DIRTY;
        return $this;
    }
}