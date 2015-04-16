<?php
/*
	Takes all data and summarizes it
*/

//Start Time -- This is necessary in case script runs too long
$startTime = time();
$maxTime = 300; //Max time in seconds to allow updating to take
$maxTime = -1; //Disabled for now, since using batch Update

//Load Tracker
require_once "loadTeemoTracker.php";
$riotAPI = new RiotAPI();

//Make db connection
$connect = mysqli_connect("localhost",SQL_USER,SQL_PASS,SQL_DATABASE);
if (mysqli_connect_errno($connect)){die("Failed to connect to MySQL: " . mysqli_connect_error());}

//first, get time of most recent update, and update key
$query = "SELECT `value` FROM `general_info` WHERE `key`='Last Statistics Update'";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));
while($row = mysqli_fetch_array($result)){
	$lastUpdate = $row['value'];
}

//Check if it has been 5 minutes since last update.  Riot only updates buckets every 5 minutes.
$now = time();
if($now - $lastUpdate < 300){
	die("5MIN: Less than 5 minutes since last update");
}

//Set Update - Quick and dirty method to ensure that script is not called multiple times at the same time
$newUpdateKey = rand(0,1000000000);

//Update Time and Key together
$query = "INSERT INTO `general_info` (`key`,`value`) VALUES ('Last Statistics Update',$now), ('Update Key',$newUpdateKey) ON DUPLICATE KEY UPDATE `key`=VALUES(`key`),`value`=VALUES(`value`);";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));

//Wait a moment
sleep(1);

//Check if update key is still the same
$query = "SELECT `value` FROM `general_info` WHERE `key`='Update Key'";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));
while($row = mysqli_fetch_array($result)){
	$currentUpdateKey = $row['value'];
}
if($currentUpdateKey != $newUpdateKey){
	die("SIMUL: Update is currently being performed");
}


//Okay, do the update.

//First, get new buckets
while(preg_match("/^OK:.+/",$riotAPI->getNextBucket())){
	//Keep looping while getNextBucket returns OK:
	
	//Check to make sure we aren't taking too long
	if(time() - $startTime > $maxTime){
		break;
	}
}
//Second, Check new matches
while(preg_match("/^OK:.+/",$riotAPI->checkNextMatch())){
	//Keep looping while checkNextMatch returns OK:
	
	//Check to make sure we aren't taking too long
	if(time() - $startTime > $maxTime){
		break;
	}
}
//Third, Analyze new matches
while(preg_match("/^OK:.+/",$riotAPI->analyzeNextMatch())){
	//Keep looping while analyzeNextMatch returns OK:
	
	//Check to make sure we aren't taking too long
	if(time() - $startTime > $maxTime){
		break;
	}
}



//Now, summarize the statistics
$trackedChampion = TRACKED_CHAMPION; //To make things easier with EODs

//Match Statistics
//summarize `matches` and put data in `match_stats`

$query = <<<EOD

INSERT INTO `champion_stats` 
(
	`championId`, 
	`winner`, 
	`k`, 
	`d`, 
	`a`, 
	`k2`, 
	`k3`, 
	`k4`, 
	`k5`, 
	`k6`, 
	`damageDealt`, 
	`damageToChampions`, 
	`damageTaken`, 
	`minions`, 
	`gold`, 
	`firstblood`, 
	`inhibitors`, 
	`towers`, 
	`wardsPlaced`, 
	`wardsKilled`, 
	`totalMatches`
)
SELECT 
	`championId`, 
	SUM(`winner`) as `winnersum`,
	SUM(`k`) as `k`,
	SUM(`d`) as `d`,
	SUM(`a`) as `a`,
	SUM(`k2`) as `k2`,
	SUM(`k3`) as `k3`,
	SUM(`k4`) as `k4`,
	SUM(`k5`) as `k5`,
	SUM(`k6`) as `k6`,
	SUM(`damageDealt`) as `damageDealt`,
	SUM(`damageToChampions`) as `damageToChampions`,
	SUM(`damageTaken`) as `damageTaken`,
	SUM(`minions`) as `minions`,
	SUM(`gold`) as `gold`,
	SUM(`firstblood`) as `firstblood`,
	SUM(`inhibitors`) as `inhibitors`,
	SUM(`towers`) as `towers`,
	SUM(`wardsPlaced`) as `wardsPlaced`,
	SUM(`wardsKilled`) as `wardsKilled`,
	COUNT(`championId`) as `totalMatches`
FROM `match_stats`
WHERE `championId` = $trackedChampion
ON DUPLICATE KEY UPDATE 
	`winner`=VALUES(`winner`),
	`k`=VALUES(`k`),
	`d`=VALUES(`d`),
	`a`=VALUES(`a`),
	`k2`=VALUES(`k2`),
	`k3`=VALUES(`k3`),
	`k4`=VALUES(`k4`),
	`k5`=VALUES(`k5`),
	`k6`=VALUES(`k6`),
	`damageDealt`=VALUES(`damageDealt`),
	`damageToChampions`=VALUES(`damageToChampions`),
	`damageTaken`=VALUES(`damageTaken`),
	`minions`=VALUES(`minions`),
	`gold`=VALUES(`gold`),
	`firstblood`=VALUES(`firstblood`),
	`inhibitors`=VALUES(`inhibitors`),
	`towers`=VALUES(`towers`),
	`wardsPlaced`=VALUES(`wardsPlaced`),
	`wardsKilled`=VALUES(`wardsKilled`),
	`totalMatches`=VALUES(`totalMatches`)

EOD;

$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));


//Jungle Stats
//summarize `monsters` and put data in `monster_stats`
$monsterArray = array();

$query = "SELECT `monster`, COUNT(`monster`) as total FROM `monsters` WHERE `killerChampion` = $trackedChampion GROUP BY `monster`";

$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));
while($row = mysqli_fetch_array($result)){
	$monsterArray[$row['monster']] = $row['total'];
}

//Insert / Update new info
$query = "INSERT INTO `monster_stats` (`championId`, `blue`, `red`, `dragon`, `baron`) VALUES ($trackedChampion, ".$monsterArray['BLUE_GOLEM'].", ".$monsterArray['RED_LIZARD'].", ".$monsterArray['DRAGON'].", ".$monsterArray['BARON_NASHOR'].") ON DUPLICATE KEY UPDATE `blue`=VALUES(`blue`), `red`=VALUES(`red`), `dragon`=VALUES(`dragon`), `baron`=VALUES(`baron`)";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));



//Ward Stats
$wardPlaceArray = array();
$query = "SELECT `wardType`, COUNT(`matchID`) as total FROM `ward_place` WHERE `champion` = $trackedChampion GROUP BY `wardType` ";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));
while($row = mysqli_fetch_array($result)){
	$wardPlaceArray[$row['wardType']] = $row['total'];
}

$wardKillArray = array();
$query = "SELECT `wardType`, COUNT(`matchID`) as total FROM `ward_kill` WHERE `killerChampion` = $trackedChampion GROUP BY `wardType`";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));
while($row = mysqli_fetch_array($result)){
	$wardKillArray[$row['wardType']] = $row['total'];
}


//Insert / Update new info
$query = "
INSERT INTO `ward_stats` 
(
	`championId`,
	`sightPlaced`,
	`visionPlaced`,
	`trinketPlaced`,
	`trinket2Placed`,
	`mushroomPlaced`,
	`sightKilled`,
	`visionKilled`,
	`trinketKilled`,
	`trinket2Killed`,
	`mushroomKilled`
)
VALUES
(
	$trackedChampion,
	".$wardPlaceArray['SIGHT_WARD'].",
	".$wardPlaceArray['VISION_WARD'].",
	".$wardPlaceArray['YELLOW_TRINKET'].",
	".$wardPlaceArray['YELLOW_TRINKET_UPGRADE'].",
	".$wardPlaceArray['TEEMO_MUSHROOM'].",
	".$wardKillArray['SIGHT_WARD'].",
	".$wardKillArray['VISION_WARD'].",
	".$wardKillArray['YELLOW_TRINKET'].",
	".$wardKillArray['YELLOW_TRINKET_UPGRADE'].",
	".$wardKillArray['TEEMO_MUSHROOM']."
)
ON DUPLICATE KEY UPDATE 
`sightPlaced`=VALUES(`sightPlaced`),
`visionPlaced`=VALUES(`visionPlaced`),
`trinketPlaced`=VALUES(`trinketPlaced`),
`trinket2Placed`=VALUES(`trinket2Placed`),
`mushroomPlaced`=VALUES(`mushroomPlaced`),
`sightKilled`=VALUES(`sightKilled`),
`visionKilled`=VALUES(`visionKilled`),
`trinketKilled`=VALUES(`trinketKilled`),
`trinket2Killed`=VALUES(`trinket2Killed`),
`mushroomKilled`=VALUES(`mushroomKilled`)
";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));






//Teemo Specific Stats

//teemo_kda_array
$teemoKdaArray = array();

//Kills
$query = "SELECT `victimChampion`, COUNT(`matchID`) as total FROM `kills` GROUP BY `victimChampion`";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));
while($row = mysqli_fetch_array($result)){
	$teemoKdaArray[$row['victimChampion']]['k'] = $row['total'];
}

//Deaths
$query = "SELECT `killerChampion`, COUNT(`matchID`) as total FROM `deaths` GROUP BY `killerChampion`";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));
while($row = mysqli_fetch_array($result)){
	$teemoKdaArray[$row['killerChampion']]['d'] = $row['total'];
}

//Assist Allies
$query = "SELECT `killerChampion`, COUNT(`matchID`) as total FROM `assists` GROUP BY `killerChampion`";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));
while($row = mysqli_fetch_array($result)){
	$teemoKdaArray[$row['killerChampion']]['aa'] = $row['total'];
}

//Assist Victims
$query = "SELECT `victimChampion`, COUNT(`matchID`) as total FROM `assists` GROUP BY `victimChampion`";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));
while($row = mysqli_fetch_array($result)){
	$teemoKdaArray[$row['victimChampion']]['av'] = $row['total'];
}

//generate query
$query = "INSERT INTO `teemo_kda_array` (`championId`, `kills`, `deaths`, `assistally`, `assistvictim`) VALUES ";
foreach($teemoKdaArray as $championId => $v){
	$kills = $v['k'] == "" ? 0 : $v['k'];
	$deaths = $v['d'] == "" ? 0 : $v['d'];
	$aally = $v['aa'] == "" ? 0 : $v['aa'];
	$avictim = $v['av'] == "" ? 0 : $v['av'];
	
	$query .= "($championId, $kills, $deaths, $aally, $avictim),";
}
$query = preg_replace("/,$/","",$query);
$query .= " ON DUPLICATE KEY UPDATE `championId`=VALUES(`championId`), `kills`=VALUES(`kills`), `deaths`=VALUES(`deaths`), `assistally`=VALUES(`assistally`), `assistvictim`=VALUES(`assistvictim`)";

//Update teemo_kda_array
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));

//Generate Heatmaps
define('HEATMAP_FROM_CRON_UPDATE',true);
require_once('makeHeatmap.php');


//Update Time Complete
$timeComplete = time();
$query = "UPDATE `general_info` SET `value` = $timeComplete WHERE `key`='Last Statistics Update Complete';";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));


echo "OK: Completed updating successfully";



?>