<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted } from "vue";
import { Printer, X } from "lucide-vue-next";

import { formatAmount } from "~/utils/currency";
import type { BookingRetrieve } from "~/types/booking";

const props = defineProps<{
    booking: BookingRetrieve;
    branchName?: string | null;
    branchImage?: string | null;
}>();

const emit = defineEmits<{
    close: [];
}>();

const isFacility = computed(() => props.booking.category === "facility");

const serviceType = computed(() => {
    const booking = props.booking;

    if (booking.homecare?.type === "Medical") return "Medical Services";
    if (booking.homecare?.type === "ADL") return "Activity of Daily Living (ADL)";
    if (booking.facility?.type === "Complete") return "Complete Admission";
    if (booking.facility?.type) return booking.facility.type;

    return "—";
});

const patientName = computed(() => {
    const patient = props.booking.patient;

    return [patient?.first_name, patient?.middle_name, patient?.last_name]
        .filter(Boolean)
        .join(" ")
        .trim() || "—";
});

const scheduleDate = computed(() =>
    isFacility.value
        ? props.booking.facility?.admission_date
        : props.booking.homecare?.date,
);

const serviceAddress = computed(() =>
    isFacility.value
        ? "On-site — at the facility"
        : props.booking.homecare?.address ?? "—",
);

const payment = computed(() => props.booking.payment ?? null);

const displayBranchName = computed(
    () => props.branchName ?? (props.booking as any)?.branch_name ?? "—",
);

const branchImage = computed(
    () => props.branchImage ?? (props.booking as any)?.branch_image ?? null,
);

function longDate(value: string | null | undefined) {
    if (!value) return "—";

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) return "—";

    return parsed.toLocaleDateString("en-PH", {
        month: "long",
        day: "numeric",
        year: "numeric",
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

function peso(amount: number | string | null | undefined) {
    return formatAmount(amount, { treatMissingAsZero: true });
}

function handlePrint() {
    if (typeof window !== "undefined") window.print();
}

onMounted(() => {
    document.body.classList.add("acknowledgement-printing");
});

onBeforeUnmount(() => {
    document.body.classList.remove("acknowledgement-printing");
});
</script>

<template>
    <Teleport to="body">
        <div
            class="acknowledgement-overlay fixed inset-0 z-[60] overflow-y-auto bg-gray-950/50 p-4 backdrop-blur-sm"
            @click.self="emit('close')"
        >
            <div class="mx-auto max-w-3xl">
                <div
                    class="acknowledgement-actions mb-3 flex justify-end gap-2"
                >
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100 dark:bg-secondary dark:text-gray-400 dark:hover:bg-white/10"
                        @click="handlePrint"
                    >
                        <Printer class="h-4 w-4" />
                        Print
                    </button>

                    <button
                        type="button"
                        aria-label="Close"
                        class="rounded-lg bg-white/10 p-2 text-white transition hover:bg-white/20 dark:hover:bg-white/10"
                        @click="emit('close')"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div
                    id="acknowledgement-print"
                    class="flex flex-col rounded-xl bg-white p-10 text-slate-900 shadow-2xl dark:bg-secondary dark:text-white"
                >
                    <div
                        class="flex items-start justify-between gap-6 border-b-2 border-slate-900 pb-5"
                    >
                        <div class="flex items-start gap-3">
                            <img
                                v-if="branchImage"
                                :src="branchImage"
                                :alt="displayBranchName"
                                class="h-12 w-12 shrink-0 rounded-lg object-cover ring-1 ring-slate-200 dark:ring-white/10"
                            />

                            <div>
                                <p
                                    class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-gray-400"
                                >
                                    {{ displayBranchName }}
                                </p>

                                <h1 class="mt-1 text-2xl font-bold">
                                    Booking Acknowledgement
                                </h1>
                            </div>
                        </div>

                        <div class="text-right">
                            <p
                                class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400"
                            >
                                Reference No.
                            </p>
                            <p class="text-lg font-bold tabular-nums">
                                {{ booking.reference_id ?? "—" }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                                Issued {{ longDateTime(booking.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-x-8 gap-y-5">
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400"
                            >
                                Patient
                            </p>
                            <p class="mt-0.5 text-sm font-semibold">
                                {{ patientName }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400"
                            >
                                Status
                            </p>
                            <p class="mt-0.5 text-sm font-semibold capitalize">
                                {{ booking.status ?? "—" }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400"
                            >
                                Booking Type
                            </p>
                            <p class="mt-0.5 text-sm capitalize">
                                {{ booking.category ?? "—" }} ·
                                {{ serviceType }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400"
                            >
                                {{
                                    isFacility
                                        ? "Admission Date"
                                        : "Service Date"
                                }}
                            </p>
                            <p class="mt-0.5 text-sm">
                                {{ longDate(scheduleDate) }}
                            </p>
                        </div>

                        <div class="col-span-2">
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400"
                            >
                                Service Address
                            </p>
                            <p class="mt-0.5 text-sm">{{ serviceAddress }}</p>
                        </div>

                        <div v-if="booking.valid_until" class="col-span-2">
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400"
                            >
                                Booking Valid Until
                            </p>
                            <p class="mt-0.5 text-sm">
                                {{ longDateTime(booking.valid_until) }}
                                <span class="text-slate-500 dark:text-gray-400">
                                    — the branch must review and accept this
                                    request before this time, otherwise it
                                    expires.
                                </span>
                            </p>
                        </div>
                    </div>

                    <div v-if="payment" class="mt-7">
                        <p
                            class="border-b border-slate-300 pb-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400 dark:border-white/10"
                        >
                            Payment
                        </p>

                        <table class="mt-3 w-full text-sm">
                            <tbody>
                                <tr class="border-b border-slate-100 dark:border-white/10">
                                    <td class="py-1.5 text-slate-600 dark:text-gray-400">
                                        Amount paid
                                    </td>
                                    <td
                                        class="py-1.5 text-right font-semibold tabular-nums"
                                    >
                                        ₱{{ peso(payment.total_amount) }}
                                    </td>
                                </tr>

                                <tr
                                    v-if="payment.payment_method"
                                    class="border-b border-slate-100 dark:border-white/10"
                                >
                                    <td class="py-1.5 text-slate-600 dark:text-gray-400">
                                        Method
                                    </td>
                                    <td class="py-1.5 text-right">
                                        {{ payment.payment_method }}
                                    </td>
                                </tr>

                                <tr v-if="payment.masked_card_number">
                                    <td class="py-1.5 text-slate-600 dark:text-gray-400">Card</td>
                                    <td
                                        class="py-1.5 text-right tabular-nums"
                                    >
                                        {{ payment.masked_card_number }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        class="mt-auto border-t border-slate-200 pt-4 text-[11px] leading-5 text-slate-500 dark:border-white/10 dark:text-gray-400"
                    >
                        <p>
                            This document acknowledges that the booking above
                            was submitted through AMUMA. It is not a medical
                            record and does not by itself confirm the service —
                            a booking stays a request until the care team
                            reviews and accepts it. Present the reference number
                            above when following up with the branch.
                        </p>

                        <div class="mt-8 flex justify-between gap-10">
                            <div class="flex-1">
                                <div class="border-t border-slate-400 pt-1">
                                    <p class="text-[10px] uppercase">
                                        Client / Guardian signature
                                    </p>
                                </div>
                            </div>

                            <div class="flex-1">
                                <div class="border-t border-slate-400 pt-1">
                                    <p class="text-[10px] uppercase">
                                        Received by (staff)
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style>
@media print {
    body.acknowledgement-printing * {
        visibility: hidden !important;
    }

    body.acknowledgement-printing #acknowledgement-print,
    body.acknowledgement-printing #acknowledgement-print * {
        visibility: visible !important;
    }

    body.acknowledgement-printing .acknowledgement-overlay {
        position: static !important;
        overflow: visible !important;
        background: #ffffff !important;
        padding: 0 !important;
        backdrop-filter: none !important;
    }

    body.acknowledgement-printing .acknowledgement-actions {
        display: none !important;
    }

    body.acknowledgement-printing #acknowledgement-print {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: 100%;
        /* A4 height less the @page margins, so mt-auto can push the
           signatures onto the foot of the sheet. */
        min-height: 269mm;
        margin: 0;
        padding: 0;
        box-shadow: none !important;
        color: #000000 !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    @page {
        size: A4 portrait;
        margin: 14mm;
    }
}
</style>
