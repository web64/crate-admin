<?php
/**
 * 
 *      Rebuild CrateDB Tables
 */
require "config.php";
require "vendor/autoload.php";

$crate = new Web64\CrateAdmin\Cratedb( CRATE_DSN );

$tables = $crate->get_tables( false );
print_r( $tables );

$skip_tables = ['acast.channels', 'facebook.comments', 'facebook.events', 'facebook.events_new'];

foreach($tables as $table)
{
    $tmp_table = $table. "_new";

    if ( array_search($table, $skip_tables) !== false )
    {
        echo "Skip table {$table}\n";
        continue;
    }
    
    $crate->create_temp_table($table, $tmp_table);

    $crate->copy($table, $tmp_table);
    echo "sleep..\n";
    sleep(3);

    $crate->refresh_table($tmp_table);
    echo "sleep..\n";
    sleep(3);

    $crate->swap_table($tmp_table, $table);
    echo "sleep..\n";
    sleep(3);

    if ( $crate->has_rows($table))
    {
        echo "Recreated table has rows - delete temp..\n";
        $crate->drop_table($tmp_table);
    }else{
        echo "Skipped deleting temp table - no rows in new table\n";
    }

    echo "\n-------- COMPLETED {$table} ------------------\n";
    
}