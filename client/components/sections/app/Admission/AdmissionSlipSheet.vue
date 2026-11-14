<template>
    <PrintDocument
        :branch="slip.branch"
        title="Admission Slip"
        :icon="BedDouble"
        doc-type="Admission Record"
        :reference="slip.patient.patient_code ? `Ref. ${slip.patient.patient_code}` : null"
        :lines="lines"
        :footer-label="`${slip.patient.full_name || 'Patient'} · Admission Slip`"
        :generated-at="generatedAt"
    >
        <h2 class="print-doc-subhead">
            <BedDouble class="print-doc-icon" />
            Admission
        </h2>

        <table class="print-doc-table">
            <thead>
                <tr>
                    <th>Admitted</th>
                    <th>Covered until</th>
                    <th>Accommodation</th>
                    <th>Room / Bed</th>
                    <th>Billing cycle</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="print-doc-strong">
                        {{ formatDate(slip.admission.admitted_at) }}
                    </td>
                    <td>{{ formatDate(slip.admission.discharged_at) }}</td>
                    <td>{{ accommodation }}</td>
                    <td>{{ roomAndBed }}</td>
                    <td>{{ printLabel(slip.admission.billing_cycle) || "—" }}</td>
                </tr>
            </tbody>
        </table>

        <template v-if="diagnosisCases.length">
            <h2 class="print-doc-subhead">
                <Stethoscope class="print-doc-icon" />
                Diagnosis cases
            </h2>

            <table class="print-doc-table">
                <thead>
                    <tr>
                        <th>Diagnosis</th>
                        <th>Diagnosis case</th>
                        <th>Billed</th>
                        <th class="print-doc-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, index) in diagnosisCases" :key="index">
                        <td class="print-doc-strong">{{ row.diagnosis || "—" }}</td>
                        <td>{{ row.diagnosis_case || "—" }}</td>
                        <td>{{ printLabel(row.billed) || "Once" }}</td>
                        <td class="print-doc-right">{{ money(row.amount) }}</td>
                    </tr>
                </tbody>
            </table>
        </template>

        <h2 class="print-doc-subhead">
            <Receipt class="print-doc-icon" />
            Billing
        </h2>

        <table class="print-doc-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>For</th>
                    <th class="print-doc-right">Amount due</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in invoices" :key="row.code">
                    <td class="print-doc-strong">{{ row.code }}</td>
                    <td>{{ row.label }}</td>
                    <td class="print-doc-right">{{ money(row.amount) }}</td>
                </tr>
                <tr v-if="invoices.length > 1" class="print-doc-total">
                    <td colspan="2">Total due</td>
                    <td class="print-doc-right">{{ money(totalDue) }}</td>
                </tr>
            </tbody>
        </table>

        <h2 class="print-doc-subhead">
            <Users class="print-doc-icon" />
            Family portal access
        </h2>

        <table class="print-doc-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="slip.portal.name">
                    <td class="print-doc-key">Account holder</td>
                    <td>{{ slip.portal.name }}</td>
                </tr>
                <tr>
                    <td class="print-doc-key">Email / username</td>
                    <td>
                        <span class="print-doc-mono">{{ slip.portal.email }}</span>
                    </td>
                </tr>
                <template v-if="slip.portal.default_password">
                    <tr>
                        <td class="print-doc-key">Default password</td>
                        <td>
                            lastnameYYYY: the account holder's last name in
                            lowercase, then the year the account was created.
                            <span class="print-doc-subtle">
                                e.g. {{ exampleLastName }} created in
                                {{ exampleYear }} →
                                <span class="print-doc-mono">{{
                                    slip.portal.default_password
                                }}</span>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="print-doc-key">First sign in</td>
                        <td>Change the password right after the first sign in.</td>
                    </tr>
                </template>
                <template v-else>
                    <tr>
                        <td class="print-doc-key">Account</td>
                        <td>
                            Linked to an existing Amuma account. This admission
                            was added to it.
                        </td>
                    </tr>
                    <tr>
                        <td class="print-doc-key">Password</td>
                        <td>
                            Unchanged. Sign in with the password already used
                            for this email.
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>

        <p class="print-doc-note">
            {{ slip.portal.name || "The guardian" }} may use the family portal
            to follow this admission. Keep this slip private.
        </p>
    </PrintDocument>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { BedDouble, Receipt, Stethoscope, Users } from "lucide-vue-next";
import PrintDocument from "~/components/print/PrintDocument.vue";
import { formatAmount } from "~/utils/currency";
import { formatPhone } from "~/utils/phone";
import { printLabel } from "~/utils/print";
import { formatDate, stringToDateTime } from "~/utils/time";
import { calculateAge } from "~/utils/user";
import type { AdmissionSlip } from "~/types/admission-slip";
import type { PrintLine } from "~/types/print";

const props = defineProps<{
    slip: AdmissionSlip;
}>();

const generatedAt = stringToDateTime(new Date());

const lines = computed<PrintLine[]>(() => {
    const { patient } = props.slip;

    return [
        { label: "Patient ID", value: patient.patient_code },
        { label: "Gender", value: printLabel(patient.gender) },
        { label: "Name", value: patient.full_name, strong: true },
        { label: "Contact", value: formatPhone(patient.phone_number) },
        {
            label: "Date of birth",
            value: patient.date_of_birth ? calculateAge(patient.date_of_birth) : null,
        },
    ];
});

const accommodation = computed(() => {
    const type = props.slip.admission.accommodation_type;

    if (!type) return "—";

    return type.toUpperCase() === "VIP" ? "VIP" : printLabel(type);
});

const roomAndBed = computed(() => {
    const { room, bed, floor } = props.slip.admission;

    return (
        [
            room ? `Room ${room}` : null,
            bed ? `Bed ${bed}` : null,
            floor ? `${floor} floor` : null,
        ]
            .filter(Boolean)
            .join(" · ") || "—"
    );
});

const diagnosisCases = computed(() => props.slip.diagnosis_cases ?? []);

const invoices = computed(() => {
    const { invoice, diagnosis_invoice, admission } = props.slip;
    const plan = [accommodation.value, printLabel(admission.billing_cycle)]
        .filter((part) => part && part !== "—")
        .join(", ");

    return [
        {
            code: invoice.invoice_code ?? "—",
            label: plan ? `Accommodation · ${plan}` : "Accommodation",
            amount: Number(invoice.total_amount ?? 0),
        },
        ...(diagnosis_invoice
            ? [
                  {
                      code: diagnosis_invoice.invoice_code ?? "—",
                      label: "Diagnosis cases",
                      amount: Number(diagnosis_invoice.total_amount ?? 0),
                  },
              ]
            : []),
    ];
});

const totalDue = computed(() =>
    invoices.value.reduce((sum, row) => sum + row.amount, 0),
);

function money(value: unknown) {
    return `₱${formatAmount(Number(value ?? 0), { treatMissingAsZero: true })}`;
}

const exampleYear = computed(
    () => props.slip.portal.default_password?.match(/\d{4}$/)?.[0] ?? "",
);

const exampleLastName = computed(() => {
    const password = props.slip.portal.default_password ?? "";

    return password.slice(0, password.length - exampleYear.value.length);
});
</script>
