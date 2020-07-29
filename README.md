# CrateDB Admin Console

A set of simple utilities useful for admin work on CrateDB tables.

This is a work in progress so test carefully before using in production!

## Config

config.php

```php
define('CRATE_DSN', 'crate:crate1.web64.io:4200');
```

## Setup

```php
<?php

require "config.php";
require "vendor/autoload.php";

$crate = new Web64\CrateAdmin\Cratedb( CRATE_DSN );
```

## Usage

Run any query

```php
$resul = $crate->query($sql, $fields = null);
```

Get Crate timestamp from php date string

```php
$crate->ts( $str );
```

Get array of ids from array of rows

```php
$ids = $create->get_ids($item_a, $id_field);
```

Get array of tables in database

```php
$crate->get_tables( $include_system_tables = false )
```

SHOW CREATE TABLE

```php
$sql = $crate->show_create_table('tableName');
```

Get array of field names in table (requires at least 1 record)

```php
$crate->get_table_fields('tableName');
```

Checks if table has data/rows:

```php
if ($crate->has_rows('tableName')){
    //...
}
```

Copy all data

```php
$crate->copy($from_table, $to_table);
```

Swap Table (`ALTER CLUSTER SWAP TABLE $from_table TO $to_table`)

```php
$crate->swap_table($from_table, $to_table)
```

Refresh Table

```php
$crate->refresh_table($table)
```

Drop Table

```php
$crate->drop_table($table)
```

Create a temporary table

```php
$crate->create_temp_table('old_table', 'temp_table');
```
