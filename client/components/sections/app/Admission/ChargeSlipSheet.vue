<template>
    <PrintDocument
        :branch="slip.branch"
        :title="title"
        :icon="icon"
        doc-type="Billing Record"
        :reference="reference"
        :lines="lines"
        :footer-label="`${slip.patient_name || 'Patient'} · ${title}`"
        :generated-at="generatedAt"
    >
        <h2 class="print-doc-subhead">
            <component :is="icon" class="print-doc-icon" />
            {{ slip.section || "Charges" }}
        </h2>

        <table v-if="slip.kind === 'diagnosis'" class="print-doc-table">
            <thead>
                <tr>
                    <th>Diagnosis case</th>
                    <th>Diagnosis</th>
                    <th>Billed</th>
                    <th class="print-doc-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="charge in slip.charges" :key="charge.id">
                    <td class="print-doc-strong">{{ charge.type_label }}</td>
                    <td>{{ charge.description }}</td>
                    <td>{{ charge.billed || "—" }}</td>
                    <td class="print-doc-right">
                        {{ formatCurrency(charge.amount) }}
                    </td>
                </tr>
                <tr class="print-doc-total">
                    <td colspan="3">{{ totalLabel }}</td>
                    <td class="print-doc-right">{{ formatCurrency(slip.total) }}</td>
                </tr>
            </tbody>
        </table>

        <table v-else class="print-doc-table">
            <thead>
                <tr>
                    <th>{{ columns[0] }}</th>
                    <th class="print-doc-right">{{ columns[1] }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="charge in slip.charges" :key="charge.id">
                    <td>
                        <template v-if="charge.type_label">
                            <span class="print-doc-strong">{{
                                charge.type_label
                            }}</span>
                            <span class="print-doc-subtle">{{
                                charge.description
                            }}</span>
                        </template>
                        <template v-else>{{ charge.description }}</template>
                    </td>
                    <td class="print-doc-right">
                        {{ formatCurrency(charge.amount) }}
                    </td>
                </tr>
                <tr class="print-doc-total">
                    <td>{{ totalLabel }}</td>
                    <td class="print-doc-right">{{ formatCurrency(slip.total) }}</td>
                </tr>
            </tbody>
        </table>

        <p v-if="!slip.hide_cashier_note" class="print-doc-note">
            Please present this slip to the cashier to settle the amount
            due<template v-if="slip.invoice_code">
                for invoice {{ slip.invoice_code }}</template
            >.
        </p>
    </PrintDocument>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Receipt, Stethoscope, Wallet } from "lucide-vue-next";
import PrintDocument from "~/components/print/PrintDocument.vue";
import { formatCurrency } from "~/utils/currency";
import { stringToDateTime } from "~/utils/time";
import type { ChargeSlip } from "~/types/charge-slip";
import type { PrintLine } from "~/types/print";

const props = defineProps<{
    slip: ChargeSlip;
}>();

const generatedAt = stringToDateTime(new Date());

const title = computed(() => props.slip.title || "Charge Slip");

const icon = computed(() => {
    if (props.slip.kind === "diagnosis") return Stethoscope;
    if (props.slip.kind === "balance") return Wallet;

    return Receipt;
});

const reference = computed(() => {
    const ref = props.slip.invoice_code || props.slip.patient_code;

    return ref ? `Ref. ${ref}` : null;
});

const columns = computed(
    () => props.slip.column_labels ?? (["Charge", "Amount"] as const),
);

const totalLabel = computed(() => props.slip.total_label || "Amount due");

const lines = computed<PrintLine[]>(() => [
    { label: "Patient ID", value: props.slip.patient_code },
    { label: "Invoice No.", value: props.slip.invoice_code },
    { label: "Name", value: props.slip.patient_name, strong: true },
    { label: "Prepared by", value: props.slip.prepared_by },
]);
</script>
