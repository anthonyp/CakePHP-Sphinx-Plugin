<?php
/**
 * SphinxSource Test Case
 *
 * Copyright (c) 2011 Anthony Putignano
 *
 * Distributed under the terms of the MIT License.
 * Redistributions of files must retain the above copyright notice.
 *
 * PHP version 5.3
 * CakePHP version 1.3
 *
 * @package    sphinx
 * @subpackage sphinx.models.datasources
 * @copyright  2011 Anthony Putignano <contact@anthonyputignano.com>
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://github.com/anthonyp/CakePHP-Sphinx-Plugin
 *
 */

App::import('Model', 'Sphinx.SphinxAppModel');

/**
 * SphinxTest Model Class
 * 
 * @package       sphinx
 * @subpackage    sphinx.tests.cases.models.datasources
 */
class SphinxTest extends SphinxAppModel {
	
	public $_full_text_indexes = array(
		'description'
	);

	public $_attributes = array(
		'name',
		'foreign_id'
	);

	public $_schema = array(
		'id' => array(
			'type' => 'integer',
			'null' => false,
			'default' => '',
			'length' => 11,
			'key' => 'primary'
		),
		'name' => array(
			'type' => 'string',
			'null' => false,
			'default' => '',
			'length' => 255
		),
		'description' => array(
			'type' => 'text',
			'null' => false,
			'default' => ''
		),
		'foreign_id' => array(
			'type' => 'integer',
			'null' => false,
			'default' => '',
			'length' => 11
		)
	);
	
}

/**
 * SphinxSource Test Case
 *
 * @package     sphinx
 * @subpackage  sphinx.tests.cases.models.datasources
 * @since       0.1
 * @see         SphinxSource
 * @author      Anthony Putignano <contact@anthonyputignano.com>
 */
class SphinxSourceTestCase extends CakeTestCase {

	/**
     * @var     array
     */
    public $fixtures = array();
    
   	/**
	 * @author  Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @return	void
	 */
    private function _populateData () {
    	
    	$this->SphinxTest->create(array(
			'SphinxTest' => array(
				'id' => 1,
				'name' => 'test name',
				'description' => 'test description',
				'foreign_id' => 3
			)
		));
		$this->SphinxTest->save();
		
		$this->SphinxTest->create(array(
			'SphinxTest' => array(
				'id' => 2,
				'name' => 'test another name',
				'description' => 'test another description',
				'foreign_id' => 5
			)
		));
		$this->SphinxTest->save();
		
		$this->SphinxTest->saveAll(array(
			array(
				'id' => 3,
				'name' => 'test a 3rd name',
				'description' => 'test a 3rd description - another bad description',
				'foreign_id' => 6
			),
			array(
				'id' => 4,
				'name' => 'test a 4th name',
				'description' => 'test a 4th description - another description',
				'foreign_id' => 7
			)
		));
    	
    }
	
	/**
	 * @author  Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @return	void
	 */
	public function start() {

		parent::start();

	}
	
	/**
	 * startTest
	 *
	 * @author  Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @return	void
	 */
	public function startTest () {
		
		ClassRegistry::config(array());
		$this->SphinxTest = ClassRegistry::init('SphinxTest');
		$this->SphinxTest->deleteAll('1 = 1');
		
	}

	/**
	 * endTest
	 *
	 * @author  Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @return	void
	 */
	public function endTest () {
		
		$this->SphinxTest->deleteAll('1 = 1');
		unset($this->SphinxTest);
		ClassRegistry::flush();
		
	}

	/**
	 * Test Instance Creation
	 *
	 * @author  Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @return  void
	 */
	public function testInstanceSetup() {

		$this->assertIsA($this->SphinxTest, 'Model');

	}
	
	/**
	 * Test create
	 *
	 * @author  Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @return  void
	 */
	public function testCreate () {

		$this->_populateData();
		
		$count = $this->SphinxTest->find('count');
		
		$this->assertEqual($count, 4, '4 records should be populated.');

	}
	
	/**
	 * Test update
	 *
	 * @author  Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @return  void
	 */
	public function testUpdate () {
		
		$this->_populateData();
		
		// Sphinx only allows you to update integer attributes, one record at a time
		
		$this->SphinxTest->id = 2;
		$result = $this->SphinxTest->saveField('foreign_id', 7);
		$this->assertTrue($result, 'The update should be successful.');
		
		$foreign_id = $this->SphinxTest->field('foreign_id');
		
		$this->assertEqual($foreign_id, 7, 'The `foreign_id` should be set to 7 for ID 2.');
		
		$this->SphinxTest->id = 3;
		$result = $this->SphinxTest->save(array(
			'SphinxTest' => array(
				'foreign_id' => 10
			)
		));
		$this->assertTrue($result, 'The update should be successful.');
		
		$foreign_id = $this->SphinxTest->field('foreign_id');
		
		$this->assertEqual($foreign_id, 10, 'The `foreign_id` should be set to 10 for ID 3.');
		
	}
	
	/**
	 * Test delete
	 *
	 * @author  Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @return  void
	 */
	public function testDelete () {
		
		$this->_populateData();
		
		$this->SphinxTest->delete(1);
		$leftover_ids = array_values($this->SphinxTest->find('list', array('fields' => 'id')));
		$this->assertEqual($leftover_ids, array(2, 3, 4), 'IDs 2, 3, 4 should be leftover after deleting ID 1.');
		
		$this->SphinxTest->deleteAll(array(
			'SphinxTest.foreign_id' => 6
		));
		$leftover_ids = array_values($this->SphinxTest->find('list', array('fields' => 'id')));
		$this->assertEqual($leftover_ids, array(2, 4), 'IDs 2, 4 should be leftover after deleting ID 3.');
		
		$this->SphinxTest->deleteAll('1 = 1');
		$count = $this->SphinxTest->find('count');
		$this->assertEqual($count, 0, 'No records should be leftover after deleting WHERE 1 = 1.');
		
		$this->_populateData();
		
		$this->SphinxTest->deleteAll(array(
			'SphinxTest.id' => array(1, 2, 4)
		));
		$leftover_ids = array_values($this->SphinxTest->find('list', array('fields' => 'id')));
		$this->assertEqual($leftover_ids, array(3), 'ID 3 should be leftover after deleting IDs 1, 2, 4.');
		
		$this->SphinxTest->deleteAll('1 = 1');
		$count = $this->SphinxTest->find('count');
		$this->assertEqual($count, 0, 'No records should be leftover after deleting WHERE 1 = 1.');
		
		$this->_populateData();
		
		$this->SphinxTest->deleteAll(array(
			'foreign_id' => array(3, 5)
		));
		$leftover_ids = array_values($this->SphinxTest->find('list', array('fields' => 'id')));
		$this->assertEqual($leftover_ids, array(3, 4), 'IDs 3, 4 should be leftover after deleting IDs 1, 2.');
		
		$this->SphinxTest->deleteAll(array(
			'id' => array(3, 4)
		));
		$count = $this->SphinxTest->find('count');
		$this->assertEqual($count, 0, 'No records should be leftover after deleting IDs 3, 4.');
		
		$this->_populateData();
		
		$this->SphinxTest->deleteAll(array(
			'SphinxTest.foreign_id' => array(3)
		));
		$leftover_ids = array_values($this->SphinxTest->find('list', array('fields' => 'id')));
		$this->assertEqual($leftover_ids, array(2, 3, 4), 'IDs 2, 3, 4 should be leftover after deleting ID 1.');
		
		$this->SphinxTest->deleteAll(array(
			'id' => array(3)
		));
		$leftover_ids = array_values($this->SphinxTest->find('list', array('fields' => 'id')));
		$this->assertEqual($leftover_ids, array(2, 4), 'IDs 2, 4 should be leftover after deleting ID 3.');
		
		$this->SphinxTest->deleteAll('1=1');
		$count = $this->SphinxTest->find('count');
		$this->assertEqual($count, 0, 'No records should be leftover after deleting WHERE 1=1.');
		
	}
	
	/**
	 * Test read
	 *
	 * @author  Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @return  void
	 */
	public function testRead () {
		
		$this->_populateData();
		
		$count = $this->SphinxTest->find('count');
		$this->assertEqual($count, 4, 'There should be 4 records WHERE 1 = 1.');
		
		$count = $this->SphinxTest->find('count', array(
			'conditions' => array(
				'SphinxTest.foreign_id' => array(3, 4)
			)
		));
		$this->assertEqual($count, 1, 'There should be 1 record WHERE SphinxTest.foreign_id IN (3, 4).');
		
		$count = $this->SphinxTest->find('count', array(
			'conditions' => array(
				'foreign_id' => array(3, 6)
			)
		));
		$this->assertEqual($count, 2, 'There should be 2 records WHERE foreign_id IN (3, 6).');
		
		$count = $this->SphinxTest->find('count', array(
			'conditions' => array(
				'SphinxTest.id' => array(2, 3)
			)
		));
		$this->assertEqual($count, 2, 'There should be 2 records WHERE SphinxTest.id IN (2, 3).');
		
		$count = $this->SphinxTest->find('count', array(
			'conditions' => array(
				'id' => array(3, 4)
			)
		));
		$this->assertEqual($count, 2, 'There should be 2 records WHERE id IN (3, 4).');
		
		$this->SphinxTest->id = 1;
		$name = $this->SphinxTest->field('name');
		$this->assertEqual($name, 'test name', '"test name" should be returned.');
		
		$result = $this->SphinxTest->find('list', array('fields' => array('id')));
		$expects = array(1 => 1, 2 => 2, 3 => 3, 4 => 4);
		$this->assertEqual($result, $expects, 'A full list should be returned where the ID is both the key & value.');
		
		$records = $this->SphinxTest->find('all', array(
			'conditions' => array(
				'SphinxTest.foreign_id' => array(3, 7)
			)
		));
		$expects = array(
			array(
				'SphinxTest' => array(
					'id' => 1,
					'weight' => 1,
					'foreign_id' => 3,
					'name' => 'test name'
				)
			),
			array(
				'SphinxTest' => array(
					'id' => 4,
					'weight' => 1,
					'foreign_id' => 7,
					'name' => 'test a 4th name'
				)
			)
		);
		$this->assertEqual($records, $expects, '2 records should be returned WHERE SphinxTest.foreign_id IN (3, 7).');
		
		$records = $this->SphinxTest->find('all', array(
			'fields' => array(
				'id',
				'foreign_id',
				'weight'
			),
			'conditions' => array(
				'MATCH' => "another description"
			),
			'order' => array('SphinxTest.weight' => 'desc'),
			'options' => array(
				'ranker' => 'proximity'
			),
			'limit' => 3,
			'page' => 1
		));
		$expects = array(
			array(
				'SphinxTest' => array(
					'id' => 2,
					'weight' => 2,
					'foreign_id' => 5
				)
			),
			array(
				'SphinxTest' => array(
					'id' => 4,
					'weight' => 2,
					'foreign_id' => 7
				)
			),
			array(
				'SphinxTest' => array(
					'id' => 3,
					'weight' => 1,
					'foreign_id' => 6
				)
			)
		);
		$this->assertEqual($records, $expects, '3 records should be returned matching "another description" with a LIMIT of 3.');
		
		$records = $this->SphinxTest->find('all', array(
			'fields' => array(
				'id',
				'name',
				'foreign_id',
				'weight'
			),
			'conditions' => array(
				'SphinxTest.foreign_id' => array(3, 5, 6, 7),
				'MATCH' => "another description"
			),
			'order' => array('SphinxTest.weight' => 'desc'),
			'limit' => 2,
			'page' => 2
		));
		$expects = array(
			array(
				'SphinxTest' => array(
					'id' => 3,
					'weight' => 1336,
					'foreign_id' => 6,
					'name' => 'test a 3rd name'
				)
			)
		);
		$this->assertEqual($records, $expects, '1 record should be returned matching "another description" AND SphinxTest.foreign_id IN (3, 5, 6, 7) with a LIMIT of 2 on page 2.');
		
	}
	
}

?>