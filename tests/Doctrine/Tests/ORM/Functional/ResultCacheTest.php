<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Common\Cache\ArrayCache;

require_once __DIR__ . '/../../TestInit.php';

/**
 * ResultCacheTest
 *
 * @author robo
 */
class ResultCacheTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testResultCache()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'dev';
        $this->_em->persist($user);
        $this->_em->flush();


        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $cache = new ArrayCache;
        $cache->setManageCacheIds(true);
        $query->setResultCacheDriver($cache);
		$this->assertEquals(0, $cache->count());
		
        $users = $query->getResult();

       	$this->assertEquals(1, $cache->count());
        $this->assertEquals(1, count($users));
        $this->assertEquals('Roman', $users[0]->name);
        
        $this->_em->clear();
        
        $query2 = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $query2->setResultCacheDriver($cache);
        
        $users = $query2->getResult();

       	$this->assertEquals(1, $cache->count());
        $this->assertEquals(1, count($users));
        $this->assertEquals('Roman', $users[0]->name);
    }

    public function testSetResultCacheId()
    {
        $cache = new ArrayCache;

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $query->setResultCacheDriver($cache);
        $query->setResultCacheId('testing_result_cache_id');
        $users = $query->getResult();

        $this->assertTrue($cache->contains('testing_result_cache_id'));
    }

    public function testUseResultCache()
    {
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $this->_em->getConfiguration()->setResultCacheImpl($cache);

        $query = $this->_em->createQuery('select ux from Doctrine\Tests\Models\CMS\CmsUser ux');
        $query->useResultCache(true);
        $query->setResultCacheId('testing_result_cache_id');
        $users = $query->getResult();

        $this->assertTrue($cache->contains('testing_result_cache_id'));

        $this->_em->getConfiguration()->setResultCacheImpl(null);
    }
}