<template>
    <div>
        <PrintDocument
            :branch="branch"
            title="Statement of Account"
            :icon="Receipt"
            doc-type="Billing Statement"
            :reference="reference"
            :lines="details"
            :footer-label="`${patientName} · Statement of Account`"
            :generated-at="issuedOn"
        >
            <h2 class="print-doc-subhead">
                <Wallet class="print-doc-icon" />
                Invoices
            </h2>

            <p v-if="!invoices.length" class="print-doc-empty">
                No invoices on record.
            </p>

            <table v-else class="print-doc-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="print-doc-right">Total</th>
                        <th class="print-doc-right">Paid</th>
                        <th v-if="hasRefunds" class="print-doc-right">Refunded</th>
                        <th v-if="hasWriteOffs" class="print-doc-right">Written off</th>                        <th class="print-doc-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in invoices" :key="row.invoice_code">
                        <td>
                            <span class="print-doc-strong">{{ row.invoice_code }}</span>
                            <span class="print-doc-subtle">{{ row.description }}</span>                        </td>
                        <td>{{ formatDate(row.created_at) }}</td>
                        <td>{{ label(row.status) }}</td>
                        <td class="print-doc-right">{{ money(row.total) }}</td>
                        <td class="print-doc-right">{{ money(row.paid) }}</td>
                        <td v-if="hasRefunds" class="print-doc-right">
                            {{ money(row.refunded) }}
                        </td>
                        <td v-if="hasWriteOffs" class="print-doc-right">
                            {{ money(row.written_off) }}
                        </td>                        <td class="print-doc-right print-doc-strong">
                            {{ money(row.balance) }}
                        </td>
                    </tr>
                    <tr class="print-doc-total">
                        <td colspan="3">Total</td>
                        <td class="print-doc-right">{{ money(totals.total) }}</td>
                        <td class="print-doc-right">{{ money(totals.paid) }}</td>
                        <td v-if="hasRefunds" class="print-doc-right">
                            {{ money(totals.refunded) }}
                        </td>
                        <td v-if="hasWriteOffs" class="print-doc-right">
                            {{ money(totals.written_off) }}
                        </td>                        <td class="print-doc-right">{{ money(totals.balance) }}</td>
                    </tr>
                </tbody>
            </table>

            <p class="print-doc-note">
                <template v-if="totals.balance > 0">
                    Balance due: {{ money(totals.balance) }}. Please settle at
                    the branch counter or through the AMUMA family portal.
                </template>
                <template v-else>
                    This account is fully settled. No balance is due.
                </template>
                This statement reflects the account as of the date above.
                Payments made after it was printed are not included.
            </p>
        </PrintDocument>

        <PrintDocument
            v-if="transactions"
            :branch="branch"
            title="Transaction History"
            :icon="ArrowLeftRight"
            doc-type="Billing Statement"
            :reference="reference"
            :lines="details"
            :footer-label="`${patientName} · Transaction History`"
            :generated-at="issuedOn"
        >
            <p v-if="!transactions.length" class="print-doc-empty" style="margin-top: 4mm">
                No transactions on record.
            </p>

            <table v-else class="print-doc-table" style="margin-top: 4mm">
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
                            {{ money(row.amount) }}
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
import { formatDate } from "~/utils/time";
import type { PrintBranch, PrintLine, StatementInvoice } from "~/types/print";

const props = defineProps<{
    branch: PrintBranch;
    patientName: string;
    patientCode?: string | null;
    invoices: StatementInvoice[];
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

const hasRefunds = computed(() => props.invoices.some((row) => row.refunded > 0));

const hasWriteOffs = computed(() =>
    props.invoices.some((row) => row.written_off > 0),
);

const totals = computed(() =>
    props.invoices.reduce(
        (sum, row) => ({
            total: sum.total + row.total,
            paid: sum.paid + row.paid,
            refunded: sum.refunded + row.refunded,
            written_off: sum.written_off + row.written_off,
            balance: sum.balance + row.balance,
        }),
        { total: 0, paid: 0, refunded: 0, written_off: 0, balance: 0 },
    ),
);
function money(value: unknown) {
    const amount = Number(value ?? 0);

    return `₱${formatAmount(Number.isFinite(amount) ? amount : 0)}`;
}

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
