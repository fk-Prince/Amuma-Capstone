DO $$
DECLARE
    patient_locations bigint[];
    schedule_locations bigint[];
    wiped text[] := ARRAY[
        'refund_allocation', 'refunds', 'payment_invoice_allocation', 'payments', 'transactions',
        'invoice_adjustments', 'admission_diagnosis', 'admission_charge_diagnosis', 'additional_charges', 'patient_bookings',
        'invoice_services', 'invoice_admission', 'invoices',
        'caregiver_shifts', 'room_transfers', 'admission_periods', 'patient_admissions',
        'online_schedules', 'schedule_assigned', 'schedule_services', 'schedules',
        'medication_schedules', 'medications', 'vitals', 'patient_diagnosis', 'patient_assessments', 'patient_activities',
        'messages', 'conversations', 'reviews',
        'patient_accesses', 'patients', 'bookings'
    ];
    tbl text;
    pk text;
    seq text;
BEGIN
    SELECT COALESCE(array_agg(location_id), '{}') INTO patient_locations
    FROM patients WHERE location_id IS NOT NULL;

    SELECT COALESCE(array_agg(location_id), '{}') INTO schedule_locations
    FROM schedules WHERE location_id IS NOT NULL;

    DELETE FROM refund_allocation;
    DELETE FROM refunds;
    DELETE FROM payment_invoice_allocation;
    DELETE FROM payments;
    DELETE FROM transactions;

    DELETE FROM invoice_adjustments;
    DELETE FROM admission_diagnosis;
    DELETE FROM admission_charge_diagnosis;
    DELETE FROM additional_charges;
    DELETE FROM patient_bookings;
    DELETE FROM invoice_services;
    DELETE FROM invoice_admission;
    DELETE FROM invoices;

    DELETE FROM caregiver_shifts;
    DELETE FROM room_transfers;
    DELETE FROM admission_periods;
    DELETE FROM patient_admissions;

    DELETE FROM online_schedules;
    DELETE FROM schedule_assigned;
    DELETE FROM schedule_services;
    DELETE FROM schedules;

    DELETE FROM medication_schedules;
    DELETE FROM medications;
    DELETE FROM vitals;
    DELETE FROM patient_diagnosis;
    DELETE FROM patient_assessments;
    DELETE FROM patient_activities;

    DELETE FROM messages;
    DELETE FROM conversations;
    DELETE FROM reviews;

    DELETE FROM patient_accesses;
    DELETE FROM patients;
    DELETE FROM bookings;

    DELETE FROM notifications WHERE message_type = 'Booking';

    DELETE FROM locations
    WHERE location_id = ANY(patient_locations || schedule_locations);

    UPDATE beds SET status = 'Available' WHERE status IN ('Occupied', 'Reserved');

    FOREACH tbl IN ARRAY wiped LOOP
        SELECT a.attname INTO pk
        FROM pg_index i
        JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
        WHERE i.indrelid = tbl::regclass AND i.indisprimary;

        seq := pg_get_serial_sequence(tbl, pk);

        IF seq IS NOT NULL THEN
            EXECUTE format('ALTER SEQUENCE %s RESTART WITH 1', seq);
        END IF;
    END LOOP;
END
$$
