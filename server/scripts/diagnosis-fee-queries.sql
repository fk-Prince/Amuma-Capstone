-- Run each block on its own in pgAdmin's Query Tool.

-- 1. Diagnosis fees recorded per admission
SELECT p.first_name || ' ' || p.last_name AS patient,
       pa.patient_admission_id,
       pd.diagnosis,
       dc.title AS diagnosis_case,
       acd.amount,
       acd.created_at
FROM admission_charge_diagnosis acd
JOIN patient_admissions pa ON pa.patient_admission_id = acd.patient_admission_id
JOIN patients p ON p.patient_id = pa.patient_id
JOIN patient_diagnosis pd ON pd.patient_diagnosis_id = acd.patient_diagnosis_id
LEFT JOIN diagnosis_cases dc ON dc.diagnosis_case_id = acd.diagnosis_case_id
ORDER BY acd.created_at DESC;

-- 2. Admission period price split: room vs diagnosis fee
SELECT pa.patient_admission_id,
       ap.admission_period_id,
       ap.reason,
       ap.status,
       SUM(ia.price) AS period_price,
       CASE WHEN ap.reason IN ('admitted', 'extended')
            THEN COALESCE((SELECT SUM(amount) FROM admission_charge_diagnosis x
                           WHERE x.patient_admission_id = pa.patient_admission_id), 0)
                 * CASE WHEN UPPER(bc.billing_cycle) = 'YEARLY' THEN 12 ELSE 1 END
            ELSE 0 END AS diagnosis_fee,
       SUM(ia.price) - CASE WHEN ap.reason IN ('admitted', 'extended')
            THEN COALESCE((SELECT SUM(amount) FROM admission_charge_diagnosis x
                           WHERE x.patient_admission_id = pa.patient_admission_id), 0)
                 * CASE WHEN UPPER(bc.billing_cycle) = 'YEARLY' THEN 12 ELSE 1 END
            ELSE 0 END AS room_price
FROM admission_periods ap
JOIN patient_admissions pa ON pa.patient_admission_id = ap.patient_admission_id
JOIN branch_contracts bc ON bc.branch_contract_id = ap.branch_contract_id
LEFT JOIN invoice_admission ia ON ia.admission_period_id = ap.admission_period_id
GROUP BY pa.patient_admission_id, ap.admission_period_id, ap.reason, ap.status, bc.billing_cycle
ORDER BY pa.patient_admission_id, ap.admission_period_id;

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
        FROM admission_charge_diagnosis acd
        JOIN diagnosis_cases dc ON dc.diagnosis_case_id = acd.diagnosis_case_id
        WHERE acd.patient_diagnosis_id = pd.patient_diagnosis_id
        ORDER BY acd.admission_charge_diagnosis_id DESC
        LIMIT 1) AS last_case
FROM patient_diagnosis pd
WHERE pd.patient_id = 1 -- change me
ORDER BY pd.diagnosis_date DESC;
