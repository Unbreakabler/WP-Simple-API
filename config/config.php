<?php

/** MySQL database name **/

define('DB_NAME', 'kamloopsthisweek');

define('SITE_URL', 'http://www.kamloopsthisweek.com');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

/** MySQL hostname */
define('DB_HOST', 'localhost');

define('TABLE_PREFIX', 'ez_');

//define the views meta_key in config, I doubt tie_views is standard
define('VIEW_METAKEY', 'tie_views');

define('FEATURED_TERM_ID', '2');
define('DEFAULT_TERM_ID', '5');


define('DEFAULT_POST_IMAGE', 'http://www.kamloopsthisweek.com/wp-content/uploads/2014/09/NEWS-HEADER-2-300x150.jpg');
define('DEFAULT_POST_IMAGE70', 'http://www.kamloopsthisweek.com/wp-content/uploads/2014/09/NEWS-HEADER-2-70x70.jpg');
define('DEFAULT_POST_IMAGE150', 'http://www.kamloopsthisweek.com/wp-content/uploads/2014/09/NEWS-HEADER-2-150x150.jpg');

ini_set('xdebug.var_display_max_depth', 5);
ini_set('xdebug.var_display_max_children', 256);
ini_set('xdebug.var_display_max_data', 1024);

?>
