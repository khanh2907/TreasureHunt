<?php
/**
 * Database functions. You need to modify each of these to interact with the database and return appropriate results. 
 */

/**
 * Connect to database
 * This function does not need to be edited - just update config.ini with your own 
 * database connection details. 
 * @param string $file Location of configuration data
 * @return PDO database object
 * @throws exception
 */
function connect($file = 'config.ini') {
	// read database seetings from config file
    if ( !$settings = parse_ini_file($file, TRUE) ) 
        throw new exception('Unable to open ' . $file);
    
    // parse contents of config.ini
    $dns = $settings['database']['driver'] . ':' .
            'host=' . $settings['database']['host'] .
            ((!empty($settings['database']['port'])) ? (';port=' . $settings['database']['port']) : '') .
            ';dbname=' . $settings['database']['schema'];
    $user= $settings['db_user']['username'];
    $pw  = $settings['db_user']['password'];

	// create new database connection
    try {
        $dbh=new PDO($dns, $user, $pw);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        print "Error Connecting to Database: " . $e->getMessage() . "<br/>";
        die();
    }
    return $dbh;
}

/**
 * Check login details
 * @param string $name Login name
 * @param string $pass Password
 * @return boolean True is login details are correct
 */
function checkLogin($name,$pass) {
    $db = connect();
    try {
        $stmt = $db->prepare('SELECT TreasureHunt.check_password(:name, :pass);');
        $stmt->bindValue(':name', $name, PDO::PARAM_INT);
        $stmt->bindValue(':pass', $pass, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        $stmt->closeCursor();
    } catch (PDOException $e) { 
        print "Error checking login: " . $e->getMessage(); 
        return FALSE;
    }
    return ($result == 't');
}

/**
 * Get details of the current user
 * @param string $user login name user
 * @return array Details of user - see index.php
 */
function getUserDetails($user) {
    $db = connect();
    try {
        $stmt = $db->prepare('SELECT p.name, p.addr, m.team, COUNT(pa.hunt) AS nhunts
                                FROM TreasureHunt.Player p 
                                RIGHT JOIN TreasureHunt.MemberOf m ON (p.name = m.player)
                                RIGHT JOIN TreasureHunt.participates pa  ON (m.team = pa.team)
                                WHERE m.current IS TRUE AND p.name = :user
                                GROUP BY p.name, p.addr, m.team');
        $stmt->bindValue(':user', $user, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetch(); // we expect a small result here - so Ok to use fetchAll()
        $stmt->closeCursor();
    } catch (PDOException $e) { 
        print "Error listing user details: " . $e->getMessage(); 
        die();
    }
    return $results;
}

function getBadges($user) {
    $db = connect();
    try {
        $stmt = $db->prepare('SELECT badge, description
                                FROM TreasureHunt.Achievements a RIGHT JOIN TreasureHunt.Badge b ON (a.badge = b.name)
                                WHERE player = :user');
        $stmt->bindValue(':user', $user, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(); // we expect a small result here - so Ok to use fetchAll()
        $stmt->closeCursor();
    } catch (PDOException $e) { 
        print "Error listing badges: " . $e->getMessage(); 
        die();
    }
    return $results;

}

/**
 * List hunts that are currently available
 * @return array Various details of for available hunts - see hunts.php
 * @throws Exception 
 */
function getAvailableHunts() {
    $db = connect();
    try {
        $stmt = $db->prepare('SELECT id, title, distance, numwaypoints, starttime
                                FROM TreasureHunt.Hunt
                                WHERE status <> :status
                                ORDER BY title ASC');
        $stmt->bindValue(':status', "under construction", PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(); // we expect a small result here - so Ok to use fetchAll()
        $stmt->closeCursor();
    } catch (PDOException $e) { 
        print "Error listing available hunts: " . $e->getMessage(); 
        die();
    }
    return $results;
}

/**
 * Get details for a specific hunt
 * @param integer $hunt ID of hunt
 * @return array Various details of current hunt - see huntdetails.php
 * @throws Exception 
 */
function getHuntDetails($hunt) {
    $db = connect();
    try {
        $stmt = $db->prepare('SELECT title, description, distance, numwaypoints, starttime, COUNT(team) AS nhunt
                            FROM TreasureHunt.Hunt h RIGHT JOIN TreasureHunt.participates p ON (h.id = p.hunt)
                            WHERE h.id = :hunt
                            GROUP BY title, description, distance, numwaypoints, starttime');
        $stmt->bindValue(':hunt', $hunt, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetch(); // we expect a small result here - so Ok to use fetchAll()
        $stmt->closeCursor();
    } catch (PDOException $e) { 
        print "Error listing hunt details: " . $e->getMessage(); 
        die();
    }
    return $results;
}

function getParticipation($hunt){
    $db = connect();
    try {
        $stmt = $db->prepare('SELECT p.team
                            FROM TreasureHunt.Hunt h RIGHT JOIN TreasureHunt.participates p ON (h.id = p.hunt)
                            WHERE h.id =:hunt');
        $stmt->bindValue(':hunt', $hunt, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(); // we expect a small result here - so Ok to use fetchAll()
        $stmt->closeCursor();
    } catch (PDOException $e) { 
        print "Error listing teams: " . $e->getMessage(); 
        die();
    }
    return $results;
}

function getMapDetails($hunt){
    $db = connect();
    try {
        $stmt = $db->prepare('SELECT MIN(gpslat) || \',\' || MIN(gpslon) AS min, MAX(gpslat) || \',\' || MAX(gpslon) AS max 
                                        FROM physicalwaypoint 
                                        WHERE hunt=:hunt');
        $stmt->bindValue(':hunt', $hunt, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetch(); // we expect a small result here - so Ok to use fetchAll()
        $stmt->closeCursor();
    } catch (PDOException $e) { 
        print "Error listing gps details: " . $e->getMessage(); 
        die();
    }
    return $results;
}

function getCompletedWaypointGPS($hunt, $current_wp){
    $db = connect();
    try {
        $stmt = $db->prepare('SELECT gpslat || \',\' || gpslon AS gps FROM physicalwaypoint
                            WHERE num BETWEEN 1 and :current_wp and hunt = :hunt');
        $stmt->bindValue(':hunt', $hunt, PDO::PARAM_INT);
        $stmt->bindValue(':current_wp', $current_wp, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(); // we expect a small result here - so Ok to use fetchAll()
        $stmt->closeCursor();
    } catch (PDOException $e) { 
        print "Error listing gps details: " . $e->getMessage(); 
        die();
    }
    return $results;
}

function getReviews($hunt){
    $db = connect();
    try {
        $stmt = $db->prepare('SELECT id, player, whendone, rating, description
                            FROM TreasureHunt.Review
                            WHERE hunt = :hunt');
        $stmt->bindValue(':hunt', $hunt, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(); // we expect a small result here - so Ok to use fetchAll()
        $stmt->closeCursor();
    } catch (PDOException $e) { 
        print "Error listing reviews: " . $e->getMessage(); 
        die();
    }
    return $results;
}

function submitReview($player, $hunt, $description, $rating){
    $db = connect();
    try {
        $stmt = $db->prepare('SELECT submit_review(:player, :hunt, :description, :rating)');
        $stmt->bindValue(':player', $player, PDO::PARAM_INT);
        $stmt->bindValue(':hunt', $hunt, PDO::PARAM_INT);
        $stmt->bindValue(':description', $description, PDO::PARAM_INT);
        $stmt->bindValue(':rating', $rating, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetch(); // we expect a small result here - so Ok to use fetchAll()
        $stmt->closeCursor();
    } catch (PDOException $e) { 
        print "Error listing reviews: " . $e->getMessage(); 
        die();
    }
    return $results;

}

/**
 * Show status of user in their current hunt
 * @param string $user
 * @return array Various details of current hunt - see current.php
 * @throws Exception 
 */
function getHuntStatus($user) {
    $db = connect();
    try {
        $stmt = $db->prepare(' SELECT mo.team, currentwp, score, rank, title, starttime, age(CURRENT_TIMESTAMP, starttime) as duration, status, id
                                FROM TreasureHunt.memberof mo RIGHT JOIN TreasureHunt.participates p ON (mo.team = p.team) RIGHT JOIN TreasureHunt.Hunt h ON (p.hunt = h.id)
                                WHERE player = :user AND status = :status');
        $stmt->bindValue(':user', $user, PDO::PARAM_INT);
        $stmt->bindValue(':status', "active", PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetch(); // we expect a small result here - so Ok to use fetchAll()
        $stmt->closeCursor();
    } catch (PDOException $e) { 
        print "Error listing units: " . $e->getMessage(); 
        die();
    }

    if (empty($results)){
        try {
        $stmt = $db->prepare(' SELECT mo.team, currentwp, score, rank, title, starttime,duration, status, id
                                FROM TreasureHunt.memberof mo RIGHT JOIN TreasureHunt.participates p ON (mo.team = p.team) RIGHT JOIN TreasureHunt.Hunt h ON (p.hunt = h.id)
                                WHERE player = :user AND status = :status');
        $stmt->bindValue(':user', $user, PDO::PARAM_INT);
        $stmt->bindValue(':status', "finished", PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetch(); // we expect a small result here - so Ok to use fetchAll()
        $stmt->closeCursor();
    } catch (PDOException $e) { 
        print "Error getting hunt status: " . $e->getMessage(); 
        die();
    }
    }
    return $results;
}

function getNextClue($hunt, $currentwp){
    $db = connect();
    $currentwp = $currentwp +1;

    //check physical
    try {
        $stmt = $db->prepare(' SELECT *
                                FROM TreasureHunt.virtualwaypoint
                                WHERE hunt = :hunt AND num = :currentwp');
        $stmt->bindValue(':hunt', $hunt, PDO::PARAM_INT);
        $stmt->bindValue(':currentwp', $currentwp, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetch(); // we expect a small result here - so Ok to use fetchAll()
        $stmt->closeCursor();
    } catch (PDOException $e) { 
        print "Error listing units: " . $e->getMessage(); 
        die();
    }
    if(empty($results)){
        try {
            $stmt = $db->prepare(' SELECT *
                                    FROM TreasureHunt.physicalwaypoint
                                    WHERE hunt = :hunt AND num = :currentwp');
            $stmt->bindValue(':hunt', $hunt, PDO::PARAM_INT);
            $stmt->bindValue(':currentwp', $currentwp, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetch(); // we expect a small result here - so Ok to use fetchAll()
            $stmt->closeCursor();
        } catch (PDOException $e) { 
            print "Error getting next clue: " . $e->getMessage(); 
            die();
        }
    }   
    return $results;
}

/**
 * Check validation code is for user's next expected waypoint 
 * @param string $user
 * @param integer $code Validation code (e.g. from QR)
 * @return array Various details of current visit - see validate.php
 * @throws Exception 
 */
function validateVisit($user,$code) {
    $db = connect();
    try {
        $stmt = $db->prepare('SELECT * FROM TreasureHunt.validate_code(:vcode, :user);');
        $stmt->bindValue(':user', $user, PDO::PARAM_INT);
        $stmt->bindValue(':vcode', $code, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        $stmt->closeCursor();
    } catch (PDOException $e) { 
        print "You submitted an invalid verification code. Please try again.";
        return FALSE;
    }
    return $result;
}
?>