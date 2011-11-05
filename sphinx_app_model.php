<?php
/**
 * Sphinx App Model File
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
 * @subpackage sphinx.models
 * @copyright  2011 Anthony Putignano <contact@anthonyputignano.com>
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://github.com/anthonyp/CakePHP-Sphinx-Plugin
 */

/**
 * Sphinx App Model Class
 *
 * @package    sphinx
 * @subpackage sphinx.models
 */
class SphinxAppModel extends AppModel {
	
	/**
	 * @var	bool
	 */
	public $useTable = false;
	
	/**
	 * Name of datasource config to use
	 *
	 * @var string
	 */
	public $useDbConfig = 'sphinx';
	
	/**
	 * Adds the datasource to the connection manager if it's not already there,
	 * which it won't be if you've not added it to your app/config/database.php
	 * file.
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param 	$id
	 * @param 	$table
	 * @param 	$ds
	 * @return	void
	 */
	public function __construct ($id = false, $table = null, $ds = null) {

		// Making sure the datasource is always set to 'sphinx', no matter what
		if (!empty($id['ds'])) {
			$id['ds'] = 'sphinx';
		}
		if (!empty($ds)) {
			$ds = 'sphinx';
		}
		
		$sources = ConnectionManager::sourceList();
		
		if (!in_array('sphinx', $sources)) {
			ConnectionManager::create('sphinx', array(
				'datasource' => 'Sphinx.SphinxSource',
				'host' => 'localhost',
				'port' => 9306,
				'login' => null,
				'password' => null
			));
		}
		
		parent::__construct($id, $table, $ds);

  }
	
}
?>