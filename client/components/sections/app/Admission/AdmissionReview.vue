<template>
    <section class="rounded-2xl p-8 md:p-10">
        <div class="flex items-baseline gap-3 mb-8">
            <span class="text-2xl text-primary">05</span>
            <div>
                <h2 class="text-xl text-primary">Review & Submit Admission</h2>
                <p class="text-[13px] text-muted dark:text-gray-400">
                    Check that everything below is correct before sending your
                    admission request.
                </p>
            </div>
        </div>

        <div class="space-y-6">
            <div
                class="rounded-xl border border-slate-200 dark:border-white/10"
            >
                <div
                    class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 dark:border-white/10"
                >
                    <h3
                        class="text-sm font-semibold text-slate-800 dark:text-white"
                    >
                        Admission Details
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
                        v-for="row in admissionRows"
                        :key="row.label"
                        :class="row.span ? 'sm:col-span-2' : ''"
                    >
                        <dd
                            v-if="row.label === 'Total'"
                            class="mt-2 rounded-lg border border-slate-200 bg-slate-50 pl-4 pr-5 py-4 dark:border-white/10 dark:bg-white/5"
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
                            <div
                                v-if="props.payment?.paid"
                                class="mb-6 mt-1 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 flex items-start gap-3 dark:border-emerald-500/20 dark:bg-emerald-500/10"
                            >
                                <div
                                    class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300"
                                >
                                    <CircleCheck class="h-5 w-5" />
                                </div>

                                <div>
                                    <p
                                        class="text-sm font-semibold text-emerald-800 dark:text-emerald-300"
                                    >
                                        Payment Completed
                                    </p>

                                    <p
                                        class="text-xs text-emerald-700 dark:text-emerald-300"
                                    >
                                        This admission has already been paid.
                                    </p>

                                    <p
                                        v-if="props.payment.invoice_code"
                                        class="mt-1 text-xs font-medium text-emerald-800 dark:text-emerald-300"
                                    >
                                        Invoice Code:
                                        <span class="font-bold">
                                            {{ props.payment.invoice_code }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <p
                                class="mt-1.5 text-[11px] text-slate-400 dark:text-gray-500"
                            >
                                * Prices are estimates and may change without
                                further notice.
                            </p>
                        </dd>

                        <div v-else>
                            <dt
                                class="text-xs text-slate-400 dark:text-gray-500"
                            >
                                {{ row.label }}
                            </dt>
                            <dd
                                :class="
                                    row.value
                                        ? 'text-sm font-medium text-slate-800 mt-0.5 break-words dark:text-white'
                                        : 'text-sm text-slate-300 mt-0.5 dark:text-gray-500'
                                "
                            >
                                {{ row.value || "Not provided" }}
                            </dd>
                        </div>
                    </div>
                </dl>
            </div>

            <div
                class="rounded-xl border border-slate-200 dark:border-white/10"
            >
                <div
                    class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 dark:border-white/10"
                >
                    <h3
                        class="text-sm font-semibold text-slate-800 dark:text-white"
                    >
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
                                    : 'text-sm text-slate-300 mt-0.5 dark:text-gray-500'
                            "
                        >
                            {{ row.value || "Not provided" }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div
                class="rounded-xl border border-slate-200 dark:border-white/10"
            >
                <div
                    class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 dark:border-white/10"
                >
                    <h3
                        class="text-sm font-semibold text-slate-800 dark:text-white"
                    >
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
                                    : 'text-sm text-slate-300 mt-0.5 dark:text-gray-500'
                            "
                        >
                            {{ row.value || "Not provided" }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div
                class="rounded-xl border border-slate-200 dark:border-white/10"
            >
                <div
                    class="flex items-center justify-between px-5 py-3.5 border-b border-slate-100 dark:border-white/10"
                >
                    <h3
                        class="text-sm font-semibold text-slate-800 dark:text-white"
                    >
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
                        v-if="!diagnosisGroups.length"
                        class="text-sm text-slate-400 dark:text-gray-500"
                    >
                        No diagnoses were provided.
                    </p>

                    <div v-else class="space-y-6">
                        <div
                            v-for="(rows, index) in diagnosisGroups"
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
                                <div v-for="row in rows" :key="row.label">
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

            <div
                class="rounded-xl border border-slate-200 dark:border-white/10"
            >
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
                        v-if="!assessmentRows.length && !lifeSystemRows.length"
                        class="text-sm text-slate-400 dark:text-gray-500"
                    >
                        No assessment details were provided.
                    </p>

                    <template v-else>
                        <dl
                            class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3"
                        >
                            <div v-for="row in assessmentRows" :key="row.label">
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
                            v-if="lifeSystemRows.length"
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
                                    v-for="row in lifeSystemRows"
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
import type { Patient, Guardian, Assessment, Diagnosis } from "~/types/patient";
import type { RoomContract, Reserved } from "~/types/contract";
import { CircleCheck } from "lucide-vue-next";
import { formatCurrency } from "~/utils/currency";
import { formatDate as formatDateUtil } from "~/utils/time";
import {
    LIFE_SYSTEM_ACTIVITIES,
    activityLabel,
    assessmentLabel,
    lifeSystemLabel,
} from "~/utils/assessment";
const props = defineProps<{
    reserved: Reserved;
    roomContract?: RoomContract[];
    patient: Patient;
    guardian: Guardian;
    assessment: Assessment | Assessment[];
    diagnoses?: Diagnosis[];
    payment?: any;
}>();

defineEmits<{
    (e: "edit-step", step: string): void;
}>();

type Row = { label: string; value: string; span?: boolean };

function formatDate(value?: string) {
    return value ? formatDateUtil(value) : "";
}

function fullName(parts: (string | undefined)[]) {
    return parts.filter(Boolean).join(" ");
}

const selectedContract = computed(() => {
    if (!props.reserved.contract_id) return null;
    return (
        props.roomContract?.find(
            (c) => c.contract_id === props.reserved.contract_id,
        ) ?? null
    );
});

const admissionRows = computed<Row[]>(() => {
    const contract = selectedContract.value;
    const room = props.reserved.room;
    const bed = props.reserved.bed;

    const rows: Row[] = [
        {
            label: "Accommodation Type",
            value: contract?.accommodation_type ?? "",
        },
        {
            label: "Room / Bed",
            value: `${room?.room_no ?? ""} / ${bed?.bed_no ?? ""}`,
        },
        {
            label: "Admission Date",
            value: formatDate(props.reserved.admitted_at) ?? "",
        },
        {
            label: "Billing Cycle",
            value:
                props.reserved.billing_cycle === "yearly"
                    ? "Yearly"
                    : "Monthly",
        },
    ];

    if (contract) {
        rows.push({
            label: "Total",
            value: formatCurrency(contract.price),
            span: true,
        });
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
        { label: "Gender", value: p.gender ?? "" },
        { label: "Date of Birth", value: formatDate(p.date_of_birth) },
        { label: "Phone Number", value: formatPhone(p.phone_number) },
        { label: "Address", value: p.address ?? "", span: true },
        { label: "Citizenship", value: p.citizenship ?? "" },
        { label: "Occupation", value: p.occupation ?? "" },
        { label: "Marital Status", value: p.marital_status ?? "" },
        { label: "Height / Weight", value: heightWeight },
        { label: "Blood Type", value: p.blood_type ?? "" },
    ];
});

const guardianRows = computed<Row[]>(() => {
    const g = props.guardian;
    return [
        {
            label: "Full Name",
            value: fullName([g.first_name, g.middle_name, g.last_name]),
        },
        { label: "Relationship to Patient", value: g.relationship ?? "" },
        { label: "Phone Number", value: formatPhone(g.phone_number) },
        { label: "Email", value: g.email ?? "" },
        { label: "Address", value: g.address ?? "", span: true },
        { label: "Occupation", value: g.occupation ?? "" },
    ];
});

// The booking store keeps a list, while a walk-in only ever fills one in.
const assessment = computed<Assessment>(() =>
    Array.isArray(props.assessment)
        ? (props.assessment[0] ?? {})
        : props.assessment,
);

// Diagnoses ride alongside the assessment in the booking store, but older
// walk-in drafts kept them inside it.
const diagnosisList = computed<Diagnosis[]>(() =>
    props.diagnoses?.length
        ? props.diagnoses
        : (assessment.value.diagnoses ?? []),
);

function getDiagnosisRows(entry: Diagnosis): Row[] {
    return [
        { label: "Primary Diagnosis", value: entry.diagnosis ?? "" },
        { label: "Date Diagnosed", value: formatDate(entry.diagnosis_date) },
        { label: "Diagnosis Notes", value: entry.diagnosis_notes ?? "" },
        {
            label: "Supporting Document",
            value: entry.diagnosis_file_name ?? "",
        },
    ].filter((row) => !!row.value);
}

// The form starts with a blank entry, so a diagnosis only counts once
// something has actually been filled in.
const diagnosisGroups = computed(() =>
    diagnosisList.value
        .map((entry) => getDiagnosisRows(entry))
        .filter((rows) => rows.length),
);

const assessmentRows = computed<Row[]>(() => {
    const a = assessment.value;

    return [
        { label: "Condition", value: assessmentLabel(a.condition) },
        {
            label: "Level of Consciousness",
            value: assessmentLabel(a.mental_state),
        },
        { label: "Affect", value: assessmentLabel(a.affect) },
        { label: "Behavior", value: assessmentLabel(a.behavior) },
        { label: "Communication Ability", value: a.communication ?? "" },
        { label: "Speech Pattern", value: assessmentLabel(a.speech) },
    ].filter((row) => !!row.value);
});

const lifeSystemRows = computed<Row[]>(() =>
    LIFE_SYSTEM_ACTIVITIES.map((activity) => ({
        label: activityLabel(activity),
        value: lifeSystemLabel(
            assessment.value.life_system_profile?.[activity],
        ),
    })).filter((row) => !!row.value),
);
</script>
