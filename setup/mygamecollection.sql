-- phpMyAdmin SQL Dump
-- version 5.0.0
-- https://www.phpmyadmin.net/
--
-- Host: sparkling-resonance.26291bitcoin.dbinf.buildingtogether.io
-- Generation Time: Aug 05, 2021 at 11:22 PM
-- Server version: 10.2.30-MariaDB
-- PHP Version: 7.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `26291bitcoin`
--

-- --------------------------------------------------------

--
-- Table structure for table `mygamecollection`
--

CREATE TABLE `mygamecollection` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `platform` enum('Xbox 360','Xbox One','Android','Windows','Web','Xbox Series X|S') NOT NULL,
  `backcompat` int(11) DEFAULT NULL,
  `kinect_required` int(11) DEFAULT NULL,
  `peripheral_required` int(11) DEFAULT NULL,
  `online_multiplayer` int(11) DEFAULT NULL,
  `completion_perc` int(11) NOT NULL DEFAULT 0,
  `completion_estimate` varchar(20) DEFAULT NULL,
  `hours_played` int(11) NOT NULL DEFAULT 0,
  `achievements_won` int(11) NOT NULL DEFAULT 0,
  `achievements_total` int(11) NOT NULL,
  `gamerscore_won` int(11) NOT NULL DEFAULT 0,
  `gamerscore_total` int(11) NOT NULL,
  `ta_score` int(11) DEFAULT NULL,
  `ta_total` int(11) DEFAULT NULL,
  `dlc` int(11) NOT NULL DEFAULT 0,
  `dlc_completion` int(11) NOT NULL DEFAULT 0,
  `completion_date` datetime DEFAULT NULL,
  `site_rating` float DEFAULT NULL,
  `format` varchar(20) DEFAULT NULL,
  `status` enum('available','delisted','region-locked','sale') NOT NULL,
  `purchased_price` float DEFAULT NULL,
  `current_price` float DEFAULT NULL,
  `regular_price` float DEFAULT NULL,
  `shortlist_order` int(11) DEFAULT NULL,
  `walkthrough_url` varchar(120) DEFAULT NULL,
  `game_url` varchar(120) DEFAULT NULL,
  `last_modified` datetime NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mygamecollection`
--
ALTER TABLE `mygamecollection`
  ADD PRIMARY KEY (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;