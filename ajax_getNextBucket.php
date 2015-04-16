<?php 

include_once "loadTeemoTracker.php";

$riotAPI = new RiotAPI();

echo $riotAPI->getNextBucket();

//Needed in case nothing is output
echo " ";
?>