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
                    {{ slip.branch?.name || "Amuma Care" }}
                </p>

                <h1
                    class="mt-1 text-lg font-semibold"
                    :class="print ? '' : 'dark:text-white'"
                >
                    Admission Slip
                </h1>
            </div>

            <div class="text-right">
                <p
                    class="font-mono text-[10px] uppercase tracking-[0.2em] text-muted dark:text-gray-400"
                >
                    Admission No.
                </p>

                <p
                    class="text-sm font-semibold"
                    :class="print ? '' : 'dark:text-white'"
                >
                    #{{ slip.admission.patient_admission_id }}
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
                Family portal access
            </p>

            <div
                class="mt-2 space-y-1 text-[11px] leading-5"
                :class="
                    print ? 'text-black' : 'text-gray-600 dark:text-gray-300'
                "
            >
                <p>
                    {{ slip.portal.name || "The guardian" }} may access the
                    family portal to follow this admission. The account
                    credentials are:
                </p>

                <p>
                    Email account/username:
                    <span class="font-mono font-semibold">
                        {{ slip.portal.email }}
                    </span>
                </p>

                <template v-if="slip.portal.default_password">
                    <p>
                        The password is
                        <span class="font-semibold">lastnameYYYY</span>, where
                        lastname is the account holder's last name in lowercase
                        and YYYY is the year the account was created.
                    </p>

                    <p class="italic">
                        e.g. for {{ exampleLastName }} created in
                        {{ exampleYear }}, the password is
                        <span class="font-mono font-semibold not-italic">
                            {{ slip.portal.default_password }}
                        </span>
                    </p>

                    <p>
                        Please change the password after the first sign in and
                        keep this slip private.
                    </p>
                </template>

                <p v-else>
                    This email already has an Amuma account, so it keeps its
                    existing password. This admission was added to it.
                </p>
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
import { formatAmount } from "~/utils/currency";
import { formatDate, stringToDateTime } from "~/utils/time";
import type { AdmissionSlip } from "~/types/admission-slip";

const props = defineProps<{
    slip: AdmissionSlip;
    print?: boolean;
}>();

const issuedAt = computed(() => stringToDateTime(new Date()));

const groups = computed(() => {
    const { patient, admission, invoice } = props.slip;

    const accommodation = [admission.room, admission.bed]
        .filter(Boolean)
        .join(" · ");

    return [
        {
            title: "Patient",
            rows: [
                { label: "Name", value: patient.full_name },
                {
                    label: "Date of birth",
                    value: formatDate(patient.date_of_birth),
                },
                { label: "Gender", value: patient.gender },
                { label: "Contact", value: patient.phone_number },
            ].filter((row) => !!row.value),
        },
        {
            title: "Admission",
            rows: [
                { label: "Admitted", value: formatDate(admission.admitted_at) },
                {
                    label: "Covered until",
                    value: formatDate(admission.discharged_at),
                },
                { label: "Accommodation", value: accommodation },
                { label: "Floor", value: admission.floor },
                { label: "Type", value: admission.accommodation_type },
                { label: "Billing cycle", value: admission.billing_cycle },
            ].filter((row) => !!row.value),
        },
        {
            title: "Billing",
            rows: [
                { label: "Invoice", value: invoice.invoice_code },
                {
                    label: "Amount due",
                    value: `₱${formatAmount(invoice.total_amount, { treatMissingAsZero: true })}`,
                },
            ].filter((row) => !!row.value),
        },
    ].filter((group) => group.rows.length);
});

// Split back out of the password itself so the worked example on the slip
// always matches what was actually set.
const exampleYear = computed(
    () => props.slip.portal.default_password?.match(/\d{4}$/)?.[0] ?? "",
);

const exampleLastName = computed(() => {
    const password = props.slip.portal.default_password ?? "";

    return password.slice(0, password.length - exampleYear.value.length);
});
</script>
