<?php

namespace Doctrine\Tests;

/**
 * Base testcase class for all functional ORM testcases.
 *
 * @since 2.0
 */
class OrmFunctionalTestCase extends OrmTestCase
{
    /* The metadata cache shared between all functional tests. */
    private static $_metadataCacheImpl = null;
    /* The query cache shared between all functional tests. */
    private static $_queryCacheImpl = null;

    /* Shared connection when a TestCase is run alone (outside of it's functional suite) */
    private static $_sharedConn;
    
    /** The EntityManager for this testcase. */
    protected $_em;

    /** The SchemaTool. */
    protected $_schemaTool;

    /** The names of the model sets used in this testcase. */
    private $_usedModelSets = array();

    /** Whether the database schema has already been created. */
    private static $_tablesCreated = array();

    /** List of model sets and their classes. */
    private static $_modelSets = array(
        'cms' => array(
            'Doctrine\Tests\Models\CMS\CmsUser',
            'Doctrine\Tests\Models\CMS\CmsPhonenumber',
            'Doctrine\Tests\Models\CMS\CmsAddress',
            'Doctrine\Tests\Models\CMS\CmsGroup',
            'Doctrine\Tests\Models\CMS\CmsArticle'
        ),
        'forum' => array(),
        'company' => array(
            'Doctrine\Tests\Models\Company\CompanyPerson',
            'Doctrine\Tests\Models\Company\CompanyEmployee',
            'Doctrine\Tests\Models\Company\CompanyManager',
            'Doctrine\Tests\Models\Company\CompanyOrganization',
            'Doctrine\Tests\Models\Company\CompanyEvent',
            'Doctrine\Tests\Models\Company\CompanyAuction',
            'Doctrine\Tests\Models\Company\CompanyRaffle'
        ),
        'ecommerce' => array(
            'Doctrine\Tests\Models\ECommerce\ECommerceCart',
            'Doctrine\Tests\Models\ECommerce\ECommerceCustomer',
            'Doctrine\Tests\Models\ECommerce\ECommerceProduct',
            'Doctrine\Tests\Models\ECommerce\ECommerceShipping',
            'Doctrine\Tests\Models\ECommerce\ECommerceFeature',
            'Doctrine\Tests\Models\ECommerce\ECommerceCategory'
        ),
        'generic' => array(
            'Doctrine\Tests\Models\Generic\DateTimeModel'
        )
    );

    protected function useModelSet($setName)
    {
        $this->_usedModelSets[$setName] = true;
    }
    
    /**
     * Sweeps the database tables and clears the EntityManager.
     */
    protected function tearDown()
    {
        $conn = $this->sharedFixture['conn'];
        
        if (isset($this->_usedModelSets['cms'])) {
            $conn->executeUpdate('DELETE FROM cms_users_groups');
            $conn->executeUpdate('DELETE FROM cms_groups');
            $conn->executeUpdate('DELETE FROM cms_addresses');
            $conn->executeUpdate('DELETE FROM cms_phonenumbers');
            $conn->executeUpdate('DELETE FROM cms_articles');
            $conn->executeUpdate('DELETE FROM cms_users');
        }
        
        if (isset($this->_usedModelSets['ecommerce'])) {
            $conn->executeUpdate('DELETE FROM ecommerce_carts_products');
            $conn->executeUpdate('DELETE FROM ecommerce_products_categories');
            $conn->executeUpdate('DELETE FROM ecommerce_products_related');
            $conn->executeUpdate('DELETE FROM ecommerce_carts');
            $conn->executeUpdate('DELETE FROM ecommerce_customers');
            $conn->executeUpdate('DELETE FROM ecommerce_features');
            $conn->executeUpdate('DELETE FROM ecommerce_products');
            $conn->executeUpdate('DELETE FROM ecommerce_shippings');
            $conn->executeUpdate('UPDATE ecommerce_categories SET parent_id = NULL');
            $conn->executeUpdate('DELETE FROM ecommerce_categories');
        }
        
        if (isset($this->_usedModelSets['company'])) {
            $conn->executeUpdate('DELETE FROM company_persons_friends');
            $conn->executeUpdate('DELETE FROM company_managers');
            $conn->executeUpdate('DELETE FROM company_employees');
            $conn->executeUpdate('UPDATE company_persons SET spouse_id = NULL');
            $conn->executeUpdate('DELETE FROM company_persons');
            $conn->executeUpdate('DELETE FROM company_raffles');
            $conn->executeUpdate('DELETE FROM company_auctions');
            $conn->executeUpdate('DELETE FROM company_events');
            $conn->executeUpdate('DELETE FROM company_organizations');
        }
        
        if (isset($this->_usedModelSets['generic'])) {
            $conn->executeUpdate('DELETE FROM date_time_model');
        }
        
        $this->_em->clear();
    }

    /**
     * Creates a connection to the test database, if there is none yet, and
     * creates the necessary tables.
     */
    protected function setUp()
    {
        $forceCreateTables = false;
        
        if ( ! isset($this->sharedFixture['conn'])) {
            if ( ! isset(self::$_sharedConn)) {
                self::$_sharedConn = TestUtil::getConnection();
            }
            
            $this->sharedFixture['conn'] = self::$_sharedConn;
            
            if ($this->sharedFixture['conn']->getDriver() instanceof \Doctrine\DBAL\Driver\PDOSqlite\Driver) {
                $forceCreateTables = true;
            }
        }
        
        if ( ! $this->_em) {
            $this->_em = $this->_getEntityManager();
            $this->_schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->_em);
        }

        $classes = array();
        
        foreach ($this->_usedModelSets as $setName => $bool) {
            if ( ! isset(self::$_tablesCreated[$setName])/* || $forceCreateTables*/) {
                foreach (self::$_modelSets[$setName] as $className) {
                    $classes[] = $this->_em->getClassMetadata($className);
                }
                
                self::$_tablesCreated[$setName] = true;
            }
        }
        
        if ($classes) {
            $this->_schemaTool->createSchema($classes);
        }
    }

    /**
     * Gets an EntityManager for testing purposes.
     *
     * @param Configuration $config The Configuration to pass to the EntityManager.
     * @param EventManager $eventManager The EventManager to pass to the EntityManager.
     * @return EntityManager
     */
    protected function _getEntityManager($config = null, $eventManager = null) {
        // NOTE: Functional tests use their own shared metadata cache, because
        // the actual database platform used during execution has effect on some
        // metadata mapping behaviors (like the choice of the ID generation).
        if (is_null(self::$_metadataCacheImpl)) {
            self::$_metadataCacheImpl = new \Doctrine\Common\Cache\ArrayCache;
        }
        
        if (is_null(self::$_queryCacheImpl)) {
        	self::$_queryCacheImpl = new \Doctrine\Common\Cache\ArrayCache;
        }
        
        //FIXME: two different configs! $conn and the created entity manager have
        // different configs.
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(self::$_metadataCacheImpl);
        $config->setQueryCacheImpl(self::$_queryCacheImpl);
        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $conn = $this->sharedFixture['conn'];
        
        return \Doctrine\ORM\EntityManager::create($conn, $config);
    }
}
