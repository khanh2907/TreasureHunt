-- check login
CREATE OR REPLACE FUNCTION TreasureHunt.check_password(uname TEXT, pass TEXT)
RETURNS BOOLEAN AS $$
DECLARE passed BOOLEAN;
BEGIN
        SELECT  (password = $2) INTO passed
        FROM    TreasureHunt.Player
        WHERE   name = $1;

        RETURN passed;
END;
$$  LANGUAGE plpgsql;

-- returns what team player is in
CREATE OR REPLACE FUNCTION TreasureHunt.get_team(uname TEXT)
RETURNS VARCHAR(40) AS $$
DECLARE result VARCHAR(40);
BEGIN
	SELECT team INTO result
	FROM TreasureHunt.memberOf
	WHERE player = uname;

	RETURN result;
END;
	$$  LANGUAGE plpgsql;

-- find the next waypoints vcode
CREATE OR REPLACE FUNCTION TreasureHunt.find_next_code(current_hunt INT, current_wp INT)
RETURNS INT AS $$
DECLARE next_vcode INT;
BEGIN
	SELECT verification_code INTO next_vcode FROM physicalwaypoint WHERE hunt = current_hunt AND num = (current_wp+1);

	IF next_vcode IS NULL then
		SELECT verification_code INTO next_vcode FROM virtualwaypoint WHERE hunt = current_hunt AND num = (current_wp+1);
	END IF;

	RETURN next_vcode;
END;
	$$  LANGUAGE plpgsql;

-- get the next clue
CREATE OR REPLACE FUNCTION TreasureHunt.find_next_clue(current_hunt INT, current_wp INT)
RETURNS TEXT AS $$
DECLARE next_clue TEXT;
BEGIN
	SELECT clue INTO next_clue FROM physicalwaypoint WHERE hunt = current_hunt AND num = (current_wp+1);

	IF next_clue IS NULL then
		SELECT clue INTO next_clue FROM virtualwaypoint WHERE hunt = current_hunt AND num = (current_wp+1);
	END IF;

	RETURN next_clue;
END;
	$$  LANGUAGE plpgsql;


-- get the current team, hunt id and waypoint
CREATE OR REPLACE FUNCTION TreasureHunt.find_current(uname TEXT, OUT t VARCHAR(40), OUT hid INT, OUT cwp INT, OUT nwp INT)
AS $$
BEGIN
	SELECT mo.team , h.id, numwaypoints , currentwp INTO t, hid, cwp, nwp
	FROM TreasureHunt.memberof mo 
	RIGHT JOIN TreasureHunt.participates p ON (mo.team = p.team) 
	RIGHT JOIN TreasureHunt.Hunt h ON (p.hunt = h.id)
	WHERE player = uname AND status = 'active';

END;
	$$  LANGUAGE plpgsql;

-- get the next num for the team for visits table
CREATE OR REPLACE FUNCTION TreasureHunt.get_next_visit_num(team_name TEXT)
RETURNS INT AS $$
DECLARE _next INT;
BEGIN
	SELECT MAX(num)+1 INTO _next
	FROM Visit
	WHERE team = team_name;

	IF _next IS NULL THEN
		_next := 1;
	END IF;

	RETURN _next;
END;
	$$  LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION TreasureHunt.get_score(team_name TEXT, current_hunt INT)
RETURNS INT AS $$
DECLARE current_score INT;
BEGIN
	SELECT score INTO current_score
	FROM participates
	WHERE team = team_name AND hunt = current_hunt;

	RETURN current_score;
END;
	$$  LANGUAGE plpgsql;

-- get new rank
CREATE OR REPLACE FUNCTION TreasureHunt.get_new_rank(team_name TEXT, current_hunt INT)
RETURNS INT AS $$
DECLARE new_rank INT;
BEGIN
	SELECT nrank INTO new_rank
	FROM
	(SELECT *, RANK() OVER (order by score desc) as nrank FROM participates WHERE hunt = current_hunt) AS ranker
	WHERE team = team_name;

	return new_rank;
END;
	$$	LANGUAGE plpgsql;

-- get duration of hunt = current - starttime
CREATE OR REPLACE FUNCTION TreasureHunt.get_duration(team_name TEXT, current_hunt INT)
RETURNS INT AS $$
DECLARE start_time TIMESTAMP;
DECLARE dura INT;
BEGIN
	SELECT starttime INTO start_time FROM Hunt WHERE id = current_hunt;
	SELECT (EXTRACT(EPOCH FROM AGE(CURRENT_TIMESTAMP, start_time)::INTERVAL)/60)::int INTO dura;
	return dura;
END;
	$$	LANGUAGE plpgsql;


-- validate code part
CREATE OR REPLACE FUNCTION TreasureHunt.validate_code(vcode INT, uname TEXT, OUT _status TEXT, OUT _score INT, OUT _clue TEXT)
AS $$
DECLARE current_team VARCHAR(40);
DECLARE current_hunt INT;
DECLARE current_wp INT;
DECLARE passed BOOLEAN;
DECLARE temp INT;
DECLARE num_of_wp INT;
DECLARE final_rank INT;
DECLARE final_duration INT;
BEGIN
	-- find player's team
	-- find current hunt
	-- find current waypoint
	BEGIN --TRANSACTION
	SELECT * INTO current_team, current_hunt, num_of_wp, current_wp
	FROM find_current(uname);

	IF current_team IS NOT NULL AND current_hunt IS NOT NULL AND current_wp IS NOT NULL THEN
		-- check vcode
		SELECT (find_next_code(current_hunt, current_wp) = vcode) INTO passed;

		-- if match then create new visit with T and update team score and current waypoint, return true
			-- next clue is presented via php backend
		IF passed IS TRUE THEN
			-- update team score and current waypoint
			-- get duration (time since start time?)
			SELECT get_duration(current_team, current_hunt) INTO final_duration;
			UPDATE participates SET score = score+1, currentwp = currentwp+1, duration = final_duration  WHERE hunt = current_hunt AND team = current_team;
		-- if last waypoint and correct then win -> update team hunt statistics (duration, score and rank) -> congrats
			IF ((current_wp + 1) = num_of_wp) THEN
			--finished
				-- get rank (ie order of team that finishes)
				SELECT get_new_rank(current_team, current_hunt) INTO final_rank;
				UPDATE participates SET rank = final_rank WHERE hunt = current_hunt AND team = current_team;
				SELECT 'complete' INTO _status;
			ELSE
				SELECT 'correct' INTO _status;
			END IF;
			current_wp := current_wp +1;
			SELECT * INTO _clue FROM find_next_clue(current_hunt, current_wp);
			SELECT * INTO _score FROM get_score(current_team, current_hunt);
		ELSE
			SELECT 'failed' INTO _status;
		END IF;

		-- visit is filled out regardedless of correctness so...
		SELECT get_next_visit_num(current_team) INTO temp;
		INSERT INTO Visit VALUES (current_team, temp, vcode, CURRENT_TIMESTAMP, passed, current_hunt, current_wp+1);
	END IF;

	-- if shit goes down, then go down
	EXCEPTION
		WHEN OTHERS THEN
		SELECT 'Exception Raised' INTO _status;
	END;
	-- returns correct, failed or finish	
END;
	$$  LANGUAGE plpgsql;


-- submit a review and rating
CREATE OR REPLACE FUNCTION TreasureHunt.submit_review(uname TEXT, current_hunt INT, review_text TEXT, review_rating INT)
RETURNS BOOLEAN AS $$
BEGIN
	INSERT INTO review (hunt, player, whendone, rating, description) VALUES(current_hunt, uname, CURRENT_TIMESTAMP, review_rating,  review_text);
	RETURN TRUE;
	EXCEPTION WHEN OTHERS THEN 
    raise notice 'The transaction is in an uncommittable state. '
                 'Transaction was rolled back';
    RETRUN FALSE;
END;
	$$	LANGUAGE plpgsql;
