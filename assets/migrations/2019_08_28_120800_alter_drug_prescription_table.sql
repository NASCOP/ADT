ALTER TABLE drug_prescription CHANGE `patient` `patient` varchar(20) NULL//
ALTER TABLE drug_prescription CHANGE `order_number` `order_number` bigint(20) NULL//
ALTER TABLE drug_prescription CHANGE `order_status` `order_status` varchar(30) NULL//
ALTER TABLE drug_prescription CHANGE `order_physician` `order_physician` varchar(100) NULL//
ALTER TABLE drug_prescription CHANGE `notes` `notes` varchar(200) NULL//
ALTER TABLE drug_prescription CHANGE `timecreated` `timecreated` timestamp NULL//