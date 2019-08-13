ALTER TABLE facilities ADD autobackup tinyint(1) NOT NULL DEFAULT '0' AFTER map//
ALTER TABLE facilities DROP lost_to_follow_up //
ALTER TABLE facilities ADD lost_to_follow_up int(11) default 90 AFTER autobackup//
ALTER TABLE facilities ADD service_hep int(11) NOT NULL DEFAULT '0' AFTER service_pep//
ALTER TABLE facilities ADD service_prep int(11) NOT NULL DEFAULT '0' AFTER service_pep//
ALTER TABLE facilities ADD medical_number tinyint(1) NOT NULL DEFAULT '0' AFTER map//
ALTER TABLE facilities ADD pill_count tinyint(1) NOT NULL DEFAULT '1' AFTER map//
ALTER TABLE `facilities` CHANGE `facilitycode` `facilitycode` varchar(10) NOT NULL AFTER `id`//
ALTER TABLE facilities ADD facility_dhis tinyint(1) NOT NULL DEFAULT '0' AFTER map//
ALTER TABLE facilities ADD lost_to_follow_up int(11) default 90//
ALTER TABLE facilities ADD ccc_separator char(2) NULL//
