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
 * @since       1.0
 * @version     $Revision$
 * @todo        1) [romanb] We  might want to split the SQL generation tests into multiple
 *              testcases later since we'll have a lot of them and we might want to have special SQL
 *              generation tests for some dbms specific SQL syntaxes.
 */
class Orm_Query_LanguageRecognitionTest extends Doctrine_OrmTestCase
{
    public function assertValidDql($dql)
    {
        try {
            $entityManager = $this->sharedFixture['em'];
            $query = $entityManager->createQuery($dql);
            $parserResult = $query->parse();
        } catch (Doctrine_Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function assertInvalidDql($dql)
    {
        try {
            $entityManager = $this->sharedFixture['em'];
            $query = $entityManager->createQuery($dql);
            $query->setDql($dql);
            $parserResult = $query->parse();

            $this->fail('No syntax errors were detected, when syntax errors were expected');
        } catch (Doctrine_Exception $e) {
            // It was expected!
        }
    }

    public function testEmptyQueryString()
    {
        $this->assertInvalidDql('');
    }

    public function testPlainFromClauseWithoutAlias()
    {
        $this->assertValidDql('SELECT * FROM CmsUser');

        $this->assertValidDql('SELECT id FROM CmsUser');
    }

    public function testPlainFromClauseWithAlias()
    {
        $this->assertValidDql('SELECT u.id FROM CmsUser u');
    }

    public function testSelectSingleComponentWithAsterisk()
    {
        $this->assertValidDql('SELECT u.* FROM CmsUser u');
    }

    public function testInvalidSelectSingleComponentWithAsterisk()
    {
        $this->assertInvalidDql('SELECT p.* FROM CmsUser u');
    }

    public function testSelectSingleComponentWithMultipleColumns()
    {
        $this->assertValidDql('SELECT u.name, u.username FROM CmsUser u');
    }

    public function testSelectMultipleComponentsWithAsterisk()
    {
        $this->assertValidDql('SELECT u.*, p.* FROM CmsUser u, u.phonenumbers p');
    }

    public function testSelectDistinctIsSupported()
    {
        $this->assertValidDql('SELECT DISTINCT u.name FROM CmsUser u');
    }

    public function testAggregateFunctionInSelect()
    {
        $this->assertValidDql('SELECT COUNT(u.id) FROM CmsUser u');
    }

    public function testAggregateFunctionWithDistinctInSelect()
    {
        $this->assertValidDql('SELECT COUNT(DISTINCT u.name) FROM CmsUser u');
    }

    public function testFunctionalExpressionsSupportedInWherePart()
    {
        $this->assertValidDql("SELECT u.name FROM CmsUser u WHERE TRIM(u.name) = 'someone'");
    }

    public function testArithmeticExpressionsSupportedInWherePart()
    {
        $this->assertValidDql('SELECT u.* FROM CmsUser u WHERE ((u.id + 5000) * u.id + 3) < 10000000');
    }

    public function testInExpressionSupportedInWherePart()
    {
        $this->assertValidDql('SELECT * FROM CmsUser WHERE CmsUser.id IN (1, 2)');
    }

    public function testNotInExpressionSupportedInWherePart()
    {
        $this->assertValidDql('SELECT * FROM CmsUser WHERE CmsUser.id NOT IN (1)');
    }

    public function testExistsExpressionSupportedInWherePart()
    {
        $this->assertValidDql('SELECT u.* FROM CmsUser u WHERE EXISTS (SELECT p.user_id FROM CmsPhonenumber p WHERE p.user_id = u.id)');
    }

    public function testNotExistsExpressionSupportedInWherePart()
    {
        $this->assertValidDql('SELECT u.* FROM CmsUser u WHERE NOT EXISTS (SELECT p.user_id FROM CmsPhonenumber p WHERE p.user_id = u.id)');
    }

    public function testLiteralValueAsInOperatorOperandIsSupported()
    {
        $this->assertValidDql('SELECT u.id FROM CmsUser u WHERE 1 IN (1, 2)');
    }

    public function testUpdateWorksWithOneColumn()
    {
        $this->assertValidDql("UPDATE CmsUser u SET u.name = 'someone'");
    }

    public function testUpdateWorksWithMultipleColumns()
    {
        $this->assertValidDql("UPDATE CmsUser u SET u.name = 'someone', u.username = 'some'");
    }

    public function testUpdateSupportsConditions()
    {
        $this->assertValidDql("UPDATE CmsUser u SET u.name = 'someone' WHERE u.id = 5");
    }

    public function testDeleteAll()
    {
        $this->assertValidDql('DELETE FROM CmsUser');
    }

    public function testDeleteWithCondition()
    {
        $this->assertValidDql('DELETE FROM CmsUser WHERE id = 3');
    }
/*
    public function testDeleteWithLimit()
    {
        // LIMIT is not supported in DELETE
        $this->assertValidDql('DELETE FROM CmsUser LIMIT 20');
    }

    public function testDeleteWithLimitAndOffset()
    {
        // LIMIT and OFFSET are not supported in DELETE
        $this->assertValidDql('DELETE FROM CmsUser LIMIT 10 OFFSET 20');
    }
*/

    public function testAdditionExpression()
    {
        $this->assertValidDql('SELECT u.*, (u.id + u.id) addition FROM CmsUser u');
    }

    public function testSubtractionExpression()
    {
        $this->assertValidDql('SELECT u.*, (u.id - u.id) subtraction FROM CmsUser u');
    }

    public function testDivisionExpression()
    {
        $this->assertValidDql('SELECT u.*, (u.id/u.id) division FROM CmsUser u');
    }

    public function testMultiplicationExpression()
    {
        $this->assertValidDql('SELECT u.*, (u.id * u.id) multiplication FROM CmsUser u');
    }

    public function testNegationExpression()
    {
        $this->assertValidDql('SELECT u.*, -u.id negation FROM CmsUser u');
    }

    public function testExpressionWithPrecedingPlusSign()
    {
        $this->assertValidDql('SELECT u.*, +u.id FROM CmsUser u');
    }

    public function testAggregateFunctionInHavingClause()
    {
        $this->assertValidDql('SELECT u.name FROM CmsUser u LEFT JOIN u.phonenumbers p HAVING COUNT(p.phonenumber) > 2');
        $this->assertValidDql("SELECT u.name FROM CmsUser u LEFT JOIN u.phonenumbers p HAVING MAX(u.name) = 'zYne'");
    }

    public function testMultipleAggregateFunctionsInHavingClause()
    {
        $this->assertValidDql("SELECT u.name FROM CmsUser u LEFT JOIN u.phonenumbers p HAVING MAX(u.name) = 'zYne'");
    }

    public function testLeftJoin()
    {
        $this->assertValidDql('SELECT u.*, p.* FROM CmsUser u LEFT JOIN u.phonenumbers p');
    }

    public function testJoin()
    {
        $this->assertValidDql('SELECT u.* FROM CmsUser u JOIN u.phonenumbers');
    }

    public function testInnerJoin()
    {
        $this->assertValidDql('SELECT u.*, u.phonenumbers.* FROM CmsUser u INNER JOIN u.phonenumbers');
    }

    public function testMultipleLeftJoin()
    {
        $this->assertValidDql('SELECT u.articles.*, u.phonenumbers.* FROM CmsUser u LEFT JOIN u.articles LEFT JOIN u.phonenumbers');
    }

    public function testMultipleInnerJoin()
    {
        $this->assertValidDql('SELECT u.name FROM CmsUser u INNER JOIN u.articles INNER JOIN u.phonenumbers');
    }

    public function testMultipleInnerJoin2()
    {
        $this->assertValidDql('SELECT u.name FROM CmsUser u INNER JOIN u.articles, u.phonenumbers');
    }

    public function testMixingOfJoins()
    {
        $this->assertValidDql('SELECT u.name, a.topic, p.phonenumber FROM CmsUser u INNER JOIN u.articles a LEFT JOIN u.phonenumbers p');
    }

    public function testMixingOfJoins2()
    {
        $this->assertInvalidDql('SELECT u.name, u.articles.topic, c.text FROM CmsUser u INNER JOIN u.articles.comments c');
    }

    public function testOrderBySingleColumn()
    {
        $this->assertValidDql('SELECT u.name FROM CmsUser u ORDER BY u.name');
    }

    public function testOrderBySingleColumnAscending()
    {
        $this->assertValidDql('SELECT u.name FROM CmsUser u ORDER BY u.name ASC');
    }

    public function testOrderBySingleColumnDescending()
    {
        $this->assertValidDql('SELECT u.name FROM CmsUser u ORDER BY u.name DESC');
    }

    public function testOrderByMultipleColumns()
    {
        $this->assertValidDql('SELECT u.name, u.username FROM CmsUser u ORDER BY u.username DESC, u.name DESC');
    }

    public function testOrderByWithFunctionExpression()
    {
        $this->assertValidDql('SELECT u.name FROM CmsUser u ORDER BY COALESCE(u.id, u.name) DESC');
    }
/*
    public function testSubselectInInExpression()
    {
        $this->assertValidDql("SELECT * FROM CmsUser u WHERE u.id NOT IN (SELECT u2.id FROM CmsUser u2 WHERE u2.name = 'zYne')");
    }

    public function testSubselectInSelectPart()
    {
        // Semantical error: Unknown query component u (probably in subselect)
        $this->assertValidDql("SELECT u.name, (SELECT COUNT(p.phonenumber) FROM CmsPhonenumber p WHERE p.user_id = u.id) pcount FROM CmsUser u WHERE u.name = 'zYne' LIMIT 1");
    }
*/
    public function testPositionalInputParameter()
    {
        $this->assertValidDql('SELECT * FROM CmsUser u WHERE u.id = ?');
    }

    public function testNamedInputParameter()
    {
        $this->assertValidDql('SELECT * FROM CmsUser u WHERE u.id = :id');
    }
/*
    public function testCustomJoinsAndWithKeywordSupported()
    {
        // We need existant classes here, otherwise semantical will always fail
        $this->assertValidDql('SELECT c.*, c2.*, d.* FROM Record_Country c INNER JOIN c.City c2 WITH c2.id = 2 WHERE c.id = 1');
    }
*/

    public function testJoinConditionsSupported()
    {
        $this->assertValidDql("SELECT u.name, p.* FROM CmsUser u LEFT JOIN u.phonenumbers p ON p.phonenumber = '123 123'");
    }

    public function testIndexByClauseWithOneComponent()
    {
        $this->assertValidDql('SELECT * FROM CmsUser u INDEX BY id');
    }

    public function testIndexBySupportsJoins()
    {
        $this->assertValidDql('SELECT u.*, u.articles.* FROM CmsUser u LEFT JOIN u.articles INDEX BY id'); // INDEX BY is now referring to articles
    }

    public function testIndexBySupportsJoins2()
    {
        $this->assertValidDql('SELECT u.*, p.* FROM CmsUser u INDEX BY id LEFT JOIN u.phonenumbers p INDEX BY phonenumber');
    }

    public function testBetweenExpressionSupported()
    {
        $this->assertValidDql("SELECT * FROM CmsUser u WHERE u.name BETWEEN 'jepso' AND 'zYne'");
    }

    public function testNotBetweenExpressionSupported()
    {
        $this->assertValidDql("SELECT * FROM CmsUser u WHERE u.name NOT BETWEEN 'jepso' AND 'zYne'");
    }
/*
    public function testAllExpression()
    {
        // We need existant classes here, otherwise semantical will always fail
        $this->assertValidDql('SELECT * FROM Employee e WHERE e.salary > ALL (SELECT m.salary FROM Manager m WHERE m.department = e.department)');
    }

    public function testAnyExpression()
    {
        // We need existant classes here, otherwise semantical will always fail
        $this->assertValidDql('SELECT * FROM Employee e WHERE e.salary > ANY (SELECT m.salary FROM Manager m WHERE m.department = e.department)');
    }

    public function testSomeExpression()
    {
        // We need existant classes here, otherwise semantical will always fail
        $this->assertValidDql('SELECT * FROM Employee e WHERE e.salary > SOME (SELECT m.salary FROM Manager m WHERE m.department = e.department)');
    }
*/
    public function testLikeExpression()
    {
        $this->assertValidDql("SELECT u.id FROM CmsUser u WHERE u.name LIKE 'z%'");
    }

    public function testNotLikeExpression()
    {
        $this->assertValidDql("SELECT u.id FROM CmsUser u WHERE u.name NOT LIKE 'z%'");
    }

    public function testLikeExpressionWithCustomEscapeCharacter()
    {
        $this->assertValidDql("SELECT u.id FROM CmsUser u WHERE u.name LIKE 'z|%' ESCAPE '|'");
    }


    public function testInvalidSyntaxIsRejected()
    {
        $this->assertInvalidDql("FOOBAR CmsUser");

        $this->assertInvalidDql("DELETE FROM CmsUser.articles");

        $this->assertInvalidDql("DELETE FROM CmsUser cu WHERE cu.articles.id > ?");
    }

}
