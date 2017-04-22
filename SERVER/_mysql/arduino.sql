-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 22, 2017 at 03:53 PM
-- Server version: 5.7.17-0ubuntu0.16.04.2
-- PHP Version: 5.6.30-10+deb.sury.org~xenial+2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `arduino`
--

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE `class` (
  `class` varchar(10) COLLATE utf8_czech_ci NOT NULL,
  `hardware` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  `description` varchar(30) COLLATE utf8_czech_ci NOT NULL,
  `unit` varchar(10) COLLATE utf8_czech_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `display`
--

CREATE TABLE `display` (
  `sensor` varchar(5) COLLATE utf8_czech_ci NOT NULL,
  `class` varchar(10) COLLATE utf8_czech_ci NOT NULL,
  `suffix` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  `color` varchar(7) COLLATE utf8_czech_ci NOT NULL,
  `graph` char(3) COLLATE utf8_czech_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `graph`
--

CREATE TABLE `graph` (
  `graph` char(3) COLLATE utf8_czech_ci NOT NULL,
  `description` varchar(30) COLLATE utf8_czech_ci NOT NULL,
  `unit` varchar(10) COLLATE utf8_czech_ci NOT NULL,
  `from` int(11) DEFAULT NULL,
  `to` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `measure`
--

CREATE TABLE `measure` (
  `id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sensor` varchar(5) COLLATE utf8_czech_ci NOT NULL,
  `field` int(11) NOT NULL,
  `class` varchar(10) COLLATE utf8_czech_ci NOT NULL,
  `value1` float DEFAULT NULL,
  `value2` float DEFAULT NULL,
  `value3` float DEFAULT NULL,
  `text1` varchar(32) COLLATE utf8_czech_ci DEFAULT NULL,
  `text2` varchar(32) COLLATE utf8_czech_ci DEFAULT NULL,
  `text3` varchar(32) COLLATE utf8_czech_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `program`
--

CREATE TABLE `program` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(50) COLLATE utf8_czech_ci NOT NULL,
  `color` varchar(10) COLLATE utf8_czech_ci NOT NULL,
  `priority` int(11) NOT NULL,
  `production` tinyint(1) NOT NULL,
  `sun` tinyint(1) NOT NULL,
  `mon` tinyint(1) NOT NULL,
  `tue` tinyint(1) NOT NULL,
  `wed` tinyint(1) NOT NULL,
  `thu` tinyint(1) NOT NULL,
  `fri` tinyint(1) NOT NULL,
  `sat` tinyint(1) NOT NULL,
  `from_time` time NOT NULL,
  `to_time` time NOT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `min` float DEFAULT NULL,
  `max` float DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sensor`
--

CREATE TABLE `sensor` (
  `sensor` varchar(5) COLLATE utf8_czech_ci NOT NULL,
  `comment` varchar(30) COLLATE utf8_czech_ci NOT NULL,
  `color` varchar(7) COLLATE utf8_czech_ci NOT NULL,
  `color1` varchar(7) COLLATE utf8_czech_ci NOT NULL,
  `color2` varchar(7) COLLATE utf8_czech_ci NOT NULL,
  `color3` varchar(7) COLLATE utf8_czech_ci NOT NULL,
  `color4` varchar(7) COLLATE utf8_czech_ci NOT NULL,
  `color5` varchar(7) COLLATE utf8_czech_ci NOT NULL,
  `graph1` char(1) COLLATE utf8_czech_ci NOT NULL,
  `graph2` char(1) COLLATE utf8_czech_ci NOT NULL,
  `graph3` char(1) COLLATE utf8_czech_ci NOT NULL,
  `graph4` char(1) COLLATE utf8_czech_ci NOT NULL,
  `graph5` char(1) COLLATE utf8_czech_ci NOT NULL,
  `suffix1` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  `suffix2` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  `suffix3` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  `suffix4` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  `suffix5` varchar(20) COLLATE utf8_czech_ci NOT NULL,
  `visible` tinyint(1) NOT NULL,
  `implicit` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`class`);

--
-- Indexes for table `display`
--
ALTER TABLE `display`
  ADD PRIMARY KEY (`sensor`,`class`);

--
-- Indexes for table `graph`
--
ALTER TABLE `graph`
  ADD PRIMARY KEY (`graph`);

--
-- Indexes for table `measure`
--
ALTER TABLE `measure`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `measure` (`timestamp`,`sensor`,`field`);

--
-- Indexes for table `program`
--
ALTER TABLE `program`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sensor`
--
ALTER TABLE `sensor`
  ADD PRIMARY KEY (`sensor`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `measure`
--
ALTER TABLE `measure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `program`
--
ALTER TABLE `program`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
  
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;