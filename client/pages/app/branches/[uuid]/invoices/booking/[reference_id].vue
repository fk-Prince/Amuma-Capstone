<template>
    <div class="w-full px-4 sm:px-6 lg:px-8 py-6 space-y-5 pb-8">
        <div class="flex flex-wrap items-center justify-between gap-3 no-print">
            <button
                type="button"
                @click="goBack"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-[#6B8A87] hover:text-[#16302E] transition"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none">
                    <path
                        d="M12.5 15L7.5 10L12.5 5"
                        stroke="currentColor"
                        stroke-width="1.75"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
                Back
            </button>

            <button
                type="button"
                @click="handlePrint"
                class="inline-flex items-center gap-2 rounded-full border border-[#DDECEC] bg-white px-4 py-2 text-sm font-medium text-[#0E7C7B] shadow-sm transition hover:border-[#0E7C7B] hover:text-[#0A5A58]"
            >
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M6 9V3h12v6" />
                    <path
                        d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"
                    />
                    <path d="M8 14h8v6H8z" />
                </svg>
                Print
            </button>
        </div>

        <div
            class="hidden print:block rounded-2xl border border-[#EDF4F3] bg-white p-5"
        >
            <div
                class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-[#16302E]"
            >
                <div>
                    <p
                        class="font-semibold uppercase tracking-[0.2em] text-[#6B8A87] text-[11px]"
                    >
                        Booking
                    </p>
                    <p class="mt-1 text-base font-semibold">
                        {{ booking?.reference_id ?? "—" }}
                    </p>
                    <p class="text-[#6B8A87]">
                        {{ booking?.patient?.full_name ?? "—" }}
                    </p>
                </div>
                <div class="text-right">
                    <p
                        class="font-semibold uppercase tracking-[0.2em] text-[#6B8A87] text-[11px]"
                    >
                        Status
                    </p>
                    <p class="mt-1 text-base font-semibold">
                        {{ booking?.status ?? "—" }}
                    </p>
                    <p class="text-[#6B8A87]">
                        {{ booking?.payment?.paid ? "Paid" : "Unpaid" }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="loading"
            class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 p-10 text-center text-[#6B8A87]"
        >
            Loading booking…
        </div>

        <div
            v-else-if="error"
            class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 p-10 text-center text-[#B3402F]"
        >
            {{ error }}
        </div>

        <div
            v-else-if="booking"
            class="grid grid-cols-1 xl:grid-cols-[1fr_500px] gap-5 items-start"
        >
            <div
                class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 overflow-hidden"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 px-7 py-6 border-b border-[#EDF4F3] bg-gradient-to-b from-[#0E7C7B]/[0.04] to-transparent"
                >
                    <div class="min-w-0">
                        <span
                            class="w-fit font-mono text-xs px-2 py-1 rounded-md bg-[#EAF4F2] text-[#0E7C7B] inline-block mb-2"
                        >
                            {{ booking.reference_id }}
                        </span>

                        <h2
                            class="text-lg font-semibold text-[#16302E] truncate"
                        >
                            {{ booking.patient?.full_name ?? "—" }}
                        </h2>
                        <p class="text-sm text-[#6B8A87] truncate">
                            {{ booking.category }} — {{ booking.service?.type }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <span
                            class="px-3 py-1 rounded-full text-xs font-medium capitalize"
                            :class="statusClasses(booking.status)"
                        >
                            {{ formatStatus(booking.status) }}
                        </span>

                        <span
                            class="px-3 py-1 rounded-full text-xs font-medium capitalize"
                            :class="paymentStatusClasses(booking.payment?.paid)"
                        >
                            {{ booking.payment?.paid ? "Paid" : "Unpaid" }}
                        </span>
                    </div>
                </div>

                <div
                    class="grid grid-cols-2 sm:grid-cols-3 sm:divide-x divide-[#EDF4F3] border-b border-[#EDF4F3] bg-[#FAFDFC]"
                >
                    <div class="px-4 sm:px-7 py-4 sm:py-5">
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-[#6B8A87] font-mono mb-1"
                        >
                            Total
                        </p>
                        <p class="text-2xl font-bold text-[#16302E]">
                            ₱{{ formatMoney(booking.total) }}
                        </p>
                    </div>

                    <div class="px-4 sm:px-7 py-4 sm:py-5 bg-[#E4F4EE]/40">
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-[#1F7A4D]/80 font-mono mb-1"
                        >
                            Amount Paid
                        </p>
                        <p class="text-2xl font-bold text-[#1F7A4D]">
                            ₱{{ formatMoney(booking.amount_paid) }}
                        </p>
                    </div>

                    <div class="px-4 sm:px-7 py-4 sm:py-5 bg-[#FBE8E6]/40">
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-[#B3402F]/80 font-mono mb-1"
                        >
                            Balance Due
                        </p>
                        <p class="text-2xl font-bold text-[#B3402F]">
                            ₱{{ formatMoney(booking.balance_due) }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="booking.category !== 'Facility'"
                    class="px-7 py-6 border-b border-[#EDF4F3]"
                >
                    <SectionHeader>
                        <template #icon>
                            <Stethoscope
                                class="h-3.5 w-3.5"
                                :stroke-width="2"
                            />
                        </template>
                        Service
                    </SectionHeader>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm mb-4"
                    >
                        <Field label="Type" :value="booking.service?.type" />
                        <Field
                            label="Date"
                            :value="formatDate(booking.service?.date)"
                        />
                        <Field
                            label="Preferred Time"
                            :value="booking.service?.preferred_time"
                        />
                        <Field
                            label="Address"
                            :value="booking.service?.address"
                        />
                        <Field
                            v-if="booking.service?.plan"
                            label="Plan"
                            :value="booking.service.plan"
                        />
                        <Field
                            v-if="booking.service?.billing_cycle"
                            label="Billing Cycle"
                            :value="booking.service.billing_cycle"
                        />
                    </div>

                    <div
                        v-if="booking.service?.services?.length"
                        class="space-y-2"
                    >
                        <div
                            v-for="line in booking.service.services"
                            :key="line.service_id"
                            class="flex items-center justify-between rounded-lg border border-[#EDF4F3] px-4 py-2 text-sm"
                        >
                            <span class="text-[#16302E]">{{
                                line.service_name
                            }}</span>
                            <span class="font-medium text-[#16302E]"
                                >₱{{ formatMoney(line.price) }}</span
                            >
                        </div>
                    </div>
                </div>

                <div
                    v-if="booking.reserved"
                    class="px-7 py-6 border-b border-[#EDF4F3]"
                >
                    <SectionHeader>
                        <template #icon>
                            <svg
                                class="h-3.5 w-3.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4"
                                />
                                <path d="M15 3h6v6" />
                                <path d="M10 14 21 3" />
                            </svg>
                        </template>
                        Room & Bed
                    </SectionHeader>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm"
                    >
                        <Field
                            label="Room No."
                            :value="booking.reserved.room?.room_no"
                        />
                        <Field
                            label="Room Type"
                            :value="booking.reserved.room?.room_type"
                        />
                        <Field
                            label="Floor"
                            :value="booking.reserved.room?.floor"
                        />
                        <Field
                            label="Bed No."
                            :value="booking.reserved.bed?.bed_no"
                        />
                        <Field
                            label="Accommodation"
                            :value="booking.reserved.accommodation_type"
                        />
                        <Field
                            label="Price"
                            :value="`₱${formatMoney(booking.reserved.price)}`"
                        />
                    </div>
                </div>

                <div
                    v-if="booking.patient"
                    class="px-7 py-6 border-b border-[#EDF4F3]"
                >
                    <SectionHeader>
                        <template #icon>
                            <svg
                                class="h-3.5 w-3.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path
                                    d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"
                                />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </template>
                        Patient
                    </SectionHeader>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm"
                    >
                        <Field
                            label="Name"
                            :value="booking.patient.full_name"
                        />
                        <Field label="Gender" :value="booking.patient.gender" />
                        <Field
                            label="Birth Date"
                            :value="formatDate(booking.patient.date_of_birth)"
                        />
                        <Field
                            label="Blood Type"
                            :value="booking.patient.blood_type"
                        />
                        <Field
                            label="Phone"
                            :value="booking.patient.phone_number"
                        />
                        <Field
                            label="Citizenship"
                            :value="booking.patient.citizenship"
                        />
                    </div>
                </div>

                <div class="px-7 py-6">
                    <SectionHeader>
                        <template #icon>
                            <svg
                                class="h-3.5 w-3.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <rect
                                    x="2"
                                    y="5"
                                    width="20"
                                    height="14"
                                    rx="2"
                                />
                                <path d="M2 10h20" />
                            </svg>
                        </template>
                        Payment
                    </SectionHeader>

                    <div
                        v-if="booking.payment?.paid"
                        class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm"
                    >
                        <Field label="Status" value="Paid" />
                        <Field
                            label="Amount"
                            :value="`₱${formatMoney(booking.payment.total_amount)}`"
                        />
                    </div>

                    <p
                        v-else-if="isPending"
                        class="rounded-xl border border-dashed border-[#DDECEC] bg-[#FAFDFC] px-4 py-3 text-sm font-medium text-[#6B8A87]"
                    >
                        This booking is awaiting approval. Payment isn't due
                        yet.
                    </p>

                    <p
                        v-else
                        class="rounded-xl border border-dashed border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700"
                    >
                        No payment recorded yet
                    </p>
                </div>
            </div>

            <div v-if="showPayment" class="xl:sticky xl:top-6 print:hidden">
                <div class="p-6">
                    <PaymentForm
                        :processing="processingPayment"
                        :total-amount="booking.balance_due"
                        :enable-card="false"
                        :enable-g-cash="false"
                        :enable-cash="true"
                        title="Complete Payment"
                        :description="`Balance due: ₱${formatMoney(booking.balance_due)}`"
                        cash-label="Confirm Cash Payment"
                        cash-processing-label="Confirming payment..."
                        cash-description="Enter the cash amount received."
                        @cash-pay="handleCashPay"
                        :allow-short-cash="false"
                    />
                </div>
            </div>

            <div
                v-else-if="isPending"
                class="rounded-2xl shadow-sm ring-1 ring-black/5 bg-white p-6 text-center text-sm text-[#6B8A87] xl:sticky xl:top-6 print:hidden"
            >
                This booking is pending approval. Payment can be collected once
                it's approved.
            </div>

            <div
                v-else
                class="rounded-2xl shadow-sm ring-1 ring-black/5 bg-white p-6 text-center text-sm text-[#6B8A87] xl:sticky xl:top-6 print:hidden"
            >
                This booking is fully paid.
            </div>
        </div>

        <div
            v-else
            class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 p-10 text-center text-[#6B8A87]"
        >
            No booking found.
        </div>
    </div>
</template>

<script lang="ts" setup>
import { ref, computed, onMounted, h } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Stethoscope } from "lucide-vue-next";
import { invoiceService } from "~/api/invoice/InvoiceService";
import { useToast } from "~/composables/useToast";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import type { BookingDetail } from "~/types/invoice";
import { formatStatus } from "~/types/booking";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Booking Detail",
});

const route = useRoute();
const router = useRouter();

const uuid = computed(() => route.params.uuid as string);
const referenceId = computed(() => route.params.reference_id as string);

const booking = ref<BookingDetail | null>(null);
const loading = ref(true);
const error = ref("");
const { success } = useToast();
const processingPayment = ref(false);

const isPending = computed(() => {
    return (booking.value?.status ?? "").toLowerCase() === "pending";
});

const showPayment = computed(() => {
    if (!booking.value) return false;

    // Pending bookings haven't been approved yet — never show the
    // payment form for them, regardless of what balance_due says.
    if (isPending.value) return false;

    const status = (booking.value.status ?? "").toLowerCase();

    return status === "approved" && booking.value.balance_due > 0;
});

async function fetchBooking() {
    loading.value = true;
    error.value = "";

    try {
        const response = await invoiceService.show(
            {
                reference_id: referenceId.value,
                branch_uuid: uuid.value,
                mode: route.query.mode,
            },
            referenceId.value,
        );
        booking.value = response.data ?? response ?? null;
    } catch (err) {
        console.error(err);
        error.value = "Unable to load this booking.";
    } finally {
        loading.value = false;
    }
}

async function handleCashPay(cash: number) {
    if (!booking.value) return;

    processingPayment.value = true;

    try {
        const res = await invoiceService.create({
            cash,
            mode: "booking",
            payment_method: "CASH",
            reference_id: referenceId.value,
            branch_uuid: uuid.value,
        });
        success(res.message);
        await fetchBooking();
    } catch (err) {
        console.error(err);
        error.value = "Payment failed. Please try again.";
    } finally {
        processingPayment.value = false;
    }
}
function handlePrint() {
    if (typeof window !== "undefined") {
        window.print();
    }
}

function goBack() {
    router.back();
}
function statusClasses(status: string) {
    const normalized = (status ?? "").toLowerCase();

    if (normalized === "paid") return "bg-[#E4F4EE] text-[#1F7A4D]";
    if (normalized === "partial") return "bg-[#E6F1FA] text-[#2563A6]";
    if (normalized === "overdue") return "bg-[#FBE8E6] text-[#B3402F]";
    if (normalized === "pending") return "bg-[#FDF3DE] text-[#966B1F]";
    return "bg-[#FDF3DE] text-[#966B1F]";
}

function formatMoney(amount: number | string | null | undefined) {
    return Number(amount ?? 0).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
    });
}

function formatDate(value: string | null | undefined) {
    if (!value) return "—";

    return new Date(value).toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}

onMounted(() => {
    fetchBooking();
});

const Field = (fieldProps: { label: string; value: any }, { slots }: any) =>
    h("p", { class: "flex flex-col gap-0.5" }, [
        h("span", { class: "text-xs text-[#6B8A87]" }, fieldProps.label),
        h(
            "span",
            { class: "text-[#16302E] font-medium" },
            slots.value ? slots.value() : (fieldProps.value ?? "—"),
        ),
    ]);
Field.props = ["label", "value"];

const SectionHeader = (_props: unknown, { slots }: any) =>
    h(
        "h3",
        {
            class: "flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-[#0E7C7B] mb-4",
        },
        [slots.icon?.(), slots.default?.()],
    );

function paymentStatusClasses(paid: boolean | undefined) {
    return paid ? "bg-[#E4F4EE] text-[#1F7A4D]" : "bg-[#FBE8E6] text-[#B3402F]";
}
</script>

<style scoped>
@media print {
    :global(html),
    :global(body) {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
    }

    .no-print,
    .print-hidden {
        display: none !important;
    }

    .print\:hidden {
        display: none !important;
    }

    .rounded-2xl {
        border-radius: 0 !important;
    }

    .shadow-sm,
    .shadow,
    .shadow-md {
        box-shadow: none !important;
    }

    .ring-1,
    .ring-black\/5,
    .ring {
        box-shadow: none !important;
    }

    @page {
        size: A4 landscape;
        margin: 10mm;
    }
}
</style>
