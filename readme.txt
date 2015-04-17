Teemo URF Tracker

Summarizes Match Stats for Teemo in League of Legends URF matches.
Created for the Riot API Challenge.

Demo at http://www.regory.com/teemo/

The Tracker Uses 
- PHP (Tested with 5.5.22, but nothing too fancy is used so earlier versions likely work)
- SQL (Tested with MySQL 10.0.17-MariaDB, but other versions should work)
- JQuery - https://jquery.com (v2.1.3, unmodified, included)
- gd-heatmap - https://github.com/xird/gd-heatmap (modified, included)


Setting Up The Tracker

1. Make an SQL database, and use database.sql to generate the tables.

2. Fill in the settings in loadTeemoTracker.php

3a. Use batchUpdate.php to retrieve data from the Riot API in bulk.
3b. Delete or rename batchUpdate.php and ajax_batchUpdate.php so that bad people can't endlessly spam the Riot API with your key.

4. Run cron_updateStatistics.php to summarize the retrieved data.  If desired, set a cron to run this (limited to once every 5 minutes)

Notes: 
-Don't run cron_updateStatistics.php while batchUpdate.php is running.  Nothing bad will happen, but you will double your API usage, which can put you over your call limit if MIN_SECONDS_BETWEEN_CALLS is less than 2x your actual limit.  Similarly, don't have multiple copies of batchUpdate.php running at once.