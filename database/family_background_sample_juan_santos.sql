-- Sample family background data for juan.santos@deped.gov.ph (hrid = 10001)
-- Run this in your database after tbl_emp_family_info exists.

DELETE FROM `tbl_emp_family_info` WHERE `hrid` = 10001;

INSERT INTO `tbl_emp_family_info` (`hrid`, `relationship`, `firstname`, `middlename`, `lastname`, `extension`, `dob`, `occupation`, `employer_name`, `business_add`, `tel_num`) VALUES
(10001, 'Spouse', 'Maria', 'Reyes', 'Dela Cruz', NULL, NULL, 'Teacher', 'DepEd Ozamiz City', 'Ozamiz City Central School', '088-521-1234'),
(10001, 'Child', 'Ana Santos', NULL, NULL, NULL, '15/03/2010', NULL, NULL, NULL, NULL),
(10001, 'Child', 'Jose Santos', NULL, NULL, NULL, '22/08/2012', NULL, NULL, NULL, NULL),
(10001, 'Father', 'Pedro', 'Torres', 'Santos', 'Sr.', NULL, NULL, NULL, NULL, NULL),
(10001, 'Mother', 'Rosa', 'Garcia', 'Torres', NULL, NULL, NULL, NULL, NULL, NULL);
