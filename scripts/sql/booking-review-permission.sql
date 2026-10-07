BEGIN;

ALTER TABLE employee_permissions
    ADD COLUMN IF NOT EXISTS can_review boolean NOT NULL DEFAULT false;

UPDATE employee_permissions ep
SET can_review = ep.can_update,
    can_update = false
FROM modules m
WHERE m.module_id = ep.module_id
  AND m.module_name = 'Bookings';

SELECT m.module_name,
       count(*)                             AS rows,
       count(*) FILTER (WHERE ep.can_review) AS can_review,
       count(*) FILTER (WHERE ep.can_update) AS can_update
FROM employee_permissions ep
JOIN modules m ON m.module_id = ep.module_id
WHERE m.module_name = 'Bookings'
GROUP BY m.module_name;

COMMIT;
