<?php
/**
 * SphinxSource DataSource File
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
 */

/**
 * SphinxSource DataSource Class
 *
 * @package    sphinx
 * @subpackage sphinx.models.datasources
 */
App::import('Core', array('Model', 'DataSource', 'DboSource', 'DboMysqli'));
class SphinxSource extends DboMysqli {

	/**
	 * The description of this data source
	 *
	 * @var string
	 */
	public $description = 'Sphinx DataSource';
	
	/**
	 * Connects to the database using options in the given configuration array.
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @return	boolean		True if the database could be connected, else false
	 */
	public function connect () {
	
		$connected = parent::connect();
		
		if (!$connected) {
			return false;
		}
		
		$this->_useAlias = false;
		
		return $connected;
	
	}
	
	/**
	 * Returns an detailed array of sources (tables) in the database.
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	string		$name	Table name to get parameters 
	 * @return	array 		Array of tablenames in the database
	 */
	public function listDetailedSources ($name=null) {
		return array();
	}
	
	/**
	 * Caches/returns cached results for child instances
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	mixed		$data
	 * @return	array		Array of sources available in this datasource.
	 */
	public function listSources () {
		
		$sources = array();
		if (!empty($this->config['sources'])) {
			$sources = $this->config['sources'];
		}
		return $sources;
		
	}
	
	/**
	 * Returns a Model description (metadata) or null if none found.
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	Model		$model
	 * @return	array		Array of Metadata for the $model
	 */
	public function describe (&$model) {
		
		if ($this->cacheSources === false) {
			return null;
		}
		$table = $model->tablePrefix . $model->table;

		if (isset($this->__descriptions[$table])) {
			return $this->__descriptions[$table];
		}
		$cache = $this->__cacheDescription($table);

		if ($cache !== null) {
			$this->__descriptions[$table] =& $cache;
			return $cache;
		}
		
		$fields = false;
		$cols = $this->query('DESCRIBE ' . $this->fullTableName($model));

		foreach ($cols as $column) {
			$colKey = array_keys($column);
			if (isset($column[$colKey[0]]) && !isset($column[0])) {
				$column[0] = $column[$colKey[0]];
			}
			if (isset($column[0])) {
				$fields[$column[0]['Field']] = array(
					'type' => $this->column($column[0]['Type']),
					'null' => false, // TODO: Any way to detect whether or not NULL is acceptable?
					'default' => '',
					'length' => 0, // TODO: Add in true 'length' support
				);
				if (!empty($column[0]['Key']) && isset($this->index[$column[0]['Key']])) {
					$fields[$column[0]['Field']]['key'] = $this->index[$column[0]['Key']];
				}
			}
		}
		$this->__cacheDescription($this->fullTableName($model, false), $fields);
		
		return $fields;
		
	}
	
	/**
	 * Generates the fields list of an SQL query.
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	Model		$model
	 * @param	string		$alias		Alias tablename
	 * @param	mixed		$fields
	 * @param	boolean		$quote 		If false, returns fields array unquoted
	 * @return	array
	 */
	public function fields(&$model, $alias = null, $fields = array(), $quote = true) {
		
		if (empty($fields)) {
			$fields = array_keys($model->schema());
		}
		
		if (is_string($fields)) {
			$fields = array($fields);
		}
		
		if (!empty($model->_full_text_indexes)) {
			foreach ($fields as $key => $field) {
				if (in_array($field, $model->_full_text_indexes)) {
					unset($fields[$key]);
				}
			}
			$fields = array_values($fields);
		}
		
		foreach ($fields as $key => $field) {
			$fields[$key] = str_replace($model->alias . '.', '', $field);
			if ($fields[$key] == 'id' || $fields[$key] == '`id`') {
				$fields[$key] = '@id `id`';
			}
			if ($fields[$key] == 'weight' || $fields[$key] == '`weight`') {
				$fields[$key] = '@weight `weight`';
			}
		}
		
		if (count($fields) == 1 && $fields[0] != '@count `count`' && $fields[0] != '@id `id`') {
			$fields[] = '@id `id`';
		}
	
		$return = parent::fields(&$model, $alias, $fields, false);
		
		return $return;
	
	}
	
	/**
	 * Returns an SQL calculation, i.e. COUNT() or MAX()
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	model		$model
	 * @param	string		$func		Lowercase name of SQL function, i.e. 'count' or 'max'
	 * @param	array		$params		Function parameters (any values must be quoted manually)
	 * @return	string 		An SQL calculation function
	 */
	public function calculate (&$model, $func='', $params=array()) {
		
		$params = (array)$params;

		switch (strtolower($func)) {
			case 'count':
				/*if (!isset($params[0])) {
					$params[0] = '*';
				}*/
				if (!isset($params[1])) {
					$params[1] = 'count';
				}
				/*if (is_object($model) && $model->isVirtualField($params[0])){
					$arg = $this->__quoteFields($model->getVirtualField($params[0]));
				} else {
					$arg = $this->name($params[0]);
				}*/
				return '@count ' . $this->name($params[1]);
			case 'max':
			case 'min':
				if (!isset($params[1])) {
					$params[1] = $params[0];
				}
				if (is_object($model) && $model->isVirtualField($params[0])) {
					$arg = $this->__quoteFields($model->getVirtualField($params[0]));
				} else {
					$arg = $this->name($params[0]);
				}
				return strtoupper($func) . '(' . $arg . ') ' . $this->name($params[1]);
			break;
		}
		
	}
	
	/**
	 * Creates a WHERE clause by parsing given conditions data.  If an array or string
	 * conditions are provided those conditions will be parsed and quoted.  If a boolean
	 * is given it will be integer cast as condition.  Null will return 1 = 1.
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	mixed		$conditions		Array or string of conditions, or any value.
	 * @param	boolean		$quoteValues	If true, values should be quoted
	 * @param	boolean		$where			If true, "WHERE " will be prepended to the return value
	 * @param	Model		$model			A reference to the Model instance making the query
	 * @return	string SQL fragment
	 */
	public function conditions($conditions, $quoteValues=true, $where=true, $model=null) {
		
		if (!empty($conditions) && is_array($conditions)) {
			if (!empty($conditions['MATCH'])) {
				$match = $conditions['MATCH'];
				unset($conditions['MATCH']);
			}
		}
		
		if (is_array($conditions)) {
			foreach ($conditions as $field => $condition) {
				if (is_array($condition) && count($condition) == 1) {
					$conditions[$field] = array_pop($condition);
				}
			}
		}
		
		$conditions = parent::conditions($conditions, $quoteValues, $where, $model);
		
		if (str_replace(' ', '', $conditions) == 'WHERE1=1') {
			$conditions = '';
		}
		
		if (!empty($match)) {
			if (empty($conditions)) {
				$conditions = ' WHERE ';
			} else {
				$conditions .= ' AND ';
			}
			$conditions .= "MATCH('" . str_replace("'", "\\'", $match) . "')";
		}
		
		$conditions = str_replace(' id ', ' `id` ', $conditions);
		
		return $conditions;
	
	}
	
	/**
	 * Gets a list of record IDs for the given conditions.	Used for multi-record updates and deletes
	 * in databases that do not support aliases in UPDATE/DELETE queries.
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param 	Model	$model
	 * @param 	mixed 	$conditions
	 * @return 	array 	List of record IDs
	 */
	public function _matchRecords (&$model, $conditions=null) {
		if ($conditions === true) {
			$conditions = $this->conditions(true, true, true, $model);
		} elseif ($conditions === null) {
			$conditions = $this->conditions($this->defaultConditions($model, $conditions, false), true, true, $model);
		} else {
			$noJoin = true;
			foreach ($conditions as $field => $value) {
				$originalField = $field;
				if (strpos($field, '.') !== false) {
					list($alias, $field) = explode('.', $field);
					$field = ltrim($field, $this->startQuote);
					$field = rtrim($field, $this->endQuote);
				}
				if (!$model->hasField($field)) {
					$noJoin = false;
					break;
				}
				if ($field !== $originalField) {
					$conditions[$field] = $value;
					unset($conditions[$originalField]);
				}
			}
			if ($noJoin === true) {
				return $this->conditions($conditions, true, true, $model);
			}
			$idList = $model->find('all', array(
				'fields' => "{$model->alias}.{$model->primaryKey}",
				'conditions' => $conditions
			));

			if (empty($idList)) {
				return false;
			}
			$conditions = $this->conditions(array(
				$model->primaryKey => Set::extract($idList, "{n}.{$model->alias}.{$model->primaryKey}")
			), true, true, $model);
		}
		return $conditions;
	}
	
	/**
	 * Quotes and prepares fields and values for an SQL UPDATE statement
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	Model		$model
	 * @param	array		$fields
	 * @param	boolean		$quoteValues		If values should be quoted, or treated as SQL snippets
	 * @param	boolean		$alias				Include the model alias in the field name
	 * @return	array 		Fields and values, quoted and preparted
	 */
	public function _prepareUpdateFields (&$model, $fields=array(), $quoteValues=true, $alias=false) {
		
		$updates = parent::_prepareUpdateFields($model, $fields, $quoteValues, $alias);
		if (!empty($updates)) {
			foreach ($updates as $key => $update) {
				if (substr($update, 0, 4) == '`id`') {
					unset($updates[$key]);
				}
			}
		}
		
		return array_values($updates);
		
	}
	
	/**
	 * Builds and generates an SQL statement from an array.	 Handles final clean-up before conversion.
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	array		$query		An array defining an SQL query
	 * @param	object		$model		The model object which initiated the query
	 * @return	string		An executable SQL statement
	 * @see 	SphinxSource::renderStatement()
	 */
	public function buildStatement($query, &$model) {
		
		$query = array_merge(array('offset' => null, 'joins' => array()), $query);
		if (!empty($query['joins'])) {
			$count = count($query['joins']);
			for ($i = 0; $i < $count; $i++) {
				if (is_array($query['joins'][$i])) {
					$query['joins'][$i] = $this->buildJoinStatement($query['joins'][$i]);
				}
			}
		}
		
		if (in_array('@count `count`', $query['fields'])) {
			if (!in_array('@id `id`', $query['fields'])) {
				$query['fields'][] = '@id `id`';
			}
			$query['group'] = array($model->alias . '.id');
		}
		
		return $this->renderStatement('select', array(
			'conditions' => $this->conditions($query['conditions'], true, true, $model),
			'fields' => implode(', ', $query['fields']),
			'table' => $query['table'],
			'alias' => '',
			'order' => $this->order($query['order'], 'ASC', $model),
			'limit' => $this->limit($query['limit'], $query['offset']),
			'joins' => implode(' ', $query['joins']),
			'group' => $this->group($query['group'], $model)
		));
		
	}
	
	/**
	 * Builds final SQL statement
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	string		$type		Query type
	 * @param	array		$data		Query data
	 * @return	string
	 */
	public function renderStatement($type='', $data=array()) {
		
		$statement = parent::renderStatement($type, $data);
		
		$statement = preg_replace_callback('/`{1}?\\w+?`{1}?\\.{1}?`{1}?(?P<field>\\w+?)`{1}?/', create_function('$matches', 'return "`" . $matches["field"] . "`";'), $statement);
		
		$beginning = substr($statement, 0, 7);
		if ($beginning == 'UPDATE ' || $beginning == 'DELETE ') {
			$statement = str_replace('`id`', 'id', $statement);
		}
		
		return $statement;
		
	}
	
	/**
	 * Generates an array representing a query or part of a query from a single model or two associated models
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	Model		$model
	 * @param	Model		$linkModel
	 * @param	string		$type
	 * @param	string		$association
	 * @param	array		$assocData
	 * @param	array		$queryData
	 * @param	boolean		$external
	 * @param	array		$resultSet
	 * @return	mixed
	 */
	public function generateAssociationQuery(&$model, &$linkModel, $type='', $association=null, $assocData=array(), &$queryData, $external=false, &$resultSet) {
		
		$query = parent::generateAssociationQuery($model, $linkModel, $type, $association, $assocData, $queryData, $external, $resultSet);
		
		if (!empty($queryData['options'])) {
			$query .= ' OPTION ';
			$first = true;
			foreach ($queryData['options'] as $key => $value) {
				if (!$first) {
					$query .= ', ';
				}
				$query .= $key . '=' . $value;
				$first = false;
			}
		}
		
		return $query;
		
	}
	
	/**
	 * Passes association results thru afterFind filters of corresponding model
	 *
	 * @author	Anthony Putignano <contact@anthonyputignano.com>
	 * @since	0.1
	 * @param	array		$results		Reference of resultset to be filtered
	 * @param	object		$model			Instance of model to operate against
	 * @param	array		$filtered		List of classes already filtered, to be skipped
	 * @return	array 		Array of results that have been filtered through $model->afterFind
	 */
	public function __filterResults (&$results, &$model, $filtered=array()) {
		
		$filtering = array();
		
		if (!empty($results)) {
			if (!empty($results[0][0]['@count'])) {
				$count = 0;
				foreach ($results as $result) {
					$count = ($count + $result[0]['@count']);
				}
				$results = array(
					0 => array(
						0 => array(
							'count' => $count
						)
					)
				);
			}
			foreach ($results as $key => $data) {
				unset($results[$key][0]);
				$results[$key][$model->alias] = (!empty($data[$model->alias]) ? $data[$model->alias] : $data[0]);
			}
		}
		
		return $filtering;
		
	}

}
?>