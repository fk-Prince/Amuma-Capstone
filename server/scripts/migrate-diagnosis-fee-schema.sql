-- Brings an existing database up to the new schema without wiping data.
-- Run the whole script once in pgAdmin. It is a single transaction, so any
-- error leaves the database unchanged.

BEGIN;

-- 1. additional_charges belongs to the patient, not the admission
ALTER TABLE additional_charges ADD COLUMN patient_id bigint;

UPDATE additional_charges ac
SET patient_id = pa.patient_id
FROM patient_admissions pa
WHERE pa.patient_admission_id = ac.patient_admission_id;

ALTER TABLE additional_charges ALTER COLUMN patient_id SET NOT NULL;

ALTER TABLE additional_charges
    ADD CONSTRAINT additional_charges_patient_id_foreign
    FOREIGN KEY (patient_id) REFERENCES patients (patient_id)
    ON UPDATE CASCADE ON DELETE RESTRICT;

-- 2. Old diagnosis-case charges stay as ordinary charges (already invoiced),
--    keeping the diagnosis name in the description
UPDATE additional_charges ac
SET description = LEFT(ac.description || ' (' || pd.diagnosis || ')', 255)
FROM additional_charge_diagnosis acd
JOIN patient_diagnosis pd ON pd.patient_diagnosis_id = acd.patient_diagnosis_id
WHERE acd.additional_charge_id = ac.additional_charge_id
  AND ac.type = 'diagnosis_case'
  AND pd.diagnosis IS NOT NULL;

UPDATE additional_charges SET type = 'additional_charges' WHERE type = 'diagnosis_case';

ALTER TABLE additional_charges DROP CONSTRAINT IF EXISTS additional_charges_type_check;
ALTER TABLE additional_charges
    ADD CONSTRAINT additional_charges_type_check
    CHECK (type::text = ANY (ARRAY['medication', 'supplies', 'additional_charges']::text[]));

-- 3. Drop the admission link and the old pivot
ALTER TABLE additional_charges DROP COLUMN patient_admission_id;

DROP TABLE IF EXISTS additional_charge_diagnosis;

-- 4. Diagnosis cases: a charge (period, invoice, total) and the diagnoses under it (case, amount, description)
DROP TABLE IF EXISTS admission_diagnosis;
DROP TABLE IF EXISTS admission_charge_diagnosis;

CREATE TABLE admission_charge_diagnosis (
    admission_charge_diagnosis_id bigserial PRIMARY KEY,
    admission_period_id bigint NOT NULL
        REFERENCES admission_periods (admission_period_id) ON DELETE CASCADE,
    invoice_id bigint NULL
        REFERENCES invoices (invoice_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    amount numeric(10, 2) NOT NULL,
    created_at timestamp(0) without time zone NULL,
    updated_at timestamp(0) without time zone NULL
);

CREATE TABLE admission_diagnosis (
    admission_diagnosis_id bigserial PRIMARY KEY,
    admission_charge_diagnosis_id bigint NOT NULL
        REFERENCES admission_charge_diagnosis (admission_charge_diagnosis_id) ON DELETE CASCADE,
    patient_diagnosis_id bigint NOT NULL
        REFERENCES patient_diagnosis (patient_diagnosis_id) ON DELETE CASCADE,
    diagnosis_case_id bigint NULL
        REFERENCES diagnosis_cases (diagnosis_case_id) ON DELETE SET NULL,
    description varchar(255) NULL,
    amount numeric(10, 2) NOT NULL,
    created_at timestamp(0) without time zone NULL,
    updated_at timestamp(0) without time zone NULL
);

-- 5. Keep Laravel's migration list in step so `php artisan migrate` has nothing left to run
DELETE FROM migrations WHERE migration = '2026_09_28_120100_create_additional_charge_diagnosis_table';

INSERT INTO migrations (migration, batch)
SELECT name, COALESCE((SELECT MAX(batch) FROM migrations), 0) + 1
FROM (VALUES
    ('2026_09_28_120100_create_admission_charge_diagnosis_table'),
    ('2026_09_28_120200_create_admission_diagnosis_table')
) AS wanted(name)
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = wanted.name);

COMMIT;

-- Check afterwards: should list patient_id and no patient_admission_id
SELECT column_name FROM information_schema.columns
WHERE table_name = 'additional_charges' ORDER BY ordinal_position;
