<?php
/*
	This file loads various components of Teemo Tracker
*/

//Allows us to test that Teemo Tracker was loaded
define("TEEMO_TRACKER_LOADED",true);

//Load custom classes used in Teemo Tracker
require_once "classes.php"; 

//STATIC VARIABLES
{
	//api
	define("MIN_SECONDS_BETWEEN_CALLS", 2); //Minimum number of seconds between API calls (Note: demo limit ~1.2)
	define("RIOT_API_KEY", "key"); //Riot API Key
	define("SERVER", "na"); //Server
	
	//tracker settings
	define("TRACKED_CHAMPION",17); // The champion you want to track.  17 = Teemo
	
	//sql
	define("SQL_DATABASE","db"); //The SQL Database
	define("SQL_USER",""); //SQL Username
	define("SQL_PASS",""); //SQL Password

}



?>