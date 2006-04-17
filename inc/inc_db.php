<?php

if (defined('_INC_DB')) return;
define('_INC_DB', '1');

// if -- configuration -- mysql or postgresql
// ....

if (isset($GLOBALS['lcm_db_type']) && $GLOBALS['lcm_db_type'])
	include_lcm('inc_db_' . $GLOBALS['lcm_db_type']);
else
	include_lcm('inc_db_mysql');

#  include_lcm('inc_db_pgsql');

?>
