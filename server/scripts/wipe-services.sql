DO $$
DECLARE
    wiped text[] := ARRAY['employee_services', 'services'];
    tbl text;
    pk text;
    seq text;
BEGIN
    DELETE FROM online_schedules
    WHERE schedule_assigned_id IN (
        SELECT sa.schedule_assigned_id
        FROM schedule_assigned sa
        JOIN schedule_services ss ON ss.schedule_services_id = sa.schedule_services_id
        WHERE ss.service_id IS NOT NULL
    );

    DELETE FROM schedule_assigned
    WHERE schedule_services_id IN (
        SELECT schedule_services_id FROM schedule_services WHERE service_id IS NOT NULL
    );

    DELETE FROM invoice_services
    WHERE schedule_services_id IN (
        SELECT schedule_services_id FROM schedule_services WHERE service_id IS NOT NULL
    );

    DELETE FROM schedule_services WHERE service_id IS NOT NULL;

    DELETE FROM employee_services;
    DELETE FROM services;

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
