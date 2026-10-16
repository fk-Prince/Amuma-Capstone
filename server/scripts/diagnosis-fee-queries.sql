-- Run each block on its own in pgAdmin's Query Tool.

-- 1. Diagnosis cases billed per admission period, with their invoice
SELECT p.first_name || ' ' || p.last_name AS patient,
       pa.patient_admission_id,
       ap.admission_period_id,
       ap.reason,
       pd.diagnosis,
       dc.title AS diagnosis_case,
       ad.amount AS monthly_amount,
       acd.amount AS billed_amount,
       ad.description,
       i.invoice_code,
       i.status,
       acd.created_at
FROM admission_diagnosis ad
JOIN admission_charge_diagnosis acd ON acd.admission_charge_diagnosis_id = ad.admission_charge_diagnosis_id
JOIN admission_periods ap ON ap.admission_period_id = acd.admission_period_id
JOIN patient_admissions pa ON pa.patient_admission_id = ap.patient_admission_id
JOIN patients p ON p.patient_id = pa.patient_id
JOIN patient_diagnosis pd ON pd.patient_diagnosis_id = ad.patient_diagnosis_id
LEFT JOIN invoices i ON i.invoice_id = acd.invoice_id
LEFT JOIN diagnosis_cases dc ON dc.diagnosis_case_id = ad.diagnosis_case_id
ORDER BY acd.created_at DESC;

-- 2. Waiting admissions and whether both invoices (stay + diagnosis cases) are settled
SELECT pa.patient_admission_id,
       p.first_name || ' ' || p.last_name AS patient,
       pa.status AS admission_status,
       (SELECT string_agg(DISTINCT i.invoice_code || ' (' || i.status || ')', ', ')
        FROM invoice_admission ia
        JOIN invoices i ON i.invoice_id = ia.invoice_id
        JOIN admission_periods ap ON ap.admission_period_id = ia.admission_period_id
        WHERE ap.patient_admission_id = pa.patient_admission_id) AS stay_invoices,
       (SELECT string_agg(DISTINCT i.invoice_code || ' (' || i.status || ')', ', ')
        FROM admission_diagnosis ad
        JOIN admission_charge_diagnosis acd ON acd.admission_charge_diagnosis_id = ad.admission_charge_diagnosis_id
        JOIN admission_periods ap2 ON ap2.admission_period_id = acd.admission_period_id
        JOIN invoices i ON i.invoice_id = acd.invoice_id
        WHERE ap2.patient_admission_id = pa.patient_admission_id) AS diagnosis_invoices
FROM patient_admissions pa
JOIN patients p ON p.patient_id = pa.patient_id
WHERE pa.status = 'waiting'
ORDER BY pa.patient_admission_id;

-- 3. Invoices whose total does not match their admission lines (should return no rows)
SELECT i.invoice_id, i.invoice_code, i.total_amount, SUM(ia.price) AS lines_total
FROM invoices i
JOIN invoice_admission ia ON ia.invoice_id = i.invoice_id
GROUP BY i.invoice_id, i.invoice_code, i.total_amount
HAVING ROUND(i.total_amount, 2) <> ROUND(SUM(ia.price), 2)
   AND NOT EXISTS (SELECT 1 FROM invoice_adjustments adj WHERE adj.invoice_id = i.invoice_id);

-- 4. Additional charges per patient
SELECT p.first_name || ' ' || p.last_name AS patient,
       ac.type,
       ac.description,
       ac.amount,
       i.invoice_code,
       i.status,
       ac.created_at
FROM additional_charges ac
JOIN patients p ON p.patient_id = ac.patient_id
JOIN invoices i ON i.invoice_id = ac.invoice_id
ORDER BY ac.created_at DESC;

-- 5. Accommodation changes: lines added on the new period (upgrade = price > 0, downgrade = 0)
SELECT ap.patient_admission_id, ap.admission_period_id, ap.parent_admission_period_id,
       ia.description, ia.price, i.invoice_code, i.status
FROM admission_periods ap
JOIN invoice_admission ia ON ia.admission_period_id = ap.admission_period_id
JOIN invoices i ON i.invoice_id = ia.invoice_id
WHERE ap.reason = 'accommodation_change'
ORDER BY ap.patient_admission_id, ap.admission_period_id;

-- 6. Diagnoses of a patient with the case they were last billed under
SELECT pd.patient_diagnosis_id, pd.diagnosis, pd.diagnosis_date,
       (SELECT dc.title
        FROM admission_diagnosis ad
        JOIN diagnosis_cases dc ON dc.diagnosis_case_id = ad.diagnosis_case_id
        WHERE ad.patient_diagnosis_id = pd.patient_diagnosis_id
        ORDER BY ad.admission_diagnosis_id DESC
        LIMIT 1) AS last_case
FROM patient_diagnosis pd
WHERE pd.patient_id = 1 -- change me
ORDER BY pd.diagnosis_date DESC;
