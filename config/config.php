<?php

/** MySQL database name **/
define('DB_NAME', 'kamloopsthisweek');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Post count to be included in category list **/
define('CATEGORY_POST_COUNT', 500);

define('TABLE_PREFIX', 'ez_');

//TODO: Define 5000000000 constants for all of dumb as fuck term and taxonomy relationships.

//News, Sports, Business, Community, Entertainment, Opinion
$categories = [
    '5', //News
    '45', //Sports
    '125', //Business
    '78', //Community
    '79', //Entertainment
    '83'
]

?>
