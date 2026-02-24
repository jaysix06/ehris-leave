/*
Navicat MySQL Data Transfer

Source Server         : localhost_3306
Source Server Version : 50505
Source Host           : localhost:3306
Source Database       : ehris_db

Target Server Type    : MYSQL
Target Server Version : 50505
File Encoding         : 65001

Date: 2026-02-16 14:10:23
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `activity_log`
-- ----------------------------
DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `fk_user_id` int(11) NOT NULL,
  `activity` text,
  `module` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`log_id`) USING BTREE,
  UNIQUE KEY `log_id` (`log_id`) USING BTREE,
  KEY `FK_activity_log_user_user_id` (`fk_user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8778 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of activity_log
-- ----------------------------

-- ----------------------------
-- Table structure for `block_user`
-- ----------------------------
DROP TABLE IF EXISTS `block_user`;
CREATE TABLE `block_user` (
  `blocked_from` varchar(10) COLLATE utf8mb4_bin NOT NULL,
  `blocked_to` varchar(10) COLLATE utf8mb4_bin NOT NULL,
  `time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Records of block_user
-- ----------------------------

-- ----------------------------
-- Table structure for `id_log`
-- ----------------------------
DROP TABLE IF EXISTS `id_log`;
CREATE TABLE `id_log` (
  `id` int(100) NOT NULL AUTO_INCREMENT,
  `hrid` int(100) DEFAULT NULL,
  `type_of_id` varchar(100) DEFAULT NULL,
  `employee_id` int(100) DEFAULT NULL,
  `prefix` varchar(100) DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `middlename` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `extension` varchar(100) DEFAULT NULL,
  `dob` varchar(100) DEFAULT NULL,
  `prc_no` varchar(100) DEFAULT NULL,
  `tin` varchar(100) DEFAULT NULL,
  `gsis` varchar(100) DEFAULT NULL,
  `sss` varchar(100) DEFAULT NULL,
  `pag_ibig` varchar(100) DEFAULT NULL,
  `philhealth` varchar(100) DEFAULT NULL,
  `blood_type` varchar(100) DEFAULT NULL,
  `station_code` varchar(100) DEFAULT NULL,
  `division_code` varchar(100) DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `emergency_name` varchar(100) DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_email` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `datetime` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of id_log
-- ----------------------------

-- ----------------------------
-- Table structure for `popup_messages`
-- ----------------------------
DROP TABLE IF EXISTS `popup_messages`;
CREATE TABLE `popup_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `link` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of popup_messages
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_addleave`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_addleave`;
CREATE TABLE `tbl_addleave` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hris_id` int(11) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `middlename` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `allotted_leave` decimal(10,2) DEFAULT '0.00',
  `used_leaves` decimal(10,2) DEFAULT '0.00',
  `leave_balance` decimal(10,2) DEFAULT '0.00',
  `allotted_year` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_addleave
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_affiliation`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_affiliation`;
CREATE TABLE `tbl_affiliation` (
  `affiliation_id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(11) DEFAULT NULL,
  `affiliation` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`affiliation_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_affiliation
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_attendance`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_attendance`;
CREATE TABLE `tbl_attendance` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `hrid` int(10) DEFAULT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_in_remarks` varchar(255) DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `time_out_remarks` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_attendance
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_audiovisual_depaide`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_audiovisual_depaide`;
CREATE TABLE `tbl_audiovisual_depaide` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `proj_desc` text,
  `project_type` varchar(255) DEFAULT NULL,
  `music_preference` varchar(255) DEFAULT NULL,
  `deliverables` varchar(255) DEFAULT NULL,
  `style_tone` varchar(255) DEFAULT NULL,
  `delivery_method` varchar(255) DEFAULT NULL,
  `project_deadline` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tbl_audiovisual_depaide
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_awards`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_awards`;
CREATE TABLE `tbl_awards` (
  `award_id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(11) DEFAULT NULL,
  `award_title` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `school_year` varchar(11) DEFAULT NULL,
  `award` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`award_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_awards
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_barangay`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_barangay`;
CREATE TABLE `tbl_barangay` (
  `barangay_id` int(11) NOT NULL AUTO_INCREMENT,
  `municipal_code` int(11) DEFAULT NULL,
  `barangay_code` int(11) DEFAULT NULL,
  `barangay_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`barangay_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=42037 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=81 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_barangay
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_barangay1`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_barangay1`;
CREATE TABLE `tbl_barangay1` (
  `barangay_id` int(11) NOT NULL AUTO_INCREMENT,
  `municipal_code` int(20) DEFAULT NULL,
  `barangay_code` int(20) DEFAULT NULL,
  `barangay_name1` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`barangay_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=42037 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=62 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_barangay1
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_blood_type`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_blood_type`;
CREATE TABLE `tbl_blood_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `btype` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=2048 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_blood_type
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_business`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_business`;
CREATE TABLE `tbl_business` (
  `business_id` int(11) NOT NULL AUTO_INCREMENT,
  `office_id` int(11) DEFAULT NULL,
  `BusinessUnitId` int(11) DEFAULT NULL,
  `BusinessUnit` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`business_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=4096 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_business
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_business_unit`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_business_unit`;
CREATE TABLE `tbl_business_unit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `office_id` int(11) DEFAULT NULL,
  `BusinessUnitId` int(11) DEFAULT NULL,
  `BusinessUnit` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=4096 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_business_unit
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_calendar_events`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_calendar_events`;
CREATE TABLE `tbl_calendar_events` (
  `event_id` int(100) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `start` varchar(100) DEFAULT NULL,
  `end` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`event_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_calendar_events
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_citizenship`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_citizenship`;
CREATE TABLE `tbl_citizenship` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `citizenship` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=8192 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_citizenship
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_civil_status`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_civil_status`;
CREATE TABLE `tbl_civil_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `civil_status` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=2730 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_civil_status
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_cot_rpms`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_cot_rpms`;
CREATE TABLE `tbl_cot_rpms` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `hrid` int(50) DEFAULT NULL,
  `department_id` int(50) DEFAULT NULL,
  `grade_id` int(50) DEFAULT NULL,
  `subject_id` int(50) DEFAULT NULL,
  `observation_period` int(50) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `q1` varchar(50) DEFAULT NULL,
  `q2` varchar(50) DEFAULT NULL,
  `q3` varchar(50) DEFAULT NULL,
  `q4` varchar(50) DEFAULT NULL,
  `q5` varchar(50) DEFAULT NULL,
  `q6` varchar(50) DEFAULT NULL,
  `q7` varchar(50) DEFAULT NULL,
  `q8` varchar(50) DEFAULT NULL,
  `q9` varchar(50) DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_cot_rpms
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_cot_rpms_mt`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_cot_rpms_mt`;
CREATE TABLE `tbl_cot_rpms_mt` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `hrid` int(50) DEFAULT NULL,
  `department_id` int(50) DEFAULT NULL,
  `grade_id` int(50) DEFAULT NULL,
  `subject_id` int(50) DEFAULT NULL,
  `observation_period` int(50) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `q1` varchar(50) DEFAULT NULL,
  `q2` varchar(50) DEFAULT NULL,
  `q3` varchar(50) DEFAULT NULL,
  `q4` varchar(50) DEFAULT NULL,
  `q5` varchar(50) DEFAULT NULL,
  `q6` varchar(50) DEFAULT NULL,
  `q7` varchar(50) DEFAULT NULL,
  `q8` varchar(50) DEFAULT NULL,
  `q9` varchar(50) DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_cot_rpms_mt
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_deceased`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_deceased`;
CREATE TABLE `tbl_deceased` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deceased` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_deceased
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_depart`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_depart`;
CREATE TABLE `tbl_depart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `department_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=178 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_depart
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_department`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_department`;
CREATE TABLE `tbl_department` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `department_name` varchar(255) DEFAULT NULL,
  `department_abbrev` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=178 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_department
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_depedemail_depaide`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_depedemail_depaide`;
CREATE TABLE `tbl_depedemail_depaide` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `school_id` int(11) NOT NULL,
  `email_format` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tbl_depedemail_depaide
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_district`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_district`;
CREATE TABLE `tbl_district` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `district_code` int(11) NOT NULL,
  `district_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of tbl_district
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_document`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_document`;
CREATE TABLE `tbl_document` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `document` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_document
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_document_depaide`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_document_depaide`;
CREATE TABLE `tbl_document_depaide` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `event_location` text NOT NULL,
  `event_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `details` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tbl_document_depaide
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_dual_citizenship`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_dual_citizenship`;
CREATE TABLE `tbl_dual_citizenship` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dual_citizenship` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=8192 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_dual_citizenship
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_educational_level`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_educational_level`;
CREATE TABLE `tbl_educational_level` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `educational_level` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=2048 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_educational_level
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_efficiency`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_efficiency`;
CREATE TABLE `tbl_efficiency` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `ob_no` varchar(11) DEFAULT NULL,
  `e_activity` varchar(255) DEFAULT NULL,
  `pi` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=146 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_efficiency
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_efficiency1`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_efficiency1`;
CREATE TABLE `tbl_efficiency1` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `hrid` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `ob_no` varchar(11) DEFAULT NULL,
  `e_activity` varchar(255) DEFAULT NULL,
  `pi` varchar(50) DEFAULT NULL,
  `date_added` varchar(100) DEFAULT NULL,
  `running_year` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=568 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_efficiency1
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_efficiency2`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_efficiency2`;
CREATE TABLE `tbl_efficiency2` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `ob_no` varchar(11) DEFAULT NULL,
  `e_activity5` text,
  `e_activity4` text,
  `e_activity3` text,
  `e_activity2` text,
  `e_activity1` text,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_efficiency2
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_emp1_training`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_emp1_training`;
CREATE TABLE `tbl_emp1_training` (
  `training_id` int(11) NOT NULL AUTO_INCREMENT,
  `training_code` varchar(20) NOT NULL,
  `training_title` text NOT NULL,
  `training_venue` varchar(255) NOT NULL,
  `participant_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `expected_participants` int(11) NOT NULL,
  `actual_participants` int(11) DEFAULT NULL,
  `budget_utilized` decimal(12,2) NOT NULL,
  `activity_classification` varchar(50) NOT NULL,
  `nature_of_activity` varchar(50) NOT NULL,
  `education_level` varchar(255) DEFAULT NULL,
  `subject_area` varchar(100) NOT NULL,
  `program_type` varchar(100) DEFAULT NULL,
  `training_level` varchar(50) NOT NULL,
  `sponsoring_group` varchar(255) NOT NULL,
  `number_hours` int(11) NOT NULL,
  `training_rate` decimal(10,2) NOT NULL,
  `conducted_by` varchar(255) NOT NULL,
  `funded_by` varchar(255) NOT NULL,
  `registration_type` varchar(20) NOT NULL,
  `cpd_points_earned` int(11) DEFAULT NULL,
  `learning_outcome` text,
  `status` varchar(20) DEFAULT 'Upcoming',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` text,
  `venue_address` varchar(255) NOT NULL,
  `mode_conduct` varchar(255) DEFAULT NULL,
  `template_image_path` varchar(255) NOT NULL,
  `school_user_id` int(11) DEFAULT NULL,
  `specific_dates` varchar(255) DEFAULT NULL,
  `neap` int(11) DEFAULT NULL,
  PRIMARY KEY (`training_id`),
  UNIQUE KEY `training_code` (`training_code`),
  KEY `fk_school_user` (`school_user_id`),
  CONSTRAINT `fk_school_user` FOREIGN KEY (`school_user_id`) REFERENCES `tbl_school_division_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tbl_emp1_training
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_emp2_training`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_emp2_training`;
CREATE TABLE `tbl_emp2_training` (
  `training_id` int(11) NOT NULL AUTO_INCREMENT,
  `training_code` varchar(20) NOT NULL,
  `training_title` text NOT NULL,
  `training_venue` varchar(255) NOT NULL,
  `participant_type` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `expected_participants` int(11) NOT NULL,
  `actual_participants` int(11) DEFAULT NULL,
  `budget_utilized` decimal(12,2) NOT NULL,
  `activity_classification` varchar(50) NOT NULL,
  `nature_of_activity` varchar(50) NOT NULL,
  `education_level` varchar(255) DEFAULT NULL,
  `subject_area` varchar(100) NOT NULL,
  `program_type` varchar(100) DEFAULT NULL,
  `training_level` varchar(50) NOT NULL,
  `sponsoring_group` varchar(255) NOT NULL,
  `number_hours` int(11) NOT NULL,
  `training_rate` decimal(10,2) NOT NULL,
  `conducted_by` varchar(255) NOT NULL,
  `funded_by` varchar(255) NOT NULL,
  `registration_type` varchar(20) NOT NULL,
  `cpd_points_earned` int(11) DEFAULT NULL,
  `learning_outcome` text,
  `status` varchar(20) DEFAULT 'Upcoming',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` text,
  `venue_address` varchar(255) NOT NULL,
  `mode_conduct` varchar(255) DEFAULT NULL,
  `template_image_path` varchar(255) NOT NULL,
  `school_user_id` int(11) DEFAULT NULL,
  `specific_dates` varchar(255) DEFAULT NULL,
  `neap` int(11) DEFAULT NULL,
  PRIMARY KEY (`training_id`),
  UNIQUE KEY `training_code` (`training_code`),
  KEY `fk_school_user` (`school_user_id`),
  CONSTRAINT `tbl_emp2_training_ibfk_1` FOREIGN KEY (`school_user_id`) REFERENCES `tbl_school_division_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tbl_emp2_training
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_employment_status`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_employment_status`;
CREATE TABLE `tbl_employment_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_status` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=963 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_employment_status
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_emp_civil_service_info`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_emp_civil_service_info`;
CREATE TABLE `tbl_emp_civil_service_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(20) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `rating` varchar(255) DEFAULT NULL,
  `date_exam` varchar(255) DEFAULT NULL,
  `place_exam` varchar(255) DEFAULT NULL,
  `license_no` varchar(255) DEFAULT NULL,
  `date_release` varchar(255) DEFAULT NULL,
  `issuing_agency` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_emp_civil_service_info
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_emp_contact_info`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_emp_contact_info`;
CREATE TABLE `tbl_emp_contact_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(20) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `phone_num` varchar(255) DEFAULT NULL,
  `mobile_num` varchar(255) DEFAULT NULL,
  `house_block_lotnum` varchar(255) DEFAULT NULL,
  `street_add` varchar(255) DEFAULT NULL,
  `subdivision_village` varchar(255) DEFAULT NULL,
  `barangay` int(11) DEFAULT NULL,
  `city_municipality` int(11) DEFAULT NULL,
  `province` int(11) DEFAULT NULL,
  `region` int(11) DEFAULT NULL,
  `zip_code` varchar(30) DEFAULT NULL,
  `house_block_lotnum1` varchar(255) DEFAULT NULL,
  `street_add1` varchar(255) DEFAULT NULL,
  `subdivision_village1` varchar(255) DEFAULT NULL,
  `barangay1` int(11) DEFAULT NULL,
  `city_municipality1` int(11) DEFAULT NULL,
  `province1` int(11) DEFAULT NULL,
  `region1` int(11) DEFAULT NULL,
  `zip_code1` varchar(30) DEFAULT NULL,
  `emergency_name` varchar(50) DEFAULT NULL,
  `emergency_num` varchar(255) DEFAULT NULL,
  `emergency_email` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `hrid` (`hrid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1853 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=261 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_emp_contact_info
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_emp_education_info`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_emp_education_info`;
CREATE TABLE `tbl_emp_education_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(20) DEFAULT NULL,
  `education_level` varchar(255) DEFAULT NULL,
  `school_name` varchar(50) DEFAULT NULL,
  `course` varchar(255) DEFAULT NULL,
  `from_year` varchar(255) DEFAULT NULL,
  `to_year` varchar(255) DEFAULT NULL,
  `year_graduated` varchar(255) DEFAULT NULL,
  `highest_grade` varchar(255) DEFAULT NULL,
  `scholarship` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=548 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=5461 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_emp_education_info
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_emp_family_info`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_emp_family_info`;
CREATE TABLE `tbl_emp_family_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(20) DEFAULT NULL,
  `relationship` varchar(255) DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `middlename` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `extension` varchar(255) DEFAULT NULL,
  `dob` varchar(255) DEFAULT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `employer_name` varchar(50) DEFAULT NULL,
  `business_add` varchar(255) DEFAULT NULL,
  `tel_num` varchar(255) DEFAULT NULL,
  `deceased` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=459 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=8192 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_emp_family_info
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_emp_official_info`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_emp_official_info`;
CREATE TABLE `tbl_emp_official_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(11) DEFAULT NULL,
  `employee_id` int(20) DEFAULT NULL,
  `item_no` varchar(50) DEFAULT NULL,
  `prefix_name` varchar(50) DEFAULT NULL,
  `nickname` varchar(50) NOT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `middlename` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `extension` varchar(255) DEFAULT NULL,
  `work_number` varchar(255) DEFAULT NULL,
  `mode_of_employement` varchar(255) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `is_reporting_manager` tinyint(1) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `service_provider` varchar(100) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `office` varchar(255) DEFAULT NULL,
  `business_id` varchar(100) DEFAULT NULL,
  `department_id` varchar(100) DEFAULT NULL,
  `division_code` varchar(11) DEFAULT NULL,
  `station_code` varchar(11) DEFAULT NULL,
  `reporting_manager` varchar(50) DEFAULT NULL,
  `job_type` varchar(50) DEFAULT NULL,
  `job_title` varchar(50) DEFAULT NULL,
  `salary_grade` int(11) DEFAULT NULL,
  `salary_annual` int(20) DEFAULT NULL,
  `salary_step` int(11) DEFAULT NULL,
  `employ_status` varchar(50) DEFAULT NULL,
  `date_of_joining` varchar(50) DEFAULT NULL,
  `date_of_promotion` varchar(50) DEFAULT NULL,
  `date_of_leaving` varchar(50) DEFAULT NULL,
  `civil_service` varchar(50) DEFAULT NULL,
  `grade_level` varchar(50) DEFAULT NULL,
  `subject_taught` varchar(50) DEFAULT NULL,
  `year_experience` int(50) DEFAULT NULL,
  `allotted_leave` decimal(10,2) DEFAULT '0.00',
  `leave_used` decimal(10,2) DEFAULT '0.00',
  `leave_balance` decimal(10,2) DEFAULT '0.00',
  `vacation_leave` decimal(10,2) DEFAULT '0.00',
  `vacation_leave_used` decimal(10,2) DEFAULT '0.00',
  `vacation_leave_balanced` decimal(10,2) DEFAULT '0.00',
  `cto` decimal(10,2) DEFAULT '0.00',
  `cto_used` decimal(10,2) DEFAULT '0.00',
  `cto_balanced` decimal(10,2) DEFAULT '0.00',
  `allotted_year` int(11) DEFAULT NULL,
  `salary_authorized` int(20) DEFAULT NULL,
  `salary_actual` int(20) DEFAULT NULL,
  `step` int(5) DEFAULT NULL,
  `code` int(20) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `level` varchar(20) DEFAULT NULL,
  `plantilla` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=21365 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=233 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_emp_official_info
-- ----------------------------
INSERT INTO `tbl_emp_official_info` (`hrid`, `employee_id`, `firstname`, `middlename`, `lastname`, `mobile_number`, `role`, `email`, `office`, `station_code`, `job_title`, `salary_grade`, `salary_actual`, `step`, `employ_status`, `date_of_joining`) VALUES
(10001, 20001, 'Juan', 'Torres', 'Santos', '09171234567', 'Employee', 'juan.santos@deped.gov.ph', 'SDO-ICT Services (ICT)', '100104', 'Information Technology Officer I', 19, 42099, 1, 'Permanent', '2024-06-10'),
(10002, 20002, 'Maria', 'Cabrera', 'Reyes', '09182345678', 'Employee', 'maria.reyes@deped.gov.ph', 'SDO-Human Resource Management Office (HRMO)', '100107', 'Administrative Officer II', 11, 20698, 3, 'Permanent', '2023-09-18'),
(10003, 20003, 'Carlo', 'Mendoza', 'Dizon', '09193456789', 'Employee', 'carlo.dizon@deped.gov.ph', 'Ozamiz City Central School', '128164', 'Teacher I', 11, 20179, 1, 'Permanent', '2022-08-01'),
(10004, 20004, 'Angelica', 'May', 'Villanueva', '09204567890', 'Employee', 'angelica.villanueva@deped.gov.ph', 'SGOD-School Health & Nutrition (SHN)', '100303', 'Nurse II', 15, 29010, 1, 'Permanent', '2021-01-15'),
(10005, 20005, 'Mark', 'Santos', 'Bacolod', '09215678901', 'Employee', 'mark.bacolod@deped.gov.ph', 'Ozamiz City National High School', '304167', 'School Principal I', 19, 42730, 2, 'Permanent', '2020-05-20');

-- ----------------------------
-- Table structure for `tbl_emp_personal_info`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_emp_personal_info`;
CREATE TABLE `tbl_emp_personal_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(20) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `civil_stat` varchar(255) DEFAULT NULL,
  `citizenship` varchar(255) DEFAULT NULL,
  `dual_citizenship` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `blood_type` varchar(255) DEFAULT NULL,
  `height` varchar(255) DEFAULT NULL,
  `weight` int(11) DEFAULT NULL,
  `dob` varchar(255) DEFAULT NULL,
  `pob` varchar(255) DEFAULT NULL,
  `prc_no` varchar(100) DEFAULT NULL,
  `tin` varchar(255) DEFAULT NULL,
  `sss` varchar(255) DEFAULT NULL,
  `gsis_bp` varchar(255) DEFAULT NULL,
  `philhealth` varchar(255) DEFAULT NULL,
  `pag_ibig` varchar(255) DEFAULT NULL,
  `gsis` varchar(255) DEFAULT NULL,
  `agency_emp_num` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1899 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=206 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_emp_personal_info
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_emp_service_record`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_emp_service_record`;
CREATE TABLE `tbl_emp_service_record` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(11) DEFAULT NULL,
  `service_from` varchar(11) DEFAULT NULL,
  `service_to` varchar(11) DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `salary` varchar(50) DEFAULT NULL,
  `place_of_assign` varchar(50) DEFAULT NULL,
  `branch` varchar(50) DEFAULT NULL,
  `leave_from` varchar(11) DEFAULT NULL,
  `leave_to` varchar(11) DEFAULT NULL,
  `separation_date` varchar(11) DEFAULT NULL,
  `separation_cause` varchar(50) DEFAULT NULL,
  `remarks` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=250 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_emp_service_record
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_emp_training`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_emp_training`;
CREATE TABLE `tbl_emp_training` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(11) DEFAULT NULL,
  `training_code` varchar(11) DEFAULT NULL,
  `training_title` varchar(100) DEFAULT NULL,
  `training_venue` varchar(255) DEFAULT NULL,
  `entry_type` varchar(100) DEFAULT NULL,
  `school_level` varchar(100) DEFAULT NULL,
  `subject_area` varchar(100) DEFAULT NULL,
  `program_type` varchar(100) DEFAULT NULL,
  `participant_type` varchar(100) DEFAULT NULL,
  `sponsoring_group` varchar(100) DEFAULT NULL,
  `start_date` varchar(10) DEFAULT NULL,
  `end_date` varchar(10) DEFAULT NULL,
  `number_hours` int(10) DEFAULT NULL,
  `level` varchar(100) DEFAULT NULL,
  `conducted_by` varchar(100) DEFAULT NULL,
  `training_rate` varchar(100) DEFAULT NULL,
  `funded_by` varchar(100) DEFAULT NULL,
  `expected_participants` varchar(100) DEFAULT NULL,
  `registration` varchar(100) DEFAULT NULL,
  `nature_of_activity` varchar(100) DEFAULT NULL,
  `cpd_points` varchar(100) DEFAULT NULL,
  `activity_role` varchar(100) DEFAULT NULL,
  `budget_utilized` varchar(100) DEFAULT NULL,
  `dateadded` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1906314 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_emp_training
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_emp_trainingxx`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_emp_trainingxx`;
CREATE TABLE `tbl_emp_trainingxx` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(11) DEFAULT NULL,
  `training_code` varchar(11) DEFAULT NULL,
  `training_title` varchar(100) DEFAULT NULL,
  `training_venue` varchar(255) DEFAULT NULL,
  `entry_type` varchar(100) DEFAULT NULL,
  `school_level` varchar(100) DEFAULT NULL,
  `subject_area` varchar(100) DEFAULT NULL,
  `program_type` varchar(100) DEFAULT NULL,
  `participant_type` varchar(100) DEFAULT NULL,
  `sponsoring_group` varchar(100) DEFAULT NULL,
  `start_date` varchar(10) DEFAULT NULL,
  `end_date` varchar(10) DEFAULT NULL,
  `number_hours` int(10) DEFAULT NULL,
  `level` varchar(100) DEFAULT NULL,
  `conducted_by` varchar(100) DEFAULT NULL,
  `training_rate` varchar(100) DEFAULT NULL,
  `funded_by` varchar(100) DEFAULT NULL,
  `expected_participants` varchar(100) DEFAULT NULL,
  `registration` varchar(100) DEFAULT NULL,
  `nature_of_activity` varchar(100) DEFAULT NULL,
  `cpd_points` varchar(100) DEFAULT NULL,
  `activity_role` varchar(100) DEFAULT NULL,
  `budget_utilized` varchar(100) DEFAULT NULL,
  `dateadded` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_emp_trainingxx
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_emp_work_experience_info`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_emp_work_experience_info`;
CREATE TABLE `tbl_emp_work_experience_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(20) DEFAULT NULL,
  `company_name` varchar(50) DEFAULT NULL,
  `position_title` varchar(255) DEFAULT NULL,
  `inclusive_date_from` varchar(255) DEFAULT NULL,
  `inclusive_date_to` varchar(255) DEFAULT NULL,
  `monthly_salary` varchar(255) DEFAULT NULL,
  `salary_grade` varchar(255) DEFAULT NULL,
  `step` varchar(255) DEFAULT NULL,
  `employment_status` varchar(255) DEFAULT NULL,
  `government_service` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=410 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=8192 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_emp_work_experience_info
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_expertise`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_expertise`;
CREATE TABLE `tbl_expertise` (
  `expertise_id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(11) DEFAULT NULL,
  `expertise` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`expertise_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_expertise
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_extension`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_extension`;
CREATE TABLE `tbl_extension` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `extension_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=2048 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_extension
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_gad_gallery`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_gad_gallery`;
CREATE TABLE `tbl_gad_gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `folder_name` varchar(99) NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of tbl_gad_gallery
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_gad_image`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_gad_image`;
CREATE TABLE `tbl_gad_image` (
  `image_id` int(10) NOT NULL AUTO_INCREMENT,
  `image_name` varchar(250) DEFAULT NULL,
  `image_caption` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`image_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_gad_image
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_gad_issuances`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_gad_issuances`;
CREATE TABLE `tbl_gad_issuances` (
  `issuance_id` int(11) NOT NULL AUTO_INCREMENT,
  `issuance_title` varchar(100) DEFAULT NULL,
  `issuance_description` varchar(100) DEFAULT NULL,
  `issuance_date` date DEFAULT NULL,
  `issuance_file` varchar(500) DEFAULT NULL,
  `issuance_category` varchar(100) DEFAULT NULL,
  `issuance_level` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`issuance_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_gad_issuances
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_gad_iss_announcement`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_gad_iss_announcement`;
CREATE TABLE `tbl_gad_iss_announcement` (
  `announcement_id` int(10) NOT NULL AUTO_INCREMENT,
  `announcement_title` varchar(100) DEFAULT NULL,
  `announcement_content` text,
  `announcement_date` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`announcement_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_gad_iss_announcement
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_gender`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_gender`;
CREATE TABLE `tbl_gender` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gender` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=8192 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_gender
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_gov_service`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_gov_service`;
CREATE TABLE `tbl_gov_service` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gov_service` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=8192 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_gov_service
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_grade`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_grade`;
CREATE TABLE `tbl_grade` (
  `grade_id` int(11) NOT NULL AUTO_INCREMENT,
  `grade_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`grade_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_grade
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_ictmrf_depaide`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_ictmrf_depaide`;
CREATE TABLE `tbl_ictmrf_depaide` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `control_number` varchar(10) DEFAULT NULL,
  `date_current` date DEFAULT NULL,
  `time_current` time DEFAULT NULL,
  `req_name` varchar(255) DEFAULT NULL,
  `req_designation` varchar(255) DEFAULT NULL,
  `req_DO` varchar(255) DEFAULT NULL,
  `DOPE` varchar(255) DEFAULT NULL,
  `brand` varchar(255) DEFAULT NULL,
  `prop_no` varchar(255) DEFAULT NULL,
  `serial_no` varchar(255) DEFAULT NULL,
  `date_last_repair` datetime DEFAULT NULL,
  `defects` text,
  `date_inspected` datetime DEFAULT NULL,
  `IPI` varchar(255) DEFAULT NULL,
  `DTS` varchar(255) DEFAULT NULL,
  `recomend` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tbl_ictmrf_depaide
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_inspection_depaide`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_inspection_depaide`;
CREATE TABLE `tbl_inspection_depaide` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item` varchar(255) NOT NULL,
  `property_no` varchar(255) NOT NULL,
  `receipt_no` varchar(255) DEFAULT NULL,
  `acquisition_cost` varchar(255) DEFAULT NULL,
  `acquisition_date` date DEFAULT NULL,
  `complaints` text,
  `scope_last_repair` text,
  `defects` text,
  `findings` text,
  `parts_repair_replace` text,
  `job_order_no` varchar(256) NOT NULL,
  `amount` varchar(256) NOT NULL,
  `invoice_no` varchar(256) NOT NULL,
  `comment_after_repair` varchar(256) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tbl_inspection_depaide
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_ipaddress`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_ipaddress`;
CREATE TABLE `tbl_ipaddress` (
  `id` int(100) NOT NULL AUTO_INCREMENT,
  `ipaddress` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_ipaddress
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_issuances`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_issuances`;
CREATE TABLE `tbl_issuances` (
  `issuance_id` int(11) NOT NULL AUTO_INCREMENT,
  `issuance_title` varchar(100) DEFAULT NULL,
  `issuance_description` varchar(100) DEFAULT NULL,
  `issuance_date` date DEFAULT NULL,
  `issuance_file` varchar(500) DEFAULT NULL,
  `issuance_category` varchar(100) DEFAULT NULL,
  `issuance_level` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`issuance_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_issuances
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_issuances_application`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_issuances_application`;
CREATE TABLE `tbl_issuances_application` (
  `application_id` int(10) NOT NULL AUTO_INCREMENT,
  `application_title` varchar(100) DEFAULT NULL,
  `application_salary` varchar(100) DEFAULT NULL,
  `qualification1` varchar(1000) DEFAULT NULL,
  `application_date` date DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` varchar(10) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`application_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_issuances_application
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_issuances_pass`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_issuances_pass`;
CREATE TABLE `tbl_issuances_pass` (
  `issuance_update_id` int(10) NOT NULL AUTO_INCREMENT,
  `issuance_id` varchar(10) DEFAULT NULL,
  `issuance_update_posted` date DEFAULT NULL,
  `issuance_pass_level` varchar(100) DEFAULT NULL,
  `issuance_result` varchar(500) DEFAULT NULL,
  `issuance_level_update` varchar(100) DEFAULT NULL,
  `issuance_pass_title` varchar(255) DEFAULT NULL,
  `issuance_pass_category` varchar(100) DEFAULT NULL,
  `issuance_pass_desc` varchar(255) DEFAULT NULL,
  `issuance_personnel_level` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`issuance_update_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_issuances_pass
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_iss_announcement`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_iss_announcement`;
CREATE TABLE `tbl_iss_announcement` (
  `announcement_id` int(10) NOT NULL AUTO_INCREMENT,
  `announcement_title` varchar(100) DEFAULT NULL,
  `announcement_content` varchar(100) DEFAULT NULL,
  `announcement_date` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`announcement_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_iss_announcement
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_iss_applicants`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_iss_applicants`;
CREATE TABLE `tbl_iss_applicants` (
  `app_id` int(10) NOT NULL AUTO_INCREMENT,
  `app_email` varchar(100) DEFAULT NULL,
  `app_fname` varchar(20) DEFAULT NULL,
  `app_lname` varchar(20) DEFAULT NULL,
  `app_mi` varchar(10) DEFAULT NULL,
  `app_address` varchar(100) DEFAULT NULL,
  `app_contact_no` varchar(20) DEFAULT NULL,
  `app_educ` varchar(50) DEFAULT NULL,
  `app_currnet` varchar(100) DEFAULT NULL,
  `app_comschool` varchar(100) DEFAULT NULL,
  `app_encode` varchar(100) DEFAULT NULL,
  `app_tor` varchar(500) DEFAULT NULL,
  `app_pds` varchar(500) DEFAULT NULL,
  `app_position_to_apply` varchar(100) DEFAULT NULL,
  `application_id` int(11) DEFAULT NULL,
  `app_date_applied` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`app_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_iss_applicants
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_job_title`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_job_title`;
CREATE TABLE `tbl_job_title` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_title` varchar(50) DEFAULT NULL,
  `job_shorten` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=233 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_job_title
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_kra`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_kra`;
CREATE TABLE `tbl_kra` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `kra_num` int(10) DEFAULT NULL,
  `kra_desc` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_kra
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_leave_history`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_leave_history`;
CREATE TABLE `tbl_leave_history` (
  `id` int(100) NOT NULL AUTO_INCREMENT,
  `hrid` int(100) DEFAULT NULL,
  `credits_from` date DEFAULT NULL,
  `credits_to` date DEFAULT NULL,
  `no_of_days` varchar(100) DEFAULT NULL,
  `particulars` varchar(100) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `balance` varchar(100) DEFAULT NULL,
  `remarks` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_leave_history
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_leave_type`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_leave_type`;
CREATE TABLE `tbl_leave_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `leave` varchar(255) DEFAULT NULL,
  `leave_type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=4096 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_leave_type
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_level`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_level`;
CREATE TABLE `tbl_level` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `level` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_level
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_mode_emp`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_mode_emp`;
CREATE TABLE `tbl_mode_emp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mode_of_emp` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=4096 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_mode_emp
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_monthly_salary`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_monthly_salary`;
CREATE TABLE `tbl_monthly_salary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `salary_grade` int(11) DEFAULT NULL,
  `salary_step` int(11) DEFAULT NULL,
  `salary_amount` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=859 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=78 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_monthly_salary
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_mov`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_mov`;
CREATE TABLE `tbl_mov` (
  `mov_id` int(10) NOT NULL AUTO_INCREMENT,
  `mov_desc` varchar(255) DEFAULT NULL,
  `date_added` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`mov_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_mov
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_mov1`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_mov1`;
CREATE TABLE `tbl_mov1` (
  `mov_id` int(10) NOT NULL AUTO_INCREMENT,
  `hrid` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `period` varchar(100) DEFAULT NULL,
  `ob_no` varchar(11) DEFAULT NULL,
  `mov_desc` varchar(255) DEFAULT NULL,
  `subject_area` varchar(255) DEFAULT NULL,
  `mov_file` varchar(255) DEFAULT NULL,
  `date_added` varchar(100) DEFAULT NULL,
  `running_year` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`mov_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=981 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_mov1
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_municipality`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_municipality`;
CREATE TABLE `tbl_municipality` (
  `municipal_id` int(11) NOT NULL AUTO_INCREMENT,
  `province_code` int(11) DEFAULT NULL,
  `municipal_code` int(11) DEFAULT NULL,
  `municipal_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`municipal_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1635 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=81 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_municipality
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_municipality1`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_municipality1`;
CREATE TABLE `tbl_municipality1` (
  `municipal_id` int(11) NOT NULL AUTO_INCREMENT,
  `province_code` int(11) DEFAULT NULL,
  `municipal_code1` int(11) DEFAULT NULL,
  `municipal_name1` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`municipal_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1635 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=81 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_municipality1
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_objectives`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_objectives`;
CREATE TABLE `tbl_objectives` (
  `ob_id` int(10) NOT NULL AUTO_INCREMENT,
  `kra_num` int(10) DEFAULT NULL,
  `ob_no` varchar(11) DEFAULT NULL,
  `ob_desc` text,
  `ob_weight` varchar(11) DEFAULT NULL,
  PRIMARY KEY (`ob_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_objectives
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_objectives1`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_objectives1`;
CREATE TABLE `tbl_objectives1` (
  `ob_id` int(10) NOT NULL AUTO_INCREMENT,
  `hrid` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `timeline` varchar(50) DEFAULT NULL,
  `kra_num` int(10) DEFAULT NULL,
  `ob_no` varchar(11) DEFAULT NULL,
  `ob_desc` text,
  `ob_weight` varchar(11) DEFAULT NULL,
  `dateadded` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `running_year` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`ob_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1632 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_objectives1
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_office`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_office`;
CREATE TABLE `tbl_office` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `office_Id` int(11) DEFAULT NULL,
  `office_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_office
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_opcrf`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_opcrf`;
CREATE TABLE `tbl_opcrf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(10) DEFAULT NULL,
  `department_id` int(10) DEFAULT NULL,
  `period` varchar(100) DEFAULT NULL,
  `kraid` varchar(10) DEFAULT NULL,
  `objid` varchar(10) DEFAULT NULL,
  `qid` int(11) DEFAULT NULL,
  `eid` int(11) DEFAULT NULL,
  `tid` int(11) DEFAULT NULL,
  `partial_rating` decimal(11,2) DEFAULT NULL,
  `final_rating` decimal(11,2) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `running_year` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=118 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_opcrf
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_opcrf1`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_opcrf1`;
CREATE TABLE `tbl_opcrf1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(10) DEFAULT NULL,
  `department_id` int(10) DEFAULT NULL,
  `kra1` varchar(10) DEFAULT NULL,
  `kra2` varchar(10) DEFAULT NULL,
  `kra3` varchar(11) DEFAULT NULL,
  `kra4` varchar(11) DEFAULT NULL,
  `kra5` varchar(11) DEFAULT NULL,
  `rating` decimal(11,2) DEFAULT NULL,
  `running_year` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_opcrf1
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_passreset_depaide`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_passreset_depaide`;
CREATE TABLE `tbl_passreset_depaide` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reason` text,
  `attachment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tbl_passreset_depaide
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_performance`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_performance`;
CREATE TABLE `tbl_performance` (
  `performance_id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(11) DEFAULT NULL,
  `cbc` varchar(100) DEFAULT NULL,
  `other_competencies` varchar(100) DEFAULT NULL,
  `kra` varchar(100) DEFAULT NULL,
  `adjectival_rating` varchar(100) DEFAULT NULL,
  `year` varchar(100) DEFAULT NULL,
  `performance_file` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`performance_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1341 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_performance
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_plantilla_assignment`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_plantilla_assignment`;
CREATE TABLE `tbl_plantilla_assignment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code1` int(11) DEFAULT NULL,
  `code2` int(11) DEFAULT NULL,
  `station_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_plantilla_assignment
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_praise_image`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_praise_image`;
CREATE TABLE `tbl_praise_image` (
  `image_id` int(10) NOT NULL AUTO_INCREMENT,
  `image_name` varchar(250) DEFAULT NULL,
  `image_caption` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`image_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_praise_image
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_praise_issuances`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_praise_issuances`;
CREATE TABLE `tbl_praise_issuances` (
  `issuance_id` int(11) NOT NULL AUTO_INCREMENT,
  `issuance_title` varchar(100) DEFAULT NULL,
  `issuance_description` varchar(100) DEFAULT NULL,
  `issuance_date` date DEFAULT NULL,
  `issuance_file` varchar(500) DEFAULT NULL,
  `issuance_category` varchar(100) DEFAULT NULL,
  `issuance_level` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`issuance_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_praise_issuances
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_praise_issuances_pass`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_praise_issuances_pass`;
CREATE TABLE `tbl_praise_issuances_pass` (
  `issuance_update_id` int(10) NOT NULL AUTO_INCREMENT,
  `issuance_id` varchar(10) DEFAULT NULL,
  `issuance_update_posted` date DEFAULT NULL,
  `issuance_pass_level` varchar(100) DEFAULT NULL,
  `issuance_result` varchar(500) DEFAULT NULL,
  `issuance_level_update` varchar(100) DEFAULT NULL,
  `issuance_pass_title` varchar(255) DEFAULT NULL,
  `issuance_pass_category` varchar(100) DEFAULT NULL,
  `issuance_pass_desc` varchar(255) DEFAULT NULL,
  `issuance_personnel_level` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`issuance_update_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_praise_issuances_pass
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_praise_iss_announcement`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_praise_iss_announcement`;
CREATE TABLE `tbl_praise_iss_announcement` (
  `announcement_id` int(10) NOT NULL AUTO_INCREMENT,
  `announcement_title` varchar(100) DEFAULT NULL,
  `announcement_content` varchar(100) DEFAULT NULL,
  `announcement_date` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`announcement_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_praise_iss_announcement
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_prefix`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_prefix`;
CREATE TABLE `tbl_prefix` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prefix_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=2048 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_prefix
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_printingid_depaide`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_printingid_depaide`;
CREATE TABLE `tbl_printingid_depaide` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `dep_id` varchar(255) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `hr_id` varchar(255) DEFAULT NULL,
  `bday` varchar(255) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `emp_id` int(11) DEFAULT NULL,
  `prc_no` varchar(255) DEFAULT NULL,
  `emrgncy_no` varchar(255) DEFAULT NULL,
  `emrgncy_name` varchar(255) DEFAULT NULL,
  `emrgncy_email` varchar(255) DEFAULT NULL,
  `prfx_name` varchar(255) DEFAULT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `mname` varchar(255) DEFAULT NULL,
  `ext_name` varchar(255) DEFAULT NULL,
  `tin_no` varchar(256) NOT NULL,
  `gsis_no` varchar(255) DEFAULT NULL,
  `pagibig_no` varchar(255) DEFAULT NULL,
  `philhealth_no` varchar(255) DEFAULT NULL,
  `blood_type` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sign` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tbl_printingid_depaide
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_program_type`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_program_type`;
CREATE TABLE `tbl_program_type` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `program_type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_program_type
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_province`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_province`;
CREATE TABLE `tbl_province` (
  `province_id` int(11) NOT NULL AUTO_INCREMENT,
  `region_code` int(11) DEFAULT NULL,
  `province_code` int(11) DEFAULT NULL,
  `province_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`province_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=188 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_province
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_province1`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_province1`;
CREATE TABLE `tbl_province1` (
  `province_id` int(11) NOT NULL AUTO_INCREMENT,
  `region_code` int(11) DEFAULT NULL,
  `province_code1` int(11) DEFAULT NULL,
  `province_name1` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`province_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=188 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_province1
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_psipop`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_psipop`;
CREATE TABLE `tbl_psipop` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `hrid` varchar(50) DEFAULT NULL,
  `item_no` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `salary_grade` varchar(100) DEFAULT NULL,
  `salary_authorized` varchar(100) DEFAULT NULL,
  `salary_actual` varchar(100) DEFAULT NULL,
  `step` varchar(5) DEFAULT NULL,
  `code` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `ppa_attribution` varchar(200) DEFAULT NULL,
  `name_of_incumbent` varchar(200) DEFAULT NULL,
  `sex` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `tin` varchar(100) DEFAULT NULL,
  `date_of_original_appointment` varchar(100) DEFAULT NULL,
  `date_of_last_promotion` varchar(100) DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL,
  `civil_service_eligibility` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_psipop
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_quality`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_quality`;
CREATE TABLE `tbl_quality` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ob_no` varchar(11) DEFAULT NULL,
  `q_activity` text,
  `pi` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=146 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_quality
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_quality2`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_quality2`;
CREATE TABLE `tbl_quality2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ob_no` varchar(11) DEFAULT NULL,
  `q_activity5` text,
  `q_activity4` text,
  `q_activity3` text,
  `q_activity2` text,
  `q_activity1` text,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_quality2
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_region`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_region`;
CREATE TABLE `tbl_region` (
  `region_id` int(11) NOT NULL AUTO_INCREMENT,
  `region_code` int(11) DEFAULT NULL,
  `region_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`region_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=910 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_region
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_region1`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_region1`;
CREATE TABLE `tbl_region1` (
  `region_id` int(11) NOT NULL AUTO_INCREMENT,
  `region_code1` int(11) DEFAULT NULL,
  `region_name1` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`region_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=910 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_region1
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_register`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_register`;
CREATE TABLE `tbl_register` (
  `registration_id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(100) DEFAULT NULL,
  `middlename` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `extension` varchar(100) DEFAULT NULL,
  `contact_no` varchar(100) DEFAULT NULL,
  `emp_status` varchar(100) DEFAULT NULL,
  `station_code` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`registration_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_register
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_relationship`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_relationship`;
CREATE TABLE `tbl_relationship` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `relationship` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=4096 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_relationship
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_remarks`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_remarks`;
CREATE TABLE `tbl_remarks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `remarks` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=8192 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_remarks
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_reporting_manager`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_reporting_manager`;
CREATE TABLE `tbl_reporting_manager` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) DEFAULT NULL,
  `manager_name` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=233 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_reporting_manager
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_requests`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_requests`;
CREATE TABLE `tbl_requests` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `hrid` int(10) DEFAULT NULL,
  `purpose` varchar(100) DEFAULT NULL,
  `attachment` varchar(100) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `type_of_request` varchar(100) DEFAULT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `running_year` varchar(10) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2075 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_requests
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_request_depaide`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_request_depaide`;
CREATE TABLE `tbl_request_depaide` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) DEFAULT NULL,
  `request_type_id` int(11) DEFAULT NULL,
  `request_type_table` enum('ict_maintenance','software_development','ict_equipment_inspection','documentation','audio_visual_editing','deped_email_request','password_reset','id_card_printing') DEFAULT NULL,
  `remarks` text,
  `stat` enum('Pending','In Progress','Completed','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rated` int(11) DEFAULT '0',
  PRIMARY KEY (`request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=424 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tbl_request_depaide
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_request_leave`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_request_leave`;
CREATE TABLE `tbl_request_leave` (
  `leaved_id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(11) DEFAULT NULL,
  `empId` int(11) DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `middlename` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `extension` varchar(255) DEFAULT NULL,
  `leave_type` varchar(255) DEFAULT NULL,
  `fdate` date DEFAULT NULL,
  `tdate` date DEFAULT NULL,
  `leave_count` int(100) DEFAULT NULL,
  `applied_on` datetime DEFAULT NULL,
  `leave_for` varchar(255) DEFAULT NULL,
  `reporting_manager` varchar(255) DEFAULT NULL,
  `reasons` varchar(255) DEFAULT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `monthly_salary` decimal(11,2) DEFAULT NULL,
  `case_in_vacation` varchar(255) DEFAULT NULL,
  `case_vacation_specify` varchar(255) DEFAULT NULL,
  `case_sick_leave` varchar(255) DEFAULT NULL,
  `case_sick_specify` varchar(255) DEFAULT NULL,
  `commutation` varchar(255) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `department` int(11) DEFAULT NULL,
  `approved_by_reporting_manager` varchar(255) DEFAULT NULL,
  `disapproved_due_to` varchar(255) DEFAULT NULL,
  `date_approved_or_disapproved_by_rm` datetime DEFAULT NULL,
  `date_approved_or_disapproved_by_hr` datetime DEFAULT NULL,
  `hr_disapproved_due_to` varchar(255) DEFAULT NULL,
  `approved_by_ao` varchar(255) DEFAULT NULL,
  `ao_disapproved_due_to` varchar(255) DEFAULT NULL,
  `date_approved_or_disapproved_by_ao` datetime DEFAULT NULL,
  `approved_by_sds` varchar(255) DEFAULT NULL,
  `sds_disapproved_due_to` varchar(255) DEFAULT NULL,
  `date_approved_or_disapproved_by_sds` datetime DEFAULT NULL,
  `days_with_pay` decimal(10,2) DEFAULT NULL,
  `days_without_pay` decimal(10,2) DEFAULT NULL,
  `others_specify` varchar(255) DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `running_year` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`leaved_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=8192 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_request_leave
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_researches`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_researches`;
CREATE TABLE `tbl_researches` (
  `research_id` int(11) NOT NULL AUTO_INCREMENT,
  `hrid` int(11) DEFAULT NULL,
  `title_of_research` varchar(255) DEFAULT NULL,
  `year_conducted` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`research_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_researches
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_role`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_role`;
CREATE TABLE `tbl_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=4096 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_role
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_salary_grade`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_salary_grade`;
CREATE TABLE `tbl_salary_grade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `salary_grade` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=606 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_salary_grade
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_school_division_users`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_school_division_users`;
CREATE TABLE `tbl_school_division_users` (
  `id` int(11) NOT NULL,
  `name` varchar(55) DEFAULT NULL,
  `email` varchar(28) DEFAULT NULL,
  `password` varchar(53) DEFAULT NULL,
  `role` varchar(12) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `id_2` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of tbl_school_division_users
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_school_level`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_school_level`;
CREATE TABLE `tbl_school_level` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `school_level` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_school_level
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_self_assessment`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_self_assessment`;
CREATE TABLE `tbl_self_assessment` (
  `id` int(50) NOT NULL AUTO_INCREMENT,
  `hrid` varchar(50) DEFAULT NULL,
  `age` varchar(50) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `employ_status` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `degree_level` varchar(100) DEFAULT NULL,
  `degree_title` varchar(200) DEFAULT NULL,
  `years_teaching` varchar(10) DEFAULT NULL,
  `aos` varchar(500) DEFAULT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `grade_level` varchar(250) DEFAULT NULL,
  `q1_1` varchar(50) DEFAULT NULL,
  `q1_2` varchar(50) DEFAULT NULL,
  `q1_3` varchar(50) DEFAULT NULL,
  `q2_1` varchar(50) DEFAULT NULL,
  `q2_2` varchar(50) DEFAULT NULL,
  `q2_3` varchar(50) DEFAULT NULL,
  `q3_1` varchar(50) DEFAULT NULL,
  `q3_2` varchar(50) DEFAULT NULL,
  `q3_3` varchar(50) DEFAULT NULL,
  `q4_1` varchar(50) DEFAULT NULL,
  `q4_2` varchar(50) DEFAULT NULL,
  `q4_3a` varchar(50) DEFAULT NULL,
  `q5_1` varchar(50) DEFAULT NULL,
  `self_management` varchar(50) DEFAULT NULL,
  `professionalism_and_ethics` varchar(50) DEFAULT NULL,
  `result_focus` varchar(50) DEFAULT NULL,
  `teamwork` varchar(50) DEFAULT NULL,
  `service_orientation` varchar(50) DEFAULT NULL,
  `innovation` varchar(50) DEFAULT NULL,
  `q1_1b` varchar(50) DEFAULT NULL,
  `q1_2b` varchar(50) DEFAULT NULL,
  `q1_3b` varchar(50) DEFAULT NULL,
  `q2_1b` varchar(50) DEFAULT NULL,
  `q2_2b` varchar(50) DEFAULT NULL,
  `q2_3b` varchar(50) DEFAULT NULL,
  `q3_1b` varchar(50) DEFAULT NULL,
  `q3_2b` varchar(50) DEFAULT NULL,
  `q3_3b` varchar(50) DEFAULT NULL,
  `q4_1b` varchar(50) DEFAULT NULL,
  `q4_2b` varchar(50) DEFAULT NULL,
  `q4_3b` varchar(50) DEFAULT NULL,
  `q5_1b` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_self_assessment
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_self_assessment_backup`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_self_assessment_backup`;
CREATE TABLE `tbl_self_assessment_backup` (
  `id` int(50) NOT NULL,
  `hrid` int(50) DEFAULT NULL,
  `age` int(10) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `employ_status` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `degree_level` varchar(100) DEFAULT NULL,
  `degree_title` varchar(200) DEFAULT NULL,
  `years_teaching` varchar(10) DEFAULT NULL,
  `aos` varchar(500) DEFAULT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `grade_level` varchar(250) DEFAULT NULL,
  `q1_1` varchar(50) DEFAULT NULL,
  `q1_2` varchar(50) DEFAULT NULL,
  `q1_3` varchar(50) DEFAULT NULL,
  `q2_1` varchar(50) DEFAULT NULL,
  `q2_2` varchar(50) DEFAULT NULL,
  `q2_3` varchar(50) DEFAULT NULL,
  `q3_1` varchar(50) DEFAULT NULL,
  `q3_2` varchar(50) DEFAULT NULL,
  `q3_3` varchar(50) DEFAULT NULL,
  `q4_1` varchar(50) DEFAULT NULL,
  `q4_2` varchar(50) DEFAULT NULL,
  `q4_3` varchar(50) DEFAULT NULL,
  `q5_1` varchar(50) DEFAULT NULL,
  `self_management` varchar(50) DEFAULT NULL,
  `professionalism_and_ethics` varchar(50) DEFAULT NULL,
  `result_focus` varchar(50) DEFAULT NULL,
  `teamwork` varchar(50) DEFAULT NULL,
  `service_orientation` varchar(50) DEFAULT NULL,
  `innovation` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_self_assessment_backup
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_self_assessment_mt`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_self_assessment_mt`;
CREATE TABLE `tbl_self_assessment_mt` (
  `id` int(50) NOT NULL AUTO_INCREMENT,
  `hrid` varchar(50) DEFAULT NULL,
  `age` varchar(50) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `employ_status` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `degree_level` varchar(100) DEFAULT NULL,
  `degree_title` varchar(200) DEFAULT NULL,
  `years_teaching` varchar(10) DEFAULT NULL,
  `aos` varchar(500) DEFAULT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `grade_level` varchar(250) DEFAULT NULL,
  `q1_1` varchar(50) DEFAULT NULL,
  `q1_2` varchar(50) DEFAULT NULL,
  `q1_3` varchar(50) DEFAULT NULL,
  `q2_1` varchar(50) DEFAULT NULL,
  `q2_2` varchar(50) DEFAULT NULL,
  `q2_3` varchar(50) DEFAULT NULL,
  `q3_1` varchar(50) DEFAULT NULL,
  `q3_2` varchar(50) DEFAULT NULL,
  `q3_3` varchar(50) DEFAULT NULL,
  `q4_1` varchar(50) DEFAULT NULL,
  `q4_2` varchar(50) DEFAULT NULL,
  `q4_3a` varchar(50) DEFAULT NULL,
  `q5_1` varchar(50) DEFAULT NULL,
  `self_management` varchar(50) DEFAULT NULL,
  `professionalism_and_ethics` varchar(50) DEFAULT NULL,
  `result_focus` varchar(50) DEFAULT NULL,
  `teamwork` varchar(50) DEFAULT NULL,
  `service_orientation` varchar(50) DEFAULT NULL,
  `innovation` varchar(50) DEFAULT NULL,
  `q1_1b` varchar(50) DEFAULT NULL,
  `q1_2b` varchar(50) DEFAULT NULL,
  `q1_3b` varchar(50) DEFAULT NULL,
  `q2_1b` varchar(50) DEFAULT NULL,
  `q2_2b` varchar(50) DEFAULT NULL,
  `q2_3b` varchar(50) DEFAULT NULL,
  `q3_1b` varchar(50) DEFAULT NULL,
  `q3_2b` varchar(50) DEFAULT NULL,
  `q3_3b` varchar(50) DEFAULT NULL,
  `q4_1b` varchar(50) DEFAULT NULL,
  `q4_2b` varchar(50) DEFAULT NULL,
  `q4_3b` varchar(50) DEFAULT NULL,
  `q5_1b` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_self_assessment_mt
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_self_assessment_t`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_self_assessment_t`;
CREATE TABLE `tbl_self_assessment_t` (
  `id` int(50) NOT NULL AUTO_INCREMENT,
  `hrid` varchar(50) DEFAULT NULL,
  `age` varchar(50) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `employ_status` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `degree_level` varchar(100) DEFAULT NULL,
  `degree_title` varchar(200) DEFAULT NULL,
  `years_teaching` varchar(10) DEFAULT NULL,
  `aos` varchar(500) DEFAULT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `grade_level` varchar(250) DEFAULT NULL,
  `q1_1` varchar(50) DEFAULT NULL,
  `q1_2` varchar(50) DEFAULT NULL,
  `q1_3` varchar(50) DEFAULT NULL,
  `q2_1` varchar(50) DEFAULT NULL,
  `q2_2` varchar(50) DEFAULT NULL,
  `q2_3` varchar(50) DEFAULT NULL,
  `q3_1` varchar(50) DEFAULT NULL,
  `q3_2` varchar(50) DEFAULT NULL,
  `q3_3` varchar(50) DEFAULT NULL,
  `q4_1` varchar(50) DEFAULT NULL,
  `q4_2` varchar(50) DEFAULT NULL,
  `q4_3a` varchar(50) DEFAULT NULL,
  `q5_1` varchar(50) DEFAULT NULL,
  `self_management` varchar(50) DEFAULT NULL,
  `professionalism_and_ethics` varchar(50) DEFAULT NULL,
  `result_focus` varchar(50) DEFAULT NULL,
  `teamwork` varchar(50) DEFAULT NULL,
  `service_orientation` varchar(50) DEFAULT NULL,
  `innovation` varchar(50) DEFAULT NULL,
  `q1_1b` varchar(50) DEFAULT NULL,
  `q1_2b` varchar(50) DEFAULT NULL,
  `q1_3b` varchar(50) DEFAULT NULL,
  `q2_1b` varchar(50) DEFAULT NULL,
  `q2_2b` varchar(50) DEFAULT NULL,
  `q2_3b` varchar(50) DEFAULT NULL,
  `q3_1b` varchar(50) DEFAULT NULL,
  `q3_2b` varchar(50) DEFAULT NULL,
  `q3_3b` varchar(50) DEFAULT NULL,
  `q4_1b` varchar(50) DEFAULT NULL,
  `q4_2b` varchar(50) DEFAULT NULL,
  `q4_3b` varchar(50) DEFAULT NULL,
  `q5_1b` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_self_assessment_t
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_settings`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_settings`;
CREATE TABLE `tbl_settings` (
  `settings_id` int(11) NOT NULL AUTO_INCREMENT,
  `type` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  PRIMARY KEY (`settings_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

-- ----------------------------
-- Records of tbl_settings
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_softdev_depaide`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_softdev_depaide`;
CREATE TABLE `tbl_softdev_depaide` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proj_name` varchar(255) DEFAULT NULL,
  `brief_desc` text NOT NULL,
  `prime_obj` text NOT NULL,
  `features` text NOT NULL,
  `spec` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `proj_deadline` datetime DEFAULT NULL,
  `add_info` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tbl_softdev_depaide
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_step`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_step`;
CREATE TABLE `tbl_step` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `step` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=1820 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_step
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_subject_area`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_subject_area`;
CREATE TABLE `tbl_subject_area` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `subject_area` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_subject_area
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_survey_questionnaire`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_survey_questionnaire`;
CREATE TABLE `tbl_survey_questionnaire` (
  `survey_question_id` int(11) NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `frm_option` varchar(255) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `survey_id` int(30) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`survey_question_id`),
  KEY `survey_id` (`survey_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of tbl_survey_questionnaire
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_survey_response`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_survey_response`;
CREATE TABLE `tbl_survey_response` (
  `survey_response_id` int(30) NOT NULL AUTO_INCREMENT,
  `survey_id` int(30) NOT NULL,
  `userId` int(11) NOT NULL,
  `answer` text NOT NULL,
  `question_id` int(30) NOT NULL,
  `is_limit` tinyint(1) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`survey_response_id`),
  KEY `survey_id` (`survey_id`),
  KEY `userId` (`userId`),
  KEY `question_id` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=605 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of tbl_survey_response
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_survey_set`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_survey_set`;
CREATE TABLE `tbl_survey_set` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of tbl_survey_set
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_tap_n_smile`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_tap_n_smile`;
CREATE TABLE `tbl_tap_n_smile` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `hrid` int(50) DEFAULT NULL,
  `grade_id` int(50) DEFAULT NULL,
  `subject_id` int(50) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `q1` varchar(50) DEFAULT NULL,
  `q2` varchar(50) DEFAULT NULL,
  `q3` varchar(50) DEFAULT NULL,
  `q4` varchar(50) DEFAULT NULL,
  `q5` varchar(50) DEFAULT NULL,
  `q6` varchar(50) DEFAULT NULL,
  `q7` varchar(50) DEFAULT NULL,
  `q8` varchar(50) DEFAULT NULL,
  `q9` varchar(50) DEFAULT NULL,
  `q10` varchar(50) DEFAULT NULL,
  `q11` varchar(50) DEFAULT NULL,
  `q12` varchar(50) DEFAULT NULL,
  `q13` varchar(50) DEFAULT NULL,
  `q14` varchar(50) DEFAULT NULL,
  `q15` varchar(50) DEFAULT NULL,
  `q16` varchar(50) DEFAULT NULL,
  `q17` varchar(50) DEFAULT NULL,
  `q18` varchar(50) DEFAULT NULL,
  `q19` varchar(50) DEFAULT NULL,
  `q20` varchar(50) DEFAULT NULL,
  `q21` varchar(50) DEFAULT NULL,
  `q22` varchar(50) DEFAULT NULL,
  `q23` varchar(50) DEFAULT NULL,
  `q24` varchar(50) DEFAULT NULL,
  `q25` varchar(50) DEFAULT NULL,
  `q26` varchar(50) DEFAULT NULL,
  `q27` varchar(50) DEFAULT NULL,
  `q28` varchar(50) DEFAULT NULL,
  `q29` varchar(50) DEFAULT NULL,
  `q30` varchar(50) DEFAULT NULL,
  `q31` varchar(50) DEFAULT NULL,
  `q32` varchar(50) DEFAULT NULL,
  `q33` varchar(50) DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_tap_n_smile
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_timeliness`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_timeliness`;
CREATE TABLE `tbl_timeliness` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `ob_no` varchar(11) DEFAULT NULL,
  `t_activity` varchar(255) DEFAULT NULL,
  `pi` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=146 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_timeliness
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_timeliness1`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_timeliness1`;
CREATE TABLE `tbl_timeliness1` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `hrid` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `ob_no` varchar(11) DEFAULT NULL,
  `t_activity` varchar(255) DEFAULT NULL,
  `pi` varchar(50) DEFAULT NULL,
  `date_added` varchar(100) DEFAULT NULL,
  `running_year` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=460 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_timeliness1
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_timeliness2`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_timeliness2`;
CREATE TABLE `tbl_timeliness2` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `ob_no` varchar(11) DEFAULT NULL,
  `t_activity5` text,
  `t_activity4` text,
  `t_activity3` text,
  `t_activity2` text,
  `t_activity1` text,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_timeliness2
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_trainings`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_trainings`;
CREATE TABLE `tbl_trainings` (
  `training_id` int(10) NOT NULL AUTO_INCREMENT,
  `training_code` varchar(100) DEFAULT NULL,
  `entry_type` varchar(100) DEFAULT NULL,
  `school_level` varchar(100) DEFAULT NULL,
  `subject_area` varchar(100) DEFAULT NULL,
  `program_type` varchar(100) DEFAULT NULL,
  `participant_type` varchar(100) DEFAULT NULL,
  `seminar_name` varchar(100) DEFAULT NULL,
  `training_venue` varchar(100) DEFAULT NULL,
  `sponsoring_group` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `number_hours` int(100) DEFAULT NULL,
  `level` varchar(100) DEFAULT NULL,
  `conducted_by` varchar(100) DEFAULT NULL,
  `funded_by` varchar(100) DEFAULT NULL,
  `training_rate` varchar(100) DEFAULT NULL,
  `expected_participants` varchar(100) DEFAULT NULL,
  `registration` varchar(100) DEFAULT NULL,
  `nature_of_activity` varchar(100) DEFAULT NULL,
  `cpd_points` varchar(100) DEFAULT NULL,
  `activity_role` varchar(100) DEFAULT NULL,
  `budget_utilized` varchar(100) DEFAULT NULL,
  `date_added` datetime DEFAULT NULL,
  `running_year` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`training_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_trainings
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_trainingsxx`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_trainingsxx`;
CREATE TABLE `tbl_trainingsxx` (
  `training_id` int(10) NOT NULL AUTO_INCREMENT,
  `training_code` varchar(100) DEFAULT NULL,
  `entry_type` varchar(100) DEFAULT NULL,
  `school_level` varchar(100) DEFAULT NULL,
  `subject_area` varchar(100) DEFAULT NULL,
  `program_type` varchar(100) DEFAULT NULL,
  `participant_type` varchar(100) DEFAULT NULL,
  `seminar_name` varchar(100) DEFAULT NULL,
  `training_venue` varchar(100) DEFAULT NULL,
  `sponsoring_group` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `number_hours` int(100) DEFAULT NULL,
  `level` varchar(100) DEFAULT NULL,
  `conducted_by` varchar(100) DEFAULT NULL,
  `funded_by` varchar(100) DEFAULT NULL,
  `training_rate` varchar(100) DEFAULT NULL,
  `expected_participants` varchar(100) DEFAULT NULL,
  `registration` varchar(100) DEFAULT NULL,
  `nature_of_activity` varchar(100) DEFAULT NULL,
  `cpd_points` varchar(100) DEFAULT NULL,
  `activity_role` varchar(100) DEFAULT NULL,
  `budget_utilized` varchar(100) DEFAULT NULL,
  `date_added` datetime DEFAULT NULL,
  PRIMARY KEY (`training_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_trainingsxx
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_training_participants`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_training_participants`;
CREATE TABLE `tbl_training_participants` (
  `participant_id` int(11) NOT NULL AUTO_INCREMENT,
  `training_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `training_hall` varchar(100) NOT NULL,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `participant_type` varchar(50) NOT NULL DEFAULT 'participant',
  PRIMARY KEY (`participant_id`),
  KEY `training_id` (`training_id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=180 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of tbl_training_participants
-- ----------------------------

-- ----------------------------
-- Table structure for `tbl_user`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_user`;
CREATE TABLE `tbl_user` (
  `userId` int(11) NOT NULL AUTO_INCREMENT,
  `hrId` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `middlename` varchar(255) DEFAULT NULL,
  `extname` varchar(50) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'avatar-default.jpg',
  `job_title` varchar(255) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `date_created` date DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`userId`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=21365 DEFAULT CHARSET=latin1 AVG_ROW_LENGTH=192 ROW_FORMAT=COMPACT;

-- ----------------------------
-- Records of tbl_user
-- ----------------------------
INSERT INTO `tbl_user` (`hrId`, `email`, `password`, `lastname`, `firstname`, `middlename`, `extname`, `avatar`, `job_title`, `role`, `active`, `date_created`, `fullname`, `department_id`) VALUES
(10001, 'juan.santos@deped.gov.ph', '1234', 'Santos', 'Juan', 'Torres', NULL, 'avatar-default.jpg', 'Information Technology Officer I', 'Employee', 1, '2026-02-22', 'Juan Torres Santos', 100104),
(10002, 'maria.reyes@deped.gov.ph', '1234', 'Reyes', 'Maria', 'Cabrera', NULL, 'avatar-default.jpg', 'Administrative Officer II', 'Employee', 1, '2026-02-22', 'Maria Cabrera Reyes', 100107),
(10003, 'carlo.dizon@deped.gov.ph', '1234', 'Dizon', 'Carlo', 'Mendoza', NULL, 'avatar-default.jpg', 'Teacher I', 'Employee', 1, '2026-02-22', 'Carlo Mendoza Dizon', 128164),
(10004, 'angelica.villanueva@deped.gov.ph', '1234', 'Villanueva', 'Angelica', 'May', NULL, 'avatar-default.jpg', 'Nurse II', 'Employee', 1, '2026-02-22', 'Angelica May Villanueva', 100303),
(10005, 'mark.bacolod@deped.gov.ph', '1234', 'Bacolod', 'Mark', 'Santos', NULL, 'avatar-default.jpg', 'School Principal I', 'Employee', 1, '2026-02-22', 'Mark Santos Bacolod', 304167);

-- ----------------------------
-- Table structure for `user`
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(20) NOT NULL,
  `unique_id` varchar(20) CHARACTER SET latin1 NOT NULL,
  `user_fname` varchar(50) CHARACTER SET latin1 NOT NULL,
  `user_lname` varchar(30) CHARACTER SET latin1 NOT NULL,
  `user_email` varchar(50) CHARACTER SET latin1 NOT NULL,
  `bio` varchar(100) CHARACTER SET latin1 NOT NULL,
  `created_at` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `dob` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `address` varchar(200) COLLATE utf8mb4_bin NOT NULL,
  `user_pass` varchar(20) CHARACTER SET latin1 NOT NULL,
  `user_avtar` varchar(200) CHARACTER SET latin1 NOT NULL,
  `user_status` varchar(10) CHARACTER SET latin1 NOT NULL,
  `last_logout` varchar(30) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`unique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Records of user
-- ----------------------------

-- ----------------------------
-- Table structure for `user_messages`
-- ----------------------------
DROP TABLE IF EXISTS `user_messages`;
CREATE TABLE `user_messages` (
  `time` datetime(6) NOT NULL,
  `sender_message_id` varchar(20) CHARACTER SET latin1 NOT NULL,
  `receiver_message_id` varchar(20) CHARACTER SET latin1 NOT NULL,
  `message` varchar(500) CHARACTER SET latin1 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Records of user_messages
-- ----------------------------
