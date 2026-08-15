/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) DEFAULT NULL,
  `parent_account_id` varchar(30) NOT NULL,
  `status_id` enum('1','0','-1','-2','-3','-4') DEFAULT '-1' COMMENT '0=inactive, -2=suspended',
  `account_type` enum('CUSTOMER','RESELLER') DEFAULT 'CUSTOMER',
  `account_level` smallint(6) unsigned DEFAULT NULL COMMENT '0',
  `dp` tinyint(1) DEFAULT 4,
  `currency_id` int(11) DEFAULT 1,
  `account_cc` int(11) DEFAULT 1,
  `account_cps` int(11) DEFAULT NULL,
  `tax_number` varchar(30) DEFAULT NULL,
  `tax_type` enum('inclusive','exclusive') DEFAULT 'exclusive',
  `vat_flag` enum('NONE','TAX','VAT') DEFAULT 'NONE',
  `tax1` double(8,4) DEFAULT NULL,
  `tax2` double(8,4) DEFAULT NULL,
  `tax3` double(8,4) DEFAULT NULL,
  `cli_check` int(11) DEFAULT 0,
  `dialpattern_check` enum('1','0') DEFAULT '0',
  `llr_check` enum('1','0') DEFAULT '0',
  `account_codecs` varchar(255) DEFAULT 'G729,PCMU,PCMA,G722',
  `media_transcoding` enum('1','0') DEFAULT '1',
  `media_rtpproxy` enum('1','0') DEFAULT '0',
  `force_dst_src_cli_prefix` enum('1','0') DEFAULT '0',
  `codecs_force` enum('1','0') DEFAULT '0',
  `max_callduration` int(11) DEFAULT 30,
  `round_logic` enum('CEIL','ROUND') DEFAULT NULL,
  `create_dt` datetime NOT NULL,
  `create_by` varchar(30) NOT NULL,
  `update_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `update_by` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_am` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_account_id` varchar(30) DEFAULT NULL,
  `account_manager` varchar(30) DEFAULT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_card_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) NOT NULL,
  `card_name` varchar(30) NOT NULL,
  `card_data` text NOT NULL,
  `dt_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_payment_credentials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) NOT NULL,
  `payment_method` enum('paypal-client','paypal-sdk','ccavenue','secure-trading') NOT NULL,
  `credentials` text NOT NULL,
  `status` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_api_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_data` text NOT NULL,
  `response_data` text NOT NULL,
  `function_return` text NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) DEFAULT NULL,
  `activity_type` varchar(20) DEFAULT NULL,
  `sql_table` varchar(50) DEFAULT NULL,
  `sql_key` varchar(255) DEFAULT NULL,
  `sql_query` text DEFAULT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  `dt_created` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_site_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event` enum('track','insert','update','delete') NOT NULL DEFAULT 'track',
  `session_id` varchar(100) NOT NULL,
  `user_name` varchar(128) NOT NULL,
  `account_id` varchar(30) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `remote_address` varchar(255) NOT NULL,
  `page_url` varchar(255) NOT NULL,
  `referrer_url` varchar(255) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `ci_class_method` varchar(255) NOT NULL,
  `created_dt` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_account_sdr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) DEFAULT NULL,
  `rule_type` varchar(30) DEFAULT NULL,
  `service_number` varchar(1000) DEFAULT '',
  `billing_date` date DEFAULT NULL,
  `unit` int(11) DEFAULT 0,
  `rate` double(20,10) DEFAULT 0.0000000000,
  `cost` double(20,10) DEFAULT 0.0000000000,
  `totalcost` double(20,10) DEFAULT 0.0000000000,
  `sallerunit` int(11) DEFAULT 0,
  `sallerrate` double(20,10) DEFAULT 0.0000000000,
  `sallercost` double(20,10) DEFAULT 0.0000000000,
  `totalsallercost` double(20,10) DEFAULT 0.0000000000,
  `startdate` date DEFAULT NULL,
  `enddate` date DEFAULT NULL,
  `createdate` datetime DEFAULT NULL,
  `invoice_id` varchar(50) DEFAULT NULL,
  `dategeneratedby` enum('service','api') DEFAULT 'service',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_billing_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `billingeventid` varchar(50) DEFAULT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  `item_id` varchar(30) DEFAULT NULL,
  `price_id` varchar(30) DEFAULT NULL,
  `item_product_id` varchar(30) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `start_dt` date DEFAULT NULL,
  `status_id` enum('0','1','2','-1') DEFAULT '1',
  `stop_dt` date DEFAULT NULL,
  `lastbilldate` date DEFAULT NULL,
  `record_type` varchar(30) DEFAULT NULL,
  `lastbill_execute_date` date DEFAULT NULL,
  `r1lastbilldate` date DEFAULT NULL,
  `r2lastbilldate` date DEFAULT NULL,
  `r3lastbilldate` date DEFAULT NULL,
  `r1lastbill_execute_date` date DEFAULT NULL,
  `r2lastbill_execute_date` date DEFAULT NULL,
  `r3lastbill_execute_date` date DEFAULT NULL,
  `event_delete_status` enum('0','1') DEFAULT '0',
  `child_billingeventid` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_id` (`billingeventid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_carrier_sdr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carrier_id` varchar(30) DEFAULT NULL,
  `carrier_name` varchar(100) DEFAULT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  `currency_id` int(11) DEFAULT NULL,
  `currency_name` varchar(20) DEFAULT NULL,
  `account_currency_id` int(11) DEFAULT NULL,
  `currency_ratio` decimal(12,6) NOT NULL DEFAULT 1.000000,
  `rule_type` varchar(30) DEFAULT NULL,
  `prefix` varchar(30) DEFAULT NULL,
  `destination` varchar(150) DEFAULT NULL,
  `unit` int(11) DEFAULT 0,
  `rate` double(20,10) DEFAULT 0.0000000000,
  `carriercost` double(20,10) DEFAULT 0.0000000000,
  `carriercost_customer_currency` double(20,10) DEFAULT 0.0000000000,
  `calls_date` date DEFAULT NULL,
  `customer_cost` double(20,10) DEFAULT NULL,
  `customer_rate` double(20,10) DEFAULT NULL,
  `calls` int(11) DEFAULT 0,
  `billing_date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_customer_priceplan` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) DEFAULT NULL,
  `billing_cycle` enum('DAILY','WEEKLY','MONTHLY') DEFAULT NULL,
  `payment_terms` int(3) DEFAULT NULL,
  `itemised_billing` enum('1','0') DEFAULT '1',
  `invoice_via_email` enum('1','0') DEFAULT '1',
  `emails` varchar(150) DEFAULT NULL,
  `invoice_generation_status` enum('1','0') DEFAULT '1',
  `invoice_generation_status_update` datetime DEFAULT NULL,
  `last_invoice_date` datetime DEFAULT NULL,
  `next_invoice_date` datetime DEFAULT NULL,
  `invoice_id` varchar(50) DEFAULT NULL,
  `created_by` varchar(30) NOT NULL,
  `updated_by` varchar(30) NOT NULL,
  `created_dt` datetime NOT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `priceplan_id` varchar(30) DEFAULT NULL,
  `status_message` text DEFAULT NULL,
  `monthly_charges_day` smallint(6) DEFAULT 1,
  `billing_day` smallint(6) DEFAULT 1,
  `stope_invoicing` enum('0','1') DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_customerpricelist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_account_id` varchar(30) DEFAULT NULL,
  `price_id` varchar(30) DEFAULT NULL,
  `item_id` varchar(30) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `account_id` varchar(30) NOT NULL,
  `record_type` enum('rate','fixcharge') DEFAULT 'rate',
  `created_by` varchar(30) NOT NULL,
  `updated_by` varchar(30) NOT NULL,
  `created_dt` datetime NOT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `price_id` (`item_id`,`customer_account_id`,`record_type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_email_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) DEFAULT NULL,
  `email_name` varchar(30) DEFAULT NULL,
  `template_for` varchar(30) DEFAULT NULL,
  `email_subject` text DEFAULT NULL,
  `email_body` text DEFAULT NULL,
  `email_bcc` text DEFAULT NULL,
  `email_cc` text DEFAULT NULL,
  `email_daemon` enum('PHPMAIL','SMTP') DEFAULT 'PHPMAIL',
  `smtp_id` varchar(30) DEFAULT NULL,
  `created_by` varchar(30) NOT NULL,
  `updated_by` varchar(30) NOT NULL,
  `created_dt` datetime NOT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_id` (`account_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_invoice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` varchar(30) DEFAULT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  `company_name` varchar(50) DEFAULT '',
  `company_address` text DEFAULT NULL,
  `email_address` varchar(50) DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `tax_number` varchar(50) DEFAULT NULL,
  `tax1` double(20,10) DEFAULT 0.0000000000,
  `tax2` double(20,10) DEFAULT 0.0000000000,
  `tax3` double(20,10) DEFAULT 0.0000000000,
  `bill_date` date DEFAULT NULL,
  `billing_cycle` enum('MONTHLY','DAILY','WEEKLY') DEFAULT 'MONTHLY',
  `payment_terms` int(11) DEFAULT 1,
  `itemised_billing` enum('1','0') NOT NULL DEFAULT '0',
  `next_billing_date` date DEFAULT NULL,
  `billing_date_from` date DEFAULT NULL,
  `billing_date_to` date DEFAULT NULL,
  `last_bill_amount` double(20,10) DEFAULT 0.0000000000,
  `currency_symbol` varchar(5) DEFAULT NULL,
  `currency_name` varchar(15) DEFAULT NULL,
  `craete_dt` datetime DEFAULT NULL,
  `update_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `payments` double(25,10) DEFAULT NULL,
  `refund_amount` double(25,10) DEFAULT NULL,
  `usage_amount` double(25,10) DEFAULT NULL,
  `tax1_amount` double(25,10) DEFAULT NULL,
  `tax2_amount` double(25,10) DEFAULT NULL,
  `tax3_amount` double(25,10) DEFAULT NULL,
  `current_due_amount` double(25,10) DEFAULT NULL,
  `bill_amount` double(25,10) DEFAULT NULL,
  `contact_name` varchar(50) DEFAULT NULL,
  `status_id` enum('0','1') DEFAULT '1',
  `status_message` varchar(300) DEFAULT NULL,
  `account_manager` varchar(100) DEFAULT NULL,
  `create_dt` datetime NOT NULL,
  `due_status` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_invoice_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) DEFAULT NULL,
  `logo` varchar(300) DEFAULT NULL,
  `company_name` varchar(300) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `bank_detail` text DEFAULT NULL,
  `footer_text` text DEFAULT NULL,
  `support_text` text DEFAULT NULL,
  `created_by` varchar(30) NOT NULL,
  `updated_by` varchar(30) NOT NULL,
  `created_dt` datetime NOT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_id` (`account_id`) USING BTREE,
  UNIQUE KEY `account_id_2` (`account_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_itemlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_id` varchar(30) DEFAULT NULL,
  `item_id` varchar(30) DEFAULT NULL,
  `item_name` varchar(150) DEFAULT NULL,
  `item_name_invoice_display` varchar(150) DEFAULT NULL,
  `can_set_price` enum('0','1') DEFAULT '1',
  `created_by` varchar(30) NOT NULL,
  `updated_by` varchar(30) NOT NULL,
  `created_dt` datetime NOT NULL,
  `updated_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_id` (`item_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_pricelist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `price_id` varchar(30) DEFAULT NULL,
  `item_id` varchar(30) NOT NULL,
  `currency_id` varchar(30) DEFAULT '',
  `description` varchar(250) NOT NULL,
  `reguler_charges` enum('EMA','EME','NA') DEFAULT 'NA',
  `free_item` int(4) DEFAULT NULL,
  `charges` double(20,10) DEFAULT 0.0000000000,
  `additional_charges_as` enum('SE','NA') DEFAULT 'NA',
  `additional_charges` double(20,10) DEFAULT 0.0000000000,
  `account_id` varchar(30) NOT NULL,
  `created_by` varchar(30) NOT NULL,
  `updated_by` varchar(30) NOT NULL,
  `created_dt` datetime NOT NULL,
  `updated_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `price_id` (`price_id`,`account_id`,`currency_id`,`item_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_pricelist_customer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_account_id` varchar(30) DEFAULT NULL,
  `price_id` varchar(30) DEFAULT NULL,
  `item_id` varchar(30) NOT NULL,
  `currency_id` varchar(30) DEFAULT '',
  `description` varchar(250) NOT NULL,
  `reguler_charges` enum('EMA','EME','NA') DEFAULT 'NA',
  `free_item` int(4) DEFAULT NULL,
  `charges` double(20,10) DEFAULT 0.0000000000,
  `additional_charges_as` enum('SE','NA') DEFAULT 'NA',
  `additional_charges` double(20,10) DEFAULT 0.0000000000,
  `account_id` varchar(30) NOT NULL,
  `created_by` varchar(30) NOT NULL,
  `updated_by` varchar(30) NOT NULL,
  `created_dt` datetime NOT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `price_id` (`price_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_priceplan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `priceplan_id` varchar(20) NOT NULL,
  `priceplan_name` varchar(250) NOT NULL,
  `account_id` varchar(30) NOT NULL,
  `created_by` varchar(30) NOT NULL,
  `updated_by` varchar(30) NOT NULL,
  `created_dt` datetime NOT NULL,
  `updated_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `currency_id` varchar(30) DEFAULT NULL,
  `status` enum('0','1') DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `price_plan_id` (`priceplan_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_priceplan_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `priceplan_item_id` varchar(20) NOT NULL,
  `priceplan_id` varchar(20) NOT NULL,
  `item_id` varchar(20) NOT NULL,
  `price_id` varchar(20) NOT NULL,
  `account_id` varchar(30) NOT NULL,
  `created_by` varchar(30) NOT NULL,
  `updated_by` varchar(30) NOT NULL,
  `created_dt` datetime NOT NULL,
  `updated_dt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `price_plan_item_id` (`priceplan_item_id`) USING BTREE,
  UNIQUE KEY `itemplan_key` (`priceplan_id`,`item_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bill_smtp_config` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) DEFAULT '',
  `smtp_config_id` varchar(200) DEFAULT NULL,
  `smtp_auth` enum('0','1') DEFAULT NULL,
  `smtp_secure` enum('SSL','TSL') DEFAULT NULL,
  `smtp_host` varchar(100) DEFAULT NULL,
  `smtp_port` varchar(30) DEFAULT NULL,
  `smtp_username` varchar(30) DEFAULT NULL,
  `smtp_password` varchar(30) DEFAULT NULL,
  `smtp_from` varchar(100) DEFAULT NULL,
  `smtp_from_name` varchar(30) DEFAULT NULL,
  `smtp_xmailer` varchar(100) DEFAULT NULL,
  `smtp_host_name` varchar(100) DEFAULT NULL,
  `created_by` varchar(30) NOT NULL,
  `updated_by` varchar(30) NOT NULL,
  `created_dt` datetime NOT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `smtp_config_id` (`smtp_config_id`) USING BTREE,
  UNIQUE KEY `smtp_config` (`smtp_config_id`,`account_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bundle_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bundle_package_id` varchar(30) DEFAULT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  `assign_dt` date DEFAULT NULL,
  `account_bundle_key` varchar(30) DEFAULT NULL,
  `bundle_package_desc` varchar(50) DEFAULT NULL,
  `lastbill_execute_date` date DEFAULT NULL,
  `lastbilldate` date DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_bundle_key` (`account_bundle_key`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bundle_package` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bundle_package_id` varchar(30) DEFAULT '',
  `bundle_package_name` varchar(30) DEFAULT '',
  `bundle_package_currency_id` int(11) DEFAULT 1,
  `bundle_package_status` enum('1','0') DEFAULT '1',
  `bundle_package_description` varchar(50) DEFAULT '',
  `created_by` varchar(30) NOT NULL,
  `create_dt` datetime DEFAULT NULL,
  `update_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `package_option` enum('1','0') DEFAULT '0',
  `monthly_charges` double DEFAULT 0,
  `bundle_option` enum('1','0') DEFAULT '0',
  `bundle1_type` enum('MINUTE','COST') DEFAULT 'MINUTE',
  `bundle1_value` double(12,6) DEFAULT NULL,
  `bundle2_type` enum('MINUTE','COST') DEFAULT 'MINUTE',
  `bundle2_value` double(12,6) DEFAULT NULL,
  `bundle3_type` enum('MINUTE','COST') DEFAULT 'MINUTE',
  `bundle3_value` double(12,6) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bundle_package_id` (`bundle_package_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bundle_package_prefixes` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `bundle_package_id` varchar(30) NOT NULL,
  `bundle_id` enum('1','2','3') NOT NULL DEFAULT '1',
  `prefix` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bundle_package_id` (`bundle_package_id`,`bundle_id`,`prefix`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `carrier` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carrier_id` varchar(30) DEFAULT NULL,
  `carrier_name` varchar(30) NOT NULL,
  `tariff_id` varchar(30) NOT NULL,
  `carrier_type` enum('INBOUND','OUTBOUND') DEFAULT 'OUTBOUND',
  `carrier_status` int(11) DEFAULT 1,
  `carrier_cps` int(11) DEFAULT 10,
  `carrier_cc` int(11) DEFAULT 10,
  `carrier_currency_id` int(11) DEFAULT 1,
  `provider_id` varchar(30) DEFAULT NULL,
  `carrier_progress_timeout` int(11) DEFAULT 5,
  `carrier_ring_timeout` int(11) DEFAULT 30,
  `cli_prefer` enum('rpid','pid','no') DEFAULT 'rpid',
  `carrier_codecs` varchar(50) DEFAULT 'G729,PCMU,PCMA',
  `gateway_withmedia` enum('1','0') DEFAULT '0',
  `tax1` float DEFAULT 0,
  `tax2` float DEFAULT 0,
  `tax3` float DEFAULT 0,
  `tax_type` enum('inclusive','exclusive') DEFAULT 'inclusive',
  `dp` int(11) DEFAULT 4,
  `vat_flag` enum('TAX','VAT','NONE','SZE','REVERSE') DEFAULT 'NONE',
  `tax_number` varchar(30) DEFAULT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  `diversion_header_as_comingcli_db` enum('1','0') DEFAULT '1' COMMENT '1=as incoming  CLI; 0= from DB',
  `diversion_header_format` varchar(300) DEFAULT '<sip:${RDN}@${network_addr}>;reason=no-answer;counter=1;privacy=off',
  `diversion_header_option` enum('1','0') DEFAULT '1',
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_carrier_id_name` (`carrier_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `carrier_callerid` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `maching_string` varchar(30) DEFAULT NULL,
  `remove_string` varchar(15) DEFAULT '%',
  `add_string` varchar(15) DEFAULT NULL,
  `carrier_id` varchar(30) DEFAULT NULL,
  `display_string` varchar(60) DEFAULT NULL,
  `action_type` enum('0','1') DEFAULT '1',
  `route` enum('INBOUND','OUTBOUND') DEFAULT 'OUTBOUND',
  `account_id` varchar(30) DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `carrier_callerid_key` (`carrier_id`,`maching_string`,`route`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `carrier_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carrier_ip_id` varchar(30) DEFAULT NULL,
  `carrier_id` varchar(30) DEFAULT NULL,
  `ipaddress_name` varchar(30) NOT NULL,
  `ipaddress` varchar(30) DEFAULT NULL,
  `load_share` int(11) NOT NULL DEFAULT 100,
  `priority` smallint(6) DEFAULT 1,
  `ip_status` enum('1','0') DEFAULT '1',
  `auth_type` enum('IP','CUSTOMER') DEFAULT 'IP',
  `username` varchar(50) DEFAULT NULL,
  `passwd` varchar(50) DEFAULT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `carrier_id` (`carrier_id`,`ipaddress_name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `carrier_prefix` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carrier_id` varchar(30) DEFAULT NULL,
  `maching_string` varchar(30) DEFAULT NULL,
  `remove_string` varchar(30) DEFAULT NULL,
  `add_string` varchar(30) DEFAULT NULL,
  `display_string` varchar(35) DEFAULT NULL,
  `route` enum('INBOUND','OUTBOUND') DEFAULT 'INBOUND',
  `account_id` varchar(30) DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `carrier_prefix_id_key` (`carrier_id`,`maching_string`,`route`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `carrier_rates` (
  `rate_id` int(11) NOT NULL AUTO_INCREMENT,
  `ratecard_id` varchar(30) NOT NULL,
  `prefix` varchar(25) NOT NULL,
  `destination` varchar(150) NOT NULL,
  `setup_charge` double(12,6) NOT NULL DEFAULT 0.000000,
  `rental` double(12,6) NOT NULL DEFAULT 0.000000,
  `rate` double(12,6) NOT NULL DEFAULT 0.000000,
  `connection_charge` double DEFAULT 0,
  `minimal_time` int(11) NOT NULL DEFAULT 1,
  `resolution_time` int(11) DEFAULT 1,
  `grace_period` int(11) DEFAULT 0,
  `rate_multiplier` decimal(5,2) DEFAULT 1.00,
  `rate_addition` decimal(5,2) DEFAULT 0.00,
  `rates_status` enum('0','1') NOT NULL DEFAULT '1',
  `exclusive_per_channel_rental` double(12,6) DEFAULT 0.000000,
  `inclusive_channel` int(11) DEFAULT 1,
  `account_id` varchar(30) DEFAULT NULL,
  `minimal_charge` double(12,6) DEFAULT NULL,
  `ani_prefix` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `create_dt` timestamp NULL DEFAULT NULL,
  `update_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`rate_id`),
  UNIQUE KEY `pt` (`ratecard_id`,`prefix`) USING BTREE,
  KEY `prefix` (`prefix`) USING BTREE,
  KEY `tariff_id` (`ratecard_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cc_balance_ledger` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `idempotency_key` varchar(64) NOT NULL,
  `account_id` varchar(30) NOT NULL,
  `kind` enum('topup','adjustment','debit') NOT NULL DEFAULT 'topup',
  `amount` decimal(19,6) NOT NULL,
  `balance_before` decimal(19,6) NOT NULL,
  `balance_after` decimal(19,6) NOT NULL,
  `payment_history_id` int(11) DEFAULT NULL,
  `actor` varchar(100) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_idem` (`idempotency_key`),
  KEY `idx_account` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='CommsChannel portal: idempotent balance top-up audit ledger';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cc_credit_holds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `call_uuid` varchar(64) NOT NULL,
  `account_id` varchar(30) NOT NULL,
  `hold_amount` decimal(19,6) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hold_uuid` (`call_uuid`),
  KEY `idx_hold_acct` (`account_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='CommsChannel prepaid credit reservations (held at call setup, released at CDR)';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cc_endpoint_headers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sip_username` varchar(30) NOT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  `header_name` varchar(64) NOT NULL,
  `header_value` varchar(255) NOT NULL,
  `direction` enum('inbound','outbound','both') NOT NULL DEFAULT 'outbound',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ep_hdr` (`sip_username`,`header_name`),
  KEY `idx_ep` (`sip_username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='CommsChannel: custom SIP headers injected per endpoint (consumed by the switch dialplan)';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ci_cookies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cookie_id` varchar(255) DEFAULT NULL,
  `netid` varchar(255) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `orig_page_requested` varchar(120) DEFAULT NULL,
  `php_session_id` varchar(40) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ci_sessions` (
  `session_id` varchar(40) NOT NULL DEFAULT '0',
  `ip_address` varchar(45) NOT NULL DEFAULT '0',
  `user_agent` varchar(120) NOT NULL,
  `last_activity` int(10) unsigned NOT NULL DEFAULT 0,
  `user_data` text NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `last_activity_idx` (`last_activity`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_scheduler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) NOT NULL,
  `credit_amount` double(12,6) NOT NULL,
  `execution_date` datetime NOT NULL,
  `is_emergency_credit` enum('Y','N') NOT NULL DEFAULT 'N',
  `status_id` enum('0','1','2') NOT NULL DEFAULT '0' COMMENT '0=acive,1=executed,2=cancelled',
  `created_by` varchar(30) NOT NULL,
  `create_date` datetime NOT NULL,
  `modify_date` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `credit_limit` double(12,6) DEFAULT 0.000000,
  `balance` double(12,6) DEFAULT 0.000000,
  `account_id` varchar(30) DEFAULT NULL,
  `maxcredit_limit` double(12,6) DEFAULT 0.000000,
  `update_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `service_type` enum('SWITCH','PBX') DEFAULT 'SWITCH',
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_id` (`account_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_bundle_sdr` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) DEFAULT NULL,
  `account_bundle_key` varchar(50) DEFAULT '',
  `bundle_package_id` varchar(30) DEFAULT '',
  `rule_type` varchar(30) DEFAULT NULL,
  `yearmonth` varchar(10) DEFAULT NULL,
  `bundle_package_name` varchar(150) DEFAULT '',
  `total_allowed` double(18,6) DEFAULT 0.000000,
  `bundle_type` varchar(300) DEFAULT '',
  `sdr_consumption` double(20,6) DEFAULT NULL,
  `service_startdate` date DEFAULT NULL,
  `service_stopdate` date DEFAULT NULL,
  `action_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `package_id` (`account_id`,`rule_type`,`yearmonth`,`account_bundle_key`,`bundle_package_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_callerid` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `maching_string` varchar(30) DEFAULT NULL,
  `match_length` smallint(6) DEFAULT NULL,
  `remove_string` varchar(15) DEFAULT '%',
  `add_string` varchar(15) DEFAULT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  `display_string` varchar(60) DEFAULT NULL,
  `action_type` enum('0','1') DEFAULT '1',
  `route` enum('INBOUND','OUTBOUND','DTSBASEDCLI') DEFAULT 'OUTBOUND',
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `service_type` enum('SWITCH','PBX') DEFAULT 'SWITCH',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_callerid_key` (`account_id`,`maching_string`,`route`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_dialpattern` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) DEFAULT NULL,
  `maching_string` varchar(30) DEFAULT NULL,
  `match_length` smallint(6) DEFAULT NULL,
  `remove_string` varchar(20) DEFAULT NULL,
  `add_string` varchar(20) DEFAULT NULL,
  `display_string` varchar(30) DEFAULT '1',
  `action_type` enum('1','0') DEFAULT '1',
  `route` enum('INBOUND','OUTBOUND') DEFAULT 'OUTBOUND',
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `service_type` enum('SWITCH','PBX') DEFAULT 'SWITCH',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_dialplan_key` (`account_id`,`maching_string`,`route`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_dialplan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) NOT NULL,
  `dialplan_id` varchar(30) NOT NULL DEFAULT '1',
  `maching_string` varchar(30) DEFAULT NULL,
  `display_string` varchar(30) DEFAULT NULL,
  `remove_string` varchar(30) DEFAULT NULL,
  `add_string` varchar(30) DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_carrier_dialplan_key` (`account_id`,`maching_string`) USING BTREE,
  KEY `maching_string_key` (`maching_string`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) DEFAULT '',
  `ipaddress` varchar(30) DEFAULT NULL,
  `ip_status` enum('1','0') DEFAULT '1',
  `ip_cc` int(11) DEFAULT 10,
  `ip_cps` int(11) DEFAULT 1,
  `description` varchar(30) DEFAULT NULL,
  `dialprefix` varchar(30) DEFAULT NULL,
  `ipauthfrom` enum('SRC','FROM','NO') DEFAULT 'SRC',
  `billingcode` varchar(30) DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_ips_ipaddress_key` (`ipaddress`,`dialprefix`,`billingcode`) USING BTREE,
  KEY `account_id` (`account_id`) USING BTREE,
  KEY `ipaddress` (`ipaddress`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) NOT NULL,
  `notify_name` enum('low-balance','daily-balance') NOT NULL,
  `notify_emails` varchar(255) NOT NULL,
  `notify_amount` varchar(50) NOT NULL,
  `status` enum('Y','N') NOT NULL,
  `email_status` enum('1','0') DEFAULT '0',
  `update_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_id` (`account_id`,`notify_name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_rates` (
  `rate_id` int(11) NOT NULL AUTO_INCREMENT,
  `ratecard_id` varchar(30) NOT NULL,
  `prefix` varchar(25) NOT NULL,
  `destination` varchar(150) NOT NULL,
  `setup_charge` double(12,6) NOT NULL DEFAULT 0.000000,
  `rental` double(12,6) NOT NULL DEFAULT 0.000000,
  `rate` double(12,6) NOT NULL DEFAULT 0.000000,
  `connection_charge` double DEFAULT 0,
  `minimal_time` int(11) NOT NULL DEFAULT 1,
  `resolution_time` int(11) DEFAULT 1,
  `grace_period` int(11) DEFAULT 0,
  `rate_multiplier` decimal(5,2) DEFAULT 1.00,
  `rate_addition` decimal(5,2) DEFAULT 0.00,
  `rates_status` enum('0','1') NOT NULL DEFAULT '1',
  `exclusive_per_channel_rental` double(12,6) DEFAULT 0.000000,
  `inclusive_channel` int(11) DEFAULT 1,
  `account_id` varchar(30) DEFAULT NULL,
  `minimal_charge` double(12,6) DEFAULT NULL,
  `ani_prefix` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `create_dt` datetime DEFAULT NULL,
  `update_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`rate_id`),
  UNIQUE KEY `pt` (`ratecard_id`,`prefix`) USING BTREE,
  KEY `prefix` (`prefix`) USING BTREE,
  KEY `tariff_id` (`ratecard_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_sip_account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(30) DEFAULT NULL,
  `secret` varchar(30) DEFAULT NULL,
  `ipaddress` varchar(30) DEFAULT NULL,
  `status` enum('1','0') DEFAULT '1',
  `account_id` varchar(30) DEFAULT NULL,
  `sip_cc` int(11) DEFAULT 1,
  `sip_cps` int(11) DEFAULT 1,
  `ipauthfrom` enum('FROM','SRC','NO') DEFAULT 'NO',
  `extension_no` int(11) DEFAULT NULL,
  `voicemail_enabled` enum('Y','N') DEFAULT 'N',
  `voicemail` varchar(30) DEFAULT NULL,
  `display_name` varchar(30) DEFAULT NULL,
  `caller_id` varchar(150) DEFAULT NULL,
  `cli_prefer` enum('rpid','pid','no') DEFAULT 'rpid',
  `codecs` varchar(50) DEFAULT 'G729,PCMU,PCMA',
  `moh_sound` varchar(255) NOT NULL DEFAULT 'default',
  `name` varchar(100) NOT NULL,
  `email_address` varchar(150) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `ring_timeout` int(11) DEFAULT 30,
  `call_forward_all` enum('Y','N') DEFAULT 'N',
  `cfall_destination_type` enum('NA','CUSTOMURI','PSTN','IP','EXTEN','HANGUP','IVR','TIMECONDITION','VOICEMAIL','ANNOUNCEMENT','QUEUE','RINGGROUP') NOT NULL DEFAULT 'HANGUP',
  `cfall_destination` varchar(30) DEFAULT NULL,
  `call_forward_no_answer` enum('Y','N') DEFAULT 'N',
  `cfnoans_destination_type` enum('NA','CUSTOMURI','PSTN','IP','EXTEN','HANGUP','IVR','TIMECONDITION','VOICEMAIL','ANNOUNCEMENT','QUEUE','RINGGROUP') NOT NULL DEFAULT 'HANGUP',
  `cfnoans_destination` varchar(30) DEFAULT NULL,
  `call_forward_busy` enum('Y','N') DEFAULT 'N',
  `cfbusy_destination_type` enum('NA','CUSTOMURI','PSTN','IP','EXTEN','HANGUP','IVR','TIMECONDITION','VOICEMAIL','ANNOUNCEMENT','QUEUE','RINGGROUP') NOT NULL DEFAULT 'HANGUP',
  `cfbusy_destination` varchar(30) DEFAULT NULL,
  `cfnoans_timeout` smallint(6) DEFAULT NULL,
  `call_recording` enum('1','0') NOT NULL DEFAULT '0',
  `dnd` enum('Y','N') DEFAULT 'N',
  `created_by` varchar(30) NOT NULL,
  `created_by_account_id` varchar(30) NOT NULL,
  `updated_by` varchar(30) NOT NULL,
  `created_dt` datetime NOT NULL,
  `updated_dt` datetime DEFAULT NULL,
  `user_type` enum('SWITCH','PBX') DEFAULT 'SWITCH',
  `extension_id` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`) USING BTREE,
  UNIQUE KEY `account_exten` (`account_id`,`extension_no`) USING BTREE,
  KEY `account_id` (`account_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_voipminuts` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `customer_voipminute_id` varchar(30) DEFAULT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  `billingcode` varchar(30) DEFAULT NULL,
  `account_type` varchar(30) DEFAULT NULL,
  `tariff_id` varchar(30) DEFAULT NULL,
  `status` enum('1','0') DEFAULT '1',
  `created_by` varchar(30) NOT NULL,
  `updated_by` varchar(30) NOT NULL,
  `created_dt` datetime NOT NULL,
  `updated_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voip_id` (`customer_voipminute_id`) USING BTREE,
  KEY `account_id` (`account_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(50) DEFAULT NULL,
  `company_name` varchar(50) NOT NULL,
  `contact_name` varchar(150) DEFAULT NULL,
  `name` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `country_id` int(11) DEFAULT NULL,
  `state_code_id` mediumint(9) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `emailaddress` varchar(1000) DEFAULT NULL,
  `billing_type` enum('prepaid','postpaid','netoff') NOT NULL DEFAULT 'prepaid',
  `billing_cycle` enum('weekly','monthly') NOT NULL DEFAULT 'monthly',
  `payment_terms` int(11) NOT NULL DEFAULT 30,
  `next_billing_date` date DEFAULT NULL,
  `pincode` varchar(15) DEFAULT NULL,
  `view_ipdevices` enum('1','0') DEFAULT '1',
  `view_sipdevice` enum('1','0') DEFAULT '1',
  `view_src_out` enum('1','0') DEFAULT '1',
  `view_dst_out` enum('1','0') DEFAULT '1',
  `view_src_did` enum('1','0') DEFAULT '1',
  `view_dst_did` enum('1','0') DEFAULT '1',
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `accountid` (`account_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `delete_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `delete_type` varchar(30) NOT NULL,
  `delete_status` varchar(30) NOT NULL,
  `delete_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `delete_code` varchar(30) NOT NULL,
  `deleted_by` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialplan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) DEFAULT NULL,
  `dialplan_id` varchar(30) DEFAULT NULL,
  `dialplan_name` varchar(20) DEFAULT NULL,
  `dialplan_status` enum('1','0') DEFAULT '1',
  `failover_sipcause_list` varchar(300) DEFAULT 'NO_ROUTE_DESTINATION,CHANNEL_UNACCEPTABLE,410,483,503,488,501,504,401,402,403,404',
  `dialplan_description` varchar(50) DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `create_dt` timestamp NULL DEFAULT NULL,
  `update_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `dialplan_id_name` (`dialplan_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialplan_prefix_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) DEFAULT NULL,
  `dialplan_id` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `dial_prefix` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `priority` smallint(6) NOT NULL DEFAULT 1,
  `route_status` enum('0','1') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '1',
  `carrier_id` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `start_day` smallint(6) DEFAULT 0,
  `start_time` varchar(8) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT '00:00:00',
  `end_day` smallint(6) DEFAULT 6,
  `end_time` varchar(8) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT '24:00:00',
  `load_share` int(11) DEFAULT 100,
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `create_dt` datetime DEFAULT NULL,
  `update_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `dialplan_list_name` (`dial_prefix`,`carrier_id`,`dialplan_id`) USING BTREE,
  KEY `dialplan_id_name` (`dialplan_id`) USING BTREE,
  KEY `dial_prefix` (`dial_prefix`) USING BTREE,
  KEY `route_status` (`route_status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin7 COLLATE=latin7_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `did` (
  `did_id` int(11) NOT NULL AUTO_INCREMENT,
  `did_number` varchar(30) DEFAULT NULL,
  `did_status` enum('NEW','USED','DEAD','BLOCKED') DEFAULT 'NEW',
  `carrier_id` varchar(30) DEFAULT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  `assign_date` datetime DEFAULT NULL,
  `reseller1_account_id` varchar(30) DEFAULT NULL,
  `reseller1_assign_date` datetime DEFAULT NULL,
  `reseller2_account_id` varchar(30) DEFAULT NULL,
  `reseller2_assign_date` datetime DEFAULT NULL,
  `reseller3_account_id` varchar(30) DEFAULT NULL,
  `reseller3_assign_date` datetime DEFAULT NULL,
  `create_date` datetime DEFAULT NULL,
  `channels` int(11) DEFAULT 1,
  `did_name` varchar(150) DEFAULT NULL,
  `number_type` enum('TFN','DID') DEFAULT 'DID',
  `lastbilldate` date DEFAULT NULL,
  `r1lastbilldate` date DEFAULT NULL,
  `r2lastbilldate` date DEFAULT NULL,
  `r3lastbilldate` date DEFAULT NULL,
  PRIMARY KEY (`did_id`),
  UNIQUE KEY `did_number` (`did_number`) USING BTREE,
  UNIQUE KEY `did_number_2` (`did_number`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `did_dst` (
  `did_dst_id` int(11) NOT NULL AUTO_INCREMENT,
  `did_number` varchar(30) DEFAULT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  `dst_type` enum('IP','CUSTOMER','PSTN') DEFAULT 'IP',
  `dst_destination` varchar(30) DEFAULT NULL,
  `create_date` datetime DEFAULT NULL,
  `update_date` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `dst_type2` enum('IP','CUSTOMER','PSTN') DEFAULT 'IP',
  `dst_destination2` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`did_dst_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `emaillog` (
  `email_log_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) DEFAULT NULL,
  `action_date` datetime DEFAULT NULL,
  `subject` varchar(300) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `attachement` blob DEFAULT NULL,
  `actionfrom` varchar(500) DEFAULT NULL,
  `email_to` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`email_log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `htable` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key_name` varchar(64) NOT NULL DEFAULT '',
  `key_type` int(11) NOT NULL DEFAULT 0,
  `value_type` int(11) NOT NULL DEFAULT 0,
  `key_value` varchar(128) NOT NULL DEFAULT '',
  `expires` int(11) NOT NULL DEFAULT 0,
  `ip` varchar(30) DEFAULT NULL,
  `htime` datetime DEFAULT NULL,
  `custom_field` varchar(128) NOT NULL DEFAULT '',
  `email_status` int(11) DEFAULT 0,
  `serverid` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ov_htable_keyname_ind` (`key_name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `htabledump` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key_name` varchar(64) NOT NULL DEFAULT '',
  `key_type` int(11) NOT NULL DEFAULT 0,
  `value_type` int(11) NOT NULL DEFAULT 0,
  `key_value` varchar(128) NOT NULL DEFAULT '',
  `expires` int(11) NOT NULL DEFAULT 0,
  `ip` varchar(30) DEFAULT NULL,
  `htime` datetime DEFAULT NULL,
  `custom_field` varchar(128) NOT NULL DEFAULT '',
  `email_status` int(11) DEFAULT 0,
  `serverid` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `htable_keyname_ind` (`key_name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ip_blocker` (
  `ip_id` int(11) NOT NULL AUTO_INCREMENT,
  `checking_type` enum('allow','disallow','inactive') NOT NULL,
  PRIMARY KEY (`ip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ip_blocker_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `livecalls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `carrier_ratecard_id` varchar(30) DEFAULT NULL,
  `carrier_tariff_id` varchar(30) DEFAULT NULL,
  `carrier_prefix` varchar(15) DEFAULT NULL,
  `carrier_destination` varchar(50) DEFAULT NULL,
  `carrier_rate` float(10,6) DEFAULT NULL,
  `carrier_id` varchar(30) DEFAULT NULL,
  `carrier_name` varchar(30) DEFAULT NULL,
  `carrier_ipaddress` varchar(30) DEFAULT NULL,
  `carrier_ipaddress_name` varchar(30) DEFAULT NULL,
  `carrier_currency_id` int(11) DEFAULT NULL,
  `carrier_src_caller` varchar(30) DEFAULT NULL,
  `carrier_src_callee` varchar(30) DEFAULT NULL,
  `carrier_dst_caller` varchar(30) DEFAULT NULL,
  `carrier_dst_callee` varchar(30) DEFAULT NULL,
  `dialplan_id` varchar(30) DEFAULT NULL,
  `customer_account_id` varchar(30) DEFAULT NULL,
  `customer_tariff_id` varchar(30) DEFAULT NULL,
  `customer_currency_id` int(11) DEFAULT NULL,
  `customer_ipaddress` varchar(30) DEFAULT NULL,
  `customer_ratecard_id` varchar(30) DEFAULT NULL,
  `customer_prefix` varchar(15) DEFAULT NULL,
  `customer_destination` varchar(50) DEFAULT NULL,
  `customer_rate` float(10,6) DEFAULT NULL,
  `customer_src_caller` varchar(30) DEFAULT NULL,
  `customer_src_callee` varchar(30) DEFAULT NULL,
  `customer_src_ip` varchar(30) DEFAULT NULL,
  `reseller1_account_id` varchar(30) DEFAULT NULL,
  `reseller1_tariff_id` varchar(30) DEFAULT NULL,
  `reseller1_ratecard_id` varchar(30) DEFAULT NULL,
  `reseller1_prefix` varchar(15) DEFAULT NULL,
  `reseller1_destination` varchar(50) DEFAULT NULL,
  `reseller1_rate` float(10,6) DEFAULT NULL,
  `reseller2_account_id` varchar(30) DEFAULT NULL,
  `reseller2_tariff_id` varchar(30) DEFAULT NULL,
  `reseller2_ratecard_id` varchar(30) DEFAULT NULL,
  `reseller2_prefix` varchar(15) DEFAULT NULL,
  `reseller2_destination` varchar(50) DEFAULT NULL,
  `reseller2_rate` float(10,6) DEFAULT NULL,
  `reseller3_account_id` varchar(30) DEFAULT NULL,
  `reseller3_tariff_id` varchar(30) DEFAULT NULL,
  `reseller3_ratecard_id` varchar(50) DEFAULT NULL,
  `reseller3_prefix` varchar(50) DEFAULT NULL,
  `reseller3_destination` varchar(50) DEFAULT NULL,
  `reseller3_rate` float(10,6) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `answer_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `fscause` varchar(50) DEFAULT NULL,
  `Q850CODE` varchar(30) DEFAULT NULL,
  `SIPCODE` varchar(30) DEFAULT NULL,
  `caller_callid` varchar(150) DEFAULT NULL,
  `callee_callid` varchar(150) DEFAULT NULL,
  `common_uuid` varchar(150) DEFAULT NULL,
  `fs_host` varchar(30) DEFAULT NULL,
  `in_useragent` varchar(150) DEFAULT NULL,
  `callstatus` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `customer_company` varchar(150) DEFAULT NULL,
  `loadbalancer` varchar(30) DEFAULT NULL,
  `call_flow` enum('PSTN','DID') DEFAULT 'PSTN',
  PRIMARY KEY (`id`),
  KEY `carrier_destination` (`carrier_destination`) USING BTREE,
  KEY `carrier_gateway_ipaddress` (`carrier_ipaddress`) USING BTREE,
  KEY `carrier_carrier_id_name` (`carrier_id`) USING BTREE,
  KEY `user_ipaddress` (`customer_ipaddress`) USING BTREE,
  KEY `user_account_id` (`customer_account_id`) USING BTREE,
  KEY `common_uuid` (`common_uuid`) USING BTREE,
  KEY `live_call_status` (`callstatus`) USING BTREE,
  KEY `reseller1_account_id` (`reseller1_account_id`) USING BTREE,
  KEY `reseller2_account_id` (`reseller2_account_id`) USING BTREE,
  KEY `reseller3_account_id` (`reseller3_account_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus` (
  `menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(50) NOT NULL,
  `menu` text NOT NULL,
  `update_by` varchar(50) NOT NULL,
  `update_dt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`menu_id`),
  UNIQUE KEY `account_id` (`account_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_history` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) NOT NULL,
  `payment_option_id` varchar(30) NOT NULL,
  `payment_collection_id` varchar(30) DEFAULT NULL,
  `amount` decimal(12,6) NOT NULL,
  `paid_on` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `file_name` varchar(20) NOT NULL,
  `other_data` text NOT NULL,
  `invoice_data` text NOT NULL,
  `created_by` varchar(30) NOT NULL,
  `create_dt` datetime NOT NULL,
  PRIMARY KEY (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_tracking` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(100) NOT NULL,
  `amount` decimal(12,6) NOT NULL,
  `tracking_id` varchar(50) NOT NULL,
  `order_status` enum('initiated','failed','success','not_accepted','card_attempt') NOT NULL DEFAULT 'initiated',
  `payment_method` varchar(30) NOT NULL,
  `bank_ref_no` varchar(30) NOT NULL,
  `account_id` varchar(30) NOT NULL,
  `send_string` text NOT NULL,
  `response_string` text NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `attempt_check` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `plugins` (
  `plugin_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `plugin_system_name` varchar(255) NOT NULL,
  `plugin_name` varchar(255) NOT NULL,
  `plugin_uri` varchar(120) DEFAULT NULL,
  `plugin_version` varchar(30) NOT NULL,
  `plugin_description` text DEFAULT NULL,
  `plugin_author` varchar(120) DEFAULT NULL,
  `plugin_author_uri` varchar(120) DEFAULT NULL,
  `plugin_data` longtext DEFAULT NULL,
  PRIMARY KEY (`plugin_id`),
  UNIQUE KEY `plugin_index` (`plugin_system_name`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_id` varchar(30) DEFAULT NULL,
  `provider_name` varchar(30) DEFAULT NULL,
  `provider_address` varchar(200) DEFAULT NULL,
  `provider_emailid` varchar(100) NOT NULL,
  `currency_id` int(4) DEFAULT NULL,
  `account_id` varchar(30) DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `created_dt` datetime DEFAULT NULL,
  `modify_by` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ratecard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ratecard_id` varchar(30) DEFAULT NULL,
  `ratecard_name` varchar(30) DEFAULT NULL,
  `ratecard_type` enum('CARRIER','CUSTOMER') DEFAULT 'CARRIER',
  `account_id` varchar(30) NOT NULL,
  `ratecard_currency_id` int(11) DEFAULT NULL,
  `ratecard_for` enum('INCOMING','OUTGOING') DEFAULT 'OUTGOING',
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ratecard_id` (`ratecard_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reseller_dialplan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) DEFAULT NULL,
  `dialplan_id` varchar(30) DEFAULT NULL,
  `create_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reseller_dialplan_key` (`account_id`,`dialplan_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `resellers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) NOT NULL,
  `company_name` varchar(50) NOT NULL,
  `contact_name` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `country_id` int(11) DEFAULT NULL,
  `state_code_id` mediumint(9) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `emailaddress` varchar(1000) DEFAULT NULL,
  `pincode` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_id` (`account_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `signup_plan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `signplan_id` varchar(30) NOT NULL,
  `signplan_name` varchar(30) NOT NULL,
  `tariff_id` varchar(30) NOT NULL,
  `billing_type` varchar(30) DEFAULT 'PREPAID',
  `max_callduration` int(11) DEFAULT 120,
  `account_type` enum('CUSTOMER','RESELLER') DEFAULT 'CUSTOMER',
  `currency_id` int(11) NOT NULL DEFAULT 1,
  `dp` tinyint(1) DEFAULT 4,
  `account_cc` int(11) DEFAULT 10,
  `account_cps` int(11) DEFAULT 1,
  `tax1` double(6,2) DEFAULT 0.00,
  `tax2` double(6,2) DEFAULT 0.00,
  `tax3` double(6,2) DEFAULT 0.00,
  `tax_type` enum('inclusive','exclusive') DEFAULT 'exclusive',
  `account_codecs` varchar(150) DEFAULT 'G729,PCMU,PCMA,G722',
  `media_transcoding` enum('1','0') DEFAULT '1',
  `media_rtpproxy` enum('1','0') DEFAULT '1',
  `dialplan_id` varchar(100) NOT NULL,
  `account_level` int(11) DEFAULT 1,
  `create_dt` datetime DEFAULT NULL,
  `update_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by_user_id` varchar(30) DEFAULT NULL,
  `created_by_account_id` varchar(30) DEFAULT 'SYSTEM',
  PRIMARY KEY (`id`),
  UNIQUE KEY `signplan_id` (`signplan_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_countries` (
  `country_id` int(11) NOT NULL AUTO_INCREMENT,
  `country_abbr` char(3) NOT NULL,
  `country_iso` varchar(2) DEFAULT NULL,
  `country_prefix` int(10) NOT NULL,
  `country_name` varchar(100) NOT NULL,
  `status_id` int(10) unsigned NOT NULL DEFAULT 2,
  `display_sequence` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`country_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_currencies` (
  `currency_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `symbol` varchar(20) NOT NULL DEFAULT '',
  `detail_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_currencies_conversions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ratio` decimal(12,4) NOT NULL DEFAULT 1.0000,
  `currency_id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_currency` (`currency_id`,`date`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_payment_credentials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) NOT NULL,
  `payment_method` varchar(30) DEFAULT NULL,
  `credentials` text DEFAULT NULL,
  `status` enum('Y','N') DEFAULT 'Y',
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_id` (`account_id`,`payment_method`,`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_rule_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `option_id` varchar(100) NOT NULL,
  `option_name` varchar(100) NOT NULL,
  `option_group` varchar(50) NOT NULL,
  `status_id` enum('1','0') NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_sdr_terms` (
  `term_id` int(11) NOT NULL AUTO_INCREMENT,
  `term_group` varchar(30) NOT NULL,
  `term` varchar(30) NOT NULL,
  `display_text` varchar(255) NOT NULL,
  `cost_calculation_formula` varchar(10) NOT NULL,
  `service_id` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`term_id`),
  UNIQUE KEY `term` (`term`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_sitesetup` (
  `sitesetup_id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_invoice_counter` bigint(20) NOT NULL,
  `prorata_billing` enum('1','0') NOT NULL DEFAULT '0',
  PRIMARY KEY (`sitesetup_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_states` (
  `state_id` int(30) NOT NULL AUTO_INCREMENT,
  `state_name` varchar(60) DEFAULT NULL,
  `state_code_id` varchar(20) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'INDIA',
  PRIMARY KEY (`state_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tariff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tariff_id` varchar(30) DEFAULT NULL,
  `tariff_name` varchar(30) DEFAULT NULL,
  `tariff_currency_id` int(11) DEFAULT 1,
  `tariff_status` enum('1','0') DEFAULT '1',
  `tariff_description` varchar(50) DEFAULT NULL,
  `tariff_type` enum('CARRIER','CUSTOMER') NOT NULL DEFAULT 'CARRIER',
  `account_id` varchar(30) NOT NULL,
  `package_option` enum('1','0') DEFAULT '0',
  `monthly_charges` double DEFAULT 0,
  `bundle_option` enum('1','0') DEFAULT '0',
  `bundle1_type` enum('MINUTE','COST') DEFAULT 'MINUTE',
  `bundle1_value` double(12,6) DEFAULT NULL,
  `bundle2_type` enum('MINUTE','COST') DEFAULT 'MINUTE',
  `bundle2_value` double(12,6) DEFAULT NULL,
  `bundle3_type` enum('MINUTE','COST') DEFAULT 'MINUTE',
  `bundle3_value` double(12,6) DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) NOT NULL,
  `create_dt` datetime DEFAULT NULL,
  `update_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tariff_id_name` (`tariff_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tariff_ratecard_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ratecard_id` varchar(30) DEFAULT NULL,
  `tariff_id` varchar(30) DEFAULT NULL,
  `start_day` int(11) DEFAULT NULL,
  `start_time` varchar(8) DEFAULT '00:00:00',
  `end_day` int(11) DEFAULT NULL,
  `end_time` varchar(8) DEFAULT '24:00:00',
  `priority` int(11) DEFAULT 1,
  `status` enum('1','0') DEFAULT '1',
  `ratecard_for` enum('INCOMING','OUTGOING') DEFAULT 'OUTGOING',
  `account_id` varchar(30) DEFAULT NULL,
  `created_by` varchar(30) DEFAULT NULL,
  `updated_by` varchar(30) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `updated_dt` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ratecard_id` (`ratecard_id`) USING BTREE,
  KEY `tariff_id` (`tariff_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_assigned_to` (
  `assigned_to_id` int(11) NOT NULL AUTO_INCREMENT,
  `assigned_to_name` varchar(50) NOT NULL,
  PRIMARY KEY (`assigned_to_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_attachments` (
  `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `file_name` varchar(100) NOT NULL,
  `file_name_display` varchar(100) NOT NULL,
  PRIMARY KEY (`attachment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_parent_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `status` enum('Y','N') NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tickets` (
  `ticket_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `account_id` varchar(30) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `assigned_to_id` int(11) DEFAULT NULL,
  `assigned_to_user_id` varchar(30) NOT NULL,
  `assigned_to_user_name` varchar(100) NOT NULL,
  `status` enum('open','closed','assigned','working','waiting-confirmation','not-fixed') NOT NULL DEFAULT 'open',
  `hide_from_customer` enum('Y','N') NOT NULL DEFAULT 'N',
  `created_by_ip` varchar(30) NOT NULL,
  `created_by` varchar(30) NOT NULL,
  `created_by_name` varchar(30) NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `close_date` datetime DEFAULT NULL,
  `author_name` varchar(30) NOT NULL,
  `author_email` varchar(50) NOT NULL,
  `author_email_subscribe` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_audit_trails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `event` enum('insert','update','delete') NOT NULL,
  `table_name` varchar(128) NOT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text NOT NULL,
  `url` varchar(255) NOT NULL,
  `name` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_type_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type` varchar(50) NOT NULL,
  `permissions` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(1) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(30) NOT NULL,
  `account_id` varchar(30) NOT NULL,
  `gcode` varchar(300) DEFAULT NULL,
  `user_type` varchar(30) NOT NULL,
  `username` varchar(30) NOT NULL,
  `secret` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `emailaddress` varchar(100) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `address` varchar(256) NOT NULL,
  `country_id` smallint(6) NOT NULL,
  `status_id` int(11) NOT NULL DEFAULT 1,
  `create_dt` timestamp NOT NULL DEFAULT current_timestamp(),
  `create_by` varchar(30) NOT NULL,
  `update_dt` datetime DEFAULT NULL,
  `update_by` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`) USING BTREE,
  KEY `account_id` (`account_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `version` (
  `table_name` varchar(32) NOT NULL,
  `table_version` int(10) unsigned NOT NULL DEFAULT 0,
  UNIQUE KEY `table_name_idx` (`table_name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `voicemail` (
  `vm_id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` varchar(30) NOT NULL,
  `vm_name` varchar(30) NOT NULL,
  `vm_no` int(11) NOT NULL,
  `mailbox` varchar(30) DEFAULT NULL,
  `vm_password` varchar(30) DEFAULT NULL,
  `no_of_vm` int(11) DEFAULT NULL,
  `no_of_vm_len` int(11) DEFAULT NULL,
  `send_email` enum('0','1') DEFAULT '0',
  `email_address` text DEFAULT NULL,
  `email_attach_file` enum('0','1') DEFAULT '0',
  `greetings_id` varchar(30) DEFAULT NULL,
  `group_id` varchar(30) NOT NULL,
  `status_id` enum('1','0') DEFAULT '1',
  `created_by` varchar(30) NOT NULL,
  `created_by_account_id` varchar(30) NOT NULL,
  `updated_by` varchar(30) NOT NULL,
  `created_dt` datetime NOT NULL,
  `updated_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`vm_id`),
  UNIQUE KEY `mailbox` (`mailbox`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `voicemail_msgs` (
  `created_epoch` int(11) DEFAULT NULL,
  `read_epoch` int(11) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `uuid` varchar(255) DEFAULT NULL,
  `cid_name` varchar(255) DEFAULT NULL,
  `cid_number` varchar(255) DEFAULT NULL,
  `in_folder` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `message_len` int(11) DEFAULT NULL,
  `flags` varchar(255) DEFAULT NULL,
  `read_flags` varchar(255) DEFAULT NULL,
  `forwarded_by` varchar(255) DEFAULT NULL,
  KEY `voicemail_msgs_idx1` (`created_epoch`) USING BTREE,
  KEY `voicemail_msgs_idx2` (`username`) USING BTREE,
  KEY `voicemail_msgs_idx3` (`domain`) USING BTREE,
  KEY `voicemail_msgs_idx4` (`uuid`) USING BTREE,
  KEY `voicemail_msgs_idx5` (`in_folder`) USING BTREE,
  KEY `voicemail_msgs_idx6` (`read_flags`) USING BTREE,
  KEY `voicemail_msgs_idx7` (`forwarded_by`) USING BTREE,
  KEY `voicemail_msgs_idx8` (`read_epoch`) USING BTREE,
  KEY `voicemail_msgs_idx9` (`flags`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `voicemail_prefs` (
  `username` varchar(255) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `name_path` varchar(255) DEFAULT NULL,
  `greeting_path` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  KEY `voicemail_prefs_idx1` (`username`) USING BTREE,
  KEY `voicemail_prefs_idx2` (`domain`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

