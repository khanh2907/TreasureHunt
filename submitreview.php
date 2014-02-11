<?php 
require_once('include/common.php');
require_once('include/database.php');
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if ($_REQUEST['rating'] == "" OR $_REQUEST['description'] == ""){
	echo "<center><img src=\"img/pika.gif\"></center>";

	echo "<script> alert(\"You must give a rating and write a review.\");
				history.back();
	</script>";
}
else{

	$result = submitReview($_REQUEST['player'] , $_REQUEST['huntid'] , $_REQUEST['description'],$_REQUEST['rating']);
	echo "<center><img src=\"img/pika.gif\"></center>";

	echo $result['submit_review'][0];

	if ($result['submit_review'] == ""){
		echo "<script> alert(\"Something went wrong. Try again later.\");
				history.back();
		</script>";
	}
	else{
	echo "<script> alert(\"Your review has been successfully submitted. Thank you.\");
				history.back();
	</script>";
	}
}	

	

?>



