<template>
    <div class="space-y-5 pb-8">
        <div
            class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 overflow-hidden"
        >
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 px-7 py-6 border-b border-[#EDF4F3] bg-gradient-to-b from-[#0E7C7B]/[0.04] to-transparent"
            >
                <div class="flex items-center gap-4 min-w-0">
                    <div class="min-w-0">
                        <div class="flex gap-2 items-center mb-2">
                            <span
                                class="w-fit font-mono text-xs px-2 py-1 rounded-md bg-[#EAF4F2] text-[#0E7C7B] inline-block"
                            >
                                #{{ booking.reference_id }}
                            </span>

                            <span
                                class="px-3 py-1 rounded-sm text-xs font-medium capitalize"
                                :class="statusClasses(status)"
                            >
                                {{ formatStatus(booking.status) }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3 min-w-0">
                            <h2
                                class="text-lg font-semibold text-[#16302E] truncate"
                            >
                                {{
                                    fullName(
                                        booking.patient?.first_name,
                                        booking.patient?.middle_name,
                                        booking.patient?.last_name,
                                    )
                                }}
                            </h2>

                            <button
                                v-if="hasPatientUuid"
                                type="button"
                                class="shrink-0 inline-flex items-center gap-2 px-3 py-1.5 mt-0.5 rounded-lg bg-primary text-white text-xs font-semibold hover:bg-primary/90 transition"
                                @click="viewPatientInfo"
                            >
                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M20 21a8 8 0 0 0-16 0" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>

                                View Patient Profile
                            </button>
                        </div>

                        <p class="text-sm text-muted truncate capitalize">
                            {{ booking.category }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-col items-end gap-2 shrink-0">
                    <div class="text-right">
                        <p
                            class="text-[10px] uppercase tracking-[0.15em] text-[#6B8A87] font-mono"
                        >
                            Created
                        </p>

                        <p class="text-sm font-medium text-[#16302E]">
                            {{ stringToDateTime(booking.created_at) }}
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <span
                            v-if="
                                paymentStatus &&
                                booking.facility?.type === 'Complete'
                            "
                            class="px-3 py-1 uppercase rounded-sm text-xs font-medium"
                            :class="{
                                'bg-emerald-100 text-emerald-700':
                                    paymentStatus === 'paid',
                                'bg-red-100 text-red-700':
                                    paymentStatus === 'refunded',
                                'bg-amber-100 text-amber-700':
                                    paymentStatus === 'pending',
                            }"
                        >
                            {{ paymentStatus }}
                        </span>

                        <div class="text-right">
                            <p
                                class="text-[10px] uppercase tracking-[0.15em] text-[#6B8A87] font-mono"
                            >
                                Total
                            </p>

                            <p class="text-base font-semibold text-[#16302E]">
                                {{ formatCurrency(totalPrice) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-7 py-6 space-y-8">
                <section v-if="isPaid && paymentRows.length">
                    <h3
                        class="flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-[#0E7C7B] mb-4"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <rect x="2" y="5" width="20" height="14" rx="2" />
                            <path d="M2 10h20" />
                        </svg>

                        Payment Information
                    </h3>

                    <div
                        class="rounded-lg border border-emerald-100 bg-emerald-50/40 px-5 py-4"
                    >
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm"
                        >
                            <div v-for="row in paymentRows" :key="row.label">
                                <p
                                    class="text-[10px] uppercase tracking-[0.15em] text-[#6B8A87] font-mono"
                                >
                                    {{ row.label }}
                                </p>

                                <p
                                    class="mt-0.5 font-medium text-[#16302E] break-all"
                                >
                                    {{ row.value }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <h3
                        class="flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-[#0E7C7B] mb-4"
                    >
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

                        {{
                            isFacility
                                ? "Admission Information"
                                : "Service Information"
                        }}
                    </h3>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm"
                    >
                        <Field label="Category" :value="booking.category" />

                        <Field label="Type" :value="serviceTypeLabel" />

                        <Field
                            v-if="
                                isFacility &&
                                booking.facility.type !== 'Pre-Admission'
                            "
                            label="Admission Date"
                            :value="
                                formatDate(
                                    booking.facility?.admission_date ||
                                        reserveInfo?.admitted_at,
                                )
                            "
                        />

                        <template
                            v-else-if="
                                booking.facility?.type !== 'Pre-Admission'
                            "
                        >
                            <Field
                                label="Schedule Date / Preferred Time"
                                :value="
                                    [
                                        formatDate(booking.homecare?.date),
                                        preferredTimeLabel,
                                    ]
                                        .filter(Boolean)
                                        .join(' - ')
                                "
                            />

                            <Field
                                v-if="booking.homecare?.time_span"
                                label="Duration"
                                :value="
                                    formatDuration(
                                        Number(booking.homecare?.time_span),
                                    )
                                "
                            />

                            <Field
                                label="Address"
                                :value="booking.homecare?.address"
                            />
                        </template>

                        <Field
                            v-if="
                                isFacility &&
                                booking.facility?.type !== 'Pre-Admission'
                            "
                            label="Plan"
                            :value="
                                booking.facility?.plan ||
                                reserveInfo?.accommodation_type
                            "
                        />

                        <div class="capitalize">
                            <Field
                                v-if="
                                    isFacility &&
                                    booking.facility?.type !== 'Pre-Admission'
                                "
                                label="Billing Cycle"
                                :value="
                                    booking.facility?.billing_cycle ||
                                    reserveInfo?.billing_cycle
                                "
                            />
                        </div>

                        <template v-if="reserveInfo">
                            <Field
                                label="Room / Bed"
                                :value="`${reserveInfo.room_no} / ${reserveInfo.bed_no}`"
                            />
                        </template>
                    </div>

                    <section
                        class="mt-5"
                        v-if="booking.homecare?.services?.length"
                    >
                        <h3
                            class="flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-[#0E7C7B] mb-4"
                        >
                            <Stethoscope
                                class="h-3.5 w-3.5"
                                :stroke-width="2"
                            />
                            Booked Medical Services
                        </h3>

                        <div class="space-y-3">
                            <div
                                v-for="item in booking.homecare.services"
                                :key="item.service_id"
                                class="rounded-xl px-5"
                            >
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm"
                                >
                                    <div>
                                        <p
                                            class="text-xs text-[#6B8A87] uppercase tracking-wide"
                                        >
                                            Service
                                        </p>

                                        <p
                                            class="text-sm font-semibold text-[#16302E]"
                                        >
                                            {{ item.service_name }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </section>

                <!-- <section
                    v-if="booking.homecare?.type?.toLowerCase() === 'adl'"
                    class="flex flex-col"
                >
                    <h3
                        class="flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-[#0E7C7B] mb-4"
                    >
                        <Stethoscope class="h-3.5 w-3.5" :stroke-width="2" />

                        Assigned Medical Staff
                    </h3>

                    <p
                        class="text-xs text-[#6B8A87] uppercase tracking-wide mb-3 ml-5"
                    >
                        List of Medical Staff
                    </p>
                </section> -->

                <PatientDetails :booking="booking" />
                <GuardianAssessmentDetails :booking="booking" />

                <section
                    class="pt-5 border-t border-[#EDF4F3] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                >
                    <div>
                        <p
                            class="text-xs font-mono uppercase tracking-widest text-[#6B8A87] mb-1"
                        >
                            Booking Made By
                        </p>

                        <div class="flex gap-2 items-center">
                            <div>
                                <p class="text-sm font-medium text-[#16302E]">
                                    {{ booking.guardian?.first_name }}
                                    {{ booking.guardian?.last_name }}
                                </p>

                                <p class="text-sm text-[#6B8A87]">
                                    {{ booking.guardian?.email }}
                                </p>

                                <p class="text-sm text-[#6B8A87]">
                                    {{ stringToDateTime(booking.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { computed } from "vue";
import { fullName } from "~/utils/user";
import { stringToDateTime, formatDate, formatDuration } from "~/utils/time";
import { formatCurrency } from "~/utils/currency";
import { Stethoscope } from "lucide-vue-next";
import { Field } from "~/utils/fields";
import { format24To12 } from "~/utils/time";
import PatientDetails from "./PatientDetails.vue";
import GuardianAssessmentDetails from "./GuardianAssessmentDetails.vue";
import { useRouter, useRoute } from "vue-router";
import {
    formatStatus,
    statusClasses,
    type BookingRetrieve,
} from "~/types/booking";

const router = useRouter();
const route = useRoute();

const props = defineProps<{
    booking: BookingRetrieve;
    loading?: boolean;
}>();

const booking = computed(() => props.booking);

const hasPatientUuid = computed(() => {
    return !!props.booking?.patient?.uuid;
});

const patientUuid = computed(() => {
    return props.booking?.patient?.uuid;
});

const viewPatientInfo = () => {
    if (!patientUuid.value) return;

    router.push({
        path: `/app/branches/${route.params.uuid}/patients/${patientUuid.value}`,
    });
};

const status = computed(() => {
    return (props.booking?.status ?? "").toLowerCase();
});

const category = computed(() => {
    return (props.booking?.category ?? "").toLowerCase();
});

const isFacility = computed(() => {
    return category.value === "facility";
});

const serviceType = computed(() => {
    if (props.booking.booking_type !== "online") {
        return "walk-in";
    }

    if (props.booking.category === "facility") {
        return props.booking.facility.type;
    }

    return props.booking.homecare.type;
});

const reserveInfo = computed(() => {
    const reserved = props.booking?.reserved;

    if (!reserved) return null;

    return {
        accommodation_type: reserved.accommodation_type,
        billing_cycle: reserved.billing_cycle,
        room_no: reserved.room?.room_no ?? "—",
        bed_no: reserved.bed?.bed_no ?? "—",
        admitted_at: reserved.admitted_at,
        price: Number(reserved.price) || 0,
    };
});

const serviceTypeLabel = computed(() => {
    switch (serviceType.value) {
        case "Medical":
            return "Medical Services";
        case "Complete":
            return "Complete Admission";
        case "Pre-Admission":
            return "Pre-Admission";
        case "ADL":
            return "Activities of Daily Living (ADL)";
        default:
            return serviceType.value;
    }
});

const preferredTimeLabel = computed(() => {
    const time = props.booking.homecare?.prefered_time;

    if (!time) return "";

    return format24To12(time);
});

const totalPrice = computed(() => {
    const paymentTotal = props.booking?.payment?.total_amount;

    if (paymentTotal !== undefined && paymentTotal !== null) {
        return Number(paymentTotal);
    }

    const services = props.booking.homecare?.services ?? [];

    return services.reduce(
        (sum: number, s: any) => sum + (Number(s.price) || 0),
        0,
    );
});

const paymentStatus = computed(() => {
    return props.booking?.payment?.payment_status ?? null;
});

const isPaid = computed(() => paymentStatus.value === "paid");

// Only a settled payment carries a method, reference and card — a pending one
// holds nothing but the total, so the section stays hidden until it's paid.
const paymentRows = computed(() => {
    const payment = props.booking?.payment;

    if (!payment) return [];

    const label = (method?: string | null) => {
        if (!method) return null;

        const known: Record<string, string> = {
            "CREDIT-CARD": "Credit / Debit card",
            GCASH: "GCash",
            CASH: "Cash",
        };

        return known[String(method).toUpperCase()] ?? method;
    };

    return [
        { label: "Amount paid", value: formatCurrency(Number(payment.total_amount) || 0) },
        { label: "Method", value: label(payment.payment_method) },
        { label: "Card", value: payment.masked_card_number },
        { label: "Reference", value: payment.xendit_invoice_id },
    ].filter((row) => row.value);
});
</script>
