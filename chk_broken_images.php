<?php

include 'config.php';
include 'db_layer.php';


set_time_limit(0);


/**
 * WPLocateBrokenImages class
 *
 * @package default
 * @author
 **/
class WPLocateBrokenImages
{


	/**
	 * constructor function
	 *
	 * @return void
	 * @author
	 **/
	function __construct()
	{

		$this->db = new Database(DB_HOST,DB_USERNAME,DB_PASSWORD,DB_NAME);
	}

	/**
	 * Check for broken images and update database function
	 *
	 * @return void
	 * @author
	 **/
	function run()
	{
		while (1)
		{

			/**
			 * Randomly pick images so we can run same file multiple times to initiate multipler threads.
			 */
			srand(time());
			$id = mt_rand(0, 100442) + mt_rand(1, 88442);
			$this->db->query("SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;");
			$img = $this->db->getResults('*',DB_TABLE, ' post_status = "inherit" ',' rand() ', 0, 1);
			$this->db->query("COMMIT;");

			$img = $this->db->getObject($img);

			// Mark image to be in process
			$this->db->query("update ".DB_TABLE." set post_status = 'in_process' where ID='".$img->ID."'; ");

			// Check image status
			$status = $this->get_url_status($img->guid);
			// var_dump($status);

			//
			/**
			 * Update post_status with the http status
			 * Run following SQL Query to view cron results by http-status
			 * SELECT post_status, COUNT(*) FROM DB_TABLE GROUP BY post_status
			 */
			$this->db->query("update ".DB_TABLE." set post_status = '".$status."' where ID='".$img->ID."'; ");
			echo '.';
		}

	}

	function get_url_status($url, $timeout = 10)
	{
		$ch = curl_init();
		// set cURL options
		$opts = array(CURLOPT_RETURNTRANSFER => true, // do not output to browser
		            CURLOPT_URL => $url,            // set URL
		            CURLOPT_NOBODY => true,         // do a HEAD request only
		            CURLOPT_TIMEOUT => $timeout);   // set timeout
		curl_setopt_array($ch, $opts);
		curl_exec($ch); // do it!
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE); // find HTTP status
		curl_close($ch); // close handle

		return $status;
	}

}



$checker = new WPLocateBrokenImages();
$checker->run();

?>
