<?php
/**
 * Common functionality across web pages
 */

/**
 *  Magically redirect to login page if not logged in
 */
function startValidSession() {
    session_start();
    if ( !isset($_SESSION['logged_in']) || $_SESSION['logged_in']!=true ) {
        redirectTo('login.php');
    }
}

/**
 * Redirect to given page, retaining GET query parameters
 * @param string $target 
 */
function redirectTo($target) {
    // Pass on query parameters
    $qstring = http_build_query($_GET);
    if(!empty($qstring)) {
        $target = $target.'?'.$qstring;
    }
    header('Location:'.$target);
    exit;
}

/**
 *  Output top material common to each page
 */
function htmlHead() {
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Treasure Hunt</title>
    <link rel="stylesheet" type="text/css" href="css/main.css" />
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>
    <script src="js/jquery.modal.js" type="text/javascript" charset="utf-8"></script>
</head>
<body>
<div id="wrapper">
    <ul id="nav">
        <li><a href="index.php">Home</a></li>
        <li><a href="current.php">Current</a></li>
        <li><a href="hunts.php">Browse</a></li>
        <li><a href="validate.php">Validate</a></li>
        <li><a href="login.php">Quit</a></li>
    </ul>
    <div id="content">



<?php
}

/// Output bottom material common to each page
function htmlFoot() {
?>
    </div>
    <div id="push"></div>
</div>
<div id="footer">
</div>
</body>
</html>
<?php
}

function jsMap($min, $max, $hunt, $wp){




    if (is_null($min) && is_null($max)){
        echo 'There is no physical location for this hunt!';
    }
    else{

        $points = getCompletedWaypointGPS($hunt, $wp);

    echo
        "<script
        src=\"http://maps.googleapis.com/maps/api/js?key=AIzaSyBnHaGoNckTGU4G4V91ZhAkW7es6-XIm9Q&sensor=false\">
        </script>

        <script>
        var x=new google.maps.LatLng(", $max ,");
        
        var min=new google.maps.LatLng(", $min ,");
        var max=new google.maps.LatLng(", $max ,");
        
        var bounds = new google.maps.LatLngBounds(min, max);";

        for($index =0; $index < sizeof($points); $index++) {
            echo "var m",$index,"= new google.maps.LatLng(", $points[$index]['gps'],");";
        }
        
        echo "
        function initialize()
        {
        var mapProp = {
          center:x,
          zoom:6,
          mapTypeId: google.maps.MapTypeId.ROADMAP
          };
          
        var map=new google.maps.Map(document.getElementById(\"googleMap\"),mapProp);";
        
        for($index =0; $index < sizeof($points); $index++) {
           echo "var marker = new google.maps.Marker({
                position: m",$index,",
                map: map
            });";
        }
          
         echo "var rectangle = new google.maps.Rectangle({
            strokeColor: '#93c572',
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: '#1eb486',
            fillOpacity: 0.35,
            map: map,
            bounds: bounds
          });
            
            map.fitBounds (bounds);
        }
        
        google.maps.event.addDomListener(window, 'load', initialize);
        </script>";
    }
}

?>