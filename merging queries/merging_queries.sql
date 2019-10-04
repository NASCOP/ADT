

-- first make calls for all pk ->fk fields

CALL mergeTable('kitale_ADT','adt_central','generic_name','id','drugcode','generic_name','name');
CALL mergeTable('kitale_ADT','adt_central','drug_classification','id','drugcode','classification','name');
CALL mergeTable('kitale_ADT','adt_central','generic_name','id','drugcode','generic_name','name');
CALL mergeTable('kitale_ADT','adt_central','regimen_category','id','regimen','category','name');
CALL mergeTable('kitale_ADT','adt_central','drug_source','id','drug_stock_movement','Source_Destination','name');
CALL mergeTable('kitale_ADT','adt_central','patient_source','id','patient','source','name');
CALL mergeTable('kitale_ADT','adt_central','patient_status','id','patient','current_status','name'); 


CALL mergeTable('kitale_ADT', 'adt_central',
'regimen_change_purpose','id',
'regimen_change_log,patient_visit',
'change_purpose,regimen_change_reason'
); 


CALL mergeTable('kitale_ADT', 'adt_central',
'transaction_type', 'id',
'drug_stock_movement,drug_stock_movement', -- table params
'transaction_type,batch_number' -- fk params
); 

drop table tmp_table;
CALL mergeTable('kitale_ADT','adt_central',
'regimen','id',
'patient_visit,patient,patient,patient,regimen_drug,regimen_change_log,regimen_change_log', -- ref_tbls
'regimen,last_regimen,start_regimen,current_regimen,regimen,old_regimen,new_regimen' ,-- fk_cols
'regimen_code'
); 

CALL mergeTable('kitale_ADT','adt_central',
'transaction_type','id',
'drug_stock_movement,drug_stock_movement', -- ref_tbls
'transaction_type,batch_number' ,-- fk_cols
'name'
); 


CALL mergeTable('kitale_ADT','adt_central',
'visit_purpose','id',
'patient_visit', -- ref_tbls
'visit_purpose' , -- fk_cols
'name'

); 


---- updating ref values first 
-- 1. Patient table

update kitale_ADT.patient ,adt_central.regimen,kitale_ADT.regimen set kitale_ADT.patient.start_regimen = adt_central.regimen.id
where kitale_ADT.regimen.regimen_code = adt_central.regimen.regimen_code and kitale_ADT.patient.start_regimen = kitale_ADT.regimen.id ;

update kitale_ADT.patient ,adt_central.regimen,kitale_ADT.regimen set kitale_ADT.patient.current_regimen = adt_central.regimen.id
where kitale_ADT.regimen.regimen_code = adt_central.regimen.regimen_code and kitale_ADT.patient.current_regimen = kitale_ADT.regimen.id ;

update kitale_ADT.patient ,adt_central.drug_prophylaxis,kitale_ADT.drug_prophylaxis set kitale_ADT.patient.drug_prophylaxis = adt_central.drug_prophylaxis.id
where kitale_ADT.drug_prophylaxis.name = adt_central.drug_prophylaxis.name and kitale_ADT.patient.drug_prophylaxis = kitale_ADT.drug_prophylaxis.id ;

update kitale_ADT.patient ,adt_central.patient_status,kitale_ADT.patient_status set kitale_ADT.patient.current_status = adt_central.patient_status.id
where kitale_ADT.patient_status.name = adt_central.patient_status.name and kitale_ADT.patient.current_status = kitale_ADT.patient_status.id ;

update kitale_ADT.patient ,adt_central.patient_source,kitale_ADT.patient_source set kitale_ADT.patient.source = adt_central.patient_source.id
where kitale_ADT.patient_source.name = adt_central.patient_source.name and kitale_ADT.patient.source = kitale_ADT.patient_source.id ;

update kitale_ADT.patient ,adt_central.regimen_service_type,kitale_ADT.regimen_service_type set kitale_ADT.patient.service = adt_central.regimen_service_type.id
where kitale_ADT.regimen_service_type.name = adt_central.regimen_service_type.name and kitale_ADT.patient.service = kitale_ADT.regimen_service_type.id ;

update kitale_ADT.patient ,adt_central.gender,kitale_ADT.gender set kitale_ADT.patient.gender = adt_central.gender.id
where kitale_ADT.gender.name = adt_central.gender.name and kitale_ADT.patient.gender = kitale_ADT.gender.id ;


insert into adt_central.patient
(medical_record_number,patient_number_ccc,first_name,last_name,other_name,dob,pob,gender,pregnant,breastfeeding,weight,height,sa,bmi,phone,physical,alternate,other_illnesses,other_drugs,adr,tb,smoke,alcohol,date_enrolled,source,supported_by,timestamp,facility_code,service,start_regimen,start_regimen_date,current_status,migration_id,machine_code,sms_consent,partner_status,fplan,tbphase,startphase,endphase,disclosure,non_commun,status_change_date,partner_type,support_group,current_regimen,Start_Regimen_Merged_From,Current_Regimen_Merged_From,nextappointment,clinicalappointment,differentiated_care,adherence,start_height,start_weight,start_bsa,start_bmi,transfer_from,active,drug_allergies,tb_test,pep_reason,who_stage,drug_prophylaxis,ccc_store_sp,isoniazid_start_date,isoniazid_end_date,tb_category)
select medical_record_number,patient_number_ccc,first_name,last_name,other_name,dob,pob,gender,pregnant,breastfeeding,weight,height,sa,bmi,phone,physical,alternate,other_illnesses,other_drugs,adr,tb,smoke,alcohol,date_enrolled,source,supported_by,timestamp,facility_code,service,start_regimen,start_regimen_date,current_status,migration_id,machine_code,sms_consent,partner_status,fplan,tbphase,startphase,endphase,disclosure,non_commun,status_change_date,partner_type,support_group,current_regimen,Start_Regimen_Merged_From,Current_Regimen_Merged_From,nextappointment,clinicalappointment,differentiated_care,adherence,start_height,start_weight,start_bsa,start_bmi,transfer_from,active,drug_allergies,tb_test,pep_reason,who_stage,drug_prophylaxis,ccc_store_sp,isoniazid_start_date,isoniazid_end_date,tb_category from kitale_ADT.patient
union 
select medical_record_number,patient_number_ccc,first_name,last_name,other_name,dob,pob,gender,pregnant,breastfeeding,weight,height,sa,bmi,phone,physical,alternate,other_illnesses,other_drugs,adr,tb,smoke,alcohol,date_enrolled,source,supported_by,timestamp,facility_code,service,start_regimen,start_regimen_date,current_status,migration_id,machine_code,sms_consent,partner_status,fplan,tbphase,startphase,endphase,disclosure,non_commun,status_change_date,partner_type,support_group,current_regimen,Start_Regimen_Merged_From,Current_Regimen_Merged_From,nextappointment,clinicalappointment,differentiated_care,adherence,start_height,start_weight,start_bsa,start_bmi,transfer_from,active,drug_allergies,tb_test,pep_reason,who_stage,drug_prophylaxis,ccc_store_sp,isoniazid_start_date,isoniazid_end_date,tb_category from adt_central.patient;

insert into adt_central.dependants (parent,child)select parent,child from kitale_ADT.dependants;

-- add patient child (referenced) tables

insert into adt_central.dependants (parent,child) select parent,child from kitale_ADT.dependants;

insert into adt_central.patient_viral_load (patient_ccc_number,test_date,date_collected,result,justification,test_id) select patient_ccc_number,test_date,date_collected,result,justification,test_id from kitale_ADT.patient_viral_load;

-- 2. patient_visit 