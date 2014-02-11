<?php 
/**
 * Web page to get details of a specific hunt
 */
ini_set('display_errors', 'On');
error_reporting(E_ALL);
require_once('include/common.php');
require_once('include/database.php');
startValidSession();
htmlHead();
?>
<h1>Hunt Status</h1>
<?php 
try {
    $hunt = getHuntStatus($_SESSION['player']);
    if($hunt['status']=='active') {
        echo '<h2>Hunt Name</h2> ', $hunt['title'];
        echo '<h2>Playing in team</h2> ',$hunt['team'];
        echo '<h2>Started</h2> ',$hunt['starttime'];
        echo '<h2>Time elapsed</h2> ',$hunt['duration'];
        echo '<h2>Current score</h2> ',$hunt['score'];
        echo '<h2>Completed waypoints</h2> ',$hunt['currentwp'];  

        $gps = getMapDetails($hunt['id']);
        jsMap($gps['min'],$gps['max'], $hunt['id'], $hunt['currentwp']);
        echo '<div id="googleMap" style="width:500px;height:380px;margin-left: auto;
        margin-right: auto;"></div>';
        echo '<center>This map shows the physical waypoints that the player has visited.</center>';

        $clue = getNextClue($hunt['id'], $hunt['currentwp']);
        echo '<h2>Next Waypoint\'s clue</h2> <quote>',$clue['clue'],'</quote>';
        echo '<form action="validate.php" id="verify" method="post">
            <label>Verification code <input type=text name="vcode" /></label>(test with "1234")<br />
               <input type=submit value="Verify"/>
        </form>';
    } else if ($hunt['status']=='finished') {
        echo '<h2>Hunt Name</h2> ', $hunt['name'];
        echo '<h2>Played in team</h2> ',$hunt['team'];
        echo '<h2>Started</h2> ',$hunt['start_time'];
        echo '<h2>Final score</h2> ',$hunt['score'];
    } else {
        echo 'No hunt history.';
    }
} catch (Exception $e) {
    echo 'Cannot get current hunt status';
}
htmlFoot();
?>
