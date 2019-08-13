UPDATE `sync_regimen_category` SET `Active` = '1' WHERE `Active` = ''//
UPDATE `sync_regimen_category` SET `Active` = '0' WHERE  `Name` IN ('Other Pediatric Regimen', 'OIs Medicines {CM} and {OC} For Diflucan Donation Program ONLY')//
UPDATE sync_regimen_category SET Active = 0 WHERE Name IN ('Other Adult ART', 'OIs Medicines {CM} and {OC} For Diflucan Donation')//
UPDATE sync_regimen SET category_id = 20 WHERE code IN ('OI4AN', 'OI4CN') AND category_id = 24//