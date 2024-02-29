-- MYSQL DUMP For JotForm Version 3
-- DB Schema for Version 50
-- !!!!!!!!!!!! IMPORTANT !!!!!!!!!!!!!
-- 1) Must move all triggers to END of the file
-- 2) If you want to update this file make sure you add the Current ruckusing DB Version
-- 3) Add blank username
-- Ask serkan if you don't know what to do


SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `jotform_new`
--
CREATE DATABASE IF NOT EXISTS jotforms;
USE jotforms;

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE IF NOT EXISTS `announcement` (
  `username` varchar(31) collate utf8_unicode_ci NOT NULL default '',
  `password` varchar(31) collate utf8_unicode_ci default NULL,
  `email` varchar(127) collate utf8_unicode_ci default NULL,
  `account_type` enum('FREE','PREMIUM','ENTERPRISE','ADMIN') collate utf8_unicode_ci default NULL,
  `status` varchar(31) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE IF NOT EXISTS `answers` (
  `form_id` bigint(20) unsigned NOT NULL default '0',
  `submission_id` bigint(20) unsigned NOT NULL default '0',
  `question_id` smallint(6) unsigned NOT NULL default '0',
  `item_name` varchar(100) collate utf8_unicode_ci NOT NULL COMMENT 'For joined fields like address or matrix',
  `value` text collate utf8_unicode_ci NOT NULL,
  KEY `form_id_index` (`form_id`),
  KEY `submission_id` (`submission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_iphone`
--

CREATE TABLE IF NOT EXISTS `api_iphone` (
  `username` varchar(31) collate utf8_unicode_ci NOT NULL,
  `iphone_id` varchar(512) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `block_list`
--

CREATE TABLE IF NOT EXISTS `block_list` (
  `email` varchar(250) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE IF NOT EXISTS `cities` (
  `id` bigint(20) NOT NULL auto_increment,
  `state_id` bigint(20) NOT NULL,
  `city` varchar(200) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `state_id` (`state_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=146604 ;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE IF NOT EXISTS `countries` (
  `id` bigint(20) NOT NULL auto_increment,
  `country` varchar(150) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=219 ;

-- --------------------------------------------------------

--
-- Table structure for table `custom_settings`
--

CREATE TABLE IF NOT EXISTS `custom_settings` (
  `identifier` varchar(50) NOT NULL COMMENT 'any identifier to get unique property',
  `key` varchar(50) NOT NULL COMMENT 'setting name',
  `value` text NOT NULL,
  `updated_at` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  UNIQUE KEY `identifier` (`identifier`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_forms`
--

CREATE TABLE IF NOT EXISTS `deleted_forms` (
  `id` bigint(20) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forms`
--

CREATE TABLE IF NOT EXISTS `forms` (
  `id` bigint(20) unsigned NOT NULL default '0',
  `username` varchar(31) collate utf8_unicode_ci default NULL,
  `title` varchar(255) collate utf8_unicode_ci default NULL,
  `height` smallint(5) unsigned default NULL,
  `status` enum('ENABLED','DISABLED','SUSPENDED','DELETED','AUTOSUSPENDED') collate utf8_unicode_ci default 'ENABLED',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL default NULL,
  `new` tinyint(1) default NULL,
  `count` int(11) default NULL,
  `source` text collate utf8_unicode_ci NOT NULL,
  `slug` varchar(35) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `forms_username_slug_index` (`username`,`slug`),
  KEY `username` (`username`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `form_properties`
--

CREATE TABLE IF NOT EXISTS `form_properties` (
  `form_id` bigint(20) unsigned NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `type` varchar(50) collate utf8_unicode_ci NOT NULL,
  `prop` varchar(100) collate utf8_unicode_ci NOT NULL,
  `value` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`form_id`,`item_id`,`type`,`prop`),
  KEY `prop` (`prop`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `integrations`
--

CREATE TABLE IF NOT EXISTS `integrations` (
  `partner` varchar(32) NOT NULL,
  `username` varchar(32) NOT NULL,
  `form_id` bigint(20) NOT NULL,
  `key` varchar(50) NOT NULL,
  `value` text NOT NULL,
  `updated_at` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`partner`,`username`,`form_id`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `jotform_payments`
--

CREATE TABLE IF NOT EXISTS `jotform_payments` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `date_time` datetime NOT NULL,
  `operation_date` datetime NOT NULL,
  `action` varchar(31) NOT NULL,
  `gateway` varchar(31) NOT NULL,
  `username` varchar(31) NOT NULL,
  `total` float NOT NULL,
  `period` varchar(31) NOT NULL,
  `currency` varchar(31) NOT NULL,
  `payment_status` varchar(31) NOT NULL,
  `subscription_id` varchar(200) NOT NULL,
  `note` text NOT NULL,
  `ip` varchar(200) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `date_time` (`date_time`),
  KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=17227 ;

-- --------------------------------------------------------

--
-- Table structure for table `listings`
--

CREATE TABLE IF NOT EXISTS `listings` (
  `id` bigint(20) unsigned NOT NULL,
  `form_id` bigint(20) unsigned NOT NULL,
  `title` varchar(80) collate utf8_unicode_ci NOT NULL,
  `fields` text collate utf8_unicode_ci NOT NULL,
  `list_type` varchar(10) collate utf8_unicode_ci NOT NULL,
  `status` enum('ENABLED','SUSPENDED','DELETED','AUTOSUSPENDED','DISABLED') collate utf8_unicode_ci default 'ENABLED',
  `password` varchar(64) collate utf8_unicode_ci NOT NULL COMMENT 'password for public listings',
  PRIMARY KEY  (`id`),
  KEY `form_id` (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monthly_usage`
--

CREATE TABLE IF NOT EXISTS `monthly_usage` (
  `username` varchar(31) collate utf8_unicode_ci NOT NULL,
  `submissions` int(10) unsigned NOT NULL default '0',
  `ssl_submissions` int(10) unsigned NOT NULL default '0',
  `payments` int(10) unsigned NOT NULL default '0',
  `uploads` varchar(20) collate utf8_unicode_ci NOT NULL,
  `tickets` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE IF NOT EXISTS `payments` (
  `id` bigint(11) unsigned NOT NULL,
  `payer_name` varchar(80) collate utf8_unicode_ci default NULL,
  `payer_email` varchar(80) collate utf8_unicode_ci default NULL,
  `total` float NOT NULL,
  `curr` varchar(10) collate utf8_unicode_ci NOT NULL,
  `submission_id` bigint(11) unsigned default NULL,
  `status` varchar(80) collate utf8_unicode_ci default NULL,
  `date_time` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `submission_id` (`submission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_data_log`
--

CREATE TABLE IF NOT EXISTS `payment_data_log` (
  `form_id` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `submission_id` bigint(20) NOT NULL,
  `gateway` varchar(30) collate utf8_unicode_ci NOT NULL,
  `log_type` varchar(30) collate utf8_unicode_ci NOT NULL COMMENT 'type of the log, (posted data or IPN response or such)',
  `log_name` varchar(30) collate utf8_unicode_ci NOT NULL COMMENT 'identifier of the log data',
  `log_data` text collate utf8_unicode_ci NOT NULL COMMENT 'content of the log, searchable text',
  UNIQUE KEY `form_id_2` (`form_id`,`submission_id`,`gateway`,`log_type`,`log_name`),
  KEY `form_id` (`form_id`,`submission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Keeps the any data transferred between gateways and jotform';

-- --------------------------------------------------------

--
-- Table structure for table `payment_log`
--

CREATE TABLE IF NOT EXISTS `payment_log` (
  `date_time` datetime NOT NULL,
  `activity` varchar(80) collate utf8_unicode_ci default NULL,
  `submission_id` bigint(11) NOT NULL,
  `payment_id` bigint(11) NOT NULL,
  `total` float NOT NULL,
  `curr` varchar(10) collate utf8_unicode_ci NOT NULL,
  `note` varchar(2048) collate utf8_unicode_ci default NULL,
  `ip` varchar(20) collate utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_products`
--

CREATE TABLE IF NOT EXISTS `payment_products` (
  `payment_id` bigint(11) unsigned NOT NULL,
  `product_id` bigint(11) unsigned NOT NULL,
  KEY `payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pending_emails`
--

CREATE TABLE IF NOT EXISTS `pending_emails` (
  `id` int(11) NOT NULL auto_increment,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `email_config` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pending_redirects`
--

CREATE TABLE IF NOT EXISTS `pending_redirects` (
  `form_id` bigint(20) NOT NULL,
  `submission_id` bigint(20) NOT NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `type` varchar(30) collate utf8_unicode_ci NOT NULL,
  `value` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`form_id`,`submission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pending_submissions`
--

CREATE TABLE IF NOT EXISTS `pending_submissions` (
  `submission_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `type` varchar(50) NOT NULL,
  `form_id` bigint(20) unsigned NOT NULL,
  `token` varchar(50) NOT NULL,
  `serialized_data` text NOT NULL,
  `session_id` varchar(90) character set utf8 collate utf8_unicode_ci NOT NULL COMMENT 'Unique with formID, use for pagination',
  PRIMARY KEY  (`submission_id`),
  KEY `token` (`token`),
  KEY `form_id` (`form_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE IF NOT EXISTS `products` (
  `form_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(11) unsigned NOT NULL,
  `name` varchar(80) collate utf8_unicode_ci default NULL,
  `price` float default NULL,
  `subs_type` varchar(10) collate utf8_unicode_ci default NULL,
  `subs_duration` int(11) default NULL,
  `setup_fee` int(11) default NULL,
  `currency` varchar(10) collate utf8_unicode_ci default NULL,
  `trial_type` varchar(10) collate utf8_unicode_ci default NULL,
  `trial_duration` int(11) default NULL,
  `type` varchar(20) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`product_id`),
  KEY `form_id` (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `question_properties`
--

CREATE TABLE IF NOT EXISTS `question_properties` (
  `form_id` bigint(20) unsigned NOT NULL default '0',
  `question_id` smallint(6) unsigned NOT NULL default '0',
  `prop` varchar(255) collate utf8_unicode_ci NOT NULL default '',
  `value` text collate utf8_unicode_ci,
  PRIMARY KEY  (`form_id`,`question_id`,`prop`),
  KEY `prop` (`prop`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE IF NOT EXISTS `reports` (
  `id` bigint(20) unsigned NOT NULL,
  `form_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) collate utf8_unicode_ci default NULL,
  `configuration` text collate utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL default NULL,
  `password` varchar(64) collate utf8_unicode_ci NOT NULL COMMENT 'password for public reports',
  PRIMARY KEY  (`id`),
  KEY `form_id` (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- --------------------------------------------------------

--
-- Table structure for table `scheduled_downgrades`
--

CREATE TABLE IF NOT EXISTS `scheduled_downgrades` (
  `username` varchar(31) NOT NULL,
  `eot_time` datetime NOT NULL,
  `gateway` varchar(31) NOT NULL,
  `reason` varchar(31) NOT NULL,
  PRIMARY KEY  (`username`),
  KEY `eot_time` (`eot_time`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `schema_info`
--

CREATE TABLE IF NOT EXISTS `schema_info` (
  `version` int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
-- Add current DB version
INSERT INTO `schema_info` (`version`) VALUES (50);
-- --------------------------------------------------------

--
-- Table structure for table `spam_filter`
--

CREATE TABLE IF NOT EXISTS `spam_filter` (
  `id` int(11) NOT NULL auto_increment,
  `word` varchar(50) collate utf8_unicode_ci default NULL,
  `occurance_count` int(11) default NULL,
  `is_spam` tinyint(1) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `word` (`word`,`is_spam`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=40075 ;

-- --------------------------------------------------------

--
-- Table structure for table `spam_prob`
--

CREATE TABLE IF NOT EXISTS `spam_prob` (
  `id` int(11) NOT NULL auto_increment,
  `form_id` bigint(20) default NULL,
  `spam_prob` float default NULL,
  `suspended` tinyint(1) default NULL,
  `status` varchar(50) collate utf8_unicode_ci NOT NULL default 'NORMAL',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `form_id` (`form_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=120470809 ;

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

CREATE TABLE IF NOT EXISTS `states` (
  `id` bigint(20) NOT NULL auto_increment,
  `country_id` bigint(20) NOT NULL,
  `state` varchar(150) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `country_id` (`country_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3291 ;

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE IF NOT EXISTS `submissions` (
  `id` bigint(20) unsigned NOT NULL default '0',
  `form_id` bigint(20) unsigned NOT NULL,
  `ip` varchar(21) collate utf8_unicode_ci NOT NULL default '',
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `status` enum('ACTIVE','OVERQUOTA') collate utf8_unicode_ci default NULL,
  `new` tinyint(1) default '1',
  `flag` tinyint(4) NOT NULL,
  `notes` text collate utf8_unicode_ci NOT NULL,
  `updated_at` timestamp NULL default NULL,
  PRIMARY KEY  (`id`),
  KEY `form_id` (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `temp_payment_log`
--

CREATE TABLE IF NOT EXISTS `temp_payment_log` (
  `date_time` datetime NOT NULL,
  `activity` varchar(80) collate utf8_unicode_ci default NULL,
  `submission_id` bigint(11) NOT NULL,
  `payment_id` bigint(11) NOT NULL,
  `total` float NOT NULL,
  `curr` varchar(10) collate utf8_unicode_ci NOT NULL,
  `note` text collate utf8_unicode_ci,
  `ip` varchar(20) collate utf8_unicode_ci NOT NULL,
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(100) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `username` (`username`),
  KEY `date_time` (`date_time`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=28587 ;

-- --------------------------------------------------------

--
-- Table structure for table `test_goals`
--

CREATE TABLE IF NOT EXISTS `test_goals` (
  `id` bigint(20) NOT NULL auto_increment,
  `username` varchar(100) collate utf8_unicode_ci NOT NULL,
  `goal_name` varchar(100) collate utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=278237 ;

-- --------------------------------------------------------

--
-- Table structure for table `test_participants`
--

CREATE TABLE IF NOT EXISTS `test_participants` (
  `id` bigint(20) NOT NULL auto_increment,
  `username` varchar(100) collate utf8_unicode_ci NOT NULL,
  `test_name` varchar(100) collate utf8_unicode_ci NOT NULL,
  `group_name` varchar(100) collate utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username` (`username`,`test_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=226995 ;

-- --------------------------------------------------------

--
-- Table structure for table `upload_files`
--

CREATE TABLE IF NOT EXISTS `upload_files` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) character set utf8 collate utf8_unicode_ci NOT NULL,
  `type` varchar(31) character set utf8 collate utf8_unicode_ci NOT NULL,
  `size` int(10) unsigned NOT NULL,
  `username` varchar(31) character set utf8 collate utf8_unicode_ci NOT NULL,
  `form_id` bigint(20) unsigned NOT NULL default '0',
  `submission_id` bigint(20) unsigned NOT NULL default '0',
  `uploaded` tinyint(1) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `upload_entry` (`name`,`username`,`form_id`,`submission_id`),
  KEY `submission_id` (`submission_id`),
  KEY `form_id` (`form_id`),
  KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1427785 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `username` varchar(31) collate utf8_unicode_ci NOT NULL,
  `password` char(64) collate utf8_unicode_ci default NULL,
  `name` varchar(63) collate utf8_unicode_ci default NULL,
  `email` varchar(127) collate utf8_unicode_ci default NULL,
  `website` varchar(255) collate utf8_unicode_ci default NULL,
  `time_zone` varchar(30) collate utf8_unicode_ci default NULL,
  `ip` varchar(30) collate utf8_unicode_ci default NULL,
  `account_type` enum('FREE','OLDPREMIUM','PREMIUM','PROFESSIONAL','ADMIN','GUEST') collate utf8_unicode_ci NOT NULL default 'FREE',
  `status` enum('ACTIVE','SUSPENDED','DELETED','AUTOSUSPENDED','OVERLIMIT') collate utf8_unicode_ci NOT NULL default 'ACTIVE',
  `saved_emails` text collate utf8_unicode_ci,
  `created_at` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL default NULL,
  `last_seen_at` timestamp NULL default NULL,
  `folder_config` text collate utf8_unicode_ci NOT NULL COMMENT 'My Forms page folder configurations',
  `last_migration` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT 'Just to keep migration we will delete this later',
  `submission_last_migration` timestamp NOT NULL default '0000-00-00 00:00:00' COMMENT 'Just to keep migration we will delete this later',
  `referer` varchar(500) collate utf8_unicode_ci NOT NULL COMMENT 'Origin of the user',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `username_UNIQUE` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=932121 ;

-- So that anonymous forms can also be created. tayfunsen
INSERT INTO users(username) VALUES('');


-- --------------------------------------------------------

--
-- Table structure for table `whitelist`
--

CREATE TABLE IF NOT EXISTS `whitelist` (
  `form_id` bigint(20) NOT NULL,
  PRIMARY KEY  (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcement`
--
ALTER TABLE `announcement`
  ADD CONSTRAINT `announcement_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `forms`
--
ALTER TABLE `forms`
  ADD CONSTRAINT `forms_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `form_properties`
--
ALTER TABLE `form_properties`
  ADD CONSTRAINT `form_properties_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `listings`
--
ALTER TABLE `listings`
  ADD CONSTRAINT `listings_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `monthly_usage`
--
ALTER TABLE `monthly_usage`
  ADD CONSTRAINT `monthly_usage_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pending_submissions`
--
ALTER TABLE `pending_submissions`
  ADD CONSTRAINT `pending_submissions_fk_id` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `question_properties`
--
ALTER TABLE `question_properties`
  ADD CONSTRAINT `question_properties_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `upload_files`
--
ALTER TABLE `upload_files`
  ADD CONSTRAINT `upload_files_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `upload_files_ibfk_2` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

  
  
  
-- Triggers at the end of file
--
-- Triggers `forms`
--
DROP TRIGGER IF EXISTS `forms_before_insert`;
DELIMITER //
CREATE TRIGGER `forms_before_insert` BEFORE INSERT ON `forms`
 FOR EACH ROW BEGIN
    IF NEW.slug = "" THEN
        SET NEW.slug = NEW.id;
    END IF;
    SET NEW.updated_at = NOW();
END
//
DELIMITER ;
DROP TRIGGER IF EXISTS `update_form`;
DELIMITER //
CREATE TRIGGER `update_form` BEFORE UPDATE ON `forms`
 FOR EACH ROW SET NEW.updated_at = NOW()
//
DELIMITER ;

--
-- Triggers `reports`
--
DROP TRIGGER IF EXISTS `update_report`;
DELIMITER //
CREATE TRIGGER `update_report` BEFORE UPDATE ON `reports`
 FOR EACH ROW SET NEW.updated_at = NOW()
//
DELIMITER ;

--
-- Triggers `submissions`
--
DROP TRIGGER IF EXISTS `update_submission`;
DELIMITER //
CREATE TRIGGER `update_submission` BEFORE UPDATE ON `submissions`
 FOR EACH ROW SET NEW.updated_at = NOW()
//
DELIMITER ;

--
-- Triggers `users`
--
DROP TRIGGER IF EXISTS `insert_user`;
DELIMITER //
CREATE TRIGGER `insert_user` BEFORE INSERT ON `users`
 FOR EACH ROW SET NEW.updated_at = NOW(), NEW.last_seen_at = NOW()
//
DELIMITER ;
DROP TRIGGER IF EXISTS `update_user`;
DELIMITER //
CREATE TRIGGER `update_user` BEFORE UPDATE ON `users`
 FOR EACH ROW SET NEW.updated_at = NOW()
//
DELIMITER ;

--
-- Dumping data for table `users`
--
-- WHERE:  username='USER_TABLES'

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (932466,'USER_TABLES','b7ad567477c83756aab9a542b2be04f77dbae25115d85f22070d74d8cc4779dc','Support','support@auxiliumgroup.com',NULL,'America/Toronto','72.38.111.130','PROFESSIONAL','ACTIVE',NULL,'2012-11-19 15:35:06',NULL,'2024-02-23 12:14:49','','2024-02-23 12:14:49','2024-02-23 12:14:49','');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;