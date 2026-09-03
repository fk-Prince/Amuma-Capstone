<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import PatientHeader from "~/components/sections/app/Patient/PatientHeader.vue";
import ActionMedicationModal from "~/components/sections/app/Patient/ActionMedicationModal.vue";
import MedicationTable from "~/components/sections/app/Patient/MedicationTable.vue";
import ActionVitalModal from "~/components/sections/app/Patient/ActionVitalModal.vue";
import VitalSignsTable from "~/components/sections/app/Patient/VitalSignsTable.vue";
import ActionPatientActivityModal from "~/components/sections/app/Patient/ActionPatientActivityModal.vue";
import PatientActivityTable from "~/components/sections/app/Patient/PatientActivityTable.vue";
import { useToast } from "~/composables/useToast";
import Overview from "~/components/sections/app/Patient/Overview.vue";
import PatientAssessment from "~/components/sections/app/Patient/PatientAssessment.vue";
import ServicePatient from "~/components/sections/app/Patient/ServicePatient.vue";
import AssignEmployeeModal from "~/components/sections/app/Patient/AssignEmployeeModal.vue";
import type { ScheduleItem } from "~/types/schedule";
import ScheduleDetails from "~/components/sections/app/Patient/ScheduleDetails.vue";
import HomecareADL from "~/components/sections/app/Patient/HomecareADL.vue";
import PatientPrintModal from "~/components/sections/app/Patient/PatientPrintModal.vue";
import SchedulePatient from "~/components/sections/app/Patient/SchedulePatient.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import Pagination from "~/components/ui/Pagination.vue";
import PatientAdmission from "~/components/sections/app/Patient/PatientAdmission.vue";
import {
    ChevronRight,
    ArrowLeft,
    Stethoscope,
    HeartPulse,
} from "lucide-vue-next";
import { type ConflictItem } from "~/types/schedule";
import type {
    Medication,
    MedicationForm,
    MarkDosePayload,
    Vital,
    VitalFormData,
} from "~/types/medication";
import type {
    PatientActivity,
    PatientActivityForm,
} from "~/types/patient-activity";

useHead({ title: "Patient Information" });

const { success, error } = useToast();

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

const route = useRoute();
const router = useRouter();

const {
    patientData,
    serviceData,
    scheduleData,
    employeeData,
    loading,
    loadingSecond,
    medications,
    vitals,
    patientActivities,
    medicationsMeta,
    vitalsMeta,
    patientActivitiesMeta,
    isFetchingMedications,
    isFetchingVitals,
    isFetchingPatientActivities,
    fetchData,
    fetchMedications,
    fetchVitals,
    fetchPatientActivities,
    fetchEmployee,
    handleVitalAction,
    handleMedicationAction,
    handleDosageAction,
    handlePatientActivityAction,
    handleScheduleAction,
    handleAssignment,
    updateSchedule,
    fetchSchedules,
} = usePatient();

const uuid = computed(() => route.params.p_uuid as string);
const b_uuid = computed(() => route.params.uuid as string);

function goBack() {
    if (window.history.state?.back) {
        router.back();
        return;
    }

    router.push(`/app/branches/${b_uuid.value}/patients`);
}

const tabs = [
    "Overview",
    "Diagnosis & Assessment",
    "Admission",
    "Schedule",
    "Service",
    "Medication",
    "Vital Signs",
    "Activity",
] as const;
type Tab = (typeof tabs)[number];

const tabSlugMap: Record<Tab, string> = {
    Overview: "overview",
    Assessment: "assessment",
    Schedule: "schedule",
    Service: "service",
    Medication: "medication",
    "Vital Signs": "vitals",
    Admission: "admissions",
    Activity: "activity",
};

const slugToTab: Record<string, Tab> = Object.fromEntries(
    Object.entries(tabSlugMap).map(([tab, slug]) => [slug, tab]),
) as Record<string, Tab>;

function resolveTabFromQuery(): Tab {
    const q = route.query.tab;
    const slug = Array.isArray(q) ? q[0] : q;
    if (slug && slugToTab[slug]) {
        return slugToTab[slug];
    }
    return "Overview";
}

const activeTab = ref<Tab>(resolveTabFromQuery());

function setActiveTab(tab: Tab) {
    activeTab.value = tab;
    router.replace({
        query: { ...route.query, tab: tabSlugMap[tab] },
    });
}

const showAddMedication = ref(false);
const savingMedication = ref(false);
const showRecordVital = ref(false);
const showAddActivity = ref(false);
const showScheduleModal = ref(false);
const showPrintModal = ref(false);

const savingVital = ref(false);
const savingActivity = ref(false);
const savingSchedule = ref(false);
const savingDosage = ref(false);
const savingAssignment = ref(false);
const updatingAssignment = ref(false);

const isFetchingEmployee = ref(false);
const isFetchingSchedule = ref(loadingSecond.value);

const selectedVital = ref<Vital | null>(null);
const selectedActivity = ref<PatientActivity | null>(null);
const selectedSchedule = ref<ScheduleItem | null>(null);
const selectedMedication = ref<Medication | null>(null);

const assignModalOpen = ref(false);
const assigneSchedule = ref<ScheduleItem>();
const scheduleType = ref<"medical" | "homecare">("medical");

const SCHEDULE_TYPES = [
    { value: "medical", label: "Medical Service", icon: Stethoscope },
    {
        value: "homecare",
        label: "Activities of Daily Living (ADL)",
        icon: HeartPulse,
    },
] as const;


const yesterdayStr = new Date(Date.now() - 86400000).toISOString().slice(0, 10);
const nextWeekStr = new Date(Date.now() + 7 * 86400000)
    .toISOString()
    .slice(0, 10);

const scheduleFrom = ref(yesterdayStr);
const scheduleTo = ref(nextWeekStr);

// A scheduling conflict is never allowed to proceed — there's no
// "assign anyway" override, so a conflict just surfaces as an error
// instead of a confirmation the user could push past.
function describeConflicts(conflicts: ConflictItem[]): string {
    if (conflicts.length === 1) {
        const c = conflicts[0];
        const codes = c.conflict_schedule_codes.join(", ");
        return `${c.employee_name} has a scheduling conflict with ${codes}.`;
    }

    return conflicts
        .map((c) => {
            const codes = c.conflict_schedule_codes.join(", ");
            const service = c.service_name ? ` (${c.service_name})` : "";
            return `${c.employee_name}${service} — conflicts with ${codes}`;
        })
        .join("\n");
}

function vitalAction(vital: Vital) {
    selectedVital.value = vital;
    showRecordVital.value = true;
}

function activityAction(activity: PatientActivity) {
    selectedActivity.value = activity;
    showAddActivity.value = true;
}

async function onActivitySubmit(
    action: "create" | "update",
    payload: PatientActivityForm,
    id?: string,
) {
    savingActivity.value = true;
    try {
        const res: any = await handlePatientActivityAction(
            action,
            payload,
            uuid.value,
            id,
        );
        success(res.message);
        showAddActivity.value = false;
        selectedActivity.value = null;
    } catch (err: any) {
        error(err.error);
    } finally {
        savingActivity.value = false;
    }
}

async function onVitalSubmit(
    action: "create" | "update",
    payload: VitalFormData,
    id?: string,
) {
    savingVital.value = true;
    try {
        const res: any = await handleVitalAction(
            action,
            payload,
            uuid.value,
            id,
        );
        success(res.message);
        showRecordVital.value = false;
        selectedVital.value = null;
    } catch (err: any) {
        error(err.error);
    } finally {
        savingVital.value = false;
    }
}

async function onMedicationSubmit(
    action: "create" | "update",
    payload: MedicationForm,
    id?: string,
) {
    savingMedication.value = true;
    try {
        const res = await handleMedicationAction(
            action,
            payload,
            uuid.value,
            id,
        );
        success(res.message);
        showAddMedication.value = false;
        selectedMedication.value = null;
    } catch (err: any) {
        error(err.error);
    } finally {
        savingMedication.value = false;
    }
}

async function onDosageSubmit(payload: MarkDosePayload) {
    savingDosage.value = true;
    try {
        const res = await handleDosageAction(payload, uuid.value);
        success(res.message);
    } catch (err: any) {
        error(err.error);
    } finally {
        savingDosage.value = false;
    }
}

async function onScheduleSubmit(payload: any) {
    savingSchedule.value = true;
    try {
        const res = await handleScheduleAction(
            payload,
            uuid.value,
            b_uuid.value,
        );
        success(res.message);
    } catch (err: any) {
        error(err.error);
    } finally {
        savingSchedule.value = false;
    }
}

function viewSchedule(s: ScheduleItem) {
    selectedSchedule.value = s;
    handleAssign(s, false);
    showScheduleModal.value = true;
}

async function handleAssign(s: ScheduleItem, isModal = true) {
    if (isModal) {
        assigneSchedule.value = s;
        assignModalOpen.value = true;
    }
    isFetchingEmployee.value = true;
    try {
        await fetchEmployee(b_uuid.value, s.schedule_id);
    } catch (err: any) {
        error(err.error);
    } finally {
        isFetchingEmployee.value = false;
    }
}

function updateScheduleInList(updated: ScheduleItem | undefined) {
    if (!updated?.schedule_id) return;
    const index = scheduleData.value.findIndex(
        (s: ScheduleItem) => s.schedule_id === updated.schedule_id,
    );
    if (index !== -1) {
        scheduleData.value[index] = updated;
    }
}

async function runAssignment(payload: any) {
    savingAssignment.value = true;
    try {
        const res: any = await handleAssignment(payload, b_uuid.value);

        if (res?.has_conflicts) {
            error(describeConflicts(res.conflicts ?? []));
            return;
        }

        updateScheduleInList(res?.data);
        success(res.message);
        assignModalOpen.value = false;
    } catch (err: any) {
        error(err.error);
    } finally {
        savingAssignment.value = false;
    }
}

function onAssignSubmit(payload: any) {
    return runAssignment(payload);
}

async function runScheduleUpdate(payload: any) {
    updatingAssignment.value = true;
    try {
        const res: any = await updateSchedule(payload, b_uuid.value);

        if (res?.has_conflicts) {
            error(describeConflicts(res.conflicts ?? []));
            return;
        }

        updateScheduleInList(res?.data);
        success(res.message);
        showScheduleModal.value = false;
    } catch (err: any) {
        error(err.error ?? err.message);
    } finally {
        updatingAssignment.value = false;
    }
}

function onUpdateSchedule(payload: any) {
    return runScheduleUpdate(payload);
}

const filteredScheduleData = computed(() => {
    if (!Array.isArray(scheduleData.value)) return [];

    return scheduleData.value
        .map((schedule: ScheduleItem) => {
            const services = (schedule.services ?? []).filter(
                (service: any) => {
                    if (scheduleType.value === "homecare") {
                        return service.hours_booked != null;
                    }
                    return service.service_id != null;
                },
            );

            return { ...schedule, services };
        })
        .filter((schedule) => schedule.services.length > 0);
});

const visibleTabs = computed(() => {
    if (!patientData.value?.latest_admission) {
        return tabs.filter((tab) => tab !== "Service");
    }

    return tabs;
});

function resetSchedule(s: ScheduleItem[]) {
    scheduleData.value = s;
}

async function refreshSchedule() {
    isFetchingSchedule.value = true;
    try {
        await fetchSchedules(
            uuid.value,
            b_uuid.value,
            scheduleFrom.value,
            scheduleTo.value,
        );
    } finally {
        isFetchingSchedule.value = false;
    }
}

async function loadSchedulesIfNeeded(from: string, to: string, tab: Tab) {
    if (tab !== "Schedule") return;
    isFetchingSchedule.value = true;
    try {
        await fetchSchedules(uuid.value, b_uuid.value, from, to);
    } finally {
        isFetchingSchedule.value = false;
    }
}

// Medications/vitals are only pulled once their tab is actually opened,
// not on every patient page visit.
async function loadMedicationsIfNeeded(tab: Tab, page = 1) {
    if (tab !== "Medication") return;
    await fetchMedications(uuid.value, page);
}

async function loadVitalsIfNeeded(tab: Tab, page = 1) {
    if (tab !== "Vital Signs") return;
    await fetchVitals(uuid.value, page);
}

async function loadPatientActivitiesIfNeeded(tab: Tab, page = 1) {
    if (tab !== "Activity") return;
    await fetchPatientActivities(uuid.value, page);
}

function onMedicationsPageChange(page: number) {
    fetchMedications(uuid.value, page);
}

function onVitalsPageChange(page: number) {
    fetchVitals(uuid.value, page);
}

function onPatientActivitiesPageChange(page: number) {
    fetchPatientActivities(uuid.value, page);
}

watch([scheduleFrom, scheduleTo, activeTab], ([from, to, tab]) => {
    loadSchedulesIfNeeded(from, to, tab);
    loadMedicationsIfNeeded(tab);
    loadVitalsIfNeeded(tab);
    loadPatientActivitiesIfNeeded(tab);
});
onMounted(async () => {
    await fetchData(uuid.value, b_uuid.value);

    if (route.query.tab !== tabSlugMap[activeTab.value]) {
        router.replace({
            query: { ...route.query, tab: tabSlugMap[activeTab.value] },
        });
    }

    await loadSchedulesIfNeeded(
        scheduleFrom.value,
        scheduleTo.value,
        activeTab.value,
    );
    await loadMedicationsIfNeeded(activeTab.value);
    await loadVitalsIfNeeded(activeTab.value);
    await loadPatientActivitiesIfNeeded(activeTab.value);
});

// onMounted(() => {
//     fetchData(uuid.value, b_uuid.value);

//     if (route.query.tab !== tabSlugMap[activeTab.value]) {
//         router.replace({
//             query: { ...route.query, tab: tabSlugMap[activeTab.value] },
//         });
//     }
// });

// watch(
//     [scheduleFrom, scheduleTo, activeTab],
//     async ([from, to, tab]) => {
//         if (tab !== "Schedule") return;
//         isFetchingSchedule.value = true;
//         try {
//             await fetchSchedules(uuid.value, b_uuid.value, from, to);
//         } finally {
//             isFetchingSchedule.value = false;
//         }
//     },
//     { immediate: true },
// );
</script>
<template>
    <div class="min-h-screen-header bg-gray-50 p-4 sm:p-6 dark:bg-surface">
        <div class="w-full min-w-0 space-y-4">
            <button
                type="button"
                class="flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-800 dark:text-gray-400 dark:hover:text-white"
                @click="goBack"
            >
                <ArrowLeft class="h-4 w-4" />
                Back
            </button>

            <div v-if="loading" class="space-y-4 animate-pulse">
                <div
                    class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-secondary"
                >
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 rounded-full bg-gray-200 dark:bg-white/15" />

                        <div class="space-y-3">
                            <div class="h-5 w-48 rounded bg-gray-200 dark:bg-white/15" />

                            <div class="flex gap-3">
                                <div class="h-3 w-28 rounded bg-gray-200 dark:bg-white/15" />
                                <div class="h-3 w-20 rounded bg-gray-200 dark:bg-white/15" />
                                <div class="h-3 w-32 rounded bg-gray-200 dark:bg-white/15" />
                            </div>

                            <div class="h-3 w-60 rounded bg-gray-200 dark:bg-white/15" />
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-gray-100 bg-white px-5 py-4 shadow-sm dark:border-white/10 dark:bg-secondary"
                >
                    <div class="flex gap-8">
                        <div
                            v-for="i in 4"
                            :key="i"
                            class="h-4 w-20 rounded bg-gray-200 dark:bg-white/15"
                        />
                    </div>
                </div>

                <div
                    class="flex justify-between rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-secondary"
                >
                    <div class="h-10 w-48 rounded-xl bg-gray-200 dark:bg-white/15" />

                    <div class="h-10 w-40 rounded-xl bg-gray-200 dark:bg-white/15" />
                </div>

                <div
                    v-for="i in 2"
                    :key="i"
                    class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-secondary"
                >
                    <div class="space-y-5">
                        <div class="h-5 w-44 rounded bg-gray-200 dark:bg-white/15" />

                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <div v-for="j in 4" :key="j" class="space-y-2">
                                <div class="h-3 w-20 rounded bg-gray-200 dark:bg-white/15" />
                                <div class="h-4 rounded bg-gray-200 dark:bg-white/15" />
                            </div>
                        </div>

                        <div class="grid grid-cols-10 gap-2">
                            <div
                                v-for="j in 20"
                                :key="j"
                                class="h-7 rounded-full bg-gray-200 dark:bg-white/15"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <template v-else>
                <PatientHeader
                    v-if="patientData"
                    :patient="patientData"
                    @print="showPrintModal = true"
                />

                <div
                    class="min-w-0 max-w-full overflow-hidden rounded-2xl border space-y-4 border-gray-100 bg-white px-3 sm:px-5 shadow-sm dark:border-white/10 dark:bg-secondary"
                >
                    <div class="min-w-0 overflow-x-auto scrollbar-none">
                        <nav class="flex w-max gap-4 sm:gap-7">
                            <button
                                v-for="tab in visibleTabs"
                                :key="tab"
                                class="relative shrink-0 whitespace-nowrap py-4 text-sm font-medium"
                                :class="
                                    activeTab === tab
                                        ? 'text-primary'
                                        : 'text-gray-500 dark:text-gray-400'
                                "
                                @click="setActiveTab(tab)"
                            >
                                {{ tab }}

                                <span
                                    v-if="activeTab === tab"
                                    class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary"
                                />
                            </button>
                        </nav>
                    </div>

                    <template v-if="activeTab === 'Medication'">
                        <p
                            v-if="isFetchingMedications"
                            class="py-8 text-center text-sm text-gray-400 dark:text-gray-500"
                        >
                            Loading medications...
                        </p>

                        <template v-else>
                            <MedicationTable
                                :medications="medications"
                                :saving-dose="savingDosage"
                                @add-medication="
                                    selectedMedication = null;
                                    showAddMedication = true;
                                "
                                @mark-dose="onDosageSubmit"
                            />

                            <Pagination
                                v-if="
                                    medicationsMeta && medicationsMeta.total > 0
                                "
                                :current-page="medicationsMeta.current_page"
                                :total-pages="medicationsMeta.last_page"
                                :total-items="medicationsMeta.total"
                                :items-per-page="medicationsMeta.per_page"
                                class="pb-5"
                                @change-page="onMedicationsPageChange"
                            />
                        </template>
                    </template>

                    <template v-if="activeTab === 'Vital Signs'">
                        <p
                            v-if="isFetchingVitals"
                            class="py-8 text-center text-sm text-gray-400 dark:text-gray-500"
                        >
                            Loading vital signs...
                        </p>

                        <template v-else>
                            <VitalSignsTable
                                :vitals="vitals"
                                @add-vital="
                                    selectedVital = null;
                                    showRecordVital = true;
                                "
                                @edit-vital="vitalAction"
                            />

                            <Pagination
                                v-if="vitalsMeta && vitalsMeta.total > 0"
                                :current-page="vitalsMeta.current_page"
                                :total-pages="vitalsMeta.last_page"
                                :total-items="vitalsMeta.total"
                                :items-per-page="vitalsMeta.per_page"
                                class="pb-5"
                                @change-page="onVitalsPageChange"
                            />
                        </template>
                    </template>

                    <template v-if="activeTab === 'Activity'">
                        <p
                            v-if="isFetchingPatientActivities"
                            class="py-8 text-center text-sm text-gray-400 dark:text-gray-500"
                        >
                            Loading activities...
                        </p>

                        <template v-else>
                            <PatientActivityTable
                                :activities="patientActivities"
                                @add-activity="
                                    selectedActivity = null;
                                    showAddActivity = true;
                                "
                                @edit-activity="activityAction"
                            />

                            <Pagination
                                v-if="
                                    patientActivitiesMeta &&
                                    patientActivitiesMeta.total > 0
                                "
                                :current-page="
                                    patientActivitiesMeta.current_page
                                "
                                :total-pages="patientActivitiesMeta.last_page"
                                :total-items="patientActivitiesMeta.total"
                                :items-per-page="patientActivitiesMeta.per_page"
                                class="pb-5"
                                @change-page="onPatientActivitiesPageChange"
                            />
                        </template>
                    </template>

                    <Overview
                        v-if="activeTab === 'Overview' && patientData"
                        :patient="patientData"
                    />
                    <PatientAssessment
                        v-if="activeTab === 'Diagnosis & Assessment' && patientData"
                        :patient="patientData"
                    />
                    <PatientAdmission
                        v-if="activeTab === 'Admission' && patientData"
                        :patient="patientData"
                    />
                    <ServicePatient
                        v-if="activeTab === 'Service' && patientData"
                        :patient="patientData"
                        :submitLoading="savingSchedule"
                        :services="serviceData"
                        @schedule="onScheduleSubmit"
                    />

                    <div v-if="activeTab === 'Schedule'">
                        <div class="mb-5 rounded-2xl bg-white p-4 dark:bg-secondary">
                            <div
                                class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
                            >
                                <div
                                    role="tablist"
                                    aria-label="Schedule type"
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-1 rounded-2xl bg-slate-100/80 p-1 xl:w-[28rem] dark:bg-white/10"
                                >
                                    <button
                                        v-for="type in SCHEDULE_TYPES"
                                        :key="type.value"
                                        type="button"
                                        role="tab"
                                        :aria-selected="
                                            scheduleType === type.value
                                        "
                                        class="group flex items-center justify-center gap-2 rounded-xl px-3 py-2 transition-all"
                                        :class="
                                            scheduleType === type.value
                                                ? 'bg-white shadow-sm ring-1 ring-primary/20 dark:bg-secondary'
                                                : 'hover:bg-white/70'
                                        "
                                        @click="scheduleType = type.value"
                                    >
                                        <span
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg transition"
                                            :class="
                                                scheduleType === type.value
                                                    ? 'bg-primary text-white shadow-sm'
                                                    : 'bg-white text-slate-400 group-hover:text-primary dark:bg-secondary dark:text-gray-500'
                                            "
                                        >
                                            <component
                                                :is="type.icon"
                                                class="h-3.5 w-3.5"
                                            />
                                        </span>

                                        <span
                                            class="truncate text-xs font-semibold"
                                            :class="
                                                scheduleType === type.value
                                                    ? 'text-primary'
                                                    : 'text-slate-600 dark:text-gray-400'
                                            "
                                        >
                                            {{ type.label }}
                                        </span>
                                    </button>
                                </div>

                                <div class="flex items-center gap-2">
                                    <BaseInput
                                        v-model="scheduleFrom"
                                        mode="date"
                                        class-name="w-full sm:max-w-[170px]"
                                        box-class="ring-1 ring-slate-200 dark:ring-white/10"
                                    />

                                    <ChevronRight
                                        class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500"
                                    />

                                    <BaseInput
                                        v-model="scheduleTo"
                                        mode="date"
                                        class-name="w-full sm:max-w-[170px]"
                                        box-class="ring-1 ring-slate-200 dark:ring-white/10"
                                    />
                                </div>
                            </div>

                        </div>

                        <div class="mt-5">
                            <SchedulePatient
                                v-if="
                                    activeTab === 'Schedule' &&
                                    scheduleType === 'medical'
                                "
                                :schedules="filteredScheduleData"
                                :date="scheduleFrom"
                                :range-end="scheduleTo"
                                @view-details="viewSchedule"
                                @assign="handleAssign"
                                :loading="isFetchingSchedule"
                            />
                            <HomecareADL
                                v-if="
                                    activeTab === 'Schedule' &&
                                    scheduleType === 'homecare'
                                "
                                :logs="filteredScheduleData"
                                :date="scheduleFrom"
                                :range-end="scheduleTo"
                                :loading="isFetchingSchedule"
                                @update="resetSchedule"
                                @refresh="refreshSchedule"
                                @view-details="viewSchedule"
                            />
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <PatientPrintModal
            :open="showPrintModal"
            :patient-uuid="uuid"
            :branch-uuid="b_uuid"
            @close="showPrintModal = false"
        />

        <AssignEmployeeModal
            :open="assignModalOpen"
            :schedule="assigneSchedule"
            :employees="employeeData"
            :isFetching="isFetchingEmployee"
            :isSaving="savingAssignment"
            @close="assignModalOpen = false"
            @confirm="onAssignSubmit"
        />

        <ActionMedicationModal
            v-if="patientData"
            :open="showAddMedication"
            :patient="patientData"
            :submit-loading="savingMedication"
            @close="showAddMedication = false"
            @submit="onMedicationSubmit"
        />

        <ScheduleDetails
            :open="showScheduleModal"
            :schedule="selectedSchedule"
            :employees="employeeData"
            :is-fetching-employees="isFetchingEmployee"
            @schedule="onUpdateSchedule"
            :submit-loading="updatingAssignment"
            @close="showScheduleModal = false"
        />

        <ActionVitalModal
            v-if="patientData"
            :patient="patientData"
            :open="showRecordVital"
            :submitLoading="savingVital"
            :vital="selectedVital"
            @close="showRecordVital = false"
            @submit="onVitalSubmit"
        />

        <ActionPatientActivityModal
            v-if="patientData"
            :patient="patientData"
            :open="showAddActivity"
            :submit-loading="savingActivity"
            :activity="selectedActivity"
            @close="showAddActivity = false"
            @submit="onActivitySubmit"
        />
    </div>
</template>
