<?php 

include_once "loadTeemoTracker.php";

$riotAPI = new RiotAPI();

switch ($_POST['type']){
	case 'bucket':
		echo $riotAPI->getNextBucket();
		break;
	case 'check':
		echo $riotAPI->checkNextMatch();
		break;
	case 'analyze':
		echo $riotAPI->analyzeNextMatch();
		break;
	case 'statistics':
		echo $riotAPI->updateStatistics();
		break;
	default:
		die("Command Not Recognized");
		break;
}


//Needed in case nothing is output
echo " ";
?>