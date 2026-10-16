-- MySQL / phpMyAdmin version. Brings an existing database up to the new schema
-- without wiping data. Select your database in phpMyAdmin, open the SQL tab,
-- paste the whole script and run it once.
-- MySQL commits each ALTER and CREATE as it goes, so an error part way leaves
-- the earlier steps applied. If it stops, fix the cause and run only the
-- steps that have not run yet.

-- 1. additional_charges belongs to the patient, not the admission
ALTER TABLE additional_charges ADD COLUMN patient_id BIGINT UNSIGNED NULL;

UPDATE additional_charges ac
JOIN patient_admissions pa ON pa.patient_admission_id = ac.patient_admission_id
SET ac.patient_id = pa.patient_id;

ALTER TABLE additional_charges MODIFY patient_id BIGINT UNSIGNED NOT NULL;

ALTER TABLE additional_charges
    ADD CONSTRAINT additional_charges_patient_id_foreign
    FOREIGN KEY (patient_id) REFERENCES patients (patient_id)
    ON UPDATE CASCADE ON DELETE RESTRICT;

-- 2. Old diagnosis-case charges stay as ordinary charges (already invoiced),
--    keeping the diagnosis name in the description
UPDATE additional_charges ac
JOIN additional_charge_diagnosis acd ON acd.additional_charge_id = ac.additional_charge_id
JOIN patient_diagnosis pd ON pd.patient_diagnosis_id = acd.patient_diagnosis_id
SET ac.description = LEFT(CONCAT(ac.description, ' (', pd.diagnosis, ')'), 255)
WHERE ac.type = 'diagnosis_case'
  AND pd.diagnosis IS NOT NULL;

UPDATE additional_charges SET type = 'additional_charges' WHERE type = 'diagnosis_case';

ALTER TABLE additional_charges
    MODIFY type ENUM('medication', 'supplies', 'additional_charges') NOT NULL;

-- 3. Drop the admission link and the old pivot
ALTER TABLE additional_charges DROP FOREIGN KEY additional_charges_patient_admission_id_foreign;
ALTER TABLE additional_charges DROP COLUMN patient_admission_id;

DROP TABLE IF EXISTS additional_charge_diagnosis;

-- 4. Diagnosis cases: a charge (period, invoice, total) and the diagnoses under it (case, amount, description)
DROP TABLE IF EXISTS admission_diagnosis;
DROP TABLE IF EXISTS admission_charge_diagnosis;

CREATE TABLE admission_charge_diagnosis (
    admission_charge_diagnosis_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    admission_period_id BIGINT UNSIGNED NOT NULL,
    invoice_id BIGINT UNSIGNED NULL,
    amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT admission_charge_diagnosis_period_foreign
        FOREIGN KEY (admission_period_id) REFERENCES admission_periods (admission_period_id) ON DELETE CASCADE,
    CONSTRAINT admission_charge_diagnosis_invoice_foreign
        FOREIGN KEY (invoice_id) REFERENCES invoices (invoice_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE = InnoDB;

CREATE TABLE admission_diagnosis (
    admission_diagnosis_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    admission_charge_diagnosis_id BIGINT UNSIGNED NOT NULL,
    patient_diagnosis_id BIGINT UNSIGNED NOT NULL,
    diagnosis_case_id BIGINT UNSIGNED NULL,
    description VARCHAR(255) NULL,
    amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT admission_diagnosis_charge_foreign
        FOREIGN KEY (admission_charge_diagnosis_id) REFERENCES admission_charge_diagnosis (admission_charge_diagnosis_id) ON DELETE CASCADE,
    CONSTRAINT admission_diagnosis_diagnosis_foreign
        FOREIGN KEY (patient_diagnosis_id) REFERENCES patient_diagnosis (patient_diagnosis_id) ON DELETE CASCADE,
    CONSTRAINT admission_diagnosis_case_foreign
        FOREIGN KEY (diagnosis_case_id) REFERENCES diagnosis_cases (diagnosis_case_id) ON DELETE SET NULL
) ENGINE = InnoDB;

-- 5. Keep Laravel's migration list in step so `php artisan migrate` has nothing left to run
DELETE FROM migrations WHERE migration = '2026_09_28_120100_create_additional_charge_diagnosis_table';

INSERT INTO migrations (migration, batch)
SELECT wanted.name, (SELECT COALESCE(MAX(m.batch), 0) + 1 FROM migrations m)
FROM (
    SELECT '2026_09_28_120100_create_admission_charge_diagnosis_table' AS name
    UNION ALL
    SELECT '2026_09_28_120200_create_admission_diagnosis_table'
) AS wanted
WHERE NOT EXISTS (SELECT 1 FROM migrations x WHERE x.migration = wanted.name);

-- Check afterwards: should list patient_id and no patient_admission_id
SHOW COLUMNS FROM additional_charges;
