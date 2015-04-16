Teemo URF Tracker

Summarizes Match Stats for Teemo in League of Legends URF matches.
Created for the Riot API Challenge.

Setting Up The Tracker

1. Make an SQL database, and use database.sql to generate the tables.
2. Fill in the settings in loadTeemoTracker.php
3. Run batchUpdate.php to retrieve data from the Riot API in bulk.
4. Run cron_updateStatistics.php to summarize the retrieved data.  If desired, set a cron to run this (limited to once every 5 minutes)