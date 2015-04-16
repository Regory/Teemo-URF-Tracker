<?php 

include_once "loadTeemoTracker.php";

$riotAPI = new RiotAPI();

echo $riotAPI->analyzeNextMatch();


//Needed in case nothing is output
echo " ";
?>