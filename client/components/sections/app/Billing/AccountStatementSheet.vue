<template>
    <div>
        <PrintDocument
            :branch="branch"
            title="Statement of Balance"
            :icon="Receipt"
            doc-type="Billing Statement"
            :reference="reference"
            :lines="details"
            :footer-label="`${patientName} · Statement of Balance`"
            :generated-at="issuedOn"
        >
            <h2 class="print-doc-subhead">
                <Wallet class="print-doc-icon" />
                Balances
            </h2>

            <table class="print-doc-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="print-doc-right">Amount due</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(line, index) in lines" :key="index">
                        <td>{{ line.description }}</td>
                        <td class="print-doc-right">₱{{ formatAmount(line.amount) }}</td>
                    </tr>
                    <tr class="print-doc-total">
                        <td>Total amount due</td>
                        <td class="print-doc-right">₱{{ formatAmount(total) }}</td>
                    </tr>
                </tbody>
            </table>

            <p class="print-doc-note">
                This statement lists the balances still to be paid on this
                patient's account as of the date above. Payments made after it
                was printed are not reflected. Please settle at the branch
                counter or through the AMUMA family portal, and keep this copy
                for your records.
            </p>
        </PrintDocument>

        <PrintDocument
            v-if="transactions?.length"
            :branch="branch"
            title="Transaction History"
            :icon="ArrowLeftRight"
            doc-type="Billing Statement"
            :reference="reference"
            :lines="details"
            :footer-label="`${patientName} · Transaction History`"
            :generated-at="issuedOn"
        >
            <table class="print-doc-table" style="margin-top: 4mm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transaction</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Method</th>
                        <th class="print-doc-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, index) in transactions" :key="index">
                        <td>{{ formatDateTime(row.created_at) }}</td>
                        <td class="print-doc-strong">
                            {{ row.transaction_code ?? "—" }}
                            <span v-if="row.reference_id" class="print-doc-subtle">
                                {{ row.reference_id }}
                            </span>
                        </td>
                        <td>
                            {{ label(row.type) }}
                            <span v-if="row.direction" class="print-doc-subtle">
                                {{ label(row.direction) }}
                            </span>
                        </td>
                        <td>{{ label(row.status) }}</td>
                        <td>{{ label(row.method) }}</td>
                        <td class="print-doc-right print-doc-strong">
                            ₱{{ formatAmount(row.amount) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </PrintDocument>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { ArrowLeftRight, Receipt, Wallet } from "lucide-vue-next";
import PrintDocument from "~/components/print/PrintDocument.vue";
import { formatAmount } from "~/utils/currency";
import { printLabel } from "~/utils/print";
import type { PrintBranch, PrintLine } from "~/types/print";

const props = defineProps<{
    branch: PrintBranch;
    patientName: string;
    patientCode?: string | null;
    lines: { description: string; amount: number }[];
    total: number;
    issuedBy: string;
    issuedOn: string;
    transactions?: {
        transaction_code?: string | null;
        reference_id?: string | null;
        type?: string | null;
        direction?: string | null;
        status?: string | null;
        method?: string | null;
        amount: number;
        created_at?: string | null;
    }[];
}>();

const reference = computed(() =>
    props.patientCode ? `Ref. ${props.patientCode}` : null,
);

const details = computed<PrintLine[]>(() => [
    { label: "Patient ID", value: props.patientCode },
    { label: "Issued on", value: props.issuedOn },
    { label: "Name", value: props.patientName, strong: true },
    { label: "Issued by", value: props.issuedBy },
]);

function label(value?: string | null) {
    return printLabel(value) || "—";
}

function formatDateTime(value?: string | null) {
    if (!value) return "—";

    const parsed = new Date(value);

    return Number.isNaN(parsed.getTime())
        ? "—"
        : parsed.toLocaleString("en-US", {
              month: "short",
              day: "numeric",
              year: "numeric",
              hour: "numeric",
              minute: "2-digit",
          });
}
</script>
