TreasureHunt
============
Group Members: Khanh Nguyen, Eric Lee, Cynthia Ng
Group Number:WO9A2
Assignment 3
PHP.

Login:
*database.php*
- The function 'checkLogin' passes variables '$name' and '$pass'
- The 'bindValue' binds the input value of 'name' and 'pass' to the '$name' and '$pass' variables respectively.
*function.sql*
- The function declares the 'passed' boolean which returns a 'true' or 'false' value to the login
- The function selects the password from the 'pass' variable and checks whether the 'username' variable matches in order for a successful login. If they don't match, the login will fail.

Player Home:
*database.php*
- The function 'getUserDetails' needs the '$user' variable to run the query
- The primary keys needed for this query are the player's name and team name
- So, this means the query needs the 'TreasureHunt' Player, MemberOf and Participates tables (all joint by a RIGHT JOIN) using the primary keys from each table
- This query returns the player's name, address, their team name and the number of hunts they've participated in
- This query will run if the current member of the team is 'true' and verifies the user.

Badges:
*database.php*
- The function 'getBadges' needs the '$user' variable to run the query
- The function joins the 'Achievement' and 'Badge' table together using the 'Achievement badge' and 'Badge name' as primary keys to be joined (Right Join)
- This show how many achievements the team and players currently have.

Hunts:
*database.php*
- The function 'getHuntDetails' needs the '$hunt' variable to run the query
- The primary key needed for this query is the hunt's id/name
- So, this means the query needs the 'TreasureHunt' 'Hunt' and 'Participates' tables (all joint by a RIGHT JOIN) using the primary keys from each table
- This query returns the Hunt's title description, distance, numwaypoints, startTime and number of teams
- There are 2 queries needed for the function 'getHuntStatus'
- The first query runs when the hunt has not yet been completed and still 'active'
- The second query runs when the hunt has been complete and 'finished'


Validation:
- The function takes in a verification code, player name and returns status, score and next clue
- The function process is:
	- Determine the current team, current hunt and current waypoint of the player
	- Check the verification code inputted by the player
		- Finds the verification of the next waypoint given the current waypoint and then checks it to the input
	- If the code is correct then do the necessary updates and insert to 'visit' with true
	- Otherwise do a insert to 'visit' with false

- The function uses the procedural language (plpgsql) so if any exceptions are caught, it will do a rollback.


Extensions 1
Ratings and Reviews:
- We decided to implement the ratings and reviews as one of our extensions.
- It simply takes an integer value and text as the rating and review respectively.
- The primary key consists of the hunt and author of the review.
- A function was created to handle the insertion of the review and rating.
- On the PHP side, at the bottom a Hunt Details page, you can see the Submit Review button which will open a modal where
  you can give a star rating and review. If you press submit, it will succeed or fail and prompt the user accordingly.


Extensions 2
Hunt Visualisation (Google Maps API):
- Rectangle overlay to display the area of which the hunt is taken place
	- Queries the database for the (minimum lat, minimum long) and (maximum lat, maximum long) from physicalWaypoint which belongs to that certain hunt.

- Marker for every physical waypoint that the team has successfully visited.
	- Queries the database to find all the 1st to currentwp-th waypoint for that specific hunt and then plot it out on the map on the current hunt page.
