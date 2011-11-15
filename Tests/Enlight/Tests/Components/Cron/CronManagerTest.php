<?php
/**
 * Enlight
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://enlight.de/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@shopware.de so we can send you a copy immediately.
 *
 * @category   Enlight
 * @package    Enlight_Tests
 * @copyright  Copyright (c) 2011, shopware AG (http://www.shopware.de)
 * @license    http://enlight.de/license/new-bsd     New BSD License
 * @version    $Id$
 * @author     $Author$
 */

/**
 * Test case
 *
 * @category   Enlight
 * @package    Enlight_Tests
 * @copyright  Copyright (c) 2011, shopware AG (http://www.shopware.de)
 * @license    http://enlight.de/license/new-bsd     New BSD License
 */
class Enlight_Tests_Components_Cron_CronManager extends Enlight_Components_Test_TestCase
{
	/**
	 * @var Enlight_Components_Cron_Adapter_Adapter
	 */
	private $_adapter = null;

	/**
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $db;

	/**
	 * @var array
	 */
	private $options = array();

	/**
	 * @var Enlight_Components_Cron_CronManager
	 */
	private $manager;

	private $jobData = array();

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
		$dir = Enlight_TestHelper::Instance()->TestPath('TempFiles');
        $this->db = Enlight_Components_Db::factory('PDO_SQLITE', array(
            'dbname'   => $dir . 'cron.db'
        ));

		$dbFile = Enlight_TestHelper::Instance()->TestPath('TempFiles') . 'cron.db';
		if(file_exists($dbFile)) {
			unlink($dbFile);
		}

		$sql = '
				CREATE TABLE IF NOT EXISTS `s_crontab` (
				  `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
				  `name` varchar(255) NOT NULL,
				  `action` varchar(255) NOT NULL,
				  `data` text NOT NULL,
				  `next` datetime DEFAULT NULL,
				  `start` datetime DEFAULT NULL,
				  `interval` int(11) NOT NULL,
				  `active` int(1) NOT NULL,
				  `end` datetime DEFAULT NULL,
				  UNIQUE (`action`)
				);
		';

		$this->db->exec($sql);

		$options = array('id' => 'id',
						'name' => 'name',
						'action' => 'action',
						'data' => 'data',
						'next' => 'next',
						'start' => 'start',
						'interval' => 'interval',
						'active' => 'active',
						'end' => 'end',
						'db'=>$this->db);

		$this->jobData =  array('id'=>'1',
						  'name'=>'Lagerbestand Warnung',
						  'action'=>'article_stock',
						  'data'=>'',
						  'next'=>'2010-10-16 12:34:33',
						  'start'=>'2010-10-16 12:34:31',
						  'interval'=>'5',
						  'active'=>'1',
						  'end'=>'2010-10-16 12:34:32',
						  'crontab'=>'s_crontab');
		
		$this->assertInstanceOf('Enlight_Components_Cron_Adapter_Adapter', $this->_adapter = new Enlight_Components_Cron_Adapter_DbAdapter($options));
		$this->assertInstanceOf('Enlight_Components_Cron_CronManager', $this->manager = new Enlight_Components_Cron_CronManager($this->_adapter));

		$job = new Enlight_Components_Cron_CronJob($this->jobData);

		$this->assertInstanceOf('Enlight_Components_Cron_CronManager',$this->manager->addCronJob($job));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
		$dbFile = Enlight_TestHelper::Instance()->TestPath('TempFiles') . 'cron.db';
		unlink($dbFile);
    }

    /**
     * 
     */
    public function testSetAdapter()
    {
		$this->assertInstanceOf('Enlight_Components_Cron_CronManager', $this->manager->setAdapter($this->_adapter));
    }

    /**
     * @todo Implement testGetAdapter().
     */
    public function testGetAdapter()
    {
       $this->assertInstanceOf('Enlight_Components_Cron_Adapter_Adapter', $this->manager->getAdapter());
    }

    /**
     * @todo Implement testDeactivateJob().
     */
    public function testDeactivateJob()
    {
        $job = $this->manager->getCronJobById(1	);
		$this->assertInstanceOf('Enlight_Components_Cron_CronJob', $job);

		$this->assertTrue($job->isActive());
		$this->manager->deactivateJob($job);
		$jobAfter = $this->manager->getCronJobById($job->getId());
		$this->assertInstanceOf('Enlight_Components_Cron_CronJob', $jobAfter);
		$this->assertFalse($jobAfter->isActive());
    }

    /**
     * @todo Implement testUpdateJob().
     */
    public function testUpdateJob()
    {
        $job = $this->manager->getCronJobById(1);
		$this->assertInstanceOf('Enlight_Components_Cron_CronJob', $job);

		$job->setName('test me');

		$this->manager->updateJob($job);

		$jobAfter = $this->manager->getCronJobById($job->getId());
		$this->assertEquals('test me', $jobAfter->getName());

    }

    /**
     * @todo Implement testGetAllCronJobs().
     */
    public function testGetAllCronJobs()
    {
        $this->assertArrayCount(1,$this->manager->getAllCronJobs());
    }

    /**
     * @todo Implement testGetCronJobById().
     */
    public function testGetCronJobById()
    {
        $this->assertInstanceOf('Enlight_Components_Cron_CronJob', $this->manager->getCronJobById(1));
    }

    /**
     * @todo Implement testGetCronJobByName().
     */
    public function testGetCronJobByName()
    {
        // Remove the following lines when you implement this test.
        $this->assertInstanceOf('Enlight_Components_Cron_CronJob', $this->manager->getCronJobByName('Lagerbestand Warnung'));
    }

    /**
     * @todo Implement testAddCronJob().
     */
    public function testAddCronJob()
    {
		$this->assertArrayCount(1,$this->manager->getAllCronJobs());
		$this->jobData['action'] = $this->jobData['action']."2";
		$this->jobData['id'] = 2;
		$job = new Enlight_Components_Cron_CronJob($this->jobData);
		$this->manager->addCronJob($job);
		$this->assertArrayCount(2,$this->manager->getAllCronJobs());
    }

    /**
     * @todo Implement testDeleteCronJob().
     */
    public function testDeleteCronJob()
    {
        // We've got one element that is for sure
		$this->assertArrayCount(1,$this->manager->getAllCronJobs());
		// Now create a new job
		$this->jobData['action'] = $this->jobData['action']."2";
		$this->jobData['id'] = 2;
		// Append new job to the crontab
        $job = new Enlight_Components_Cron_CronJob($this->jobData);
		$this->manager->addCronJob($job);
		// see if we have two elements now
		$this->assertArrayCount(2,$this->manager->getAllCronJobs());
		// Get first element
		$job2delete = $this->manager->getCronJobById(1);
		// Delete Element
		$this->manager->deleteCronJob($job2delete);
		$this->assertArrayCount(1,$this->manager->getAllCronJobs());
    }

	
}
