Teemo URF Tracker

Summarizes Match Stats for Teemo in League of Legends URF matches.
Created for the Riot API Challenge.

Demo at http://www.regory.com/teemo/

Setting Up The Tracker

1a. Make an SQL database, and use database.sql to generate the tables.
1b. Fill in the settings in loadTeemoTracker.php

2a. Use batchUpdate.php to retrieve data from the Riot API in bulk.
2b. Delete or rename batchUpdate.php and ajax_batchUpdate.php so that bad people can't endlessly spam the Riot API with your key.

3. Run cron_updateStatistics.php to summarize the retrieved data.  If desired, set a cron to run this (limited to once every 5 minutes)
Note: Don't run cron_updateStatistics.php while batchUpdate.php is running.  Nothing bad will happen, but you will double your API usage, which can put you over your call limit if MIN_SECONDS_BETWEEN_CALLS is less than 2x your actual limit.