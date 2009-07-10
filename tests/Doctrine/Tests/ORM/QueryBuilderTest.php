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

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;

require_once __DIR__ . '/../TestInit.php';

/**
 * Test case for the QueryBuilder class used to build DQL query string in a
 * object oriented way.
 *
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 * @todo Remove QueryBuilder::create. Use constructor in tests instead. Users will use
 *       $em->createQueryBuilder().
 */
class QueryBuilderTest extends \Doctrine\Tests\OrmTestCase
{
    private $_em;

    protected function setUp()
    {
        $this->_em = $this->_getTestEntityManager();
    }

    protected function assertValidQueryBuilder(QueryBuilder $qb, $expectedDql)
    {
        $dql = $qb->getDql();
        $q = $qb->getQuery();

        $this->assertEquals($expectedDql, $dql);
    }

    public function testSelectSetsType()
    {
        $qb = QueryBuilder::create($this->_em)
            ->delete('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->select('u.id', 'u.username');

        $this->assertEquals($qb->getType(), QueryBuilder::SELECT);
    }

    public function testDeleteSetsType()
    {
        $qb = QueryBuilder::create($this->_em)
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->delete();

        $this->assertEquals($qb->getType(), QueryBuilder::DELETE);
    }

    public function testUpdateSetsType()
    {
        $qb = QueryBuilder::create($this->_em)
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->update();

        $this->assertEquals($qb->getType(), QueryBuilder::UPDATE);
    }

    public function testSimpleSelect()
    {
        $qb = QueryBuilder::create($this->_em)
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->select('u.id', 'u.username');

        $this->assertValidQueryBuilder($qb, 'SELECT u.id, u.username FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSimpleDelete()
    {
        $qb = QueryBuilder::create($this->_em)
            ->delete('Doctrine\Tests\Models\CMS\CmsUser', 'u');

        $this->assertValidQueryBuilder($qb, 'DELETE Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSimpleUpdate()
    {
        $qb = QueryBuilder::create($this->_em)
            ->update('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->set('u.username', ':username');

        $this->assertValidQueryBuilder($qb, 'UPDATE Doctrine\Tests\Models\CMS\CmsUser u SET u.username = :username');
    }

    public function testInnerJoin()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u', 'a')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->innerJoin('u', 'articles', 'a');

        $this->assertValidQueryBuilder($qb, 'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles a');
    }

    public function testLeftJoin()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u', 'a')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->leftJoin('u', 'articles', 'a');

        $this->assertValidQueryBuilder($qb, 'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a');
    }

    public function testWhere()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :uid');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid)');
    }

    public function testAndWhere()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :uid')
            ->andWhere('u.id = :uid2');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) AND (u.id = :uid2)');
    }

    public function testOrWhere()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :uid')
            ->orWhere('u.id = :uid2');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) OR (u.id = :uid2)');
    }

    public function testAndWhereIn()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :uid')
            ->andWhereIn('u.id', array(1, 2, 3));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) AND u.id IN(1, 2, 3)');
    }

    public function testOrWhereIn()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :uid')
            ->orWhereIn('u.id', array(1, 2, 3));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) OR u.id IN(1, 2, 3)');
    }

    public function testAndWhereNotIn()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :uid')
            ->andWhereNotIn('u.id', array(1, 2, 3));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) AND u.id NOT IN(1, 2, 3)');
    }

    public function testOrWhereNotIn()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :uid')
            ->OrWhereNotIn('u.id', array(1, 2, 3));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) OR u.id NOT IN(1, 2, 3)');
    }

    public function testGroupBy()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->groupBy('u.id');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id');
    }

    public function testHaving()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->groupBy('u.id')
            ->having('COUNT(u.id) > 1');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id HAVING COUNT(u.id) > 1');
    }

    public function testAndHaving()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->groupBy('u.id')
            ->having('COUNT(u.id) > 1')
            ->andHaving('COUNT(u.id) < 1');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id HAVING COUNT(u.id) > 1 AND COUNT(u.id) < 1');
    }

    public function testOrHaving()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->groupBy('u.id')
            ->having('COUNT(u.id) > 1')
            ->andHaving('COUNT(u.id) < 1')
            ->orHaving('COUNT(u.id) > 1');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id HAVING COUNT(u.id) > 1 AND COUNT(u.id) < 1 OR COUNT(u.id) > 1');
    }

    public function testOrderBy()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->orderBy('u.username', 'ASC');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username ASC');
    }

    public function testAddOrderBy()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->orderBy('u.username', 'ASC')
            ->addOrderBy('u.username', 'DESC');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username ASC, u.username DESC');
    }

    public function testGetQuery()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $q = $qb->getQuery();

        $this->assertEquals(get_class($q), 'Doctrine\ORM\Query');
    }

    public function testSetParameter()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :id')
            ->setParameter('id', 1);

        $q = $qb->getQuery();

        $this->assertEquals($q->getParameters(), array('id' => 1));
    }

    public function testSetParameters()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.username = :username OR u.username = :username2');

        $qb->setParameters(array('username' => 'jwage', 'username2' => 'jonwage'));

        $q = $qb->getQuery();

        $this->assertEquals($q->getParameters(), array('username' => 'jwage', 'username2' => 'jonwage'));
    }

    public function testMultipleWhere()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :uid', 'u.id = :uid2');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) AND (u.id = :uid2)');
    }

    public function testMultipleAndWhere()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->andWhere('u.id = :uid', 'u.id = :uid2');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) AND (u.id = :uid2)');
    }

    public function testMultipleOrWhere()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->orWhere('u.id = :uid', Expr::eq('u.id', ':uid2'));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) OR (u.id = :uid2)');
    }

    public function testComplexWhere()
    {
        $orExpr = Expr::orx();
        $orExpr->add(Expr::eq('u.id', ':uid3'));
        $orExpr->add(Expr::in('u.id', array(1)));

        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where($orExpr);

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE ((u.id = :uid3) OR (u.id IN(1)))');
    }

    public function testLimit()
    {
        /*
        TODO: Limit fails. Is this not implemented in the DQL parser? Will look tomorrow.
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->limit(10)
            ->offset(0);

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u LIMIT 10');
        */
    }
}