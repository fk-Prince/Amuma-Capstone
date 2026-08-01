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
                        <span
                            class="w-fit font-mono text-xs px-2 py-1 rounded-md bg-[#EAF4F2] text-[#0E7C7B] inline-block mb-2"
                        >
                            #{{ booking.reference_id }}
                        </span>

                        <h2
                            class="text-lg font-semibold text-[#16302E] truncate"
                        >
                            {{
                                fullName(
                                    patient?.first_name,
                                    patient?.middle_name,
                                    patient?.last_name,
                                )
                            }}
                        </h2>
                        <p class="text-sm text-muted truncate">
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
                            class="px-3 py-1 rounded-full text-xs font-medium capitalize"
                            :class="statusClasses(status)"
                        >
                            {{ booking.status }}
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
                            v-if="isFacility"
                            label="Admission Date"
                            :value="
                                formatDate(
                                    service?.admission_date ||
                                        reserveInfo?.admitted_at,
                                )
                            "
                        />

                        <template v-else>
                            <Field
                                label="Schedule Date"
                                :value="formatDate(service?.date)"
                            />

                            <Field
                                v-if="preferredTimeLabel"
                                label="Preferred Time"
                                :value="preferredTimeLabel"
                            />

                            <Field label="Address" :value="service?.address" />
                        </template>

                        <Field
                            v-if="isFacility"
                            label="Plan"
                            :value="
                                service?.plan || reserveInfo?.accommodation_type
                            "
                        />

                        <div class="capitalize">
                            <Field
                                v-if="isFacility"
                                label="Billing Cycle"
                                :value="
                                    service?.billing_cycle ||
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

                    <section class="mt-5" v-if="service?.services?.length">
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
                                v-for="item in service.services"
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

                                    <div>
                                        <p
                                            class="text-xs text-[#6B8A87] uppercase tracking-wide"
                                        >
                                            Assigned Medical Staff
                                        </p>

                                        <p
                                            class="text-sm font-semibold"
                                            :class="
                                                getAssignment(item.service_id)
                                                    ? 'text-[#16302E]'
                                                    : 'text-amber-600'
                                            "
                                        >
                                            {{
                                                getAssignment(item.service_id)
                                                    ?.employee_name ??
                                                "Unassigned"
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </section>

                <section
                    v-if="
                        booking.booking_data.service.type?.toLowerCase() ===
                        'adl'
                    "
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

                    <div v-if="booking.assignments?.length" class="space-y-3">
                        <div
                            v-for="item in booking.assignments"
                            :key="item.employee_id"
                            class="rounded-xl bg-white px-4"
                        >
                            <div
                                class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm"
                            >
                                <div>
                                    <div class="flex items-center gap-3 mt-1">
                                        <div
                                            class="h-10 w-10 overflow-hidden rounded-full bg-[#EAF4F2] flex items-center justify-center shrink-0"
                                        >
                                            <img
                                                v-if="item.avatar"
                                                :src="item.avatar"
                                                :alt="item.employee_name"
                                                class="h-full w-full object-cover"
                                            />
                                            <span
                                                v-else
                                                class="text-sm font-semibold text-[#0E7C7B]"
                                            >
                                                {{
                                                    item.employee_name?.charAt(
                                                        0,
                                                    ) ?? "?"
                                                }}
                                            </span>
                                        </div>

                                        <div>
                                            <p
                                                class="text-sm font-normal text-[#16302E]"
                                            >
                                                {{
                                                    item.employee_name ||
                                                    "Unassigned"
                                                }}
                                            </p>
                                            <p
                                                v-if="item.role_name"
                                                class="text-xs font-normal text-slate-400"
                                            >
                                                {{ item.role_name }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p
                        v-else
                        class="rounded-xl border border-dashed border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700"
                    >
                        No medical staff assigned yet
                    </p>
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
                                    patient?.first_name,
                                    patient?.middle_name,
                                    patient?.last_name,
                                )
                            "
                        />
                        <Field label="Gender" :value="patient?.gender" />
                        <Field
                            label="Birth Date"
                            :value="formatDate(patient?.date_of_birth)"
                        />
                        <Field
                            label="Blood Type"
                            :value="patient?.blood_type"
                        />
                        <Field label="Phone" :value="patient?.phone_number" />
                        <Field
                            label="Occupation"
                            :value="patient?.occupation"
                        />
                        <Field
                            label="Address"
                            :value="patient?.address || service?.address"
                        />
                        <Field
                            label="Height"
                            :value="
                                patient?.height ? `${patient.height} cm` : ''
                            "
                        />
                        <Field
                            label="Weight"
                            :value="
                                patient?.weight ? `${patient.weight} kg` : ''
                            "
                        />
                    </div>
                </section>

                <section v-if="guardian">
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
                                    guardian.first_name,
                                    guardian.middle_name,
                                    guardian.last_name,
                                )
                            "
                        />
                        <Field
                            label="Relationship"
                            :value="guardian.relationship"
                        />
                        <Field
                            label="Phone Number"
                            :value="guardian.phone_number"
                        />
                        <Field label="Email" :value="guardian.email" />
                        <Field
                            label="Occupation"
                            :value="guardian.occupation"
                        />
                        <Field label="Address" :value="guardian.address" />
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
                        <template v-for="(value, key) in assessment" :key="key">
                            <Field
                                v-if="String(key) !== 'diagnosis_file'"
                                :label="formatLabel(String(key))"
                                :value="
                                    String(key) === 'diagnosis_file_name'
                                        ? undefined
                                        : value
                                "
                            >
                                <template
                                    v-if="String(key) === 'diagnosis_file_name'"
                                    #value
                                >
                                    <a
                                        v-if="assessment?.diagnosis_file"
                                        :href="assessment.diagnosis_file"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-primary underline hover:text-primary/70"
                                    >
                                        {{ value || "View file" }}
                                    </a>
                                    <span v-else>{{ value || "—" }}</span>
                                </template>
                            </Field>
                        </template>
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
                                <p class="text-sm text-[#6B8A87]">
                                    {{ stringToDateTime(booking.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <ActionButton
                            v-if="status === 'pending'"
                            :loading="loading"
                            variant="danger"
                            type="button"
                            @click="emit('reject', booking)"
                        >
                            Reject
                        </ActionButton>

                        <ActionButton
                            v-if="isPaid && status === 'awaiting'"
                            :loading="loading"
                            variant="solid"
                            @click="emit('admit', booking)"
                        >
                            Admit Patient
                        </ActionButton>

                        <ActionButton
                            v-if="isAssignableService && status === 'pending'"
                            :loading="loading"
                            variant="outline"
                            @click="assignBooking"
                        >
                            Assign Now
                        </ActionButton>

                        <ActionButton
                            v-if="status === 'pending'"
                            :loading="loading"
                            variant="solid"
                            @click="emit('confirm', booking)"
                        >
                            {{ confirmButtonLabel }}
                        </ActionButton>

                        <button
                            v-if="showAccommodationButton"
                            type="button"
                            @click="handleAdmission"
                            class="px-5 py-2 text-sm font-medium rounded-md bg-primary text-white hover:bg-primary/90 transition"
                        >
                            Select Accommodation
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { computed, h } from "vue";
import { fullName } from "~/utils/user";
import { stringToDateTime, formatDate } from "~/utils/time";
import { formatCurrency } from "~/utils/currency";
import { Stethoscope } from "lucide-vue-next";
const props = defineProps<{
    booking: any;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: "reject", booking: any): void;
    (e: "confirm", booking: any): void;
    (e: "assign", booking: any): void;
    (e: "admit", booking: any): void;
    (e: "accommodation", booking: any): void;
}>();

const status = computed(() => (props.booking?.status ?? "").toLowerCase());
const category = computed(() => (props.booking?.category ?? "").toLowerCase());
const isFacility = computed(() => category.value === "facility");
const service = computed(() => props.booking?.booking_data?.service ?? null);
const serviceType = computed(() => service.value?.type ?? "");
const patient = computed(() => props.booking?.booking_data?.patient ?? null);
const guardian = computed(() => props.booking?.booking_data?.guardian ?? null);
const assessment = computed(
    () => props.booking?.booking_data?.assessment ?? null,
);
const isPaid = computed(() => !!props.booking?.booking_data?.payment?.paid);

const hasAssessment = computed(
    () => !!assessment.value && Object.keys(assessment.value).length > 0,
);

const reserveInfo = computed(() => {
    const reserved = props.booking?.booking_data?.reserved;
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
    const type = serviceType.value;
    if (type === "Medical") return "Medical Services";
    if (type === "Complete" || type === "Pre-Admission") return "Pre-Admission";
    return type;
});

const preferredTimeLabel = computed(() => {
    const time = service.value?.prefered_time;
    if (!time) return "";
    return new Date(`1970-01-01T${time}`).toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
    });
});

const isAssignableService = computed(() =>
    ["Medical", "ADL"].includes(serviceType.value),
);

const showAccommodationButton = computed(() => {
    const type = serviceType.value?.toLowerCase() ?? "";
    const currentStatus = status.value?.toLowerCase() ?? "";

    return (
        (currentStatus === "pending" ||
            currentStatus === "awaiting" ||
            currentStatus === "approved") &&
        isFacility.value &&
        (type === "complete" || type === "walk-in admission") &&
        type !== "medical"
    );
});

const confirmButtonLabel = computed(() =>
    serviceType.value === "Complete" && props.booking?.booking_data?.reserved
        ? "Approve Admission Request"
        : "Approve Booking",
);

const totalPrice = computed(() => {
    const paymentTotal = props.booking?.booking_data?.payment?.total_amount;
    if (paymentTotal !== undefined && paymentTotal !== null) {
        return Number(paymentTotal);
    }

    const services = service.value?.services ?? [];
    return services.reduce(
        (sum: number, s: any) => sum + (Number(s.price) || 0),
        0,
    );
});

function handleAdmission() {
    emit("accommodation", props.booking);
}

function assignBooking() {
    if (!props.booking) return;
    emit("assign", props.booking);
}

function formatLabel(key: string) {
    return String(key)
        .replace(/_/g, " ")
        .replace(/([a-z])([A-Z])/g, "$1 $2")
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

function statusClasses(normalizedStatus: string) {
    if (
        normalizedStatus.includes("confirm") ||
        normalizedStatus.includes("approved")
    ) {
        return "bg-[#E4F4EE] text-[#1F7A4D]";
    }
    if (normalizedStatus.includes("complete")) {
        return "bg-[#E6F1FA] text-[#2563A6]";
    }
    if (
        normalizedStatus.includes("reject") ||
        normalizedStatus.includes("declin") ||
        normalizedStatus.includes("cancel")
    ) {
        return "bg-[#FBE8E6] text-[#B3402F]";
    }
    return "bg-[#FDF3DE] text-[#966B1F]";
}
function getAssignment(serviceId: number) {
    return props.booking?.assignments?.find(
        (assignment: any) => assignment.service_id === serviceId,
    );
}

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

const ActionButton = (
    actionProps: {
        loading?: boolean;
        variant: "solid" | "outline" | "danger";
    },
    { slots, attrs }: any,
) => {
    const variantClass =
        actionProps.variant === "solid"
            ? "bg-primary text-white hover:bg-primary/90"
            : actionProps.variant === "danger"
              ? "border border-red-300 text-red-600 hover:bg-red-50"
              : "border border-primary text-primary hover:bg-primary/10";

    return h(
        "button",
        {
            type: "button",
            disabled: actionProps.loading,
            class: `px-5 py-2 text-sm font-medium rounded-md transition ${variantClass}`,
            ...attrs,
        },
        actionProps.loading
            ? [
                  h("span", { class: "flex items-center gap-2" }, [
                      h(
                          "svg",
                          {
                              class: "w-4 h-4 animate-spin",
                              viewBox: "0 0 24 24",
                              fill: "none",
                              stroke: "currentColor",
                              "stroke-width": "3",
                          },
                          [
                              h("circle", {
                                  cx: "12",
                                  cy: "12",
                                  r: "10",
                                  class: "opacity-25",
                              }),
                              h("path", {
                                  d: "M4 12a8 8 0 018-8",
                                  class: "opacity-75",
                              }),
                          ],
                      ),
                      "Processing...",
                  ]),
              ]
            : slots.default?.(),
    );
};
ActionButton.props = ["loading", "variant"];
</script>
