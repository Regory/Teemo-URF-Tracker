<?php 
/*

The Index Page!

*/

//Page Caching
$cache_expiry_time = 1; //Cache expires every 5 minutes
$cache_file = "index_cache.php";

//Use Cache if it exists and is not expired
if (file_exists($cache_file) && time() - $cache_expiry_time < filemtime($cache_file)) {
    ob_start('ob_gzhandler'); //Use ob_gzhandler for compression where available
    readfile($cache_file);
	//echo "Using Cache";
    ob_end_flush(); //Flush and turn off output buffering
    die(); 
}

//Cache doesn't exist, or has expired.  Reconstruct page.
ob_start('ob_gzhandler');  //Use ob_gzhandler for compression where available

//==========================================================================================

include_once "loadTeemoTracker.php";

//Make db connection
$connect = mysqli_connect("localhost",SQL_USER,SQL_PASS,SQL_DATABASE);
if (mysqli_connect_errno($connect)){die("Failed to connect to MySQL: " . mysqli_connect_error());}


//Start by getting Champion Data.


//Champion Stats
$query = "SELECT * FROM `champion_stats` WHERE championId = ".TRACKED_CHAMPION;
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));

while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
	if($row['championId'] == TRACKED_CHAMPION){
		$championStats = $row;
	}
}

//Monster Stats
$query = "SELECT * FROM `monster_stats` WHERE championId = ".TRACKED_CHAMPION;
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));

while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
	if($row['championId'] == TRACKED_CHAMPION){
		$monsterStats = $row;
	}
}

//Ward Stats
$query = "SELECT * FROM `ward_stats` WHERE championId = ".TRACKED_CHAMPION;
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));

while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
	if($row['championId'] == TRACKED_CHAMPION){
		$wardStats = $row;
	}
}

//Teemo KDA
$query = "SELECT * FROM `teemo_kda_array`";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));

while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
	$kdaStats[$row['championId']] = $row;
}

//Make KDA Tables

//Kills
uasort($kdaStats, function($a,$b){return strnatcmp($b['kills'],$a['kills']);});

$killTable = "<table class='championTable'><tr><th colspan='2'>Another kill for the tally</th></tr>\n";
foreach($kdaStats as $k => $v){
	$kills = $v['kills'];
	if($kills != 0){
		$killTable .= "<tr><td><img class='champIcon' src='img/champicons/$k.png'/></td><td style='font-family: scrawl;'>$kills</td></tr>\n";
	}
}
$killTable .= "</table>\n";

//Deaths
uasort($kdaStats, function($a,$b){return strnatcmp($b['deaths'],$a['deaths']);});

$deathTable = "<table class='championTable'><tr><th colspan='2'>Don't worry, I'll have my revenge</th></tr>\n";
foreach($kdaStats as $k => $v){
	$deaths = $v['deaths'];
	if($deaths != 0){
		$deathTable .= "<tr><td><img class='champIcon' src='img/champicons/$k.png'/></td><td>$deaths</td></tr>\n";
	}
}
$deathTable .= "</table>\n";


//Assist Ally
uasort($kdaStats, function($a,$b){return strnatcmp($b['assistally'],$a['assistally']);});

$assistAllyTable = "<table class='championTable' style='width:50%;'><tr><th colspan='2'>I let you have that one</th></tr>\n";
foreach($kdaStats as $k => $v){
	$assistally = $v['assistally'];
	if($assistally != 0){
		$assistAllyTable .= "<tr><td><img class='champIcon' src='img/champicons/$k.png'/></td><td>$assistally</td></tr>\n";
	}
}
$assistAllyTable .= "</table>\n";

//Assist Victim
uasort($kdaStats, function($a,$b){return strnatcmp($b['assistvictim'],$a['assistvictim']);});

$assistVictimTable = "<table class='championTable' style='width:50%;'><tr><th colspan='2'>I don't need your blood on my hands</th></tr>\n";
foreach($kdaStats as $k => $v){
	$assistvictim = $v['assistvictim'];
	if($assistvictim != 0){
		$assistVictimTable .= "<tr><td><img class='champIcon' src='img/champicons/$k.png'/></td><td>$assistvictim</td></tr>\n";
	}
}
$assistVictimTable .= "</table>\n";

//Get Last Match Analyzed
$query = "SELECT `matchID`, `bucket` FROM `matches` WHERE `analyzed`=1 ORDER BY `matchID` DESC LIMIT 1";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));
while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
	$lastMatch = $row['matchID'];
	$time = $row['bucket'];
}

?>

<html>
	<head>
		<script src="jquery.js"></script>
		<link rel='stylesheet' type='text/css' href="style.css" />
	</head>
	<body>
		<div class='megawrapper'>
			<div class='sectionwrapper'>
				<div>
					<div class='title'>Teemo URF Stats</div>
				</div>
				<div>
					<div>
						<div class='panel2'>
							<div>Last Match Analyzed<br /><?php echo $lastMatch ?></div>
						</div>
						<div class='panel2'>
							<div>Last Analyzed Time<br /><?php echo date('Y/m/d h:i A', $time) ?></div>
						</div>
					</div>
				</div>
			</div>
			<div class='sectionwrapper'>
				<div id='kdacontainer'>
					<div>
						<div>Total Kills:</div>
						<div><?php echo $championStats['k'] ?></div>
						<?php echo $killTable?>
					</div>
					<div>
						<div>Total Deaths:</div>
						<div><?php echo $championStats['d'] ?></div>
						<?php echo $deathTable?>
					</div>
					<div>
						<div>Total Assists:</div>
						<div><?php echo $championStats['a'] ?></div>
						<?php echo $assistAllyTable.$assistVictimTable ?>
					</div>
				</div>
			</div>
			<div class='sectionwrapper'>
				<div>
					<div class='subtitle'>Heat Maps</div>
					<div>
						<div class='panel2'>
							<div>Kills</div>
							<img style='width:100%' src='killMap.png'/>
						</div>
						<div class='panel2'>
							<div>Deaths</div>
							<img style='width:100%' src='deathMap.png'/>
						</div>
					</div>
				</div>
			</div>
			<div class='sectionwrapper'>
				<div>
					<div class='subtitle'>Kill Chains</div>
					<div>
						<div class='panel2'>
							<div>Double Kills:</div>
							<div><?php echo $championStats['k2'] ?></div>
						</div>
						<div class='panel2'>
							<div>Triple Kills:</div>
							<div><?php echo $championStats['k3'] ?></div>
						</div>
					</div>
					<div>
						<div class='panel2'>
							<div>Quadra Kills:</div>
							<div><?php echo $championStats['k4'] ?></div>
						</div>
						<div class='panel2'>
							<div>Penta Kills:</div>
							<div><?php echo $championStats['k5'] ?></div>
						</div>
					</div>
					<!-- Ultra Kills: <?php echo $championStats['k6'] ?> <br /> -->
				</div>
			</div>
			<div class='sectionwrapper'>
				<div>
					<div class='subtitle'>Damage</div>
					<div>
						<div class='panel1'>
							<div>Damage Dealt:</div>
							<div><?php echo number_format($championStats['damageDealt']) ?></div>
						</div>
					</div>
					<div>
						<div class='panel2'>
							<div>Damage to Champions:</div>
							<div><?php echo number_format($championStats['damageToChampions']) ?></div>
							<div>Enough to kill <?php echo number_format(floor($championStats['damageToChampions']/6895.5)) ?> Mundos with 6 Warmogs Each</div>
						</div>
					</div>
					<div>
						<div class='panel2'>
							<div>Damage Taken:</div>
							<div><?php echo number_format($championStats['damageTaken']) ?></div>
							<div>About <?php echo round($championStats['damageTaken']/10/60/60/24, 0) ?> days spent drinking health potions</div>
						</div>
					</div>
				</div>
			</div>
			<div class='sectionwrapper'>
				<div>
					<div class='subtitle'>Mushrooms</div>
					<!--<img src='http://ddragon.leagueoflegends.com/cdn/5.7.1/img/spell/BantamTrap.png' />-->
					<div>
						<div class='panel1'>
							<div>Mushrooms Placed:</div>
							<div><?php echo number_format($wardStats['mushroomPlaced']) ?></div>
							<div>Can cover about <?php echo number_format(round($wardStats['mushroomPlaced'] * 120*120 / (14990*15100),1)) ?> Summoner's Rifts</div>
						</div>
					</div>
					<!--About 59% of Summoner's Rift is Walkable
					Summoner's Rift is 14990 x 15100
					Shroom is about 120 x 120-->
				</div>
			</div>
			<div class='sectionwrapper'>
				<div>
					<div class='subtitle'>Minions and Monsters</div>
					<div>
						<div class='panel1'>
							<div>Minions Killed:</div>
							<div><?php echo number_format($championStats['minions']) ?></div>
							<div>Around <?php echo round($championStats['minions']/15/60/24, 1) ?> days of perfect last hitting</div>
						</div>
					</div>
				</div>
				<div>
					<div>
						<div class='panel2'>
							<div>Blue Golems:</div>
							<div><?php echo number_format($monsterStats['blue']) ?></div>
						</div>
						<div class='panel2'>
							<div>Red Lizards:</div>
							<div><?php echo number_format($monsterStats['red']) ?></div>
						</div>
					</div>
					<div>
						<div class='panel2'>
							<div>Dragons:</div>
							<div><?php echo number_format($monsterStats['dragon']) ?></div>
						</div>
						<div class='panel2'>
							<div>Barons:</div>
							<div><?php echo number_format($monsterStats['baron']) ?></div>
						</div>
					</div>
				</div>
			</div>
			<div class='sectionwrapper'>
				<div>
					<div class='subtitle'>Wards</div>
					<div>
						<div class='panel3'>
							<div>Sight Wards Placed</div>
							<div><?php echo number_format($wardStats['sightPlaced']) ?></div>
						</div>
						<div class='panel3'>
							<div>Vision Wards Placed</div>
							<div><?php echo number_format($wardStats['visionPlaced']) ?></div>
						</div>
						<div class='panel3'>
							<div>Trinket Wards Placed</div>
							<div><?php echo number_format($wardStats['trinketPlaced'] + $wardStats['trinket2Placed']) ?></div>
						</div>
					</div>
					<div>
						<div class='panel3'>
							<div>Sight Wards Killed</div>
							<div><?php echo number_format($wardStats['sightKilled']) ?></div>
						</div>
						<div class='panel3'>
							<div>Vision Wards Killed</div>
							<div><?php echo number_format($wardStats['visionKilled']) ?></div>
						</div>
						<div class='panel3'>
							<div>Trinket Wards Killed</div>
							<div><?php echo number_format($wardStats['trinketKilled'] + $wardStats['trinket2Killed']) ?></div>
						</div>
						
						
						
					</div>
				</div>
			</div>
			<div class='sectionwrapper'>
				<div>
					<div class='subtitle'>Buildings</div>
					<div>
						<div class='panel2'>
							<div>Towers</div>
							<div><?php echo $championStats['towers'] ?></div>
						</div>
						<div class='panel2'>
							<div>Inhibitors</div>
							<div><?php echo $championStats['inhibitors'] ?></div>
						</div>
					</div>
				</div>
			</div>
			<div class='sectionwrapper'>
				<div>
					<div class='subtitle'>Other Stats</div>
					<div>
						<div class='panel3'>
							<div>Victories</div>
							<div><?php echo $championStats['winner'] ?></div>
						</div>
						<div class='panel3'>
							<div>Losses</div>
							<div><?php echo $championStats['totalMatches'] - $championStats['winner'] ?></div>
						</div>
						<div class='panel3'>
							<div>Win %</div>
							<div><?php echo round($championStats['winner'] / $championStats['totalMatches']*100,1) ?>%</div>
						</div>
					</div>
					<div>
						<div class='panel1'>
							<div>Gold Earned</div>
							<div><?php echo number_format($championStats['gold']) ?></div>
							<div>Equivalent To Idling on Summoners Rift for <?php echo floor($championStats['gold']*10/19/60/60/24) ?> days</div>
						</div>
					</div>
					<div>
						<div class='panel2'>
							<div>First Bloods</div>
							<div><?php echo $championStats['firstblood'] ?></div>
						</div>
						<div class='panel2'>
							<div>First Blood %</div>
							<div><?php echo round($championStats['firstblood'] / $championStats['totalMatches'] * 100,1) ?>%</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>

<?php

//============================================================================================


$fp = fopen($cache_file, 'w');  //open file
fwrite($fp, ob_get_contents()); //write buffer to cache file
fclose($fp); //close file

ob_end_flush(); //clear buffer

?>