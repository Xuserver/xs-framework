-- Adminer 4.7.1 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `auth_permission`;
CREATE TABLE `auth_permission` (
  `id_permission` int(11) NOT NULL AUTO_INCREMENT,
  `fk_privilege` int(11) NOT NULL,
  `fk_profile` int(11) NOT NULL,
  `can_create` tinyint(1) NOT NULL,
  `can_read` tinyint(1) NOT NULL,
  `can_update` tinyint(1) NOT NULL,
  `can_delete` tinyint(1) NOT NULL,
  PRIMARY KEY (`id_permission`),
  UNIQUE KEY `profile_privilege` (`fk_privilege`,`fk_profile`) USING BTREE,
  KEY `fk_profile` (`fk_profile`),
  CONSTRAINT `auth_permission_ibfk_1` FOREIGN KEY (`fk_profile`) REFERENCES `auth_profile` (`id_profile`) ON DELETE CASCADE,
  CONSTRAINT `auth_permission_ibfk_2` FOREIGN KEY (`fk_privilege`) REFERENCES `auth_privilege` (`id_privilege`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `auth_permission` (`id_permission`, `fk_privilege`, `fk_profile`, `can_create`, `can_read`, `can_update`, `can_delete`) VALUES
(1,	1,	1,	0,	1,	0,	0),
(2,	2,	1,	0,	1,	0,	1),
(3,	3,	1,	1,	1,	1,	1),
(4,	4,	1,	1,	1,	0,	1),
(5,	5,	1,	1,	1,	1,	1);

DROP TABLE IF EXISTS `auth_privilege`;
CREATE TABLE `auth_privilege` (
  `id_privilege` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  PRIMARY KEY (`id_privilege`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `auth_privilege` (`id_privilege`, `code`) VALUES
(1,	'auth'),
(2,	'auth-permission-class'),
(3,	'auth-privilege-class'),
(4,	'auth-profile-class'),
(5,	'auth-user-class');

DROP TABLE IF EXISTS `auth_profile`;
CREATE TABLE `auth_profile` (
  `id_profile` int(11) NOT NULL AUTO_INCREMENT,
  `profile` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT 'new profile',
  `description` text COLLATE latin1_general_ci,
  `may_create` tinyint(1) DEFAULT '0',
  `may_read` tinyint(1) DEFAULT '0',
  `may_update` tinyint(1) DEFAULT '0',
  `may_delete` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id_profile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `auth_profile` (`id_profile`, `profile`, `description`, `may_create`, `may_read`, `may_update`, `may_delete`) VALUES
(1,	'administrator',	'profil d\'administration du système. donne droit à l\'accès sur tous les objets et toutes les fonctionnalités',	1,	1,	1,	1),
(2,	'Chef de Projet',	'Profil chef de projet',	0,	1,	0,	0),
(3,	'visitor',	'profile visiteur',	0,	0,	0,	0);

DROP TABLE IF EXISTS `auth_user`;
CREATE TABLE `auth_user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `password` varchar(255) COLLATE latin1_general_ci DEFAULT NULL,
  `terms_conditions` tinyint(1) DEFAULT NULL,
  `my_firstname` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `my_lastname` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `my_photo` blob,
  `auth_profile` int(11) DEFAULT '3',
  `auth_locked` tinyint(1) NOT NULL DEFAULT '1',
  `auth_logins` float NOT NULL DEFAULT '0',
  `auth_credits` float(11,0) NOT NULL,
  `date_create` date NOT NULL DEFAULT '2008-01-01',
  `date_lastlog` date NOT NULL DEFAULT '2008-01-01',
  `date_expiration` date NOT NULL DEFAULT '2008-01-01',
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `user` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

INSERT INTO `auth_user` (`id_user`, `email`, `password`, `terms_conditions`, `my_firstname`, `my_lastname`, `my_photo`, `auth_profile`, `auth_locked`, `auth_logins`, `auth_credits`, `date_create`, `date_lastlog`, `date_expiration`) VALUES
(1,	'gael.jaunin@gmail.com',	'gaelloic',	NULL,	'Gael',	'Jaunin',	NULL,	2,	1,	0,	5,	'2008-01-01',	'2008-01-01',	'2008-01-01'),
(2,	'test@xuserver.net',	'gaelloic',	NULL,	'first',	'',	NULL,	3,	1,	0,	5,	'2008-01-01',	'2008-01-01',	'2008-01-01');

-- 2020-07-22 05:56:14