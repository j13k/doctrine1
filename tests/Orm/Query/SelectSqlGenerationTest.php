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
require_once 'lib/DoctrineTestInit.php';
/**
 * Test case for testing the saving and referencing of query identifiers.
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 * @todo        1) [romanb] We  might want to split the SQL generation tests into multiple
 *              testcases later since we'll have a lot of them and we might want to have special SQL
 *              generation tests for some dbms specific SQL syntaxes.
 */
class Orm_Query_SelectSqlGenerationTest extends Doctrine_OrmTestCase
{
    public function assertSqlGeneration($dqlToBeTested, $sqlToBeConfirmed)
    {
        try {
            $entityManager = $this->_em;
            $query = $entityManager->createQuery($dqlToBeTested);
            //echo print_r($query->parse()->getQueryFields(), true) . "\n";

            parent::assertEquals($sqlToBeConfirmed, $query->getSql());
            //echo $query->getSql() . "\n";

            $query->free();
        } catch (Doctrine_Exception $e) {
            $this->fail($e->getMessage());
        }
    }


    public function testPlainFromClauseWithoutAlias()
    {
        $this->assertSqlGeneration(
            'SELECT * FROM CmsUser',
            'SELECT cu.id AS cu__id, cu.status AS cu__status, cu.username AS cu__username, cu.name AS cu__name FROM cms_user cu WHERE 1 = 1'
        );

        $this->assertSqlGeneration(
            'SELECT id FROM CmsUser',
            'SELECT cu.id AS cu__id FROM cms_user cu WHERE 1 = 1'
        );
    }


    public function testPlainFromClauseWithAlias()
    {
        $this->assertSqlGeneration(
            'SELECT u.id FROM CmsUser u', 
            'SELECT cu.id AS cu__id FROM cms_user cu WHERE 1 = 1'
        );
    }


    public function testSelectSingleComponentWithAsterisk()
    {
        $this->assertSqlGeneration(
            'SELECT u.* FROM CmsUser u', 
            'SELECT cu.id AS cu__id, cu.status AS cu__status, cu.username AS cu__username, cu.name AS cu__name FROM cms_user cu WHERE 1 = 1'
        );
    }


    public function testSelectSingleComponentWithMultipleColumns()
    {
        $this->assertSqlGeneration(
            'SELECT u.username, u.name FROM CmsUser u', 
            'SELECT cu.username AS cu__username, cu.name AS cu__name FROM cms_user cu WHERE 1 = 1'
        );
    }


    public function testSelectMultipleComponentsWithAsterisk()
    {
        $this->assertSqlGeneration(
            'SELECT u.*, p.* FROM CmsUser u, u.phonenumbers p',
            'SELECT cu.id AS cu__id, cu.status AS cu__status, cu.username AS cu__username, cu.name AS cu__name, cp.user_id AS cp__user_id, cp.phonenumber AS cp__phonenumber FROM cms_user cu, cms_phonenumber cp WHERE 1 = 1'
        );
    }


    public function testSelectDistinctIsSupported()
    {
        $this->assertSqlGeneration(
            'SELECT DISTINCT u.name FROM CmsUser u',
            'SELECT DISTINCT cu.name AS cu__name FROM cms_user cu WHERE 1 = 1'
        );
    }


    public function testAggregateFunctionInSelect()
    {
        $this->assertSqlGeneration(
            'SELECT COUNT(u.id) FROM CmsUser u GROUP BY u.id',
            'SELECT COUNT(cu.id) AS dctrn__0 FROM cms_user cu WHERE 1 = 1 GROUP BY cu.id'
        );
    }

    public function testAggregateFunctionWithDistinctInSelect()
    {
        $this->assertSqlGeneration(
            'SELECT COUNT(DISTINCT u.name) FROM CmsUser u',
            'SELECT COUNT(DISTINCT cu.name) AS dctrn__0 FROM cms_user cu WHERE 1 = 1'
        );
    }


    public function testFunctionalExpressionsSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM CmsUser u WHERE TRIM(u.name) = 'someone'",
            // String quoting in the SQL usually depends on the database platform.
            // This test works with a mock connection which uses ' for string quoting.
            "SELECT cu.name AS cu__name FROM cms_user cu WHERE TRIM(cu.name) = 'someone'"
        );
    }


    // Ticket #668
    public function testKeywordUsageInStringParam()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM CmsUser u WHERE u.name LIKE '%foo OR bar%'",
            "SELECT cu.name AS cu__name FROM cms_user cu WHERE cu.name LIKE '%foo OR bar%'"
        );
    }


    // Ticket #973
    public function testSingleInValueWithoutSpace()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM CmsUser u WHERE u.id IN(46)",
            "SELECT cu.name AS cu__name FROM cms_user cu WHERE cu.id IN (46)"
        );
    }


    // Ticket 894
    public function testBetweenDeclarationWithInputParameter()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM CmsUser u WHERE u.id BETWEEN ? AND ?",
            "SELECT cu.name AS cu__name FROM cms_user cu WHERE cu.id BETWEEN ? AND ?"
        );
    }


    public function testArithmeticExpressionsSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT u.* FROM CmsUser u WHERE ((u.id + 5000) * u.id + 3) < 10000000',
            'SELECT cu.id AS cu__id, cu.status AS cu__status, cu.username AS cu__username, cu.name AS cu__name FROM cms_user cu WHERE ((cu.id + 5000) * cu.id + 3) < 10000000'
        );
    }


    public function testInExpressionSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT * FROM CmsUser WHERE CmsUser.id IN (1, 2)',
            'SELECT cu.id AS cu__id, cu.status AS cu__status, cu.username AS cu__username, cu.name AS cu__name FROM cms_user cu WHERE cu.id IN (1, 2)'
        );
    }


    public function testNotInExpressionSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT * FROM CmsUser WHERE CmsUser.id NOT IN (1)',
            'SELECT cu.id AS cu__id, cu.status AS cu__status, cu.username AS cu__username, cu.name AS cu__name FROM cms_user cu WHERE cu.id NOT IN (1)'
        );
    }


    public function testPlainJoinWithoutClause()
    {
        $this->assertSqlGeneration(
            'SELECT u.id, a.id from CmsUser u LEFT JOIN u.articles a',
            'SELECT cu.id AS cu__id, ca.id AS ca__id FROM cms_user cu LEFT JOIN cms_article ca ON cu.id = ca.user_id WHERE 1 = 1'
        );
        $this->assertSqlGeneration(
            'SELECT u.id, a.id from CmsUser u JOIN u.articles a',
            'SELECT cu.id AS cu__id, ca.id AS ca__id FROM cms_user cu INNER JOIN cms_article ca ON cu.id = ca.user_id WHERE 1 = 1'
        );
    }

}