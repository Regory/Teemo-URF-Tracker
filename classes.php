<?php 

//Check to make sure Teemo Tracker was loaded
TEEMO_TRACKER_LOADED or die("Teemo Tracker not loaded");

/*
	This class is for accessing the RiotAPI
*/
class RiotAPI{
	
	private $lastCall;
	private $bucket;
	private $connect;
	
	function __construct(){
		$this->lastCall = 0; //The time of the last call.  Since calls are not client driven, setting to zero and not tracking calls between updates should be fine.
		$this->bucket = 0; //The last bucket checked.  Will pull from database if this is zero.
		
		//make db connection
		$this->connect = mysqli_connect("localhost",SQL_USER,SQL_PASS,SQL_DATABASE);
		if (mysqli_connect_errno($this->connect)){die("Failed to connect to MySQL: " . mysqli_connect_error());}
	}
	
	/*
		This takes a call and returns a string with the curl output
		
		Input
		$call - the call
		
		Output
		decoded json - curl output
	*/
	private function curlywurly($call){
		$curlHandle = curl_init($call); //The call
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true); //Output to variable, not page
		
		//Make sure we are not spamming Riot's servers 
		//TODO: this is overly conservative.
		$t = microtime(true) - $this->lastCall;
		if($t <= MIN_SECONDS_BETWEEN_CALLS){
			sleep(MIN_SECONDS_BETWEEN_CALLS - $t);
		}
		
		$output = curl_exec($curlHandle); //Execute curl
		$this->lastCall = time(); //The last call is right now
		
		$decodedJson = json_decode($output);
		
		//Check if valid JSON
		if(json_last_error() == JSON_ERROR_NONE){
			return json_decode($output); //Return curl data
		}else{
			return "Error: JSON ERROR ".json_last_error().". <br />Output was ".$output;
		}
	}
	
	/*
		This uses a decoded json output and determines the champion played by a given participant ID.
		This is here because getting this data is a pain in the ass since integers aren't legal variable names
		
		Input
		$jsonobject - decoded json
		$pID - participant id (1 to 10)
		
		Output
		string - champion id (integer stored as type string)
	*/
	private function getChampionId($jsonobject, $pID){					
		$participantArray = (array)$jsonobject->participants;
		$index = intval($pID)-1;
		$championId = $participantArray[$index]->championId;
		return $championId;
	}
	
	/*
		Determines what the last bucket of match IDs was, and gets the next bucket.
		The match ids are stored in the database
		
		Input
		void
		
		Output
		string - Description of Success / Failure
		
	*/
	function getNextBucket(){
		//Get last bucket
		if($this->bucket == 0){
			//$this->bucket == 0 means this is the first iteration.  Get info from general info table.
			//This is required (i.e.: we cannot just look at the last game found) because cannot guarantee that all buckets contain games.
			$query = "SELECT `value` FROM `general_info` WHERE `key`='Last Bucket'";
			$result = mysqli_query($this->connect,$query);
			$result or die('Error: ' . mysqli_error($this->connect));
			while($row = mysqli_fetch_array($result)){
				$this->bucket = $row['value'];
			}
		}
		
		//next bucket is 300 seconds later
		$this->bucket += 300;
		
		//Check to make sure bucket is in the past
		if($this->bucket > microtime(true)){
			return "FIN: Next Bucket hasn't occured yet!";
		}
		
		//URF is over! :C
		//This is to stop the script from endlessly searching for new matches
		if($this->bucket > 1428918000){ //1428918000 is allegedly the last bucket with an URF match
			return "FIN: URF is over! :C";
		}
		
		//do the curlywurly
		$call = "https://na.api.pvp.net/api/lol/".SERVER."/v4.1/game/ids?beginDate=".$this->bucket."&api_key=".RIOT_API_KEY;
		$output = $this->curlywurly($call);
		
		//Determine what the most recent matchID we have is (it is assumed we are getting these in order)
		$query = "SELECT `matchID` FROM `matches` ORDER BY `matchID` DESC LIMIT 1";
		$result = mysqli_query($this->connect,$query);
		$result or die('Error: ' . mysqli_error($this->connect));
		while($row = mysqli_fetch_array($result)){
			$lastMatchInserted = $row['value'];
		}
		
		//Output should be list of matchIds
		$csvMatches = "";
		foreach($output as $v){
			//check that it is an integer
			$v == intval($v) or die("ERROR: match ID isn't an integer for some reason");
			
			//check that it isn't in the list already
			$v > $lastMatchInserted or die("ERROR: found matchID is not larger than latest included match");
			
			$csvMatches .= "($v, ".$this->bucket."),";
		}
		//remove end comma
		$csvMatches = preg_replace("/\,$/","",$csvMatches);
		
		//Insert the new matchIDs
		if($csvMatches != ""){
			$query = "INSERT INTO `matches` (`matchID`, `bucket`) VALUES $csvMatches ON DUPLICATE KEY UPDATE `bucket`=VALUES(`bucket`)";
			$result = mysqli_query($this->connect,$query);
			$result or die('Error: ' . mysqli_error($this->connect));
		}
		
		//Update last bucket info
		$query = "UPDATE `general_info` SET `value`='".$this->bucket."' WHERE `key`='Last Bucket'";
		$result = mysqli_query($this->connect,$query);
		$result or die('Error: ' . mysqli_error($this->connect));
		
		//Return Status
		$latestBucket = time() - (time() % 300);
		$remaining = floor(($latestBucket - $this->bucket)/300);
		$dateZero = new DateTime("@0");
		$dateRemaining = new DateTime("@".$remaining*MIN_SECONDS_BETWEEN_CALLS);
		$remainingTime = $dateZero->diff($dateRemaining)->format('%a days, %h hours, %i minutes and %s seconds');
		
		return "OK: Finished getting bucket #".$this->bucket." of $latestBucket <br />
		Remaining: $remaining<br />
		Estimated Time: $remainingTime";
	}
	
	/*
		Checks next match to see if Teemo exists.
		This isn't really necessary, but timelines are bloody 200kb
		Will change hasTrackedChampion from -1 (not yet checked)
		
		Input
		void
		
		Output
		string - Description of Success / Failure
	*/
	function checkNextMatch(){
		//Get the oldest matchID where `checked` is false
		$query = "SELECT `matchID`, COUNT(`matchID`) as remaining FROM `matches` WHERE `checked` = 0 ORDER BY `matchID` ASC LIMIT 1";
		$result = mysqli_query($this->connect,$query);
		$result or die('Error: ' . mysqli_error($this->connect));
		if(mysqli_num_rows($result) == 0){
			//there are no more matches to check
			return "FIN: No Matches to Check";
		}
		while($row = mysqli_fetch_array($result)){
			$matchID = $row['matchID'];
			$remaining = $row['remaining'];
		}
		
		//do the curlywurly
		$call = "https://na.api.pvp.net/api/lol/".SERVER."/v2.2/match/$matchID?includeTimeline=false&api_key=".RIOT_API_KEY;
		$output = $this->curlywurly($call);
		
		//Find Tracked Champion
		$trackedChampionExists = 0;
		foreach($output->participants as $player){
			if($player->championId == TRACKED_CHAMPION){
				$trackedChampionExists = 1;
				break;
			}
		}

		//Make update
		$query = "UPDATE `matches` SET `checked`=1, `hasTrackedChampion`=$trackedChampionExists WHERE `matchID`=$matchID";
		$result = mysqli_query($this->connect,$query);
		$result or die('Error: ' . mysqli_error($this->connect));
		
		$o = ($trackedChampionExists == 1) ? "contains the tracked champion" : "does not contain the tracked champion";
		
		$dateZero = new DateTime("@0");
		$dateRemaining = new DateTime("@".$remaining*MIN_SECONDS_BETWEEN_CALLS);
		$remainingTime = $dateZero->diff($dateRemaining)->format('%a days, %h hours, %i minutes and %s seconds');
		
		return "OK: Match $matchID $o. <br />
		Remaining: $remaining <br />
		Estimated Time: $remainingTime";
	}
	
	/*
		Gets the full match information, including timeline
		Match information is stored in the database
		
		Input
		void
		
		Output
		string - Description of Success / Failure
	*/
	function analyzeNextMatch(){
		$allOK = true;
		
		//Get the oldest matchID where `analyzed` is false and hasTrackedChampion is true
		$query = "SELECT `matchID`, COUNT(`matchID`) as remaining FROM `matches` WHERE `analyzed` = 0 AND hasTrackedChampion = 1 ORDER BY `matchID` ASC LIMIT 1";
		$result = mysqli_query($this->connect,$query);
		$result or die('Error: ' . mysqli_error($this->connect));
		if(mysqli_num_rows($result) == 0){
			//there are no more matches to check
			return "FIN: No Matches to Check";
		}
		while($row = mysqli_fetch_array($result)){
			$matchID = $row['matchID'];
			$remaining = $row['remaining'];
		}
		
		//do the curlywurly
		$call = "https://na.api.pvp.net/api/lol/".SERVER."/v2.2/match/$matchID?includeTimeline=true&api_key=".RIOT_API_KEY;
		$output = $this->curlywurly($call);
		
		
		//Get Player Champions
		$csvPlayers = '';
		//Need to account for the possibility of two teemos
		$trackedChampionExists = false;
		foreach($output->participants as $player){
			//Generally, this is set up to handle multiple champions, but for now, I only care about Teemo
			if($player->championId != TRACKED_CHAMPION){
				continue;
			}
			
			//Get Data I want to use
			$pID = $player->participantId;
			$team = $player->teamId;
			$champion = $player->championId;
			$rank = $player->highestAchievedSeasonTier;
			
			//Again, because I only care about tracking one Champion
			$trackedChampionExists = true;
			
			//Stats
			$stats = $player->stats;
			
			$winner = $stats->winner == 1 ? 1:0;
			
			$kills = $stats->kills;
			$deaths = $stats->deaths;
			$assists = $stats->assists;
			
			$kill2 = $stats->doubleKills;
			$kill3 = $stats->tripleKills;
			$kill4 = $stats->quadraKills;
			$kill5 = $stats->pentaKills;
			$kill6 = $stats->unrealKills;
			
			$damageDealt = $stats->totalDamageDealt;
			$damageToChampions = $stats->totalDamageDealtToChampions;
			$damageTaken = $stats->totalDamageTaken;
			
			$minions = $stats->minionsKilled;
			$gold = $stats->goldEarned;
			
			$firstblood = $stats->firstBloodKill == 1 ? 1:0;
			$inhibitors = $stats->inhibitorKills;
			$towers = $stats->towerKills;
			$wardsPlaced = $stats->wardsPlaced;
			$wardsKilled = $stats->wardsKilled;
			
			//echo "Player $pID is on team $team and is rank $rank and had a KDA of $kills/$deaths/$assists<br />";
			$csvPlayers .= "($matchID, $pID, $team, $champion, '$rank', $winner, $kills, $deaths, $assists, $kill2, $kill3, $kill4, $kill5, $kill6, $damageDealt, $damageToChampions, $damageTaken, $minions, $gold, $firstblood, $inhibitors, $towers, $wardsPlaced, $wardsKilled),";
		}
		//get rid of ending comma
		$csvPlayers = preg_replace("/\,$/","",$csvPlayers);
		
		//Add the players, if any exist
		if($csvPlayers != ""){
		
			$query = "INSERT INTO `match_stats` (`matchID`, `pID`, `team`, `championId`, `rank`, `winner`, `k`, `d`, `a`, `k2`, `k3`, `k4`, `k5`, `k6`, `damageDealt`, `damageToChampions`, `damageTaken`, `minions`, `gold`, `firstblood`, `inhibitors`, `towers`, `wardsPlaced`, `wardsKilled`) VALUES $csvPlayers";
			$result = mysqli_query($this->connect,$query);
			if(!$result){
				echo 'Error: ' . mysqli_error($this->connect);
				$allOK = false;
			}
		}
		
		//Again, to save on resources, ignore timeline if there are no teemos
		if($trackedChampionExists){

			//Go through Timeline for Events
			$csvBuildings = '';
			$csvKills = '';
			$csvDeaths = '';
			$csvAssists = '';
			$csvMonsters = '';
			$csvWardKills = '';
			$csvWardPlace = '';
			
			foreach($output->timeline->frames as $frame){
				if(isset($frame->events)){
					foreach($frame->events as $event){
						$eventType = $event->eventType;
						/*Legal values: 
						
						I Care
						BUILDING_KILL, 
						CHAMPION_KILL, 
						ELITE_MONSTER_KILL, 
						WARD_KILL, 
						WARD_PLACED
						
						I Don't Care
						ASCENDED_EVENT, CAPTURE_POINT, ITEM_DESTROYED, ITEM_PURCHASED, ITEM_SOLD, ITEM_UNDO, PORO_KING_SUMMON, SKILL_LEVEL_UP, 
						*/
						switch($eventType){
							case "BUILDING_KILL":
								//Only care if it is a teemo kill (extra code in place in case you want to deal with multiple champions)
								$killerPID = $event->killerId;
								$champion = $this->getChampionId($output,$killerPID);
								
								if($champion != TRACKED_CHAMPION){continue;}
								
								$timestamp = $event->timestamp;
								$lane = $event->laneType;
								$building = $event->buildingType;
								$tower = $event->towerType;
								
								$csvBuildings .= "($matchID, $killerPID, $timestamp, $champion, '$lane', '$building', '$tower'),";
								
								break;
							case "CHAMPION_KILL":
								//First, get all data
								
								$timestamp = $event->timestamp;
								
								$killerPID = $event->killerId;
								$killerChampion = $this->getChampionId($output,$killerPID);
								
								$victimPID = $event->victimId;
								$victimChampion = $this->getChampionId($output,$victimPID);
								
								$assistPIDArray = array();
								$assistChampionArray = array();
								
								//Get Assist PID Data
								if(isset($event->assistingParticipantIds)){ 
									$assistPIDArray = (array) $event->assistingParticipantIds;
								}
								
								//Get Assist Champion Data
								foreach($assistPIDArray as $k => $v){
									$assistChampionArray[$k] = $this->getChampionId($output, $v);
								}
								
								$x = $event->position->x;
								$y = $event->position->y;
								
								//Check if Teemo is a Killer
								if($killerChampion == TRACKED_CHAMPION){
									$csvKills .= "($matchID, $victimPID, $timestamp, $victimChampion, $x, $y),";
								}
								
								//Check if Teemo is Victim
								if($victimChampion == TRACKED_CHAMPION){
									if($killerChampion == ""){$killerChampion = 0;}
									$csvDeaths .= "($matchID, $killerPID, $timestamp, $killerChampion, $x, $y),";
								}
								
								//check if Teemo is Assisting
								if(in_array(TRACKED_CHAMPION, $assistChampionArray)){
									$index = array_search(TRACKED_CHAMPION, $assistChampionArray);
									$assistPID = $assistPIDArray[$index];
									$csvAssists .= "($matchID, $assistPID, $timestamp, $killerChampion, $victimChampion, $x, $y),";
								}
								break;
							case "ELITE_MONSTER_KILL":
								//Only care if it is a teemo kill (extra code in place in case you want to deal with multiple champions)
								$killerPID = $event->killerId;
								$champion = $this->getChampionId($output,$killerPID);
								
								if($champion != TRACKED_CHAMPION){continue;}
								
								$timestamp = $event->timestamp;
								$monster = $event->monsterType;
								$x = $event->position->x;
								$y = $event->position->y;
								
								$csvMonsters .= "($matchID, $killerPID, $timestamp, $champion, '$monster', $x, $y),";
								
								break;
							case "WARD_KILL":
								//Only care if it is a teemo kill (extra code in place in case you want to deal with multiple champions)
								$killerPID = $event->killerId;
								$champion = $this->getChampionId($output,$killerPID);
								
								if($champion != TRACKED_CHAMPION){continue;}
								
								$timestamp = $event->timestamp;
								$ward = $event->wardType;
								
								$csvWardKills .= "($matchID, $killerPID, $timestamp, $champion, '$ward'),";
								break;
							case "WARD_PLACED":
								//Only care if it is a teemo place (extra code in place in case you want to deal with multiple champions)
								$creatorPID = $event->creatorId;
								$champion = $this->getChampionId($output,$creatorPID);
								
								if($champion != TRACKED_CHAMPION){continue;}
								
								$timestamp = $event->timestamp;
								$ward = $event->wardType;

								//there seem to be some bugs with wards placed
								if($timestamp == $lastwardtimestamp){
									continue;
								}else{
									$lastwardtimestamp = $timestamp;
								}
								if($creatorPID == "0"){
									continue;
								}
								
								$csvWardPlace .= "($matchID, $creatorPID, $timestamp, $champion, '$ward'),";
								break;
							default:
								continue;
						}
					}
				}
			}
			
			//remove ending commas
			$csvBuildings = preg_replace("/,$/","",$csvBuildings);
			$csvKills = preg_replace("/,$/","",$csvKills);
			$csvDeaths = preg_replace("/,$/","",$csvDeaths);
			$csvAssists = preg_replace("/,$/","",$csvAssists);
			$csvMonsters = preg_replace("/,$/","",$csvMonsters);
			$csvWardKills = preg_replace("/,$/","",$csvWardKills);
			$csvWardPlace = preg_replace("/,$/","",$csvWardPlace);
			
			//update tables
			if($csvBuildings != ""){
				$query = "INSERT INTO `buildings` (`matchID`, `pID`, `timestamp`, `killerChampion`, `lane`, `building`, `tower`) VALUES $csvBuildings";
				$result = mysqli_query($this->connect,$query);
				if(!$result){
					echo 'Buildings Error: ' . mysqli_error($this->connect) . "<br />";
					$allOK = false;
				}
			}
			if($csvKills != ""){
				$query = "INSERT INTO `kills` (`matchID`, `pID`, `timestamp`, `victimChampion`, `x`, `y`) VALUES $csvKills";
				$result = mysqli_query($this->connect,$query);
				if(!$result){
					echo 'Kills Error: ' . mysqli_error($this->connect) . "<br />";
					$allOK = false;
				}
			}
			if($csvDeaths != ""){
				$query = "INSERT INTO `deaths` (`matchID`, `pID`, `timestamp`, `killerChampion`, `x`, `y`) VALUES $csvDeaths";
				$result = mysqli_query($this->connect,$query);
				if(!$result){
					echo 'Deaths Error: ' . mysqli_error($this->connect) . "<br />";
					$allOK = false;
				}
			}
			if($csvAssists != ""){
				$query = "INSERT INTO `assists` (`matchID`, `pID`, `timestamp`, `killerChampion`, `victimChampion`, `x`, `y`) VALUES $csvAssists";
				$result = mysqli_query($this->connect,$query);
				if(!$result){
					echo 'Assists Error: ' . mysqli_error($this->connect) . "<br />";
					$allOK = false;
					echo "$query <br />";
				}
			}
			if($csvMonsters != ""){
				$query = "INSERT INTO `monsters` (`matchID`, `pID`, `timestamp`, `killerChampion`, `monster`, `x`, `y`) VALUES $csvMonsters";
				$result = mysqli_query($this->connect,$query);
				if(!$result){
					echo 'Monsters Error: ' . mysqli_error($this->connect) . "<br />";
					$allOK = false;
				}
			}
			if($csvWardKills != ""){
				$query = "INSERT INTO `ward_kill` (`matchID`, `pID`, `timestamp`, `killerChampion`, `wardType`) VALUES $csvWardKills";
				$result = mysqli_query($this->connect,$query);
				if(!$result){
					echo 'WardKills Error: ' . mysqli_error($this->connect) . "<br />";
					$allOK = false;
				}
			}
			if($csvWardPlace != ""){
				$query = "INSERT INTO `ward_place` (`matchID`, `pID`, `timestamp`, `champion`, `wardType`) VALUES $csvWardPlace";
				$result = mysqli_query($this->connect,$query);
				if(!$result){
					echo 'WardPlace Error: ' . mysqli_error($this->connect) . "<br />";
					$allOK = false;
				}
			}
			
			//Mark match as investigated
			if($allOK){
				$query = "UPDATE `matches` SET `analyzed`=1 WHERE `matchID`=$matchID";
				$result = mysqli_query($this->connect,$query);
				$result or die('Error: ' . mysqli_error($this->connect));
				
				$dateZero = new DateTime("@0");
				$dateRemaining = new DateTime("@".$remaining*MIN_SECONDS_BETWEEN_CALLS);
				$remainingTime = $dateZero->diff($dateRemaining)->format('%a days, %h hours, %i minutes and %s seconds');
				
				return "OK: Match $matchID analyzed <br />
				Remaining: $remaining <br />
				Estimated Time: $remainingTime";
				
			}
			
			return "ERROR: Something went wrong";
		}
	}
}

?>