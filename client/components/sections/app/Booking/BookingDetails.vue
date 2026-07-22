<template>
    <div class="space-y-5 pb-8">
        <div
            class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 overflow-hidden"
        >
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-7 py-6 border-b border-[#EDF4F3] bg-gradient-to-b from-[#0E7C7B]/[0.04] to-transparent"
            >
                <div class="flex items-center gap-4 min-w-0">
                    <div class="min-w-0">
                        <span
                            class="w-fit font-mono text-xs px-2 py-1 rounded-md bg-[#EAF4F2] text-[#0E7C7B] inline-block mb-1.5"
                        >
                            #{{ booking.reference_id }}
                        </span>

                        <h2
                            class="text-lg font-semibold text-[#16302E] truncate"
                        >
                            {{
                                fullName(
                                    booking.booking_data.patient?.first_name,
                                    booking.booking_data.patient?.middle_name,
                                    booking.booking_data.patient?.last_name,
                                )
                            }}
                        </h2>

                        <p class="text-sm text-muted truncate">
                            {{ booking.category }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <span
                        class="px-3 py-1 rounded-full text-xs font-medium capitalize"
                        :class="statusClasses(booking.status)"
                    >
                        {{ booking.status }}
                    </span>

                    <span class="text-base font-semibold text-[#16302E]">
                        {{ formatCurrency(totalPrice(booking)) }}
                    </span>
                </div>
            </div>

            <div class="px-7 py-6 space-y-8">
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
                        Service Information
                    </h3>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm"
                    >
                        <Field
                            label="Type"
                            :value="
                                booking.booking_data?.service?.type ===
                                'Medical'
                                    ? 'Medical Services'
                                    : booking.booking_data?.service?.type
                            "
                        />
                        <Field
                            label="Date"
                            :value="
                                formatDate(booking.booking_data?.service?.date)
                            "
                        />
                        <Field
                            v-if="booking.booking_data?.service?.prefered_time"
                            label="Preferred Time"
                            :value="
                                booking.booking_data?.service?.prefered_time
                            "
                        />
                        <Field
                            label="Address"
                            :value="booking.booking_data?.service?.address"
                        />
                    </div>

                    <div
                        v-if="booking.booking_data?.service?.services?.length"
                        class="mt-5"
                    >
                        <p class="text-xs font-medium text-[#6B8A87] mb-2">
                            Services
                        </p>
                        <div
                            class="rounded-xl border border-[#EDF4F3] overflow-hidden divide-y divide-[#EDF4F3]"
                        >
                            <div
                                v-for="service in booking.booking_data.service
                                    .services"
                                :key="service.service_id"
                                class="flex justify-between items-center bg-[#F7FAF9] px-4 py-2.5 text-sm"
                            >
                                <span class="text-[#16302E]">{{
                                    service.service_name
                                }}</span>
                                <span
                                    class="font-mono font-medium text-[#0E7C7B]"
                                >
                                    {{ formatCurrency(service.price) }}
                                </span>
                            </div>
                            <div
                                class="flex justify-between items-center bg-white px-4 py-3 text-sm"
                            >
                                <span class="font-semibold text-[#16302E]">
                                    Total Amount
                                </span>
                                <span
                                    class="font-mono font-semibold text-[#0E7C7B]"
                                >
                                    {{ formatCurrency(totalPrice(booking)) }}
                                </span>
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
                                d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"
                            />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        Patient Information
                    </h3>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm"
                    >
                        <Field
                            label="Name"
                            :value="
                                fullName(
                                    booking.booking_data.patient?.first_name,
                                    booking.booking_data.patient?.middle_name,
                                    booking.booking_data.patient?.last_name,
                                )
                            "
                        />
                        <Field
                            label="Gender"
                            :value="booking.booking_data?.patient?.gender"
                        />
                        <Field
                            label="Birth Date"
                            :value="
                                formatDate(
                                    booking.booking_data?.patient
                                        ?.date_of_birth,
                                )
                            "
                        />
                        <Field
                            label="Blood Type"
                            :value="booking.booking_data?.patient?.blood_type"
                        />
                        <Field
                            label="Phone"
                            :value="booking.booking_data?.patient?.phone_number"
                        />
                        <Field
                            label="Occupation"
                            :value="booking.booking_data?.patient?.occupation"
                        />
                        <Field
                            label="Address"
                            :value="booking.booking_data?.patient?.address"
                        />
                        <Field
                            label="Height"
                            :value="
                                booking.booking_data?.patient?.height
                                    ? `${booking.booking_data.patient.height} cm`
                                    : ''
                            "
                        />
                        <Field
                            label="Weight"
                            :value="
                                booking.booking_data?.patient?.weight
                                    ? `${booking.booking_data.patient.weight} kg`
                                    : ''
                            "
                        />
                    </div>
                </section>

                <section v-if="booking.booking_data?.guardian">
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
                                d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"
                            />
                            <circle cx="10" cy="7" r="4" />
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                        Guardian
                    </h3>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm"
                    >
                        <Field
                            label="Name"
                            :value="
                                fullName(
                                    booking.booking_data.guardian.first_name,
                                    booking.booking_data.guardian.middle_name,
                                    booking.booking_data.guardian.last_name,
                                )
                            "
                        />
                        <Field
                            label="Relationship"
                            :value="booking.booking_data.guardian.relationship"
                        />
                        <Field
                            label="Phone Number"
                            :value="booking.booking_data.guardian.phone_number"
                        />
                        <Field
                            label="Email"
                            :value="booking.booking_data.guardian.email"
                        />
                        <Field
                            label="Occupation"
                            :value="booking.booking_data.guardian.occupation"
                        />
                        <Field
                            label="Address"
                            :value="booking.booking_data.guardian.address"
                        />
                    </div>
                </section>

                <section v-if="hasAssessment">
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
                            <path d="M9 11l3 3L22 4" />
                            <path
                                d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"
                            />
                        </svg>
                        Assessment
                    </h3>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm"
                    >
                        <Field
                            v-for="(value, key) in booking.booking_data
                                .assessment"
                            :key="key"
                            :label="formatLabel(String(key))"
                            :value="value"
                        />
                    </div>
                </section>

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
                            <img
                                :src="booking.user?.avatar"
                                alt="User Avatar"
                                class="w-12 h-12 rounded-full object-cover shrink-0"
                            />

                            <div>
                                <p class="text-sm font-medium text-[#16302E]">
                                    {{ booking.user?.first_name }}
                                    {{ booking.user?.last_name }}
                                </p>

                                <p class="text-sm text-[#6B8A87]">
                                    {{ booking.user?.email }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="emit('reject', booking)"
                            class="px-5 py-2 text-sm font-medium rounded-md border border-red-300 text-red-600 hover:bg-red-50 transition"
                        >
                            Reject
                        </button>
                        <button
                            type="button"
                            @click="emit('confirm', booking)"
                            class="px-5 py-2 text-sm font-medium rounded-md bg-primary text-white hover:bg-primary/90 transition"
                        >
                            Approve
                        </button>

                        <!-- <button
                            type="button"
                            @click="emit('confirm', booking)"
                            class="px-5 py-2 text-sm font-medium rounded-md bg-primary text-white hover:bg-primary/90 transition"
                        >
                            Assign Staff & Confirm
                        </button> -->
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { computed, h } from "vue";
import { fullName } from "~/utils/user";

const props = defineProps<{
    booking: any;
}>();

const emit = defineEmits<{
    (e: "reject", booking: any): void;
    (e: "confirm", booking: any): void;
}>();

const hasAssessment = computed(() => {
    const a = props.booking?.booking_data?.assessment;
    return !!a && Object.keys(a).length > 0;
});

function totalPrice(booking: any) {
    const services = booking.booking_data?.service?.services ?? [];
    return services.reduce(
        (sum: number, s: any) => sum + (Number(s.price) || 0),
        0,
    );
}

function formatLabel(key: string) {
    return String(key)
        .replace(/_/g, " ")
        .replace(/([a-z])([A-Z])/g, "$1 $2")
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

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

function formatCurrency(value?: number) {
    if (value === undefined || value === null || isNaN(Number(value)))
        return "—";
    return `₱${Number(value).toLocaleString("en-PH", { minimumFractionDigits: 2 })}`;
}

function statusClasses(status?: string) {
    const s = (status ?? "").toLowerCase();

    if (s.includes("confirm") || s.includes("approved")) {
        return "bg-[#E4F4EE] text-[#1F7A4D]";
    }

    if (s.includes("complete")) {
        return "bg-[#E6F1FA] text-[#2563A6]";
    }

    if (s.includes("reject") || s.includes("declin") || s.includes("cancel")) {
        return "bg-[#FBE8E6] text-[#B3402F]";
    }

    return "bg-[#FDF3DE] text-[#966B1F]";
}

const Field = (props: { label: string; value: any }) =>
    h("p", { class: "flex flex-col gap-0.5" }, [
        h("span", { class: "text-xs text-[#6B8A87]" }, props.label),
        h("span", { class: "text-[#16302E] font-medium" }, props.value ?? "—"),
    ]);
</script>
