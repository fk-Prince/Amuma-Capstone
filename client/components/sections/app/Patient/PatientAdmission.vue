<template>
    <div class="min-h-screen bg-slate-50">
        <div class="w-full mx-auto px-4 lg:px-8 py-8">
            <div v-if="loading" class="space-y-6">
                <div
                    class="rounded-2xl border border-primary-100 bg-white p-6 animate-pulse"
                >
                    <div class="h-6 w-64 bg-slate-200 rounded"></div>
                    <div class="h-3 w-80 bg-slate-100 rounded mt-3"></div>
                </div>
                <div class="grid lg:grid-cols-3 gap-6">
                    <div
                        class="lg:col-span-2 rounded-2xl border border-primary-100 bg-white p-6 animate-pulse h-48"
                    ></div>
                    <div
                        class="rounded-2xl border border-primary-100 bg-white p-6 animate-pulse h-48"
                    ></div>
                </div>
            </div>

            <div
                v-else-if="!patient"    
                class="rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-400"
            >
                We couldn't find this patient's admission record.
            </div>

            <template v-else>
                <div class="grid lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-6">
                        <section>
                            <h2
                                class="text-[11px] uppercase tracking-wide text-muted font-semibold mb-2.5"
                            >
                                Current admission
                            </h2>

                            <button
                                v-if="
                                    latestAdmission &&
                                    ['admitted', 'waiting'].includes(
                                        latestAdmission?.status,
                                    )
                                "
                                type="button"
                                class="w-full text-left rounded-2xl bg-white border border-primary-100 shadow-[0_0_40px_rgba(10,40,87,0.06)] p-6 hover:bg-primary-50/40 transition"
                            >
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-primary-900"
                                        >
                                            {{
                                                latestAdmission.room?.room_no
                                                    ? `Room ${latestAdmission.room.room_no}`
                                                    : "No room assigned"
                                            }}
                                            <span
                                                v-if="
                                                    latestAdmission.bed?.bed_no
                                                "
                                                class="text-muted font-normal"
                                            >
                                                · Bed
                                                {{ latestAdmission.bed.bed_no }}
                                            </span>
                                        </p>
                                        <p class="text-xs text-muted mt-1">
                                            <span
                                                v-if="
                                                    latestAdmission.status ===
                                                    'waiting'
                                                "
                                            >
                                                Waiting to Admit
                                                {{
                                                    formatDate(
                                                        latestAdmission.admitted_at,
                                                    )
                                                }}
                                            </span>

                                            <span v-else>
                                                Admitted
                                                {{
                                                    formatDate(
                                                        latestAdmission.admitted_at,
                                                    )
                                                }}

                                                <span
                                                    v-if="
                                                        latestAdmission.end_date
                                                    "
                                                >
                                                    — Ends
                                                    {{
                                                        formatDate(
                                                            latestAdmission.end_date,
                                                        )
                                                    }}
                                                </span>
                                            </span>
                                        </p>
                                    </div>

                                    <div
                                        v-if="latestAdmission.current_contract"
                                        class="text-right shrink-0"
                                    >
                                        <p
                                            class="text-lg text-primary uppercase"
                                        >
                                            {{ latestAdmission.status }}
                                        </p>
                                        <p class="text-xs text-muted mt-0.5">
                                            {{
                                                latestAdmission.current_contract
                                                    .accommodation_type
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="totalStayDays !== null" class="mt-5">
                                    <div
                                        class="h-1.5 rounded-full bg-slate-100 overflow-hidden"
                                    >
                                        <div
                                            class="h-full rounded-full bg-primary transition-all duration-300"
                                            :style="{
                                                width: `${stayProgress ?? 0}%`,
                                            }"
                                        ></div>
                                    </div>
                                    <p class="text-[11px] text-muted mt-1.5">
                                        Day {{ dayOfStay ?? 0 }} of
                                        {{ totalStayDays }}
                                    </p>
                                </div>
                            </button>

                            <div
                                v-else
                                class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-400"
                            >
                                No active admission on record.
                            </div>
                        </section>

                        <AdmissionTimeline :admissions="patient?.admissions" />
                    </div>

                    <aside class="space-y-6">
                        <div
                            class="rounded-2xl bg-white border border-primary-100 shadow-[0_0_40px_rgba(10,40,87,0.06)] p-5"
                        >
                            <div class="grid grid-cols-2 gap-3">
                                <div
                                    class="rounded-xl bg-primary-50/60 border border-primary-100 p-4"
                                >
                                    <p
                                        class="text-[10px] uppercase tracking-wide text-muted font-semibold"
                                    >
                                        Current Stay
                                    </p>

                                    <p
                                        class="text-xl font-semibold text-primary-900 mt-1"
                                    >
                                        {{ dayOfStay ?? 0 }}
                                        <span
                                            class="text-xs font-medium text-muted"
                                        >
                                            day{{ dayOfStay === 1 ? "" : "s" }}
                                        </span>
                                    </p>

                                    <p class="text-[10px] text-muted mt-1">
                                        {{
                                            latestAdmission?.status ===
                                            "admitted"
                                                ? "Currently admitted"
                                                : "No active stay"
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl bg-slate-50 border border-slate-100 p-4"
                                >
                                    <p
                                        class="text-[10px] uppercase tracking-wide text-muted font-semibold"
                                    >
                                        Total Admissions
                                    </p>

                                    <p
                                        class="text-xl font-semibold text-primary-900 mt-1"
                                    >
                                        {{ patient.admissions?.length ?? 0 }}
                                    </p>

                                    <p class="text-[10px] text-muted mt-1">
                                        Lifetime admissions
                                    </p>
                                </div>
                            </div>
                        </div>

                        <section>
                            <h2
                                class="text-[11px] uppercase tracking-wide text-muted font-semibold mb-2.5"
                            >
                                Admission history
                                <span v-if="pastAdmissions.length">
                                    ({{ pastAdmissions.length }})
                                </span>
                            </h2>

                            <div
                                v-if="!pastAdmissions.length"
                                class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-400"
                            >
                                No previous admissions.
                            </div>

                            <div v-else class="space-y-4">
                                <div
                                    v-for="admission in pastAdmissions"
                                    :key="admission.patient_admission_id"
                                    class="rounded-2xl bg-white border border-primary-100 shadow-[0_0_40px_rgba(10,40,87,0.06)] p-6 hover:bg-primary-50/40 transition"
                                >
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                                    >
                                        <div>
                                            <p
                                                class="text-sm font-semibold text-primary-900"
                                            >
                                                {{
                                                    admission.room?.room_no
                                                        ? `Room ${admission.room.room_no}`
                                                        : "No room assigned"
                                                }}

                                                <span
                                                    v-if="admission.bed?.bed_no"
                                                    class="text-muted font-normal"
                                                >
                                                    · Bed
                                                    {{ admission.bed.bed_no }}
                                                </span>
                                            </p>

                                            <p class="text-xs text-muted mt-1">
                                                Admitted
                                                {{
                                                    formatDate(
                                                        admission.admitted_at,
                                                    )
                                                }}

                                                <span v-if="admission.end_date">
                                                    — Ended
                                                    {{
                                                        formatDate(
                                                            admission.end_date,
                                                        )
                                                    }}
                                                </span>
                                            </p>
                                        </div>

                                        <div
                                            class="flex items-center gap-3 shrink-0"
                                        >
                                            <div
                                                v-if="
                                                    admission.current_contract
                                                "
                                                class="text-right"
                                            >
                                                <p
                                                    class="text-xs text-muted mt-0.5"
                                                >
                                                    {{
                                                        admission
                                                            .current_contract
                                                            .accommodation_type
                                                    }}
                                                </p>
                                            </div>

                                            <span
                                                class="text-xs font-medium capitalize rounded-full px-2.5 py-1"
                                                :class="
                                                    statusBadgeClass(
                                                        admission.status,
                                                    )
                                                "
                                            >
                                                {{ admission.status }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </aside>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRouter } from "vue-router";
import type { PatientRetrieve, Admission } from "~/types/patient";

import AdmissionTimeline from "~/components/sections/app/Admission/AdmissionTimeline.vue";

definePageMeta({
    layout: "dashboard",
    middleware: ["auth-client"],
});

useHead({
    title: "Admission History",
});

const props = withDefaults(
    defineProps<{
        patient: PatientRetrieve | null;
        loading?: boolean;
    }>(),
    {
        patient: null,
        loading: false,
    },
);

const router = useRouter();

const patient = computed(() => props.patient);
const loading = computed(() => props.loading);

const latestAdmission = computed<Admission | undefined>(
    () => patient.value?.latest_admission,
);

const pastAdmissions = computed<Admission[]>(() => {
    const all = patient.value?.admissions ?? [];
    const latestId = latestAdmission.value?.patient_admission_id;
    const latestStatus = latestAdmission.value?.status;

    return all
        .filter((admission) => {
            if (admission.status !== "discharged") {
                return false;
            }

            if (admission.patient_admission_id === latestId) {
                return latestStatus === "discharged";
            }

            return true;
        })
        .sort(
            (a, b) =>
                new Date(b.admitted_at).getTime() -
                new Date(a.admitted_at).getTime(),
        );
});

const initials = computed(() => {
    const parts = (patient.value?.full_name ?? "").trim().split(/\s+/);

    return (
        (parts[0]?.[0] ?? "") + (parts[parts.length - 1]?.[0] ?? "")
    ).toUpperCase();
});

const status = computed(() => latestAdmission.value?.status?.toLowerCase());

const dayOfStay = computed(() => {
    if (!latestAdmission.value) return null;

    if (status.value !== "admitted" || !latestAdmission.value.admitted_at) {
        return 0;
    }

    const start = new Date(latestAdmission.value.admitted_at).getTime();

    if (Number.isNaN(start)) return 0;

    return Math.max(1, Math.ceil((Date.now() - start) / 86400000));
});

const totalStayDays = computed(() => {
    if (!latestAdmission.value?.end_date) {
        return null;
    }

    const start = new Date(latestAdmission.value.admitted_at).getTime();

    const end = new Date(latestAdmission.value.end_date).getTime();

    if (Number.isNaN(start) || Number.isNaN(end)) {
        return null;
    }

    return Math.max(1, Math.ceil((end - start) / 86400000));
});

const stayProgress = computed(() => {
    if (dayOfStay.value === null || !totalStayDays.value) {
        return null;
    }

    return Math.min(
        100,
        Math.round((dayOfStay.value / totalStayDays.value) * 100),
    );
});

function formatDate(value?: string | null) {
    if (!value) return "—";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

function formatCurrency(value?: string | number | null) {
    if (value === undefined || value === null || value === "") {
        return "—";
    }

    const amount = Number(value);

    if (Number.isNaN(amount)) {
        return String(value);
    }

    return amount.toLocaleString(undefined, {
        style: "currency",
        currency: "PHP",
    });
}

function statusBadgeClass(status?: string) {
    switch (status?.toLowerCase()) {
        case "admitted":
            return "bg-emerald-100 text-emerald-700";

        case "waiting":
            return "bg-blue-100 text-blue-700";

        case "discharged":
        case "completed":
            return "bg-slate-100 text-slate-600";

        case "cancelled":
        case "rejected":
            return "bg-rose-100 text-rose-700";

        default:
            return "bg-primary-50 text-primary-600";
    }
}
</script>
