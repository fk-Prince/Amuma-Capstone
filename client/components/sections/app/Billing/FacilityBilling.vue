<template>
    <div
        v-if="mapped"
        class="rounded-2xl border border-[#E4EFED] bg-white overflow-hidden shadow-sm"
    >
        <div
            class="px-5 py-4 flex items-start justify-between bg-gradient-to-r from-primary/5 to-transparent border-b border-[#E4EFED]"
        >
            <div>
                <p
                    class="text-[11px] font-medium text-slate-400 uppercase tracking-wide"
                >
                    Reference No.
                </p>
                <p
                    class="text-base font-semibold text-slate-900 tracking-tight"
                >
                    {{ mapped.referenceId }}
                </p>
            </div>
            <span
                class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize whitespace-nowrap"
                :class="statusClass(mapped.status)"
            >
                {{ mapped.status }}
            </span>
        </div>

        <div class="px-5 py-4 border-b border-[#E4EFED]">
            <p
                class="text-[11px] font-medium text-slate-400 uppercase tracking-wide mb-1"
            >
                Booking Detail
            </p>

            <div class="flex items-baseline gap-1.5 mb-4">
                <span class="text-sm font-semibold text-slate-800 capitalize">
                    {{ mapped.category }}
                </span>
                <span v-if="categoryType" class="text-sm text-slate-400">
                    ({{ categoryType }})
                </span>
            </div>

            <dl
                v-if="mapped.category === 'facility' && mapped.facility"
                class="grid grid-cols-2 gap-y-3 gap-x-4 text-sm"
            >
                <Field
                    label="Admission Date"
                    :value="formatDate(mapped.facility.admission_date)"
                />
                <Field
                    label="Accommodation Type"
                    :value="
                        mapped.reserved.accommodation_type ??
                        mapped.reserved.room?.room_type
                    "
                />
                <Field
                    label="Billing Cycle"
                    :value="mapped.reserved.billing_cycle"
                    :capitalize="true"
                />
                <Field
                    label="Price"
                    :value="formatCurrency(mapped.reserved.price)"
                />
            </dl>

            <!-- <div
                v-else-if="mapped.category === 'homecare' && mapped.homecare"
                class="space-y-3 text-sm"
            >
                <dl class="grid grid-cols-2 gap-y-3 gap-x-4">
                    <Field
                        label="Date"
                        :value="formatDate(mapped.homecare.date)"
                    />
                    <Field
                        label="Preferred Time"
                        :value="mapped.homecare.prefered_time"
                    />
                    <Field
                        label="Duration"
                        :value="mapped.homecare.time_span"
                    />
                    <Field label="Address" :value="mapped.homecare.address" />
                </dl>
                <div v-if="mapped.homecare.services?.length">
                    <p class="text-slate-400 text-xs mb-1.5">Services</p>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="(s, i) in mapped.homecare.services"
                            :key="i"
                            class="text-xs bg-slate-100 text-slate-700 px-2 py-1 rounded-md"
                        >
                            {{ s }}
                        </span>
                    </div>
                </div>
            </div> -->
        </div>

        <div
            v-if="
                mapped.category === 'facility' &&
                (mapped.reserved?.room || mapped.reserved?.bed)
            "
            class="px-5 py-4 border-b border-[#E4EFED]"
        >
            <p
                class="text-[11px] font-medium text-slate-400 uppercase tracking-wide mb-3"
            >
                Room Assignment
            </p>
            <dl class="grid grid-cols-2 gap-y-3 gap-x-4 text-sm">
                <Field
                    v-if="mapped.reserved.room"
                    label="Room No."
                    :value="`${mapped.reserved.room.room_no} · ${mapped.reserved.room.floor} Floor`"
                />
                <Field
                    v-if="mapped.reserved.bed"
                    label="Bed No."
                    :value="mapped.reserved.bed.bed_no"
                />
                <Field
                    v-if="mapped.reserved.room"
                    label="Room Type"
                    :value="
                        mapped.reserved.accommodation_type ??
                        mapped.reserved.room.room_type
                    "
                />
            </dl>
        </div>

        <div class="px-5 py-4 border-b border-[#E4EFED]">
            <h3
                class="flex items-center gap-2 text-[11px] font-mono font-medium uppercase tracking-widest text-primary mb-3"
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
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                Patient Information
            </h3>
            <dl class="grid grid-cols-2 gap-y-3 gap-x-4 text-sm">
                <Field label="Name" :value="patientName" />
                <Field label="Contact" :value="mapped.patient.phone_number" />
                <Field label="Address" :value="mapped.patient.address" />
            </dl>
        </div>

        <div v-if="hasGuardian" class="px-5 py-4 border-b border-[#E4EFED]">
            <h3
                class="flex items-center gap-2 text-[11px] font-mono font-medium uppercase tracking-widest text-primary mb-3"
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
                    <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                    <circle cx="10" cy="7" r="4" />
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
                Guardian
            </h3>
            <dl class="grid grid-cols-2 gap-y-3 gap-x-4 text-sm">
                <Field label="Name" :value="guardianName" />
                <Field label="Contact" :value="mapped.guardian.phone_number" />
                <Field label="Address" :value="mapped.guardian.address" />
            </dl>
        </div>

        <div class="px-5 py-4 bg-[#F7FAF9] flex justify-between text-sm">
            <span class="text-slate-500">Valid Until</span>
            <span class="font-medium text-slate-700">{{
                formatDate(mapped.validUntil)
            }}</span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { mapBookingResponse, type BookingRecord } from "~/stores/booking";

const props = withDefaults(
    defineProps<{
        loading?: boolean;
        booking: BookingRecord;
    }>(),
    {
        loading: false,
        roomContract: () => [],
    },
);

const mapped = computed(() =>
    props.booking ? mapBookingResponse(props.booking) : null,
);

const categoryType = computed(() => {
    if (!mapped.value) return "";
    if (mapped.value.category === "facility") {
        return mapped.value.facility?.type ?? "";
    }
    return mapped.value.homecare?.type ?? "";
});

const patientName = computed(() => {
    if (!mapped.value) return "—";
    const { first_name, middle_name, last_name } = mapped.value.patient;
    return (
        [first_name, middle_name, last_name].filter(Boolean).join(" ") || "—"
    );
});

const patientInitials = computed(() => {
    if (!mapped.value) return "?";
    const { first_name, last_name } = mapped.value.patient;
    const initials =
        `${first_name?.[0] ?? ""}${last_name?.[0] ?? ""}`.toUpperCase();
    return initials || "?";
});

const hasGuardian = computed(() => {
    if (!mapped.value?.guardian) return false;
    const g = mapped.value.guardian;
    return !!(g.first_name || g.last_name || g.phone_number || g.address);
});

function formatDate(value?: string) {
    if (!value) return "—";
    const d = new Date(value);
    if (isNaN(d.getTime())) return value;
    return d.toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

function statusClass(status: string) {
    switch ((status || "").toLowerCase()) {
        case "confirmed":
        case "active":
            return "bg-emerald-100 text-emerald-700";
        case "pending":
            return "bg-amber-100 text-amber-700";
        case "cancelled":
        case "expired":
            return "bg-red-100 text-red-700";
        default:
            return "bg-slate-100 text-slate-600";
    }
}
const guardianName = computed(() => {
    if (!mapped.value?.guardian) return "—";
    const { first_name, middle_name, last_name } = mapped.value.guardian;
    return (
        [first_name, middle_name, last_name].filter(Boolean).join(" ") || "—"
    );
});

const Field = (props: {
    label: string;
    value?: string | number;
    capitalize?: boolean;
}) =>
    h("div", [
        h("dt", { class: "text-slate-400 text-xs" }, props.label),
        h(
            "dd",
            {
                class: [
                    "font-medium text-slate-700 mt-0.5",
                    props.capitalize ? "capitalize" : "",
                ],
            },
            props.value || "—",
        ),
    ]);
</script>
