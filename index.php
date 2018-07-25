<?php
// 64.2 Million

require "config.php";
require "vendor/autoload.php";

$crate = new Web64\CrateAdmin\Cratedb( CRATE_DSN );


$tables = $crate->get_tables( false );
print_r( $tables );

$rebuildtable = trim(readline("Rebuild table: "));
$rebuildtable_suffix = substr($rebuildtable, strpos($rebuildtable, ".") +1 );

echo "rebuildtable_suffix: {$rebuildtable_suffix} \n";
$tmp_table = $rebuildtable. "_new";

if ( empty($rebuildtable) )
    die("No table selected\n");

echo "Rebuilding table: '{$rebuildtable}' \n";

// Create temp table
$sql_create_table = $crate->show_create_table( $rebuildtable );

$rebuildtable_quotes = '"'. str_replace(".", '"."', $rebuildtable ) . '"';
$tmp_table_quotes = '"'. str_replace(".", '"."', $tmp_table) . '"';
$sql_create_table = str_replace("CREATE TABLE IF NOT EXISTS {$rebuildtable_quotes}", "CREATE TABLE IF NOT EXISTS {$tmp_table_quotes}", $sql_create_table);
echo "\nSQL:\n--------------------------------\n{$sql_create_table}\n";
$res = $crate->query($sql_create_table);
print_r( $res );


// Copy data
$fields = $crate->get_table_fields( $rebuildtable );
//print_r( $fields );
$field_list = implode(", ", $fields);

$sql_insert_data = "INSERT INTO {$tmp_table} ( {$field_list} )
    (SELECT {$field_list} FROM {$rebuildtable} );";

echo $sql_insert_data . PHP_EOL;
$res = $crate->query( $sql_insert_data );
print_r( $res );

echo " REFRESH TABLE {$tmp_table}\n";
$res = $crate->query( "REFRESH TABLE {$tmp_table}\n" );

echo "DROP TABLE {$rebuildtable}\n";
$res = $crate->query( "DROP TABLE {$rebuildtable}" );


echo "ALTER TABLE {$tmp_table} RENAME TO {$rebuildtable_suffix} \n";
$res = $crate->query( "ALTER TABLE {$tmp_table} RENAME TO {$rebuildtable_suffix}" ); 

echo "Complete!\n";
echo chr(7);


