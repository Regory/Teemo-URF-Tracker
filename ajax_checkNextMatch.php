<?php 

include_once "loadTeemoTracker.php";

$riotAPI = new RiotAPI();

echo $riotAPI->checkNextMatch();

//Needed in case nothing is output
echo " ";
?>