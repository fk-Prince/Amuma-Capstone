<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted } from "vue";

import type { PaymentReceipt } from "~/types/receipt";

const props = defineProps<{
    receipt: PaymentReceipt;
}>();

const emit = defineEmits<{
    close: [];
}>();

const lines = computed(() => props.receipt.lines ?? []);

const isOnline = computed(() => props.receipt.channel === "portal");

const hasChange = computed(() => Number(props.receipt.payment.change_due) > 0);

const settled = computed(
    () => Number(props.receipt.account.balance_after) <= 0,
);

const channelLabel = computed(() =>
    isOnline.value ? "Online Payment" : "Counter Payment",
);

const payorLabel = computed(() =>
    isOnline.value ? "Paid online by" : "Received from",
);

const vatExemptSales = computed(() =>
    Number(props.receipt.payment.amount_applied),
);

function peso(amount: number | string | null | undefined) {
    return Number(amount ?? 0).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function longDateTime(value: string | null | undefined) {
    if (!value) return "—";

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) return "—";

    return parsed.toLocaleString("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

function handlePrint() {
    if (typeof window !== "undefined") {
        window.print();
    }
}

onMounted(() => {
    document.body.classList.add("receipt-printing");
});

onBeforeUnmount(() => {
    document.body.classList.remove("receipt-printing");
});
</script>

<template>
    <Teleport to="body">
        <div
            class="receipt-overlay fixed inset-0 z-50 overflow-y-auto bg-gray-950/50 p-4 backdrop-blur-sm"
        >
            <div class="mx-auto my-4 w-full max-w-[1000px]">
                <div
                    class="receipt-actions mb-3 flex items-center justify-between gap-3"
                >
                    <p class="text-xs font-semibold text-white/90">
                        Receipt {{ receipt.receipt_no }} · {{ channelLabel }}
                    </p>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-full bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                            @click="handlePrint"
                        >
                            Print
                        </button>

                        <button
                            type="button"
                            class="rounded-full bg-white/10 px-4 py-2 text-xs font-semibold text-white transition hover:bg-white/20"
                            @click="emit('close')"
                        >
                            Close
                        </button>
                    </div>
                </div>

                <article
                    id="receipt-print"
                    class="receipt-form relative bg-white p-6 text-black shadow-2xl"
                >
                    <div
                        v-if="receipt.is_voided"
                        class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center"
                    >
                        <span
                            class="-rotate-12 text-8xl font-black tracking-widest text-rose-600/20"
                        >
                            VOID
                        </span>
                    </div>

                    <div class="border-2 border-black">
                        <!-- Masthead -->
                        <div class="flex items-stretch border-b border-black">
                            <div class="flex flex-1 items-start gap-3 p-3">
                                <img
                                    v-if="receipt.issuer.logo"
                                    :src="receipt.issuer.logo"
                                    alt=""
                                    class="h-14 w-14 shrink-0 object-contain"
                                />

                                <div class="min-w-0 leading-[1.35]">
                                    <p
                                        class="text-[13px] font-extrabold uppercase tracking-wide"
                                    >
                                        {{
                                            receipt.issuer.branch_name ||
                                            "Care Facility"
                                        }}
                                    </p>

                                    <p
                                        v-if="receipt.issuer.address"
                                        class="text-[9px]"
                                    >
                                        {{ receipt.issuer.address }}
                                    </p>

                                    <p
                                        v-if="receipt.issuer.contact"
                                        class="text-[9px]"
                                    >
                                        Tel. No. {{ receipt.issuer.contact }}
                                    </p>

                                    <p
                                        v-if="receipt.issuer.email"
                                        class="text-[9px]"
                                    >
                                        {{ receipt.issuer.email }}
                                    </p>

                                    <p
                                        v-if="receipt.issuer.tin"
                                        class="text-[9px] font-semibold"
                                    >
                                        VAT REG. TIN {{ receipt.issuer.tin }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="flex w-[210px] shrink-0 flex-col items-center justify-center border-l border-black p-2"
                            >
                                <p
                                    class="text-[17px] font-extrabold uppercase tracking-[0.24em]"
                                >
                                    Receipt
                                </p>

                                <p
                                    class="mt-1 text-[9px] font-bold uppercase tracking-[0.14em]"
                                >
                                    {{ channelLabel }}
                                </p>
                            </div>

                            <div
                                class="w-[190px] shrink-0 border-l border-black p-2 text-[9px]"
                            >
                                <p
                                    class="text-right text-[11px] font-extrabold uppercase tracking-[0.16em] text-rose-600"
                                >
                                    Original
                                </p>

                                <div class="mt-1.5 flex justify-between gap-2">
                                    <span class="shrink-0">Issue Date</span>

                                    <span
                                        class="min-w-0 flex-1 border-b border-black text-right font-mono"
                                    >
                                        {{ longDateTime(receipt.issued_at) }}
                                    </span>
                                </div>

                                <div class="mt-1.5 flex justify-between gap-2">
                                    <span class="shrink-0">Trans. No.</span>

                                    <span
                                        class="min-w-0 flex-1 border-b border-black text-right font-mono font-bold"
                                    >
                                        {{ receipt.receipt_no }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Parties -->
                        <div class="flex border-b border-black text-[10px]">
                            <div class="flex-1">
                                <div class="flex border-b border-black">
                                    <span
                                        class="w-[104px] shrink-0 border-r border-black px-2 py-1 uppercase"
                                    >
                                        {{ payorLabel }}
                                    </span>

                                    <span class="px-2 py-1 font-bold">
                                        {{ receipt.payor.name || "—" }}
                                    </span>
                                </div>

                                <div class="flex">
                                    <span
                                        class="w-[104px] shrink-0 border-r border-black px-2 py-1 uppercase"
                                    >
                                        For patient of
                                    </span>

                                    <span class="px-2 py-1 font-bold">
                                        {{ receipt.patient.full_name || "—" }}
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="!isOnline"
                                class="flex w-[300px] shrink-0 border-l border-black"
                            >
                                <span
                                    class="w-[92px] shrink-0 border-r border-black px-2 py-1 uppercase"
                                >
                                    Issued by
                                </span>

                                <span class="truncate px-2 py-1 font-bold">
                                    {{ receipt.issued_by || "—" }}
                                </span>
                            </div>
                        </div>

                        <!-- Applied-to grid -->
                        <table class="w-full border-collapse text-[10px]">
                            <thead>
                                <tr
                                    class="border-b border-black uppercase tracking-wide"
                                >
                                    <th
                                        class="w-[130px] border-r border-black px-2 py-1 text-left font-bold"
                                    >
                                        Invoice
                                    </th>

                                    <th
                                        class="border-r border-black px-2 py-1 text-left font-bold"
                                    >
                                        Description
                                    </th>

                                    <th
                                        class="w-[140px] px-2 py-1 text-right font-bold"
                                    >
                                        Amount
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="line in lines"
                                    :key="line.line_no"
                                    class="align-top"
                                >
                                    <td
                                        class="border-r border-black px-2 py-1 font-mono font-bold"
                                    >
                                        {{ line.invoice_code }}
                                    </td>

                                    <td class="border-r border-black px-2 py-1">
                                        Payment for balance
                                    </td>

                                    <td
                                        class="px-2 py-1 text-right font-mono font-bold"
                                    >
                                        {{ peso(line.amount_applied) }}
                                    </td>
                                </tr>

                                <tr
                                    v-for="n in Math.max(0, 3 - lines.length)"
                                    :key="`pad-${n}`"
                                >
                                    <td class="border-r border-black px-2 py-1">
                                        &nbsp;
                                    </td>

                                    <td
                                        class="border-r border-black px-2 py-1"
                                    />

                                    <td class="px-2 py-1" />
                                </tr>
                            </tbody>
                        </table>

                        <!-- Words + tax summary -->
                        <div class="flex border-t border-black text-[10px]">
                            <div class="flex flex-1 flex-col">
                                <div class="flex-1 border-b border-black p-2">
                                    <p
                                        class="text-[8px] font-bold uppercase tracking-[0.14em]"
                                    >
                                        Amount in words
                                    </p>

                                    <p
                                        class="mt-1 font-bold uppercase leading-4"
                                    >
                                        {{ receipt.payment.amount_in_words }}
                                    </p>
                                </div>

                                <div class="flex">
                                    <div
                                        class="flex-1 border-r border-black px-2 py-1"
                                    >
                                        <span class="uppercase">
                                            {{
                                                isOnline
                                                    ? "Amount paid"
                                                    : "Tendered"
                                            }}
                                        </span>

                                        <span class="ml-2 font-mono font-bold">
                                            {{
                                                peso(
                                                    receipt.payment
                                                        .amount_tendered,
                                                )
                                            }}
                                        </span>
                                    </div>

                                    <div
                                        v-if="!isOnline"
                                        class="flex-1 border-r border-black px-2 py-1"
                                    >
                                        <span class="uppercase">Change</span>

                                        <span class="ml-2 font-mono font-bold">
                                            {{
                                                hasChange
                                                    ? peso(
                                                          receipt.payment
                                                              .change_due,
                                                      )
                                                    : "0.00"
                                            }}
                                        </span>
                                    </div>

                                    <div class="flex-1 px-2 py-1">
                                        <span class="uppercase">
                                            Account balance
                                        </span>

                                        <span class="ml-2 font-mono font-bold">
                                            {{
                                                peso(
                                                    receipt.account
                                                        .balance_after,
                                                )
                                            }}
                                        </span>

                                        <span
                                            v-if="settled"
                                            class="ml-1 text-[8px] font-bold uppercase text-emerald-700"
                                        >
                                            Settled
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="w-[280px] shrink-0 border-l border-black"
                            >
                                <div
                                    v-for="row in [
                                        {
                                            label: 'VAT Exempt Sales',
                                            value: vatExemptSales,
                                        },
                                        { label: 'VATable Sales', value: 0 },
                                        { label: 'VAT', value: 0 },
                                    ]"
                                    :key="row.label"
                                    class="flex justify-between border-b border-black px-2 py-[3px]"
                                >
                                    <span class="uppercase">
                                        {{ row.label }}
                                    </span>

                                    <span class="font-mono">
                                        {{ peso(row.value) }}
                                    </span>
                                </div>

                                <div
                                    class="flex justify-between bg-black/[0.04] px-2 py-1.5"
                                >
                                    <span
                                        class="font-extrabold uppercase tracking-wide"
                                    >
                                        Total Payment
                                    </span>

                                    <span
                                        class="font-mono text-[13px] font-extrabold"
                                    >
                                        {{
                                            peso(receipt.payment.amount_applied)
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex items-end justify-between gap-6 border-t border-black px-2 py-2 text-[8px]"
                        >
                            <div v-if="!isOnline" class="flex gap-8">
                                <span
                                    class="inline-block w-[150px] border-t border-black pt-1 text-center"
                                >
                                    {{ receipt.issued_by || "Received by" }}
                                </span>

                                <span
                                    class="inline-block w-[150px] border-t border-black pt-1 text-center"
                                >
                                    Payor signature
                                </span>
                            </div>

                            <div v-else />

                            <div class="text-right">
                                <p
                                    v-if="receipt.issuer.permit_no"
                                    class="mt-0.5"
                                >
                                    Permit No. {{ receipt.issuer.permit_no }}
                                </p>

                                <p
                                    class="mt-1 font-mono text-[13px] font-extrabold tracking-wider text-rose-600"
                                >
                                    No. {{ receipt.receipt_no }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <p
                        v-if="receipt.is_voided"
                        class="mt-2 border-2 border-rose-600 px-3 py-1.5 text-center text-[10px] font-bold uppercase tracking-wide text-rose-700"
                    >
                        Voided {{ longDateTime(receipt.voided_at) }}

                        <template v-if="receipt.void_reason">
                            — {{ receipt.void_reason }}
                        </template>
                    </p>
                </article>
            </div>
        </div>
    </Teleport>
</template>

<style>
@media print {
    body.receipt-printing * {
        visibility: hidden !important;
    }

    body.receipt-printing #receipt-print,
    body.receipt-printing #receipt-print * {
        visibility: visible !important;
    }

    body.receipt-printing .receipt-overlay {
        position: static !important;
        overflow: visible !important;
        background: #ffffff !important;
        padding: 0 !important;
        backdrop-filter: none !important;
    }

    body.receipt-printing .receipt-actions {
        display: none !important;
    }

    body.receipt-printing #receipt-print {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
        box-shadow: none !important;
        color: #000000 !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    @page {
        size: A4 landscape;
        margin: 10mm;
    }
}
</style>
