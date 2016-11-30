<?php

// Require the Composer autoloader.
require 'vendor/autoload.php';
include 'config.php';
include 'db_layer.php';

use Aws\S3\S3Client;

/**
 * WPLocateBrokenImages class
 *
 * @package default
 * @author
 **/
class MoveS3Objects
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
		$sourceBucket = 'wpengine-bodyrock.tv';
		$sourceKeyname = 'newbodyrocktv/wp-content/uploads/2015/05/fat-burn-day-1.jpg';

		$targetBucket = 'wpengine-bodyrock.tv-new';
		$targetKeyname = 'newbodyrocktv/wp-content/uploads/2015/05/fat-burn-day-1.jpg';

		// Instantiate the client.
		$s3 = S3Client::factory(array(
		    'credentials' => array(
		       'key'    => AWS_S3_KEY,
		        'secret' => AWS_S3_SECRET,
		    ),
		    'region' => 'us-east-1',
		    'version' => 'latest'
		));


		while(1) {


			// Fetch all images that are broke. Change query as per requirement
			$img = $this->db->getResults('*',DB_TABLE, ' post_status != "200" and response is null ',' rand() ', 0, 1);
			if($img == false) {
				die('empty db object');
			}

			$img = $this->db->getObject($img);

			// guid column must not be absolute URL
			$targetKeyname = $sourceKeyname = 'newbodyrocktv/'.$img->guid;

			try {

				// Copy an image
				$result = $s3->copyObject(array(
				    'Bucket'     => $targetBucket,
				    'Key'        => $targetKeyname,
				    'CopySource' => "{$sourceBucket}/{$sourceKeyname}",
				));

				// set copy responce
				$this->db->query("update ".DB_TABLE." set response = '".addslashes($result)."' where ID='".$img->ID."'; ");
				echo 'x';
				// break;

			}
			catch (Exception $e) {
				// record exception or error
				$this->db->query("update ".DB_TABLE." set response = '".addslashes($e->getMessage())."' where ID='".$img->ID."'; ");
			    echo 'e';
			}

		}


	}

}


$mover = new MoveS3Objects();
$mover->run();

?>
