<?php
/*

This file generates the heat maps of kills and deaths

*/

defined(HEATMAP_FROM_CRON_UPDATE) or die("This file must be accessed by cron_updateStatistics.php");

require_once "gd-heatmap-master/gd_heatmap.php";

//Make db connection
$connect = mysqli_connect("localhost",SQL_USER,SQL_PASS,SQL_DATABASE);
if (mysqli_connect_errno($connect)){die("Failed to connect to MySQL: " . mysqli_connect_error());}

//==========================================================Get Kill Data
$killData = array();
$query = "SELECT `x`, `y` FROM `kills`";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));

while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
	//convert x from (-120 to 14870) to (0 to 512)
	$x = ($row['x'])*512/(14870);
	//convert y from (-120 to 14980) to (0 to 512)
	$y = ($row['y'])*512/(14980);
	//Flip y for image generation
	$y = 512-$y;
	
	$killData[] = array($x,$y,100);
}

// Config array
$config = array(
  'debug' => FALSE,
  'width' => 512,
  'height' => 512,
  'noc' => 32,
  'r' => 5,
  'dither' => FALSE,
  'format' => 'png',
);

// Create a new heatmap based on the data and the config.
$heatmap = new gd_heatmap($killData, $config);

$heatmap->output('killMap.png');

//Merge with Rift Map
$rift = imagecreatefrompng('img/rift.png');
$heat = imagecreatefrompng('killMap.png');

imagecopymerge($rift, $heat, 0, 0, 0, 0, 512, 512, 80); 

imagepng($rift,'killMap.png');

//==========================================================Get Death Data
$deathData = array();
$query = "SELECT `x`, `y` FROM `deaths`";
$result = mysqli_query($connect,$query);
$result or die('Error: ' . mysqli_error($connect));

while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
	//convert x from (-120 to 14870) to (0 to 512)
	$x = ($row['x'])*512/(14870);
	//convert y from (-120 to 14980) to (0 to 512)
	$y = ($row['y'])*512/(14980);
	//Flip y for image generation
	$y = 512-$y;
	
	$deathData[] = array($x,$y,100);
}

// Config array with all the available options. See the constructor's doc block
// for explanations.
$config = array(
  'debug' => FALSE,
  'width' => 512,
  'height' => 512,
  'noc' => 32,
  'r' => 5,
  'dither' => FALSE,
  'format' => 'png',
);

// Create a new heatmap based on the data and the config.
$heatmap = new gd_heatmap($deathData, $config);

$heatmap->output('deathMap.png');

//Merge with Rift Map
$rift = imagecreatefrompng('img/rift.png');
$heat = imagecreatefrompng('deathMap.png');

imagecopymerge($rift, $heat, 0, 0, 0, 0, 512, 512, 80); 

imagepng($rift,'deathMap.png');

//Done
//echo "OK: Done";