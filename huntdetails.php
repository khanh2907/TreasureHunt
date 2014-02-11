<?php 
/**
 * Web page to display information about a specific current hunt
 */
require_once('include/common.php');
require_once('include/database.php');
startValidSession();

htmlHead();
?>
<h1>Hunt Details</h1>
<?php 
try {
    $hunt = getHuntDetails($_GET['hunt']);
    $teams = getParticipation($_GET['hunt']);
    

    echo '<h2>Name</h2> ',$hunt['title'];
    echo '<h2>Description</h2> ',$hunt['description'];
    echo '<h2>Start Time</h2> ',$hunt['starttime'];
    echo '<h2>Distance</h2> ',$hunt['distance'];
    echo '<h2>Teams ','(', $hunt['nhunt'], ')</h2>';
    foreach($teams as $team) {
     	echo '<li>',$team['team'],'</li>';
    }
    echo '<h2>Waypoints</h2> ',$hunt['numwaypoints'];

    // TODO: Maybe show a map of the bounding box of all the waypoints
    // GOOGLE MAPS API HERE
    $gps = getMapDetails($_GET['hunt']);
    jsMap($gps['min'],$gps['max'], NULL, NULL);
    echo '<h2>Location</h2> ', 'This shows the area of the hunt.';
    echo '<div id="googleMap" style="min-width:500px;width:500px;height:380px;margin-left: auto;
    margin-right: auto;"></div>';

    //RATINGS AND REVIEWS
    $reviews = getReviews($_GET['hunt']);
    //print_r($reviews);
    echo '<h2> Ratings and Reviews </h2>';
    if (empty($reviews)){
    	echo "There currently no reviews.";
    }
    else{
      foreach($reviews as $review) {
        echo "<div id=\"modal\">
          <header><h1>";

          for ($i = 0; $i < $review['rating']; $i++){
            echo "★";
          }
    
          echo" by ",$review['player']," on ",$review['whendone'],"</h1></header>
            <section>",
            $review['description']
            ,"</section>
          </div>";
    }
    }

    
      
    
    //TODO: submit a review and rating
    //REVIEW MODAL

    echo '<div id="ex1" style="display:none;">
    <h3>Submit a rating and review</h3>
    <hr>
	<div>
		<form action="submitreview.php" id="submit" method="post">
            <input id= "player" type=integer name="player" value=',$_SESSION['player'],' hidden/>
            <input id= "huntid" type=integer name="huntid" value=',$_GET['hunt'] ,' hidden/>
            <input id= "rating_field" type=integer name="rating" hidden/></label><br />
            <legend>Please leave a review:</legend>
            <TEXTAREA NAME="description" COLS=50 ROWS=10></TEXTAREA>

            <br />

    <div>
		<div class="rating">
	    <legend>Please rate:</legend>
	    <input type="radio" id="star5" name="rating" value="5" /><label for="star5" title="Rocks!">5 stars</label>
	    <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="Pretty good">4 stars</label>
	    <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="Meh">3 stars</label>
	    <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="Kinda bad">2 stars</label>
		<input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="Sucks big time">1 star</label>
		</div>
	</div>
				 <br />
				  <br />
				   <br />

               <center><input type=submit value="Submit"/></center>

                <br />
    </div>
  	</div>';
  	//JAVASCRIPT FOR Star rating 
  	echo 
  	"	<script> $('#star5').click(function(){
  			$('#rating_field').val(5);
  		});
		
		$('#star4').click(function(){
  			$('#rating_field').val(4);
  		});

		$('#star3').click(function(){
  			$('#rating_field').val(3);
  		});

		$('#star2').click(function(){
  			$('#rating_field').val(2);
  		});

		$('#star1').click(function(){
  			$('#rating_field').val(1);
  		});

	</script>";

  echo '<br>';
  echo '<center><button><a href="#ex1" rel="modal:open">Submit Review</a></button></center>';
  echo '<br>';
  echo '<br>';

} catch (Exception $e) {
    echo 'Cannot get hunt details';
}
htmlFoot();
?>
