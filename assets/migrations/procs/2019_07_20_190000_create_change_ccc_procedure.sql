DELIMITER ;;

DROP PROCEDURE IF EXISTS `change_ccc`;;
CREATE PROCEDURE `change_ccc`(IN `old_ccc` varchar(40), IN `new_ccc` varchar(40))
BEGIN
update patient set patient_number_ccc = new_ccc where  patient_number_ccc = old_ccc;
update clinic_appointment set patient = new_ccc where patient = old_ccc;
update patient_appointment set patient = new_ccc where patient = old_ccc;
update patient_viral_load set patient_ccc_number = new_ccc where patient_ccc_number = old_ccc;
update patient_visit set patient_id = new_ccc where patient_id = old_ccc;
update spouses set primary_spouse = new_ccc where primary_spouse = old_ccc;
update spouses set secondary_spouse = new_ccc where secondary_spouse = old_ccc;
update drug_prescription set patient = new_ccc where patient = old_ccc;
update dependants set parent = new_ccc where parent = old_ccc;
update dependants set child = new_ccc where child = old_ccc;
update dcm_change_log set patient =new_ccc where patient = old_ccc;
END;;

DELIMITER ;