<?php
/*
	Takes all data and summarizes it
*/

//Start Time -- This is necessary in case script runs too long
$startTime = time();
$maxTime = 300; //Max time in seconds to allow updating to take
//$maxTime = -1; //Disabled for now, since using batch Update

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

//Now, do the update
$riotAPI->updateStatistics();

?>