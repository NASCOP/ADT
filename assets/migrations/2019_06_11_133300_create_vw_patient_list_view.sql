DROP view vw_patient_list //
CREATE VIEW vw_patient_list AS
    SELECT 
        p.id AS patient_id,
        p.patient_number_ccc AS ccc_number,
        p.first_name AS first_name,
        p.other_name AS other_name,
        p.last_name AS last_name,
        p.dob AS date_of_birth,
ROUND (DATEDIFF(CURDATE(),p.dob)/365) AS age,
        IF(ROUND (DATEDIFF(CURDATE(),p.dob)/365) >= 15,
            'Adult',
            'Paediatric') AS maturity,
        p.pob AS pob,
        IF((p.gender = 1), 'MALE', 'FEMALE') AS gender,
        IF((p.pregnant = 1), 'YES', 'NO') AS pregnant,
        p.weight AS current_weight,
        p.height AS current_height,
        p.sa AS current_bsa,
        p.bmi AS current_bmi,
        p.phone AS phone_number,
        p.physical AS physical_address,
        p.alternate AS alternate_address,
        p.other_illnesses AS other_illnesses,
        p.other_drugs AS other_drugs,
        p.adr AS drug_allergies,
        IF((p.tb = 1), 'YES', 'NO') AS tb,
        IF((p.smoke = 1), 'YES', 'NO') AS smoke,
        IF((p.alcohol = 1), 'YES', 'NO') AS alcohol,
        p.date_enrolled AS date_enrolled,
        ps.name AS patient_source,
        s.Name AS supported_by,
        rst.name AS service,
        r1.regimen_code AS start_regimen,
        p.start_regimen_date AS start_regimen_date,
        pst.Name AS current_status,
        IF((p.sms_consent = 1), 'YES', 'NO') AS sms_consent,
        p.fplan AS family_planning,
        p.tbphase AS tbphase,
        p.startphase AS startphase,
        p.endphase AS endphase,
        IF((p.partner_status = 1),
            'Concordant',
            IF((p.partner_status = 2),
                'Discordant',
                IF((p.partner_status = 3),
                    'Unknown',
                    ''))) AS partner_status,
        p.status_change_date AS status_change_date,
        IF((p.partner_type = 1), 'YES', 'NO') AS disclosure,
        p.support_group AS support_group,
        r.regimen_code AS current_regimen,
        p.nextappointment AS nextappointment,
        p.clinicalappointment AS clinicalappointment,
        (TO_DAYS(p.nextappointment) - TO_DAYS(CURDATE())) AS days_to_nextappointment,
        p.start_height AS start_height,
        p.start_weight AS start_weight,
        p.start_bsa AS start_bsa,
        p.start_bmi AS start_bmi,
        IF((p.transfer_from <> ''),
            f.name,
            'N/A') AS transfer_from,
        REPLACE(REPLACE(REPLACE(REPLACE(p.drug_prophylaxis,
                        (SELECT 
                                drug_prophylaxis.id
                            FROM
                                drug_prophylaxis
                            LIMIT 1),
                        (SELECT 
                                drug_prophylaxis.name
                            FROM
                                drug_prophylaxis
                            LIMIT 1)),
                    (SELECT 
                            drug_prophylaxis.id
                        FROM
                            drug_prophylaxis
                        LIMIT 1 , 1),
                    (SELECT 
                            drug_prophylaxis.name
                        FROM
                            drug_prophylaxis
                        LIMIT 1 , 1)),
                (SELECT 
                        drug_prophylaxis.id
                    FROM
                        drug_prophylaxis
                    LIMIT 2 , 1),
                (SELECT 
                        drug_prophylaxis.name
                    FROM
                        drug_prophylaxis
                    LIMIT 2 , 1)),
            (SELECT 
                    drug_prophylaxis.id
                FROM
                    drug_prophylaxis
                LIMIT 3 , 1),
            (SELECT 
                    drug_prophylaxis.name
                FROM
                    drug_prophylaxis
                LIMIT 3 , 1)) AS prophylaxis,
        p.isoniazid_start_date as isoniazid_start_date,
        p.isoniazid_end_date as isoniazid_end_date,
        pep_reason.name AS pep_reason,
        prep_reason.name AS prep_reason,
        patient_prep_test.test_date AS prep_reason_test_date,
        patient_prep_test.test_result AS prep_reason_test_result,
        (SELECT 
                patient_viral_load.result
            FROM
                patient_viral_load
            WHERE
                patient_ccc_number = p.patient_number_ccc
            ORDER BY test_date DESC
            LIMIT 1) AS viral_load_test_results,
        IF((p.differentiated_care = 1),
            'differentiated',
            IF((p.differentiated_care = 0),
                'notdifferentiated',
                'notdifferentiated')) AS differentiated_care_status
    FROM
        (((((((((((patient p
        LEFT JOIN regimen r ON ((r.id = p.current_regimen)))
        LEFT JOIN regimen r1 ON ((r1.id = p.start_regimen)))
        LEFT JOIN patient_source ps ON ((ps.id = p.source)))
        LEFT JOIN supporter s ON ((s.id = p.supported_by)))
        LEFT JOIN regimen_service_type rst ON ((rst.id = p.service)))
        LEFT JOIN patient_status pst ON ((pst.id = p.current_status)))
        LEFT JOIN facilities f ON ((f.facilitycode = p.transfer_from)))
        LEFT JOIN drug_prophylaxis dp ON ((dp.id = p.drug_prophylaxis)))
        LEFT JOIN patient_prep_test ON ((p.id = patient_prep_test.patient_id)))
        LEFT JOIN prep_reason ON ((patient_prep_test.prep_reason_id = prep_reason.id)))
        LEFT JOIN pep_reason ON ((p.pep_reason = pep_reason.id)))
    WHERE
        (p.active = 1) //