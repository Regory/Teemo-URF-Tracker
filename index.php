<?php 
/*

The Index Page!
This is the page people look at

*/

include_once "loadTeemoTracker.php";

//Make db connection
$connect = mysqli_connect("localhost",SQL_USER,SQL_PASS,SQL_DATABASE);
if (mysqli_connect_errno($connect)){die("Failed to connect to MySQL: " . mysqli_connect_error());}

//==========================================================================
//Page Caching

//First, get time of last statistics update completion
$query = "SELECT `value` from `general_info` WHERE `key`='Last Statistics Update Complete';";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));
while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
	$lastUpdate = $row['value'];
}

$cache_file = "index_cache.php";

//Use Cache if it exists and is not expired
if (file_exists($cache_file) and $lastUpdate < filemtime($cache_file)) {
    ob_start('ob_gzhandler'); //Use ob_gzhandler for compression where available
    readfile($cache_file);
	echo "<!-- Using Cache of Data From $lastUpdate -->";
    ob_end_flush(); //Flush and turn off output buffering
    die(); 
}

//Cache doesn't exist, or has expired.  Reconstruct page.
ob_start('ob_gzhandler');  //Use ob_gzhandler for compression where available

//==========================================================================================


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
		$wardStats[$row['winner']] = $row;
	}
}

//Tier Stats
$query = "SELECT * FROM `tier_stats` WHERE championId = ".TRACKED_CHAMPION;
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));

while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
	if($row['championId'] == TRACKED_CHAMPION){
		$tierStats[$row['tier']] = $row;
	}
}

$sortedTierStats [] = $tierStats['MASTER'];
$sortedTierStats [] = $tierStats['DIAMOND'];
$sortedTierStats [] = $tierStats['PLATINUM'];
$sortedTierStats [] = $tierStats['GOLD'];
$sortedTierStats [] = $tierStats['SILVER'];
$sortedTierStats [] = $tierStats['BRONZE'];
$sortedTierStats [] = $tierStats['UNRANKED'];


$tierTable = <<<EOD
<table class='tiertable'>
	<tr>
		<th>Tier</th>
		<th>Wins</th>
		<th>Losses</th>
		<th>Win %</th>
		<th>Kills</th>
		<th>Deaths</th>
		<th>Assists</th>
	</tr>
EOD;
foreach($sortedTierStats as $k=>$v){
	$tierTable .= "<tr>
		<th>". $v['tier'] ."</th>
		<th>". $v['wins'] ."</th>
		<th>". $v['losses'] ."</th>
		<th>". round($v['wins']/($v['wins']+$v['losses'])*100) ."%</th>
		<th>". $v['k'] ."</th>
		<th>". $v['d'] ."</th>
		<th>". $v['a'] ."</th>
	</tr>";
}


$tierTable .= "</table>";



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
							<div>Last Update<br /><?php echo date('Y/m/d h:i A',$lastUpdate) ?></div>
						</div>
						<div class='panel2'>
							<div>Last Analyzed Match<br /><?php echo date('Y/m/d h:i A', $time) ?></div>
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
							<div>Double Kills</div>
							<div><?php echo $championStats['k2'] ?></div>
						</div>
						<div class='panel2'>
							<div>Triple Kills</div>
							<div><?php echo $championStats['k3'] ?></div>
						</div>
					</div>
					<div>
						<div class='panel2'>
							<div>Quadra Kills</div>
							<div><?php echo $championStats['k4'] ?></div>
						</div>
						<div class='panel2'>
							<div>Penta Kills</div>
							<div><?php echo $championStats['k5'] ?></div>
						</div>
					</div>
					<?php if($championStats['k6'] > 0){ ?>
					<div>
						<div class='panel1'>
							<div>Ultra Kills</div>
							<div><?php echo $championStats['k6'] ?></div>
						</div>
					</div>
					<?php } ?>
				</div>
			</div>
			<div class='sectionwrapper'>
				<div>
					<div class='subtitle'>Damage</div>
					<div>
						<div class='panel1'>
							<div>Damage Dealt:</div>
							<div><?php echo number_format($championStats['damageDealt']) ?></div>
							<div>Equivalent to firing the Fountain Laser for <?php echo round($championStats['damageDealt']/2000/60/60,0) ?> hours</div>
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
			<?php if(TRACKED_CHAMPION == 17){ //Mushrooms Placed is Teemo Only ?>
			<div class='sectionwrapper'>
				<div>
					<div class='subtitle'>Mushrooms</div>
					<!--<img src='http://ddragon.leagueoflegends.com/cdn/5.7.1/img/spell/BantamTrap.png' />-->
					<div>
						<div class='panel1'>
							<div>Mushrooms Placed:</div>
							<div><?php $mushrooms = $wardStats[0]['mushroomPlaced'] + $wardStats[1]['mushroomPlaced']; echo number_format($mushrooms) ?></div>
							<div>Enough to completely cover Summoner's Rift about <?php echo number_format(round($mushrooms * 120*120 / (14990*15100),1)) ?> times</div>
						</div>
					</div>
					<!--
						About 59% of Summoner's Rift is Walkable
						Summoner's Rift is 14990 x 15100
						Shroom is about 120 x 120
					-->
				</div>
			</div>
			<?php } ?>
			<div class='sectionwrapper'>
				<div>
					<div class='subtitle'>Minions and Monsters</div>
					<div>
						<div class='panel1'>
							<div>Minions Killed:</div>
							<div><?php echo number_format($championStats['minions']) ?></div>
							<div>Around <?php echo round($championStats['minions']/13/60/24, 0) ?> days of perfect last hitting</div>
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
					<div class='subtitle'>Wards Placed</div>
					<div class='wardwins'>
						<div class='panel3'>
							<div>Sight Wards Placed in Wins</div>
							<div><?php echo number_format($wardStats[1]['sightPlaced']) ?></div>
						</div>
						<div class='panel3'>
							<div>Vision Wards Placed in Wins</div>
							<div><?php echo number_format($wardStats[1]['visionPlaced']) ?></div>
						</div>
						<div class='panel3'>
							<div>Trinket Wards Placed in Wins</div>
							<div><?php echo number_format($wardStats[1]['trinketPlaced'] + $wardStats[1]['trinket2Placed']) ?></div>
						</div>
					</div>
					<div class='wardlosses'>
						<div class='panel3'>
							<div>Sight Wards Placed in Losses</div>
							<div><?php echo number_format($wardStats[0]['sightPlaced']) ?></div>
						</div>
						<div class='panel3'>
							<div>Vision Wards Placed in Losses</div>
							<div><?php echo number_format($wardStats[0]['visionPlaced']) ?></div>
						</div>
						<div class='panel3'>
							<div>Trinket Wards Placed in Losses</div>
							<div><?php echo number_format($wardStats[0]['trinketPlaced'] + $wardStats[0]['trinket2Placed']) ?></div>
						</div>
					</div>
				</div>
			</div>
			<div class='sectionwrapper'>
				<div>
					<div class='subtitle'>Wards Destroyed</div>
					<div class='wardwins'>
						<div class='panel3'>
							<div>Sight Wards Killed in Wins</div>
							<div><?php echo number_format($wardStats[1]['sightKilled']) ?></div>
						</div>
						<div class='panel3'>
							<div>Vision Wards Killed in Wins</div>
							<div><?php echo number_format($wardStats[1]['visionKilled']) ?></div>
						</div>
						<div class='panel3'>
							<div>Trinket Wards Killed in Wins</div>
							<div><?php echo number_format($wardStats[1]['trinketKilled'] + $wardStats[1]['trinket2Killed']) ?></div>
						</div>
						
					</div>
					<div class='wardlosses'>
						<div class='panel3'>
							<div>Sight Wards Killed in Losses</div>
							<div><?php echo number_format($wardStats[0]['sightKilled']) ?></div>
						</div>
						<div class='panel3'>
							<div>Vision Wards Killed in Losses</div>
							<div><?php echo number_format($wardStats[0]['visionKilled']) ?></div>
						</div>
						<div class='panel3'>
							<div>Trinket Wards Killed in Losses</div>
							<div><?php echo number_format($wardStats[0]['trinketKilled'] + $wardStats[0]['trinket2Killed']) ?></div>
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
							<div><?php echo number_format($championStats['towers']) ?></div>
						</div>
						<div class='panel2'>
							<div>Inhibitors</div>
							<div><?php echo number_format($championStats['inhibitors']) ?></div>
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
							<div><?php echo number_format($championStats['winner']) ?></div>
						</div>
						<div class='panel3'>
							<div>Losses</div>
							<div><?php echo number_format($championStats['totalMatches'] - $championStats['winner']) ?></div>
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
							<div><?php echo number_format($championStats['firstblood']) ?></div>
						</div>
						<div class='panel2'>
							<div>First Blood %</div>
							<div><?php echo round($championStats['firstblood'] / $championStats['totalMatches'] * 100,1) ?>%</div>
						</div>
					</div>
				</div>
			</div>
			<div class='sectionwrapper'>
				<div>
					<div class='subtitle'>Tier Stats</div>
					<div>
						<div class='panel1'>
							<div><?php echo $tierTable ?></div>
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