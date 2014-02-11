<?php 
/**
 * Web page to confirm whether a valid waypoint has been visited
 */
require_once('include/common.php');
require_once('include/database.php');
startValidSession();
htmlHead();
?>
<h1>Checkpoint Visit</h1>
<?php
if (!isset($_REQUEST['vcode'])) {
    echo 'Enter a validation code to confirm your waypoint visit<br />';
    echo '<form action="validate.php" id="verify" method="post">
        <label>Verification code <input type=text name="vcode" /></label><br />
           <input type=submit value="Verify"/>
    </form>';
} else {
    try {
        $visit = validateVisit($_SESSION['player'],$_REQUEST['vcode']);
        if($visit['_status'] == 'complete') { 
            echo '<h2>Congratulations!</h2> You\'ve validated a visit to your last waypoint!';
            echo '<p>Your team has finished with a final score of ',$visit['_score'],'</p>';
        } else if($visit['_status'] == 'correct') {
            echo '<h2>Congratulations!</h2> You\'ve validated a visit to your next waypoint!';
            echo '<p>Your team\'s score is now ',$visit['_score'],'</p>';
            echo '<h2>Next Waypoint\'s clue</h2> <quote>',$visit['_clue'],'</quote>';
            echo '<form action="validate.php" id="verify" method="post">
                <label>Verification code <input type=text name="vcode" /></label><br />
                   <input type=submit value="Verify"/>
            </form>';
        } else {
            echo '<h2>Wrong verification code!</h2> (Out of order, or not in this hunt)';
        }
    } catch (Exception $e) {
            echo 'Couldn\'t validate visit status';
    }
}
htmlFoot();
?>
