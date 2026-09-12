<template>
    <div
        class="space-y-5 text-secondary"
        :class="print ? 'bg-white p-8 text-black' : ''"
    >
        <div
            class="flex items-start justify-between gap-4 border-b border-gray-200 pb-4"
        >
            <div>
                <p
                    class="font-mono text-[10px] uppercase tracking-[0.2em] text-muted dark:text-gray-400"
                >
                    {{ slip.branch.name || "Amuma Care" }}
                </p>

                <h1
                    class="mt-1 text-lg font-semibold"
                    :class="print ? '' : 'dark:text-white'"
                >
                    Employee Slip
                </h1>
            </div>

            <div class="text-right">
                <p
                    class="font-mono text-[10px] uppercase tracking-[0.2em] text-muted dark:text-gray-400"
                >
                    ID
                </p>

                <p
                    class="text-sm font-semibold"
                    :class="print ? '' : 'dark:text-white'"
                >
                    {{ slip.employee.employee_id }}
                </p>
            </div>
        </div>

        <section v-for="group in groups" :key="group.title" class="space-y-2">
            <p
                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
            >
                {{ group.title }}
            </p>

            <dl class="grid gap-x-6 gap-y-1.5 sm:grid-cols-2">
                <div
                    v-for="row in group.rows"
                    :key="row.label"
                    class="flex items-baseline justify-between gap-3 border-b border-dashed border-gray-200 py-1"
                >
                    <dt class="text-[11px] text-muted dark:text-gray-400">
                        {{ row.label }}
                    </dt>

                    <dd
                        class="text-right text-[12px] font-medium"
                        :class="print ? '' : 'dark:text-gray-100'"
                    >
                        {{ row.value }}
                    </dd>
                </div>
            </dl>
        </section>

        <section
            class="rounded-xl border border-gray-300 p-4"
            :class="print ? '' : 'bg-slate-50 dark:bg-white/5'"
        >
            <p
                class="text-[10px] font-semibold uppercase tracking-[0.14em] text-muted dark:text-gray-400"
            >
                System access
            </p>

            <div
                class="mt-2 space-y-1 text-[11px] leading-5"
                :class="
                    print ? 'text-black' : 'text-gray-600 dark:text-gray-300'
                "
            >
                <p>
                    {{ slip.employee.full_name || "This employee" }} may sign in
                    to Amuma with the credentials below.
                </p>

                <p>
                    Email account/username:
                    <span class="font-mono font-semibold">
                        {{ slip.access.email }}
                    </span>
                </p>

                <template v-if="slip.access.default_password">
                    <p>
                        The password is
                        <span class="font-semibold">lastnameYYYY</span>, where
                        lastname is the employee's last name in lowercase and
                        YYYY is their year of birth.
                    </p>

                    <p class="italic">
                        e.g. for {{ exampleLastName }} born in
                        {{ exampleYear }}, the password is
                        <span class="font-mono font-semibold not-italic">
                            {{ slip.access.default_password }}
                        </span>
                    </p>

                    <p>
                        Please change the password after the first sign in and
                        keep this slip private.
                    </p>
                </template>
            </div>
        </section>

        <p
            class="border-t border-gray-200 pt-3 text-[10px] text-muted dark:text-gray-500"
        >
            Issued {{ issuedAt }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { formatDate, stringToDateTime } from "~/utils/time";
import { formatAssignmentType } from "~/types/employee";
import type { EmployeeSlip } from "~/types/employee-slip";

function capitalize(value?: string | null) {
    if (!value) return null;

    return value.replace(/\b\w/g, (char) => char.toUpperCase());
}

const props = defineProps<{
    slip: EmployeeSlip;
    print?: boolean;
}>();

const issuedAt = computed(() => stringToDateTime(new Date()));

const groups = computed(() => {
    const { employee, access } = props.slip;

    return [
        {
            title: "Employee",
            rows: [
                { label: "Name", value: employee.full_name },
                {
                    label: "Date of birth",
                    value: formatDate(employee.birth_date),
                },
                { label: "Contact", value: employee.phone_number },
            ].filter((row) => !!row.value),
        },
        {
            title: "Assignment",
            rows: [
                { label: "Position", value: capitalize(employee.role_name) },
                {
                    label: "Assignment",
                    value: formatAssignmentType(employee.assignment_type),
                },
                {
                    label: "Modules granted",
                    value: access.module_count
                        ? String(access.module_count)
                        : null,
                },
            ].filter((row) => !!row.value),
        },
    ].filter((group) => group.rows.length);
});

const exampleYear = computed(
    () => props.slip.access.default_password?.match(/\d{4}$/)?.[0] ?? "",
);

const exampleLastName = computed(() => {
    const password = props.slip.access.default_password ?? "";

    return password.slice(0, password.length - exampleYear.value.length);
});
</script>
