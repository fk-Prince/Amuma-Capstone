<template>
    <PrintDocument
        :branch="slip.branch"
        title="Employee Slip"
        :icon="IdCard"
        doc-type="Staff Record"
        :reference="slip.employee.employee_code ? `Ref. ${slip.employee.employee_code}` : null"
        :lines="lines"
        :footer-label="`${slip.employee.full_name || 'Employee'} · Employee Slip`"
        :generated-at="generatedAt"
    >
        <h2 class="print-doc-subhead">
            <KeyRound class="print-doc-icon" />
            System access
        </h2>

        <table class="print-doc-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="print-doc-key">Email / username</td>
                    <td>
                        <span class="print-doc-mono">{{ slip.access.email }}</span>
                    </td>
                </tr>
                <template v-if="slip.access.default_password">
                    <tr>
                        <td class="print-doc-key">Default password</td>
                        <td>
                            lastnameYYYY: the employee's last name in lowercase,
                            then their year of birth.
                            <span class="print-doc-subtle">
                                e.g. {{ exampleLastName }} born in
                                {{ exampleYear }} →
                                <span class="print-doc-mono">{{
                                    slip.access.default_password
                                }}</span>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="print-doc-key">First sign in</td>
                        <td>Change the password right after the first sign in.</td>
                    </tr>
                </template>
            </tbody>
        </table>

        <p class="print-doc-note">
            {{ slip.employee.full_name || "This employee" }} may sign in to
            Amuma with the credentials above. Keep this slip private.
        </p>
    </PrintDocument>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { IdCard, KeyRound } from "lucide-vue-next";
import PrintDocument from "~/components/print/PrintDocument.vue";
import { stringToDateTime } from "~/utils/time";
import { formatPhone } from "~/utils/phone";
import { calculateAge, formatRole } from "~/utils/user";
import { formatAssignmentType } from "~/types/employee";
import type { EmployeeSlip } from "~/types/employee-slip";
import type { PrintLine } from "~/types/print";

const props = defineProps<{
    slip: EmployeeSlip;
}>();

const generatedAt = stringToDateTime(new Date());

const lines = computed<PrintLine[]>(() => {
    const { employee, access, branch } = props.slip;

    return [
        { label: "Name", value: employee.full_name, strong: true },
        { label: "Employee ID", value: employee.employee_code },
        {
            label: "Date of birth",
            value: employee.birth_date ? calculateAge(employee.birth_date) : null,
        },
        { label: "Contact", value: formatPhone(employee.phone_number) },
        {
            label: "Position",
            value: employee.role_name ? formatRole(employee.role_name) : null,
        },
        {
            label: "Assignment",
            value: employee.assignment_type
                ? formatAssignmentType(employee.assignment_type)
                : null,
        },
        {
            label: "Modules granted",
            value: access.module_count ? String(access.module_count) : null,
        },
        { label: "Branch", value: branch.name },
    ];
});

const exampleYear = computed(
    () => props.slip.access.default_password?.match(/\d{4}$/)?.[0] ?? "",
);

const exampleLastName = computed(() => {
    const password = props.slip.access.default_password ?? "";

    return password.slice(0, password.length - exampleYear.value.length);
});
</script>
