<?php

require_once RUCKUSING_BASE . '/lib/classes/class.Ruckusing_BaseAdapter.php';
require_once RUCKUSING_BASE . '/lib/classes/class.Ruckusing_iAdapter.php';
require_once RUCKUSING_BASE . '/lib/classes/adapters/class.Ruckusing_MySQLTableDefinition.php';
require_once RUCKUSING_BASE . '/lib/classes/util/class.Ruckusing_NamingUtil.php';	

define('SQL_UNKNOWN_QUERY_TYPE', 1);
define('SQL_SELECT', 2);
define('SQL_INSERT', 4);
define('SQL_UPDATE', 8);
define('SQL_DELETE', 16);
define('SQL_ALTER', 32);
define('SQL_DROP', 64);
define('SQL_CREATE', 128);
define('SQL_SHOW', 256);
define('SQL_RENAME', 512);
define('SQL_SET', 1024);


class Ruckusing_MySQLAdapter extends Ruckusing_BaseAdapter implements Ruckusing_iAdapter {

	private $name = "MySQL";
	private $tables = [];
	private $tables_loaded = false;
	private $version = '1.0';
	private $in_trx = false;

	function __construct($dsn, $logger) {
		parent::__construct($dsn);
		$this->connect($dsn);
		$this->set_logger($logger);
	}
	
	public function supports_migrations() {
	 return true;
  }
	
	public function native_database_types() {
		$types = [
      'primary_key' => "int(11) UNSIGNED auto_increment PRIMARY KEY",
      'string'      => ['name' => "varchar", 	'limit' 		=> 255],
      'text'        => ['name' => "text",],
      'integer'     => ['name' => "int", 			'limit' 		=> 11],
      'float'       => ['name' => "float",],
      'decimal'     => ['name' => "decimal",],
      'datetime'    => ['name' => "datetime",],
      'timestamp'   => ['name' => "datetime",],
      'time'        => ['name' => "time",],
      'date'        => ['name' => "date",],
      'binary'      => ['name' => "blob",],
      'boolean'     => ['name' => "tinyint", 	'limit' 		=> 1]
        ];
		return $types;
	}
	
	//-----------------------------------
	// PUBLIC METHODS
	//-----------------------------------
	
	//transaction methods
	public function start_transaction() {
		try {
			if($this->inTransaction() === false) {
				$this->beginTransaction();
			}
		}catch(Exception $e) {
			trigger_error($e->getMessage());
		}
	}
	public function commit_transaction() {
		try {
			if($this->inTransaction()) {
				$this->commit();
			}
		}catch(Exception $e) {
			trigger_error($e->getMessage());
		}
	}
	public function rollback_transaction() {
		try {
			if($this->inTransaction()) {
				$this->rollback();
			}
		}catch(Exception $e) {
			trigger_error($e->getMessage());
		}
	}
	

	
	public function column_definition($column_name, $type, $options = null) {
		$col = new Ruckusing_ColumnDefinition($this, $column_name, $type, $options);
		return $col->__toString();
	}//column_definition

	//-------- DATABASE LEVEL OPERATIONS
	public function database_exists($db) {
		$ddl = "SHOW DATABASES";
		$result = $this->select_all($ddl);
		if(count($result) == 0) {
		  return false;
	  }
	  foreach($result as $dbrow) {
	    if($dbrow['Database'] == $db) {
	      return true;
      }
    }
    return false;
	}
	public function create_database($db) {
		if($this->database_exists($db)) {
			return false;
		}
		$ddl = sprintf("CREATE DATABASE `%s`", $db);
		$result = $this->query($ddl);
		if($result === true) {
			return true;
		} else {
			return false;
		}		
	}
	
	public function drop_database($db) {
		if(!$this->database_exists($db)) {
			return false;
		}
		$ddl = sprintf("DROP DATABASE IF EXISTS `%s`", $db);
		$result = $this->query($ddl);
		if( $result === true) {
			return true;
		} else {
			return false;
		}		
	}

	/*
		Dump the complete schema of the DB. This is really just all of the 
		CREATE TABLE statements for all of the tables in the DB.
		
		NOTE: this does NOT include any INSERT statements or the actual data
		(that is, this method is NOT a replacement for mysqldump)
	*/
	public function schema() {
		$final = "";
		$this->load_tables(true);
		foreach($this->tables as $tbl => $idx) {

			if($tbl == RUCKUSING_SCHEMA_TBL_NAME) { continue; }

			$stmt = "SHOW CREATE TABLE `$tbl`";
			$result = $this->query($stmt);

      if(is_array($result) && count($result) == 1) {
        $row = $result[0];
        if(count($row) == 2) {
          $final .= $row['Create Table'] . ";\n\n";
        }
      }
		}
		return $final;
	}
	
	public function table_exists($tbl, $reload_tables = false) {
		$this->load_tables($reload_tables);
		return array_key_exists($tbl, $this->tables);
	}
		
	public function show_fields_from($tbl) {
		return "";
	}

	public function execute($query) {
		return $this->query($query);
	}

	public function query($query) {
		$this->logger->log($query);
		$query_type = $this->determine_query_type($query);
		$data = [];
		if($query_type == SQL_SELECT || $query_type == SQL_SHOW) {		  
			$res = mysql_query($query, $this->conn);
			if($this->isError($res)) { 
  			trigger_error(sprintf("Error executing 'query' with:\n%s\n\nReason: %s\n\n", $query, mysql_error($this->conn)));
		  }
		  while($row = mysql_fetch_assoc($res)) {
		    $data[] = $row; 
	    }
			return $data;
			
		} else {
		  // INSERT, DELETE, etc...
			$res = mysql_query($query, $this->conn);
			if($this->isError($res)) { 
  			trigger_error(sprintf("Error executing 'query' with:\n%s\n\nReason: %s\n\n", $query, mysql_error($this->conn)));
		  }
		  return true;
		}
	}
	
	public function select_one($query) {
		$this->logger->log($query);
		$query_type = $this->determine_query_type($query);
		if($query_type == SQL_SELECT || $query_type == SQL_SHOW) {
		  $res = mysql_query($query, $this->conn);
			if($this->isError($res)) { 
  			trigger_error(sprintf("Error executing 'query' with:\n%s\n\nReason: %s\n\n", $query, mysql_error($this->conn)));
		  }
		  return mysql_fetch_assoc($res);			
		}
		if($this->isError($result)) {
			trigger_error(sprintf("Error executing 'query' with:\n%s\n\nReason: %s\n\n", $query, mysql_error($this->conn)));
		}
		return $result;		
	}

	public function select_all($query) {
	  return $this->query($query);
	}
	

	/*
		Use this method for non-SELECT queries
		Or anything where you dont necessarily expect a result string, e.g. DROPs, CREATEs, etc.
	*/
	public function execute_ddl($ddl) {
		$result = $this->query($ddl);
		return true;

	}
	
	public function drop_table($tbl) {
		$ddl = "DROP TABLE `$tbl`";
		$result = $this->query($ddl);
		return true;
	}
	
	public function create_table($table_name, $options = []) {
		return new Ruckusing_MySQLTableDefinition($this, $table_name, $options);
	}
	
	public function quote_string($str) {
	 return mysql_real_escape_string($str); 
  }
	
	public function quote($value, $column) {
	  return $this->quote_string($value);
	}
	
	public function qualify_entity($str) {
	  return '`' . $str . '`';
  }
	
	public function rename_table($name, $new_name) {
		if(empty($name)) {
			throw new Ruckusing_ArgumentException("Missing original column name parameter");
		}
		if(empty($new_name)) {
			throw new Ruckusing_ArgumentException("Missing new column name parameter");
		}
		$sql = sprintf("RENAME TABLE %s TO %s", $name, $new_name);
		return $this->execute_ddl($sql);
	}//create_table
	
	public function add_column($table_name, $column_name, $type, $options = []) {
		if(empty($table_name)) {
			throw new Ruckusing_ArgumentException("Missing table name parameter");
		}
		if(empty($column_name)) {
			throw new Ruckusing_ArgumentException("Missing column name parameter");
		}
		if(empty($type)) {
			throw new Ruckusing_ArgumentException("Missing type parameter");
		}
		//default types
		if(!array_key_exists('limit', $options)) {
			$options['limit'] = null;
		}
		if(!array_key_exists('precision', $options)) {
			$options['precision'] = null;
		}
		if(!array_key_exists('scale', $options)) {
			$options['scale'] = null;
		}
		$sql = sprintf("ALTER TABLE %s ADD `%s` %s", $table_name, $column_name, $this->type_to_sql($type,$options['limit'], $options['precision'], $options['scale']));
		$sql .= $this->add_column_options($options);
		return $this->execute_ddl($sql);
	}//add_column
	
	public function remove_column($table_name, $column_name) {
		$sql = sprintf("ALTER TABLE `%s` DROP COLUMN `%s`", $table_name, $column_name);
		return $this->execute_ddl($sql);
	}//remove_column
	
	public function rename_column($table_name, $column_name, $new_column_name) {
		if(empty($table_name)) {
			throw new Ruckusing_ArgumentException("Missing table name parameter");
		}
		if(empty($column_name)) {
			throw new Ruckusing_ArgumentException("Missing original column name parameter");
		}
		if(empty($new_column_name)) {
			throw new Ruckusing_ArgumentException("Missing new column name parameter");
		}
		$column_info = $this->column_info($table_name, $column_name);
		$current_type = $column_info['type'];
		$sql =  sprintf("ALTER TABLE `%s` CHANGE `%s` `%s` %s", $table_name, $column_name, $new_column_name, $current_type);
		return $this->execute_ddl($sql);
	}//rename_column


	public function change_column($table_name, $column_name, $type, $options = []) {
		if(empty($table_name)) {
			throw new Ruckusing_ArgumentException("Missing table name parameter");
		}
		if(empty($column_name)) {
			throw new Ruckusing_ArgumentException("Missing original column name parameter");
		}
		if(empty($type)) {
			throw new Ruckusing_ArgumentException("Missing type parameter");
		}
		$column_info = $this->column_info($table_name, $column_name);
		//default types
		if(!array_key_exists('limit', $options)) {
			$options['limit'] = null;
		}
		if(!array_key_exists('precision', $options)) {
			$options['precision'] = null;
		}
		if(!array_key_exists('scale', $options)) {
			$options['scale'] = null;
		}
		$sql = sprintf("ALTER TABLE `%s` CHANGE `%s` `%s` %s", $table_name, $column_name, $column_name,  $this->type_to_sql($type,$options['limit'], $options['precision'], $options['scale']));
		$sql .= $this->add_column_options($options);
		return $this->execute_ddl($sql);
	}//change_column

	public function column_info($table, $column) {
		if(empty($table)) {
			throw new Ruckusing_ArgumentException("Missing table name parameter");
		}
		if(empty($column)) {
			throw new Ruckusing_ArgumentException("Missing original column name parameter");
		}
		try {
			$sql = sprintf("SHOW COLUMNS FROM `%s` LIKE '%s'", $table, $column);
			$result = $this->select_one($sql);
			if(is_array($result)) {
			  //lowercase key names
			  $result = array_change_key_case($result, CASE_LOWER);			
		  }
			return $result;
		}catch(Exception $e) {
			return null;
		}
	}//column_info
	
	public function add_index($table_name, $column_name, $options = []) {
		if(empty($table_name)) {
			throw new Ruckusing_ArgumentException("Missing table name parameter");
		}
		if(empty($column_name)) {
			throw new Ruckusing_ArgumentException("Missing column name parameter");
		}
		//unique index?
		if(is_array($options) && array_key_exists('unique', $options)) {
			$unique = true;
		} else {
			$unique = false;
		}
		//did the user specify an index name?
		if(is_array($options) && array_key_exists('name', $options)) {
			$index_name = $options['name'];
		} else {
			$index_name = Ruckusing_NamingUtil::index_name($table_name, $column_name);
		}
		if(!is_array($column_name)) {
			$column_name = [$column_name];
		}
		$sql = sprintf("CREATE %sINDEX %s ON `%s`(%s)",
											$unique ? "UNIQUE " : "",
											$index_name, 
											$table_name,
											join(", ", $column_name));
		return $this->execute_ddl($sql);		
	}//add_index
	
	public function remove_index($table_name, $column_name, $options = []) {
		if(empty($table_name)) {
			throw new Ruckusing_ArgumentException("Missing table name parameter");
		}
		if(empty($column_name)) {
			throw new Ruckusing_ArgumentException("Missing column name parameter");
		}
		//did the user specify an index name?
		if(is_array($options) && array_key_exists('name', $options)) {
			$index_name = $options['name'];
		} else {
			$index_name = Ruckusing_NamingUtil::index_name($table_name, $column_name);
		}
		$sql = sprintf("DROP INDEX `%s` ON `%s`", $index_name, $table_name);		
		return $this->execute_ddl($sql);
	}

	public function has_index($table_name, $column_name, $options = []) {
		if(empty($table_name)) {
			throw new Ruckusing_ArgumentException("Missing table name parameter");
		}
		if(empty($column_name)) {
			throw new Ruckusing_ArgumentException("Missing column name parameter");
		}
		//did the user specify an index name?
		if(is_array($options) && array_key_exists('name', $options)) {
			$index_name = $options['name'];
		} else {
			$index_name = Ruckusing_NamingUtil::index_name($table_name, $column_name);
		}
		$indexes = $this->indexes($table_name);
		foreach($indexes as $idx) {
			if($idx['name'] == $index_name) {
				return true;
			}
		}
		return false;
	}//has_index
	
	public function indexes($table_name) {
		$sql = sprintf("SHOW KEYS FROM `%s`", $table_name);
		$result = $this->select_all($sql);
		$indexes = [];
		$cur_idx = null;
		foreach($result as $row) {
		  //skip primary
		  if($row['Key_name'] == 'PRIMARY') { continue; }
			$cur_idx = $row['Key_name'];
			$indexes[] = ['name' => $row['Key_name'], 'unique' => (int)$row['Non_unique'] == 0 ? true : false];
		}
		return $indexes;
	}//has_index

	public function type_to_sql($type, $limit = null, $precision = null, $scale = null) {		
		$natives = $this->native_database_types();
		
		if(!array_key_exists($type, $natives)) {
		  $error = sprintf("Error:I dont know what column type of '%s' maps to for MySQL.", $type);
		  $error .= "\nYou provided: {$type}\n";
		  $error .= "Valid types are: \n";
		  $types = array_keys($natives);
		  foreach($types as $t) {
		    if($t == 'primary_key') { continue; }
		    $error .= "\t{$t}\n";
	    }
			throw new Ruckusing_ArgumentException($error);
	  }
		
		$native_type = $natives[$type];
		if( is_array($native_type) && array_key_exists('name', $native_type)) {
			$column_type_sql = $native_type['name'];
		} else {
			return $native_type;
		}
		if($type == "decimal") {
			//ignore limit, use precison and scale
			if($precision == null || array_key_exists('precision', $native_type)) {
				$precision = $native_type['precision'];
			}
			if($scale == null || array_key_exists('scale', $native_type)) {
				$scale = $native_type['scale'];
			}
			if($precision) {
				if($scale) {
					$column_type_sql .= sprintf("(%d, %d)", $precision, $scale);
				} else {
					$column_type_sql .= sprintf("(%d)", $precision);						
				}//scale
			} else {
				if($scale) {
					throw new Ruckusing_ArgumentException("Error adding decimal column: precision cannot be empty if scale is specified");
				}
			}//precision			
		} else {
			//not a decimal column
			if($limit == null && array_key_exists('limit', $native_type)) {
				$limit = $native_type['limit'];
			}
			if($limit) {
				$column_type_sql .= sprintf("(%d)", $limit);
			}		
		}
		return $column_type_sql;
	}//type_to_sql
	
	public function add_column_options($options) {
		$sql = "";
		if(is_array($options) && array_key_exists('default', $options) && $options['default'] !== null) {
			if($this->is_sql_method_call($options['default'])) {
				//$default_value = $options['default'];
				throw new \Exception("MySQL does not support function calls as default values, constants only.");
			} else {
        $default_format = is_bool($options['default']) ? "'%d'" : "'%s'";
        $default_value = sprintf($default_format, $options['default']);			
      }
			$sql .= sprintf(" DEFAULT %s", $default_value);
		}
		if(is_array($options) && array_key_exists('unsigned', $options) && $options['unsigned'] === true) {
			$sql .= " unsigned";
		}
		if(is_array($options) && array_key_exists('increment', $options) && $options['increment'] === true) {
			$sql .= " AUTO_INCREMENT";
		}		
		if(is_array($options) && array_key_exists('null', $options) && $options['null'] === false) {
			$sql .= " NOT NULL";
		}
		return $sql;
	}//add_column_options
	
	public function set_current_version($version) {
		$sql = sprintf("UPDATE %s SET version = %d", $this->qualify_entity(RUCKUSING_SCHEMA_TBL_NAME), $version);
		return $this->execute_ddl($sql);
	}
	
	public function __toString() {
		return "Ruckusing_MySQLAdapter, version " . $this->version;
	}

	
	//-----------------------------------
	// PRIVATE METHODS
	//-----------------------------------	
	private function connect($dsn) {
		$this->db_connect($dsn);
	}
	
  private function db_connect($dsn) {
    $db_info = $this->get_dsn();
    if($db_info) {
      $this->db_info = $db_info;
      //we might have a port
      if(!empty($db_info['port'])) {
        $host = $db_info['host'] . ':' . $db_info['port'];
      } else {
        $host = $db_info['host'];
      }
      $this->conn = mysql_connect($host, $db_info['user'], $db_info['password']);
      if(!$this->conn) {
        die("\n\nCould not connect to the DB, check host / user / password\n\n");
      }
      if(!mysql_select_db($db_info['database'], $this->conn)) {
        die("\n\nCould not select the DB, check permissions on host\n\n");
      }
      return true;
    } else {
      die("\n\nCould not extract DB connection information from: {$dsn}\n\n");
    }
  }
	

	
	//Delegate to PEAR
	private function isError($o) {
		return $o === FALSE;
	}
	
	// Initialize an array of table names
	private function load_tables($reload = true) {
		if($this->tables_loaded == false || $reload) {
			$this->tables = []; //clear existing structure
			$qry = "SHOW TABLES";
			$res = mysql_query($qry, $this->conn);
			while($row = mysql_fetch_row($res)) {
			  $table = $row[0];
			  $this->tables[$table] = true;
		  }
		}	  
  }

	private function determine_query_type($query) {
		$query = strtolower(trim($query));
		
		if(preg_match('/^select/', $query)) {
			return SQL_SELECT;
		}
		if(preg_match('/^update/', $query)) {
			return SQL_UPDATE;
		}
		if(preg_match('/^delete/', $query)) {
			return SQL_DELETE;
		}
		if(preg_match('/^insert/', $query)) {
			return SQL_INSERT;
		}
		if(preg_match('/^alter/', $query)) {
			return SQL_ALTER;
		}
		if(preg_match('/^drop/', $query)) {
			return SQL_DROP;
		}
		if(preg_match('/^create/', $query)) {
			return SQL_CREATE;
		}
		if(preg_match('/^show/', $query)) {
			return SQL_SHOW;
		}
		if(preg_match('/^rename/', $query)) {
			return SQL_RENAME;
		}
		if(preg_match('/^set/', $query)) {
			return SQL_SET;
		}
		// else
		return SQL_UNKNOWN_QUERY_TYPE;
	}
	
	private function is_select($query_type) {
		if($query_type == SQL_SELECT) {
			return true;
		}
		return false;
	}
	
	/*
		Detect whether or not the string represents a function call and if so
		do not wrap it in single-quotes, otherwise do wrap in single quotes.
	*/
	private function is_sql_method_call($str) {
		$str = trim($str);
		if(substr($str, -2, 2) == "()") {
			return true;			
		} else {
			return false;
		}
	}
	
	private function inTransaction() {
	  return $this->in_trx;
  }
  
  private function beginTransaction() {
    mysql_query("BEGIN", $this->conn);
    $this->in_trx = true;
  }
  
  private function commit() {
    if($this->in_trx === true) {
     mysql_query("COMMIT", $this->conn);
     $this->in_trx = false; 
    }
  }
  
  private function rollback() {
    if($this->in_trx === true) {
     mysql_query("ROLLBACK", $this->conn);
     $this->in_trx = false; 
    }    
  }
	
	
}//class

?>