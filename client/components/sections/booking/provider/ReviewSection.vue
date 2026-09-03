<template>
    <section class="rounded-2xl p-8 md:p-10">
        <div class="flex items-baseline gap-3 mb-8">
            <span class="text-2xl text-primary">06</span>
            <div>
                <h2 class="text-xl text-primary">
                    {{
                        category === "facility" && facility.type === "Complete"
                            ? "Review & Proceed to Payment"
                            : "Review & Submit"
                    }}
                </h2>
                <p class="text-[13px] text-muted dark:text-gray-400">
                    {{
                        category === "facility" && facility.type === "Complete"
                            ? "Check that everything below is correct, then complete payment to confirm your reservation."
                            : "Check that everything below is correct before sending your request"
                    }}
                </p>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-slate-200 dark:border-white/10">
                <div
                    class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 dark:border-white/10"
                >
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-white">
                        Booking Details
                    </h3>
                    <button
                        type="button"
                        @click="$emit('edit-step', 'step1')"
                        class="flex items-center gap-1 text-xs font-medium text-primary hover:underline underline-offset-2"
                    >
                        <Pencil class="h-3 w-3" /> Edit
                    </button>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 p-5">
                    <div
                        v-for="row in bookingRows"
                        :key="row.label"
                        :class="row.span ? 'sm:col-span-2' : ''"
                    >
                        <dd
                            v-if="row.label === 'Total'"
                            class="mt-2 rounded-lg border border-slate-200 bg-slate-50 pl-4 pr-5 py-4 dark:bg-secondary dark:border-white/10"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-gray-400"
                                >
                                    Total Amount
                                </span>
                                <span
                                    class="text-2xl font-bold text-primary tabular-nums"
                                >
                                    {{ row.value }}
                                </span>
                            </div>
                            <p
                                v-if="showPayment"
                                class="mt-1.5 text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                * Your payment will be fully refunded once it is
                                rejected.
                            </p>
                            <p v-else class="mt-1.5 text-[11px] text-slate-400 dark:text-gray-500">
                                * Prices are estimates and may change without
                                further notice.
                            </p>
                        </dd>

                        <div v-else>
                            <dt class="text-xs text-slate-400 dark:text-gray-500">
                                {{ row.label }}
                            </dt>
                            <dd
                                :class="
                                    row.value
                                        ? 'text-sm font-medium text-slate-800 mt-0.5 break-words dark:text-white'
                                        : 'text-sm text-slate-300 mt-0.5 dark:text-gray-600'
                                "
                            >
                                {{ row.value || "Not provided" }}
                            </dd>
                        </div>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-slate-200 dark:border-white/10">
                <div
                    class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 dark:border-white/10"
                >
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-white">
                        Patient Information
                    </h3>
                    <button
                        type="button"
                        @click="$emit('edit-step', 'step2')"
                        class="flex items-center gap-1 text-xs font-medium text-primary hover:underline underline-offset-2"
                    >
                        <Pencil class="h-3 w-3" /> Edit
                    </button>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 p-5">
                    <div
                        v-for="row in patientRows"
                        :key="row.label"
                        :class="row.span ? 'sm:col-span-2' : ''"
                    >
                        <dt class="text-xs text-slate-400 dark:text-gray-500">
                            {{ row.label }}
                        </dt>
                        <dd
                            :class="
                                row.value
                                    ? 'text-sm font-medium text-slate-800 mt-0.5 break-words dark:text-white'
                                    : 'text-sm text-slate-300 mt-0.5 dark:text-gray-600'
                            "
                        >
                            {{ row.value || "Not provided" }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-slate-200 dark:border-white/10">
                <div
                    class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 dark:border-white/10"
                >
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-white">
                        Guardian Information
                    </h3>
                    <button
                        type="button"
                        @click="$emit('edit-step', 'step3')"
                        class="flex items-center gap-1 text-xs font-medium text-primary hover:underline underline-offset-2"
                    >
                        <Pencil class="h-3 w-3" /> Edit
                    </button>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 p-5">
                    <div
                        v-for="row in guardianRows"
                        :key="row.label"
                        :class="row.span ? 'sm:col-span-2' : ''"
                    >
                        <dt class="text-xs text-slate-400 dark:text-gray-500">
                            {{ row.label }}
                        </dt>
                        <dd
                            :class="
                                row.value
                                    ? 'text-sm font-medium text-slate-800 mt-0.5 break-words dark:text-white'
                                    : 'text-sm text-slate-300 mt-0.5 dark:text-gray-600'
                            "
                        >
                            {{ row.value || "Not provided" }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-slate-200 dark:border-white/10">
                <div
                    class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 dark:border-white/10"
                >
                    <h3 class="text-sm font-semibold text-slate-800 dark:text-white">
                        Diagnosis
                        <span
                            class="ml-1 text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >(Optional)</span
                        >
                    </h3>
                    <button
                        type="button"
                        @click="$emit('edit-step', 'step4')"
                        class="flex items-center gap-1 text-xs font-medium text-primary hover:underline underline-offset-2"
                    >
                        <Pencil class="h-3 w-3" /> Edit
                    </button>
                </div>

                <div class="p-5">
                    <p
                        v-if="!diagnoses.length"
                        class="text-sm text-slate-400 dark:text-gray-500"
                    >
                        No diagnoses were provided.
                    </p>

                    <div v-else class="space-y-6">
                        <div
                            v-for="(entry, index) in diagnoses"
                            :key="index"
                            class="pb-6 border-b border-slate-100 last:pb-0 last:border-b-0 dark:border-white/10"
                        >
                            <h4
                                class="text-xs font-semibold text-slate-600 mb-3 dark:text-gray-300"
                            >
                                Diagnosis {{ index + 1 }}
                            </h4>

                            <dl
                                class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3"
                            >
                                <div
                                    v-for="row in getDiagnosisRows(entry)"
                                    :key="row.label"
                                >
                                    <dt
                                        class="text-xs text-slate-400 dark:text-gray-500"
                                    >
                                        {{ row.label }}
                                    </dt>
                                    <dd
                                        class="text-sm font-medium text-slate-800 mt-0.5 break-words dark:text-white"
                                    >
                                        {{ row.value }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 dark:border-white/10">
                <div
                    class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 dark:border-white/10"
                >
                    <h3
                        class="text-sm font-semibold text-slate-800 dark:text-white"
                    >
                        Assessment
                        <span
                            class="ml-1 text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >(Optional)</span
                        >
                    </h3>

                    <button
                        type="button"
                        @click="$emit('edit-step', 'step5')"
                        class="flex items-center gap-1 text-xs font-medium text-primary hover:underline underline-offset-2"
                    >
                        <Pencil class="h-3 w-3" /> Edit
                    </button>
                </div>

                <div class="p-5">
                    <p
                        v-if="!assessments.length"
                        class="text-sm text-slate-400 dark:text-gray-500"
                    >
                        No assessment details were provided.
                    </p>

                    <template v-else>
                        <dl
                            class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3"
                        >
                            <div
                                v-for="row in getAssessmentRows(assessments[0])"
                                :key="row.label"
                            >
                                <dt
                                    class="text-xs text-slate-400 dark:text-gray-500"
                                >
                                    {{ row.label }}
                                </dt>
                                <dd
                                    class="text-sm font-medium text-slate-800 mt-0.5 break-words dark:text-white"
                                >
                                    {{ row.value }}
                                </dd>
                            </div>
                        </dl>

                        <div
                            v-if="lifeSystemRows(assessments[0]).length"
                            class="mt-5 border-t border-slate-100 pt-4 dark:border-white/10"
                        >
                            <p
                                class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                            >
                                Life System Profile
                            </p>

                            <dl
                                class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3"
                            >
                                <div
                                    v-for="row in lifeSystemRows(
                                        assessments[0],
                                    )"
                                    :key="row.label"
                                >
                                    <dt
                                        class="text-xs text-slate-400 dark:text-gray-500"
                                    >
                                        {{ row.label }}
                                    </dt>
                                    <dd
                                        class="text-sm font-medium text-slate-800 mt-0.5 break-words dark:text-white"
                                    >
                                        {{ row.value }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Pencil } from "lucide-vue-next";
import type { HomecareBooking, FacilityBooking } from "~/types/booking";
import type { Patient, Guardian, Assessment } from "~/types/patient";
import type { Diagnosis } from "~/types/patient";
import type { Service } from "~/types/service";
import type { BranchFacility, BranchHomecare } from "~/types/branch";
import { useMedicalServices } from "~/composables/useBooking";
import { formatCurrency } from "~/utils/currency";
import {
    LIFE_SYSTEM_ACTIVITIES,
    activityLabel,
    assessmentLabel,
    lifeSystemLabel,
} from "~/utils/assessment";

const props = defineProps<{
    category: "homecare" | "facility" | null;
    homecare: HomecareBooking;
    facility: FacilityBooking;
    patient: Patient;
    guardian: Guardian;
    assessments: Assessment[];
    diagnoses: Diagnosis[];
    services: Service[];
    branchHomecare?: BranchHomecare;
    branchFacility?: BranchFacility[];
    showPayment?: boolean;
}>();

defineEmits<{
    (e: "edit-step", step: string): void;
}>();

type Row = {
    label: string;
    value: string;
    span?: boolean;
};

const adlRatePerHour = computed<number>(
    () => props.branchHomecare?.adl_hourly_rate ?? 0,
);

const minAdlHours = computed<number>(
    () => props.branchHomecare?.adl_min_hour ?? 0,
);

const { selectedServiceLabel, selectedServicesTotal } = useMedicalServices(
    () => props.services,
    () => props.homecare.services,
    () => adlRatePerHour.value,
    () => minAdlHours.value,
);

function formatDate(value?: string) {
    if (!value) return "";

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}

function fullName(parts: (string | undefined | null)[]) {
    return parts.filter(Boolean).join(" ");
}

const facilityTotal = computed<number>(() => {
    if (!props.branchFacility?.length) {
        return 0;
    }

    const room = props.facility.plan?.toUpperCase();
    const billing = props.facility.billing_cycle?.toUpperCase();

    if (!room || !billing) {
        return 0;
    }

    const facility = props.branchFacility.find(
        (item) =>
            item.accommodation_type?.toUpperCase() === room &&
            item.billing_cycle?.toUpperCase() === billing,
    );

    return Number(facility?.price ?? 0);
});

const bookingRows = computed<Row[]>(() => {
    if (props.category === "homecare") {
        const hc = props.homecare;

        const rows: Row[] = [
            {
                label: "Booking Type",
                value:
                    hc.type === "ADL"
                        ? "Activities of Daily Living (ADL)"
                        : (hc.type ?? ""),
            },
            {
                label: "Date",
                value: formatDate(hc.date),
            },
            {
                label: "Preferred Time",
                value: hc.prefered_time
                    ? new Date(
                          `1970-01-01T${hc.prefered_time}`,
                      ).toLocaleTimeString("en-US", {
                          hour: "numeric",
                          minute: "2-digit",
                          hour12: true,
                      })
                    : "",
            },
        ];

        if (hc.type === "Medical") {
            rows.push({
                label: "Medical Service",
                value: selectedServiceLabel.value ?? "",
            });

            rows.push({
                label: "Estimated Total",
                value: formatCurrency(selectedServicesTotal.value, {
                    treatMissingAsZero: true,
                }),
            });
        }

        if (hc.type === "ADL") {
            const hours = Number(hc.time_span ?? 0);

            rows.push({
                label: "Duration",
                value: hours ? `${formatDuration(hours)} (${hours} hrs)` : "",
            });
        }

        rows.push({
            label: "Service Location",
            value: hc.address ?? "",
            span: true,
        });

        if (hc.type === "ADL") {
            const hours = Number(hc.time_span ?? 0);
            const amount = hours * adlRatePerHour.value;

            rows.push({
                label: "Total",
                value: formatCurrency(amount),
                span: true,
            });
        } else if (hc.type === "Medical") {
            rows.push({
                label: "Total",
                value: formatCurrency(selectedServicesTotal.value, {
                    treatMissingAsZero: true,
                }),
                span: true,
            });
        }

        return rows;
    }

    const fc = props.facility;

    const rows: Row[] = [
        {
            label: "Admission Type",
            value: fc.type ?? "",
        },
    ];

    if (fc.type === "Complete") {
        rows.push(
            {
                label: "Accommodation Type",
                value: fc.plan ?? "",
            },
            {
                label: "Admission Plan",
                value: fc.billing_cycle ?? "",
            },
            {
                label: "Admission Date",
                value: formatDate(fc.admission_date),
            },
            {
                label: "Total",
                value: formatCurrency(facilityTotal.value),
                span: true,
            },
        );
    }

    return rows;
});

const patientRows = computed<Row[]>(() => {
    const p = props.patient;

    const heightWeight = [
        p.height ? `${p.height} cm` : "",
        p.weight ? `${p.weight} kg` : "",
    ]
        .filter(Boolean)
        .join(" / ");

    return [
        {
            label: "Full Name",
            value: fullName([p.first_name, p.middle_name, p.last_name]),
        },
        {
            label: "Gender",
            value: p.gender ?? "",
        },
        {
            label: "Date of Birth",
            value: formatDate(p.date_of_birth),
        },
        {
            label: "Phone Number",
            value: p.phone_number ?? "",
        },
        {
            // The patient's own address. Homecare bookings show the visit
            // address separately under the service section.
            label:
                props.category === "facility" ? "Address" : "Home Address",
            value: p.address ?? "",
            span: true,
        },
        {
            label: "Citizenship",
            value: p.citizenship ?? "",
        },
        {
            label: "Occupation",
            value: p.occupation ?? "",
        },
        {
            label: "Marital Status",
            value: p.marital_status ?? "",
        },
        {
            label: "Height / Weight",
            value: heightWeight,
        },
        {
            label: "Blood Type",
            value: p.blood_type ?? "",
        },
        {
            label: "Allergies",
            value: p.allergies ?? "",
            span: true,
        },
    ];
});

const guardianRows = computed<Row[]>(() => {
    const g = props.guardian;

    return [
        {
            label: "Full Name",
            value: fullName([g.first_name, g.middle_name, g.last_name]),
        },
        {
            label: "Relationship to Patient",
            value: g.relationship ?? "",
        },
        {
            label: "Phone Number",
            value: g.phone_number ?? "",
        },
        {
            label: "Email",
            value: g.email ?? "",
        },
        {
            label: "Address",
            value: g.address ?? "",
            span: true,
        },
        {
            label: "Occupation",
            value: g.occupation ?? "",
        },
    ];
});

function getDiagnosisRows(entry: Diagnosis): Row[] {
    return [
        { label: "Primary Diagnosis", value: entry.diagnosis ?? "" },
        { label: "Date Diagnosed", value: entry.diagnosis_date ? formatDate(entry.diagnosis_date) : "" },
        { label: "Diagnosis Notes", value: entry.diagnosis_notes ?? "" },
        { label: "Supporting Document", value: entry.diagnosis_file_name ?? "" },
    ].filter((row) => Boolean(row.value));
}

function lifeSystemRows(assessment?: Assessment): Row[] {
    return LIFE_SYSTEM_ACTIVITIES.map((activity) => ({
        label: activityLabel(activity),
        value: lifeSystemLabel(assessment?.life_system_profile?.[activity]),
    })).filter((row) => Boolean(row.value));
}

function getAssessmentRows(assessment?: Assessment): Row[] {
    if (!assessment) return [];

    const rows: Row[] = [
        {
            label: "Condition",
            value: assessmentLabel(assessment.condition),
        },
        {
            label: "Level of Consciousness",
            value: assessmentLabel(assessment.mental_state),
        },
        {
            label: "Affect",
            value: assessmentLabel(assessment.affect),
        },
        {
            label: "Behavior",
            value: assessmentLabel(assessment.behavior),
        },
        {
            label: "Communication Ability",
            value: assessment.communication ?? "",
        },
        {
            label: "Speech Pattern",
            value: assessmentLabel(assessment.speech),
        },
    ];

    return rows.filter((row) => Boolean(row.value));
}

function formatDuration(hours: number) {
    if (!Number.isFinite(hours) || hours <= 0) {
        return "";
    }

    let remainingHours = hours;

    const months = Math.floor(remainingHours / (24 * 30));
    remainingHours %= 24 * 30;

    const days = Math.floor(remainingHours / 24);
    remainingHours %= 24;

    const wholeHours = Math.floor(remainingHours);

    const parts: string[] = [];

    if (months) {
        parts.push(`${months} month${months > 1 ? "s" : ""}`);
    }

    if (days) {
        parts.push(`${days} day${days > 1 ? "s" : ""}`);
    }

    if (wholeHours) {
        parts.push(`${wholeHours} hr${wholeHours > 1 ? "s" : ""}`);
    }

    return parts.join(" and ");
}
</script>
