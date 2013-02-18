<?php
/**
 * Queryable Behavior class file.
 *
 * A collection of useful queries that relies a SQL database to execute
 * Largely a wrapper for the CakePHP dbo object
 *
 *
 * @filesource
 * @author	KimSia Sim
 * @copyright	KimSia Sim
 * @license	http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link	https://github.com/simkimsia/Queryable
 */
class QueryableBehavior extends ModelBehavior {

/**
 * Behavior settings
 *
 * @access public
 * @var array
 */
	public $settings = array();

/**
 * The dbo object CakePHP uses to access database
 *
 * @access public
 * @var object
 */
	public $dbo = null;

/**
 * Configuration method.
 *
 * @param object $Model Model object
 * @param array $config Config array
 * @access public
 * @return boolean
 */
	public function setup(Model $Model, $config = array()) {
		$this->dbo = $Model->getDatasource();
		return true;
	}

/**
 *
 * get the exact last query made to the database
 *
 * @param model class $model
 * @return string The last query in string format
 */
	public function getLastQuery(Model $model) {
		$logs = $this->dbo->getLog();
		$lastLog = end($logs['log']);
		return $lastLog['query'];
	}

/**
 *
 * get an array of queries made to the database
 *
 * @param model class $model
 * @return array. The array of queries
 */
	public function getAllQueries(Model $model) {
		return $this->dbo->getLog();
	}

/**
 *
 * execute a SQL query in the form SELECT $formula as Result
 *
 * @param model class $model
 * @param string $formula A string that is a formula like 2+3
 * @return mixed. The array of queries
 */
	public function calculateViaSQL(Model $model, $formula) {
		$formula = "Select " . $formula . " as Result;";
		$result = $this->dbo->fetchRow($formula);
		if (empty($result)){
			return false;
		} else {
			return (string) $result[0]['Result'];
		}
	}

/**
 *
 * construct FULLTEXT search conditions
 *
 * @param model class $model
 * @param array $fields to run the FULLTEXT search against
 * @param string $value Value of the search
 * @return string The key-value pair inside the $conditions of any find operations. Actually just a string. No key.
 */
	public function constructFullTextSearchCondition(Model $model, $fields, $value) {
		// to learn more about array_map, look at http://php.net/manual/en/function.array-map.php
		// to learn more about lambda function and callbacks, look at http://php.net/manual/en/functions.anonymous.php

		// $fields is an array like array('Model.field1', 'field2');
		// we want to get back an array like array('`Model`.`field1`', '`field2`')
		$fields = array_map(function($field) {
			$fieldArray = explode($field, ".");
			$escaped = array_map(function($subField) {
				return "`" . $subField . "`";
			}, $fieldArray);
			return implode(".", $escaped);
		}, $fields);

		// now we get a string like MATCH (`Model.field1`,`field2`) AGAINST (
		$fieldString = "MATCH (" . implode(",", $fields) . ") AGAINST (";

		$escapedQuery = $this->escapeUserInput($value);

		// now we get a string like MATCH (`Model.field1`,`field2`) AGAINST ($escapedQuery)
		$fieldString .= $fieldString . $escapedQuery . ")";

		return $fieldString;
	}

/**
 * Escape any user input before putting into queries
 *
 * @param model class $model 
 * @param string $value the user input to escape
 * @return string Escaped user input
 */
	public function escapeUserInput(Model $model, $value) {
		return $this->dbo->value($value);
	}
}