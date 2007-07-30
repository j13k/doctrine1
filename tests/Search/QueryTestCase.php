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
 * Doctrine_Search_Query_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Search_Query_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
        $this->tables = array('SearchTest', 'SearchTestIndex');

        parent::prepareTables();
    }
    public function prepareData()
    { }

    public function testInitData()
    {
    	$e = new SearchTest();

        $e->title = 'Once there was an ORM framework';
        $e->content = 'There are many ORM frameworks, but nevertheless we decided to create one.';

        $e->save();

    	$e = new SearchTest();

        $e->title = 'Doctrine development continues';
        $e->content = 'The development has been going well so far.';

        $e->save();
    }

    public function testTokenizeClauseSupportsAndOperator()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');

        $ret = $q->tokenizeClause('doctrine AND orm');

        $this->assertEqual($ret, array(array('doctrine', 'orm')));
    }

    public function testTokenizeClauseSupportsOrOperator()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');

        $ret = $q->tokenizeClause('doctrine OR orm');

        $this->assertIdentical($ret, array('doctrine', 'orm'));
    }

    public function testTokenizeClauseSupportsMixingOfOperators()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');

        $ret = $q->tokenizeClause('doctrine OR orm OR dbal AND database OR rdbms');
        $expected = array('doctrine', 'orm', array('dbal', 'database'), 'rdbms');

        $this->assertEqual($ret, $expected);
    }

    public function testTokenizeClauseSupportsMixingOfOperatorsAndSpaces()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');

        $ret = $q->tokenizeClause('doctrine OR orm dbal AND database OR rdbms');

        $expected = array('doctrine', array('orm', 'dbal', 'database'), 'rdbms');

        $this->assertEqual($ret, $expected);
    }

    public function testTokenizeClauseSupportsMixingOfOperatorsAndParenthesis()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');

        $ret = $q->tokenizeClause('doctrine OR orm OR (dbal AND database OR rdbms)');
        $expected = array('doctrine', 'orm', '(dbal and database or rdbms)');

        $this->assertEqual($ret, $expected);
    }
                        /*
    public function testParseClauseSupportsAndOperator()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $ret = $q->parseClause('doctrine AND orm');

        $sql = '(search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?)) '
             . 'AND (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?))';

        $this->assertEqual($ret, $sql);
    }

    public function testParseClauseSupportsMixingOfOperatorsAndParenthesis()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $ret = $q->parseClause('((doctrine OR orm) AND dbal) OR database');

        $sql = '(search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ? OR keyword = ?)) '
             . 'AND (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?))'
             . ' OR keyword = ?';

        $this->assertEqual($ret, $sql);
    }

    public function testParseClauseSupportsMixingOfOperators3()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $ret = $q->parseClause('doctrine OR orm AND dbal');

        $sql = '(search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ? OR keyword = ?)) '
             . 'AND (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?))';

        $this->assertEqual($ret, $sql);
    }

    public function testParseClauseSupportsMixingOfOperators()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $ret = $q->parseClause('(doctrine OR orm) AND dbal');

        $sql = '(search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ? OR keyword = ?)) '
             . 'AND (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?))';

        $this->assertEqual($ret, $sql);
    }

    public function testParseClauseSupportsMixingOfOperators2()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $ret = $q->parseClause('(doctrine OR orm) dbal');

        $sql = '(search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ? OR keyword = ?)) '
             . 'AND (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?))';

        $this->assertEqual($ret, $sql);
    }

    public function testParseClauseSupportsMixingOfOperatorsAndDeeplyNestedParenthesis()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $ret = $q->parseClause('(((doctrine OR orm) AND dbal) OR database) AND rdbms');

        $sql = '((search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ? OR keyword = ?)) '
             . 'AND (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?))'
             . ' OR keyword = ?) AND (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?))';

        $this->assertEqual($ret, $sql);
    }
    public function testParseClauseSupportsMixingOfOperatorsAndDeeplyNestedParenthesis2()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $ret = $q->parseClause('rdbms (((doctrine OR orm) AND dbal) OR database)');

        $sql = '(search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?)) AND '
             . '((search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ? OR keyword = ?)) '
             . 'AND (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?))'
             . ' OR keyword = ?)';

        $this->assertEqual($ret, $sql);
    }

    public function testParseClauseSupportsNegationOperator()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $ret = $q->parseClause('rdbms -doctrine');

        $sql = '(search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?)) AND '
             . '(search_test_id NOT IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?))';

        $this->assertEqual($ret, $sql);
    }
    public function testParseClauseOrOperator2()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $ret = $q->parseClause('rdbms doctrine OR database');

        $sql = '(search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?)) AND '
             . '(search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?)) '
             . 'OR keyword = ?';

        $this->assertEqual($ret, $sql);
    }
    public function testParseClauseSupportsNegationOperatorWithOrOperator()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $ret = $q->parseClause('rdbms -doctrine OR database');

        $sql = '(search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?)) AND '
             . '(search_test_id NOT IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?)) '
             . 'OR keyword = ?';

        $this->assertEqual($ret, $sql);
    }
    public function testSearchSupportsAndOperator()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $q->search('doctrine AND orm');

        $sql = 'SELECT COUNT(keyword) AS relevance, search_test_id '
             . 'FROM search_test_index '
             . 'WHERE (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?)) '
             . 'AND (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?)) '
             . 'GROUP BY search_test_id ORDER BY relevance';

        $this->assertEqual($q->getSql(), $sql);
    }
    
    /**
    public function testSearchSupportsOrOperator()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $q->search('doctrine OR orm');

        $sql = 'SELECT COUNT(keyword) AS relevance, search_test_id '
             . 'FROM search_test_index '
             . 'WHERE keyword = ? OR keyword = ? '
             . 'GROUP BY search_test_id ORDER BY relevance';

        $this->assertEqual($q->getSql(), $sql);
    }
    

    public function testQuerySupportsSingleWordSearch()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $q->search('doctrine');

        $sql = 'SELECT COUNT(keyword) AS relevance, search_test_id '
             . 'FROM search_test_index WHERE keyword = ? GROUP BY search_test_id ORDER BY relevance';

        $this->assertEqual($q->getSql(), $sql);
    }

    public function testSearchSupportsMixingOfOperators()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $q->search('(doctrine OR orm) AND dbal');

        $sql = 'SELECT COUNT(keyword) AS relevance, search_test_id '
             . 'FROM search_test_index '
             . 'WHERE (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ? OR keyword = ?)) '
             . 'AND (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?)) '
             . 'GROUP BY search_test_id ORDER BY relevance';

        $this->assertEqual($q->getSql(), $sql);
    }

    public function testSearchSupportsSingleTermWithQuotes()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $q->search("'doctrine orm'");

        $sql = 'SELECT COUNT(keyword) AS relevance, search_test_id '
             . 'FROM search_test_index WHERE keyword = ? '
             . 'AND (position + 1) = (SELECT position FROM search_test_index WHERE keyword = ?) '
             . 'GROUP BY search_test_id ORDER BY relevance';

        $this->assertEqual($q->getSql(), $sql);
    }

    public function testSearchSupportsSingleLongTermWithQuotes()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $q->search("'doctrine orm dbal'");

        $sql = 'SELECT COUNT(keyword) AS relevance, search_test_id '
             . 'FROM search_test_index WHERE keyword = ? '
             . 'AND (position + 1) = (SELECT position FROM search_test_index WHERE keyword = ?) '
             . 'AND (position + 2) = (SELECT position FROM search_test_index WHERE keyword = ?) '
             . 'GROUP BY search_test_id ORDER BY relevance';

        $this->assertEqual($q->getSql(), $sql);
    }

    public function testQuerySupportsMultiWordSearch()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $q->search('doctrine orm');

        $sql = 'SELECT COUNT(keyword) AS relevance, search_test_id '
             . 'FROM search_test_index '
             . 'WHERE (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?)) '
             . 'AND (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?)) '
             . 'GROUP BY search_test_id ORDER BY relevance';

        $this->assertEqual($q->getSql(), $sql);
    }

    public function testSearchSupportsMultipleTermsWithQuotes()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $q->search("doctrine 'orm database'");

        $sql = 'SELECT COUNT(keyword) AS relevance, search_test_id '
             . 'FROM search_test_index '
             . 'WHERE (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ?)) '
             . 'AND (search_test_id IN (SELECT search_test_id FROM search_test_index WHERE keyword = ? '
             . 'AND (position + 1) = (SELECT position FROM search_test_index WHERE keyword = ?))) '
             . 'GROUP BY search_test_id ORDER BY relevance';

        $this->assertEqual($q->getSql(), $sql);
    }
             /**
    public function testQuerySupportsMultiWordOrOperatorSearchWithQuotes()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $q->search("doctrine^2 OR 'dbal database'");

        $sql = 'SELECT foreign_id, SUM(relevancy) AS relevancy_sum ' 
             . 'FROM (SELECT COUNT(keyword) * 2 AS relevancy, foreign_id '
             . 'FROM search_index '
             . 'WHERE keyword = ? '
             . 'GROUP BY foreign_id '
             . 'UNION '
             . 'SELECT COUNT(keyword) AS relevancy, foreign_id '
             . 'FROM search_index) AS query_alias '
             . 'WHERE keyword = ? AND (position + 1) = (SELECT position FROM search_index WHERE keyword = ?) '
             . 'GROUP BY foreign_id) '
             . 'GROUP BY foreign_id '
             . 'ORDER BY relevancy_sum';

        print $q->getSql() . "<br>";
        print $sql;
        $this->assertEqual($q->getSql(), $sql);
    }

    public function testQuerySupportsMultiWordAndOperatorSearchWithQuotes()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $q->search("doctrine 'dbal database'");

        $sql = "SELECT foreign_id, SUM(relevancy) AS relevancy_sum FROM
                        (SELECT COUNT(keyword) * 2 AS relevancy, foreign_id
                            FROM search_index
                            WHERE keyword = 'doctrine'
                            GROUP BY foreign_id
                    UNION
                         SELECT COUNT(keyword) AS relevancy, foreign_id
                            FROM search_index) AS query_alias
                            WHERE keyword = 'dbal' AND (position + 1) = (SELECT position FROM search_index WHERE keyword = 'database')
                            GROUP BY foreign_id
                WHERE search_test_id IN (SELECT search_test_id FROM search_test_index
                                            WHERE keyword = ?)
                  AND seach_test_id IN (SELECT search_test_id FROM search_test_index
                                            WHERE keyword = 'dbal'
                                            AND (position + 1) = (SELECT position FROM search_index WHERE keyword = 'database')
                GROUP BY foreign_id
                ORDER BY relevancy_sum";
                
        $this->assertEqual($q->getSql(), $sql);
    }
    public function testSearchReturnsFalseForEmptyStrings()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $result = $q->search(' ');

        $this->assertFalse($result);
    }
    */
}
