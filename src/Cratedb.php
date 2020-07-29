<?php

namespace Web64\CrateAdmin;


use Crate\PDO\PDO as PDO;

class Cratedb
{
    public $conn;

    function __construct( $dsn  = '')
    {
     	if ( !empty($dsn) )
			$this->connect( $dsn );   
    }
	
	// get formatted CrateDB timestamp
	function ts( $str )
	{
		return date('Y-m-d\TH:i:s', strtotime($str) );
	}
	
	function connect( $dsn)
	{
		$this->conn = new PDO($dsn, null, null, null);
	}

    function escape( $str )
    {
        // return $this->conn->quote( $str );
        return str_replace("'", "''", $str );
    }

    function query($sql, $fields = null)
    {
        if ( !empty( $fields )  && is_array($fields) )
        {
            $statement = $this->conn->prepare( $sql );
            $statement->execute( $fields );
        }else{
             //$statement = $this->conn->query( $sql );
			$statement = $this->conn->prepare( $sql );
			$statement->execute(  );
        }
        
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

	// if array contains object -> flatten fields
	private function _flatten_fields( $fields )
	{
		$new_fields = array();
		
		foreach( $fields as $field => $value)
		{
			if ( is_array($value) )
			{
				$object_str = '{';
				foreach($value as $n => $v)
				{
					if ( strlen($object_str) > 1 )
						$object_str .= ', ';
				
					$object_str .= "{$n}='". $this->escape($v) ."'"; 
				}
				$object_str .= '}';
				$new_fields[$field] = $object_str;
				/*
				foreach($value as $n => $v)
				{
					if ( !is_array($v) )
					{
						$key = "{$field}['{$n}']";
						$new_fields[$key] = $v;
					}else{
						// IGNORE arrays 2 levels deep
					}
				}
				*/
			}else{
				$new_fields[$field] = $value;
			}
		}
		
		return $new_fields;
	}

    function insert($table, $fields, $on_duplicate_update_sql = '')
    {
        $fields_str ='';
        $values_str = '';
        $count=0;
		$pdo_fields = [];
        foreach( $fields as $field => $value)
        {
        	if ( !is_array($value) ) // ignore ovjects - INSERT INTO test.test1 (page_id, username, location) VALUES (3, 'myname3', {country='Norway', city='Omastrand'}); 
			{
	            if ( $count > 0)
	            {
	                $fields_str .= ', ';
	                $values_str  .= ', ';
	            }
	
	            $fields_str .= $field;
	            $values_str  .= ':'.$field;
				$pdo_fields[$field] = $value;
	
	            $count++;				
			}      
        }
        $sql = "INSERT INTO ". $this->escape($table) . " ($fields_str) VALUES ($values_str) ";
		
		if ( !empty($on_duplicate_update_sql) )
			$sql .= " ON DUPLICATE KEY UPDATE " . $on_duplicate_update_sql;
		
        //echo $sql . PHP_EOL;
        
		try {
			$statement = $this->conn->prepare( $sql );
        	$statement->execute( $pdo_fields );
		} catch (PDOException $e) {
			echo 'Connection failed: ' . $e->getMessage();
			
		}
		
		$err = $statement->errorInfo();
		
		if ( !empty($err[1]))
			print_r($err);
		
		//$temp = $statement->fetch(PDO::FETCH_ASSOC);
		//print_r($temp);
		
		/*
        $row_count = $statement->rowCount();
		if ( $row_count > 0 )
			echo "rowCount(): $row_count" . PHP_EOL;
		*/
    }

	function update($table, $fields, $where_sql = '')
    {
    	if ( empty($where_sql) )
			die("CrateDB->update() - missing Where SQL!");
		
        $name_values_str ='';
        $count=0;
        foreach( $fields as $field => $value)
        {      
            if ( $count > 0)
            {
                $name_values_str .= ', ';
            }


			if ( is_array($value) )
			{
				// Save Nested Object: update locations set race['name'] = 'Human' where name = 'Bartledan';
				// https://crate.io/docs/reference/sql/dml.html
				$sub_val_count = 0;
				foreach($value as $n => $v)
				{
					if ( !is_array($v) )
					{
						if ( $sub_val_count > 0) $name_values_str .= ', ';
						
						$name_values_str .= "{$field}['{$n}']='". $this->escape($v) ."'";
						$sub_val_count++;
					}
				}
			}else
            	$name_values_str .= $field."='". $this->escape($value) ."'";

            $count++;
        }
        $sql = "UPDATE ". $this->escape($table) . " SET $name_values_str WHERE $where_sql";
        echo $sql . PHP_EOL;

        $statement = $this->conn->prepare( $sql );
        $statement->execute(  );
		
		//$temp = $statement->fetch(PDO::FETCH_ASSOC);
		//print_r($temp);
        $row_count = $statement->rowCount();
		if ( $row_count > 0 )
			echo "CRATE - updated($where_sql): $row_count" . PHP_EOL;
		else{
			print_r( $statement->errorInfo() );
		}
		//$lastId = $statement->lastInsertId();
		//echo "lastInsertId(): $lastId \n";
    }

    function delete_id( $table, $field, $id )
    {

    }

    function query_row($sql, $fields = false)
    {
        if ( !empty( $fields )  && is_array($fields) )
        {
            $statement = $this->conn->prepare( $sql );
            $statement->execute( $fields );
        }else{
             //$statement = $this->conn->query( $sql );
			$statement = $this->conn->prepare( $sql );
			$statement->execute(  );
        }
        
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    function query_value()
    {
		//$result = $sth->fetchColumn();
    }
	
	
	function join_pages($item_a, $merge = true)
	{
		if (empty($item_a) )
			return;

		$page_ids = $this->get_ids($item_a , 'page_id_str');
		
		//ci64_r($page_ids);
		
		
		$join_a = $this->query("SELECT cast(page_id as string) AS page_id_str, * FROM facebook.pages WHERE page_id IN (". implode(',', $page_ids) .")");
		
		if ( empty($join_a) )
			return $item_a;

		//ci64_r($join_a);
		return $this->array_join($item_a, 'page_id_str', $join_a, '', $merge );
	}
	
	function array_join($item_a, $id_field1, $join_a, $id_field2 = '', $merge = true)
	{
		if ( empty($join_a) )
			return $item_a;

		$ids 		= array();
		$lookup_a 	= array();
		
		// get ID and lookup array
		foreach($item_a as $item)
		{
			$ids[] = $item[$id_field1];
		}
	
		if ( empty($id_field2) )
			$id_field2 = $id_field1;

		// Create lookup from $join_a
		foreach($join_a as $row)
		{
			$id = $row[$id_field2];
			$lookup_a[ $id ] = $row;
		}
		
		foreach($item_a as &$item)
		{
			$id = $item[$id_field1];
			
			if ( isset($lookup_a[$id]) )
			{
				if ( $merge )
					$item = array_merge($lookup_a[$id], $item );
				else
					$item['page'] = $lookup_a[$id];
			}
		}
		
		return $item_a;
	}
	
	function get_ids($item_a, $id_field)
	{
		$ids = [];
		
		if ( empty($item_a) )  return $ids;
		
		// get ID and lookup array
		foreach($item_a as $item)
		{
			$ids[] = $item[$id_field];
		}
		
		return $ids;
		
	}

    function get_tables( $include_system_tables = false )
    {
        $ignore_tables = ($include_system_tables) ? [] : ['information_schema', 'pg_catalog', 'sys'];
        $tables = [];

        $schemas = $this->query("show schemas");

        foreach($schemas as $s)
        {
            if ( array_search($s['schema_name'], $ignore_tables) !== false )
                continue;

            $_tables = $this->query("SHOW TABLES IN {$s['schema_name']}");
            foreach($_tables as $t)
            {
                $tables[] = $s['schema_name'] . "." . $t['table_name'];
            }
                
        }

        return $tables;
    }

    function show_create_table( $tablename )
    {
        $res = $this->query("SHOW CREATE TABLE {$tablename}");
        return array_pop( $res[0] );
    }

    function get_table_fields( $tablename )
    {
        $row = $this->query("SELECT * FROM {$tablename} LIMIT 1");
        if ( empty($row) )
            die("ERROR! Cannot rebuild empty table\n");

        print_r( $row );

        $fields = [];
        foreach( $row[0] as $name =>$value )
            $fields[] = $name;

        return $fields;
	}
	
	function has_rows( $tablename )
	{
		$row = $this->query("SELECT * FROM {$tablename} LIMIT 1");
		if ( empty($row) ) {
			return false;
		}
		
		return true;
	}

	function copy($from_table, $to_table)
	{
		if (!$this->has_rows( $from_table ))
		{
			echo "Skipping {$from_table} - no data to copy\n";
			return;
		}

		$fields = $this->get_table_fields( $from_table );
		print_r($fields);
		
		$field_list = implode(", ", $fields);

		$sql_insert_data = "INSERT INTO {$to_table} ( {$field_list} )
			(SELECT {$field_list} FROM {$from_table} );";

		echo $sql_insert_data . PHP_EOL;
		
		$res = $this->query( $sql_insert_data );
		print_r( $res );
	}

	function swap_table($from_table, $to_table)
	{
		$sql = "ALTER CLUSTER SWAP TABLE $from_table TO $to_table;";
		echo $sql . PHP_EOL;
		$this->query( $sql );
	}

	function refresh_table($table)
	{
		echo " REFRESH TABLE {$table}\n";
		return $this->query( "REFRESH TABLE {$table}\n" );
	}

	function drop_table($table)
	{
		echo "DROP TABLE {$table}\n";
		return $this->query( "DROP TABLE {$table}" );
	}

	function create_temp_table($table, $tmp_table)
	{
		$sql_create_table = $this->show_create_table( $table );
		$rebuildtable_quotes = '"'. str_replace(".", '"."', $table ) . '"';
		$tmp_table_quotes = '"'. str_replace(".", '"."', $tmp_table) . '"';
		$sql_create_table = str_replace("CREATE TABLE IF NOT EXISTS {$rebuildtable_quotes}", "CREATE TABLE IF NOT EXISTS {$tmp_table_quotes}", $sql_create_table);
		echo $sql_create_table . PHP_EOL;;
	
		$res = $this->query($sql_create_table);
		//print_r( $res );
	}
}