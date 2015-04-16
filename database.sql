-- phpMyAdmin SQL Dump
-- version 4.0.10.7
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 15, 2015 at 09:13 PM
-- Server version: 10.0.17-MariaDB
-- PHP Version: 5.4.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `regoryco_teemo`
--

-- --------------------------------------------------------

--
-- Table structure for table `assists`
--

CREATE TABLE IF NOT EXISTS `assists` (
  `matchID` int(12) NOT NULL,
  `pID` int(2) NOT NULL,
  `timestamp` int(9) NOT NULL,
  `killerChampion` int(4) NOT NULL,
  `victimChampion` int(4) NOT NULL,
  `x` int(6) NOT NULL,
  `y` int(6) NOT NULL,
  UNIQUE KEY `matchID` (`matchID`,`pID`,`timestamp`,`killerChampion`,`victimChampion`,`x`,`y`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE IF NOT EXISTS `buildings` (
  `matchID` int(12) NOT NULL,
  `pID` int(2) NOT NULL,
  `timestamp` int(9) NOT NULL,
  `killerChampion` int(4) NOT NULL,
  `lane` varchar(50) NOT NULL,
  `building` varchar(50) NOT NULL,
  `tower` varchar(50) NOT NULL,
  UNIQUE KEY `matchID` (`matchID`,`pID`,`timestamp`,`killerChampion`,`lane`,`building`,`tower`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `champion_stats`
--

CREATE TABLE IF NOT EXISTS `champion_stats` (
  `championId` int(4) NOT NULL,
  `winner` int(10) NOT NULL,
  `k` int(12) NOT NULL,
  `d` int(12) NOT NULL,
  `a` int(12) NOT NULL,
  `k2` int(8) NOT NULL,
  `k3` int(7) NOT NULL,
  `k4` int(6) NOT NULL,
  `k5` int(5) NOT NULL,
  `k6` int(4) NOT NULL,
  `damageDealt` int(16) NOT NULL,
  `damageToChampions` int(16) NOT NULL,
  `damageTaken` int(16) NOT NULL,
  `minions` int(10) NOT NULL,
  `gold` int(16) NOT NULL,
  `firstblood` int(8) NOT NULL,
  `inhibitors` int(10) NOT NULL,
  `towers` int(11) NOT NULL,
  `wardsPlaced` int(16) NOT NULL,
  `wardsKilled` int(10) NOT NULL,
  `totalMatches` int(8) NOT NULL,
  PRIMARY KEY (`championId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `deaths`
--

CREATE TABLE IF NOT EXISTS `deaths` (
  `matchID` int(12) NOT NULL,
  `pID` int(2) NOT NULL,
  `timestamp` int(9) NOT NULL,
  `killerChampion` int(4) NOT NULL,
  `x` int(6) NOT NULL,
  `y` int(6) NOT NULL,
  UNIQUE KEY `matchID` (`matchID`,`pID`,`timestamp`,`killerChampion`,`x`,`y`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



-- --------------------------------------------------------

--
-- Table structure for table `general_info`
--

CREATE TABLE IF NOT EXISTS `general_info` (
  `key` varchar(100) NOT NULL,
  `value` varchar(100) NOT NULL,
  PRIMARY KEY (`key`),
  UNIQUE KEY `key` (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `general_info`
--

INSERT INTO `general_info` (`key`, `value`) VALUES
('Last Bucket', '1427865600'),
('Last Statistics Update', '0'),
('Update Key', '0');

-- --------------------------------------------------------

--
-- Table structure for table `kills`
--

CREATE TABLE IF NOT EXISTS `kills` (
  `matchID` int(12) NOT NULL,
  `pID` int(2) NOT NULL,
  `timestamp` int(9) NOT NULL,
  `victimChampion` int(4) NOT NULL,
  `x` int(6) NOT NULL,
  `y` int(6) NOT NULL,
  UNIQUE KEY `matchID` (`matchID`,`pID`,`timestamp`,`victimChampion`,`x`,`y`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE IF NOT EXISTS `matches` (
  `matchID` int(12) NOT NULL,
  `bucket` int(12) DEFAULT NULL,
  `checked` tinyint(1) NOT NULL DEFAULT '0',
  `hasTrackedChampion` tinyint(1) NOT NULL DEFAULT '0',
  `analyzed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`matchID`),
  UNIQUE KEY `matchID` (`matchID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



-- --------------------------------------------------------

--
-- Table structure for table `match_stats`
--

CREATE TABLE IF NOT EXISTS `match_stats` (
  `matchId` int(12) NOT NULL,
  `pID` int(2) NOT NULL,
  `team` int(3) NOT NULL,
  `championId` int(4) NOT NULL,
  `rank` varchar(20) NOT NULL,
  `winner` tinyint(1) NOT NULL,
  `k` int(4) NOT NULL,
  `d` int(4) NOT NULL,
  `a` int(4) NOT NULL,
  `k2` int(3) NOT NULL,
  `k3` int(3) NOT NULL,
  `k4` int(3) NOT NULL,
  `k5` int(3) NOT NULL,
  `k6` int(3) NOT NULL,
  `damageDealt` int(8) NOT NULL,
  `damageToChampions` int(8) NOT NULL,
  `damageTaken` int(8) NOT NULL,
  `minions` int(5) NOT NULL,
  `gold` int(8) NOT NULL,
  `firstblood` tinyint(1) NOT NULL,
  `inhibitors` int(1) NOT NULL,
  `towers` int(2) NOT NULL,
  `wardsPlaced` int(4) NOT NULL,
  `wardsKilled` int(4) NOT NULL,
  PRIMARY KEY (`matchId`,`pID`),
  UNIQUE KEY `matchId` (`matchId`,`pID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



-- --------------------------------------------------------

--
-- Table structure for table `monsters`
--

CREATE TABLE IF NOT EXISTS `monsters` (
  `matchID` int(12) NOT NULL,
  `pID` int(2) NOT NULL,
  `timestamp` int(9) NOT NULL,
  `killerChampion` int(4) NOT NULL,
  `monster` varchar(50) NOT NULL,
  `x` int(6) NOT NULL,
  `y` int(6) NOT NULL,
  UNIQUE KEY `matchID` (`matchID`,`pID`,`timestamp`,`killerChampion`,`monster`,`x`,`y`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



-- --------------------------------------------------------

--
-- Table structure for table `monster_stats`
--

CREATE TABLE IF NOT EXISTS `monster_stats` (
  `championId` int(4) NOT NULL,
  `blue` int(10) NOT NULL,
  `red` int(10) NOT NULL,
  `dragon` int(10) NOT NULL,
  `baron` int(10) NOT NULL,
  PRIMARY KEY (`championId`),
  UNIQUE KEY `championId` (`championId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



-- --------------------------------------------------------

--
-- Table structure for table `teemo_kda_array`
--

CREATE TABLE IF NOT EXISTS `teemo_kda_array` (
  `championId` int(4) NOT NULL,
  `kills` int(10) NOT NULL,
  `deaths` int(10) NOT NULL,
  `assistally` int(10) NOT NULL,
  `assistvictim` int(10) NOT NULL,
  PRIMARY KEY (`championId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



-- --------------------------------------------------------

--
-- Table structure for table `ward_kill`
--

CREATE TABLE IF NOT EXISTS `ward_kill` (
  `matchID` int(12) NOT NULL,
  `pID` int(2) NOT NULL,
  `timestamp` int(9) NOT NULL,
  `killerChampion` int(4) NOT NULL,
  `wardType` varchar(50) NOT NULL,
  UNIQUE KEY `matchID` (`matchID`,`pID`,`timestamp`,`killerChampion`,`wardType`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



-- --------------------------------------------------------

--
-- Table structure for table `ward_place`
--

CREATE TABLE IF NOT EXISTS `ward_place` (
  `matchID` int(12) NOT NULL,
  `pID` int(2) NOT NULL,
  `timestamp` int(9) NOT NULL,
  `champion` int(4) NOT NULL,
  `wardType` varchar(50) NOT NULL,
  UNIQUE KEY `matchID` (`matchID`,`pID`,`timestamp`,`champion`,`wardType`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



-- --------------------------------------------------------

--
-- Table structure for table `ward_stats`
--

CREATE TABLE IF NOT EXISTS `ward_stats` (
  `championId` int(4) NOT NULL,
  `sightPlaced` int(10) NOT NULL,
  `visionPlaced` int(10) NOT NULL,
  `trinketPlaced` int(10) NOT NULL,
  `trinket2Placed` int(10) NOT NULL,
  `mushroomPlaced` int(10) NOT NULL,
  `sightKilled` int(10) NOT NULL,
  `visionKilled` int(10) NOT NULL,
  `trinketKilled` int(10) NOT NULL,
  `trinket2Killed` int(10) NOT NULL,
  `mushroomKilled` int(10) NOT NULL,
  PRIMARY KEY (`championId`),
  UNIQUE KEY `championId` (`championId`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
