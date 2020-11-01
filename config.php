<?php


//Database connection information
//Using Mysqli
define('Database_host', 'localhost');
define('Database_user', 'root');
define('Database_pass', '');
define('Database_name', 'atn');


//converter input data
define('Table_Name', 'users'); // table name you want to update
define('Table_child_Field', 'user_id');
define('Table_parent_Field', 'parent_id');
define('Table_LFT_Field', 'lft');
define('Table_RGT_Field', 'rgt');
define('Table_Depth_Field', 'depth'); //Optional, leave it empty if do not want depth calculations
define('step_limit', 100); //number of rows that will be proccessed in each loading
define('Table_Top_child_id', 10); // top level child Id


//Others
define('Temporary_table_prefix' , 'tmp_');
define('AutoRefresh' , 1000); //in sec.  0=disable refresh page untill the end of proccess


?>