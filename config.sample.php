<?php


define('DB_LOGGING', false);
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'database_name');

/**
 * Replica of wp_posts having post_type='attachment'
 * Additionaly has a response (`text` data type) column to store the response of the copyObjects
 */
define('DB_TABLE', 'wp_images');


define('AWS_S3_KEY', 'XXXXXXXXXXXXXXXXXX');
define('AWS_S3_SECRET', 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX');

?>
