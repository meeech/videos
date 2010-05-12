-- phpMyAdmin SQL Dump
-- version 3.2.5
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 23, 2010 at 12:49 PM
-- Server version: 5.1.45
-- PHP Version: 5.3.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES latin1 */;

--
-- Database: `videos5`
--

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `path` text COLLATE latin1_swedish_ci NOT NULL,
  `video_codec` tinytext COLLATE latin1_swedish_ci,
  `audio_codec` tinytext COLLATE latin1_swedish_ci,
  `html5_ready` set('browser','mobile','ipad','android') COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  `resolution` tinytext COLLATE latin1_swedish_ci,
  `queued_for_encode` tinytext COLLATE latin1_swedish_ci NOT NULL,
  `found` enum('yes','no') COLLATE latin1_swedish_ci NOT NULL DEFAULT 'yes',
  `rating` enum('G','PG','PG-13','R','NC-17','TV-Y','TV-Y7','TV-G','TV-PG','TV-14','TV-MA') COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `files`
--


-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `name` tinytext COLLATE latin1_swedish_ci NOT NULL,
  `value` text COLLATE latin1_swedish_ci NOT NULL,
  PRIMARY KEY (`name`(255))
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`name`, `value`) VALUES
('paths', '/var/hda/files/movies/'),
('encode_command', '/bin/nice /usr/bin/HandBrakeCLI -L -i $input -o $output -e x264 -q 20.0 -a 1 -E faac -B 160 -6 dpl2 -R 48 -D 0.0 -f mp4 -X 720 -Y 480 --loose-anamorphic -m -x cabac=0:ref=2:me=umh:bframes=0:8x8dct=0:trellis=0:subme=6'),
('encode_extension', 'm4v'),
('video_extensions', 'm4v,mp4,ts,mov,divx,xvid,vob,m2v,avi,mpg,mpeg,mkv,mt2,m2ts');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` tinytext COLLATE latin1_swedish_ci NOT NULL,
  `password` tinytext COLLATE latin1_swedish_ci,
  `allowed_ratings` set('Unrated','G','PG','PG-13','R','NC-17','TV-Y','TV-Y7','TV-G','TV-PG','TV-14','TV-MA') COLLATE latin1_swedish_ci NOT NULL,
  `is_admin` enum('yes','no') COLLATE latin1_swedish_ci NOT NULL DEFAULT 'no',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `password`, `allowed_ratings`, `is_admin`) VALUES
(1, 'Encoder', '1234', 'Unrated,G,PG,PG-13,R,NC-17,TV-Y,TV-Y7,TV-G,TV-PG,TV-14,TV-MA', 'yes'),
(2, 'Viewer', '1234', 'Unrated,G,PG,PG-13,R,NC-17,TV-Y,TV-Y7,TV-G,TV-PG,TV-14,TV-MA', 'no'),
(3, 'Young Viewer', NULL, 'G,PG,TV-Y,TV-Y7,TV-G,TV-PG', 'no'),
(4, 'Teenage Viewer', NULL, 'G,PG,PG-13,TV-Y,TV-Y7,TV-G,TV-PG,TV-14', 'no'),
(5, 'Rater', '1234', 'Unrated', 'yes');

-- --------------------------------------------------------

--
-- Table structure for table `dir_ratings`
--

CREATE TABLE `dir_ratings` (
  `path` text COLLATE latin1_swedish_ci NOT NULL,
  `rating` enum('G','PG','PG-13','R','NC-17','TV-Y','TV-Y7','TV-G','TV-PG','TV-14','TV-MA') COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY ( `path` ( 255 ) )
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `dir_ratings`
--


