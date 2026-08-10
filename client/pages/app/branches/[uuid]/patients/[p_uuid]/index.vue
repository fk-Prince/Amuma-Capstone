<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import PatientHeader from "~/components/sections/app/Patient/PatientHeader.vue";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import ActionMedicationModal from "~/components/sections/app/Patient/ActionMedicationModal.vue";
import MedicationTable from "~/components/sections/app/Patient/MedicationTable.vue";
import ActionVitalModal from "~/components/sections/app/Patient/ActionVitalModal.vue";
import VitalSignsTable from "~/components/sections/app/Patient/VitalSignsTable.vue";
import { useToast } from "~/composables/useToast";
import Overview from "~/components/sections/app/Patient/Overview.vue";
import ServicePatient from "~/components/sections/app/Patient/ServicePatient.vue";
import AssignEmployeeModal from "~/components/sections/app/Patient/AssignEmployeeModal.vue";
import type { ScheduleItem } from "~/types/schedule";
import ScheduleDetails from "~/components/sections/app/Patient/ScheduleDetails.vue";
import HomecareADL from "~/components/sections/app/Patient/HomecareADL.vue";
import SchedulePatient from "~/components/sections/app/Patient/SchedulePatient.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import { ChevronRight, ArrowLeft } from "lucide-vue-next";
import {
    type ConflictItem,
    conflictConfirm,
    type ConflictSource,
} from "~/types/schedule";
import type {
    Medication,
    MedicationForm,
    MarkDosePayload,
    Vital,
    VitalFormData,
} from "~/types/medication";

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
    fetchData,
    fetchEmployee,
    handleVitalAction,
    handleMedicationAction,
    handleDosageAction,
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
    "Schedule",
    "Service",
    "Medication",
    "Vital Signs",
] as const;
type Tab = (typeof tabs)[number];

const tabSlugMap: Record<Tab, string> = {
    Overview: "overview",
    Schedule: "schedule",
    Service: "service",
    Medication: "medication",
    "Vital Signs": "vitals",
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

onMounted(() => {
    fetchData(uuid.value, b_uuid.value);

    if (route.query.tab !== tabSlugMap[activeTab.value]) {
        router.replace({
            query: { ...route.query, tab: tabSlugMap[activeTab.value] },
        });
    }
});

const showAddMedication = ref(false);
const savingMedication = ref(false);
const showRecordVital = ref(false);
const showScheduleModal = ref(false);

const savingVital = ref(false);
const savingSchedule = ref(false);
const savingDosage = ref(false);
const savingAssignment = ref(false);
const updatingAssignment = ref(false);

const isFetchingEmployee = ref(false);
const isFetchingSchedule = ref(loadingSecond.value);

const selectedVital = ref<Vital | null>(null);
const selectedSchedule = ref<ScheduleItem | null>(null);
const selectedMedication = ref<Medication | null>(null);

const assignModalOpen = ref(false);
const assigneSchedule = ref<ScheduleItem>();
const scheduleType = ref<"medical" | "homecare">("medical");

const todayStr = new Date().toISOString().slice(0, 10);
const scheduleFrom = ref(todayStr);
const scheduleTo = ref(todayStr);

function jumpToScheduleToday() {
    scheduleFrom.value = todayStr;
    scheduleTo.value = todayStr;
}

watch(
    [scheduleFrom, scheduleTo, activeTab],
    async ([from, to, tab]) => {
        if (tab !== "Schedule") return;
        isFetchingSchedule.value = true;
        try {
            await fetchSchedules(uuid.value, b_uuid.value, from, to);
        } finally {
            isFetchingSchedule.value = false;
        }
    },
    { immediate: true },
);

const conflictMessage = computed(() => {
    const count = conflictConfirm.value.conflicts.length;
    if (count === 1) {
        return `${conflictConfirm.value.conflicts[0]?.employee_name} has a scheduling conflict.`;
    }
    return `${count} employees have scheduling conflicts.`;
});

const conflictDescription = computed(() =>
    conflictConfirm.value.conflicts
        .map((c) => {
            const codes = c.conflict_schedule_codes.join(", ");
            const service = c.service_name ? ` (${c.service_name})` : "";
            return `${c.employee_name}${service} — conflicts with ${codes}`;
        })
        .join("\n"),
);

function openConflictConfirm(
    source: ConflictSource,
    conflicts: ConflictItem[],
    pendingPayload: any,
) {
    conflictConfirm.value = { open: true, source, conflicts, pendingPayload };
}

function resetConflictConfirm() {
    conflictConfirm.value = {
        open: false,
        source: null,
        conflicts: [],
        pendingPayload: null,
    };
}

function cancelConflictOverride() {
    resetConflictConfirm();
}

async function confirmConflictOverride() {
    const { source, pendingPayload } = conflictConfirm.value;
    if (!source || !pendingPayload) return;

    const overridePayload = { ...pendingPayload, confirm_conflicts: true };

    if (source === "assignment") {
        await runAssignment(overridePayload);
    } else {
        await runScheduleUpdate(overridePayload);
    }
}

function vitalAction(vital: Vital) {
    selectedVital.value = vital;
    showRecordVital.value = true;
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

function reloadSchedule(updated: ScheduleItem | undefined) {
    updateScheduleInList(updated);
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
            openConflictConfirm("assignment", res.conflicts ?? [], payload);
            return;
        }

        updateScheduleInList(res?.data);
        success(res.message);
        assignModalOpen.value = false;
        resetConflictConfirm();
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
            openConflictConfirm("schedule", res.conflicts ?? [], payload);
            return;
        }

        updateScheduleInList(res?.data);
        success(res.message);
        showScheduleModal.value = false;
        resetConflictConfirm();
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
    return scheduleData.value.filter((schedule: ScheduleItem) => {
        const type = schedule.type?.toLowerCase();
        return scheduleType.value === "homecare"
            ? type === "adl"
            : type === "medical";
    });
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
</script>
<template>
    <div class="min-h-screen bg-gray-50 p-6">
        <div class="m-full space-y-4">
            <button
                type="button"
                class="flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-800"
                @click="goBack"
            >
                <ArrowLeft class="h-4 w-4" />
                Back
            </button>

            <div v-if="loading" class="space-y-4 animate-pulse">
                <div
                    class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 rounded-full bg-gray-200" />

                        <div class="space-y-3">
                            <div class="h-5 w-48 rounded bg-gray-200" />

                            <div class="flex gap-3">
                                <div class="h-3 w-28 rounded bg-gray-200" />
                                <div class="h-3 w-20 rounded bg-gray-200" />
                                <div class="h-3 w-32 rounded bg-gray-200" />
                            </div>

                            <div class="h-3 w-60 rounded bg-gray-200" />
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-gray-100 bg-white px-5 py-4 shadow-sm"
                >
                    <div class="flex gap-8">
                        <div
                            v-for="i in 4"
                            :key="i"
                            class="h-4 w-20 rounded bg-gray-200"
                        />
                    </div>
                </div>

                <div
                    class="flex justify-between rounded-2xl border border-gray-100 bg-white p-4 shadow-sm"
                >
                    <div class="h-10 w-48 rounded-xl bg-gray-200" />

                    <div class="h-10 w-40 rounded-xl bg-gray-200" />
                </div>

                <div
                    v-for="i in 2"
                    :key="i"
                    class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm"
                >
                    <div class="space-y-5">
                        <div class="h-5 w-44 rounded bg-gray-200" />

                        <div class="grid grid-cols-4 gap-4">
                            <div v-for="j in 4" :key="j" class="space-y-2">
                                <div class="h-3 w-20 rounded bg-gray-200" />
                                <div class="h-4 rounded bg-gray-200" />
                            </div>
                        </div>

                        <div class="grid grid-cols-10 gap-2">
                            <div
                                v-for="j in 20"
                                :key="j"
                                class="h-7 rounded-full bg-gray-200"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <template v-else>
                <PatientHeader v-if="patientData" :patient="patientData" />

                <div
                    class="rounded-2xl border space-y-4 border-gray-100 bg-white px-5 shadow-sm"
                >
                    <nav class="flex gap-7">
                        <button
                            v-for="tab in visibleTabs"
                            :key="tab"
                            class="relative py-4 text-sm font-medium"
                            :class="
                                activeTab === tab
                                    ? 'text-primary'
                                    : 'text-gray-500'
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

                    <MedicationTable
                        v-if="activeTab === 'Medication'"
                        :medications="medications"
                        :saving-dose="savingDosage"
                        @add-medication="
                            selectedMedication = null;
                            showAddMedication = true;
                        "
                        @mark-dose="onDosageSubmit"
                    />

                    <VitalSignsTable
                        v-if="activeTab === 'Vital Signs'"
                        :vitals="vitals"
                        @add-vital="
                            selectedVital = null;
                            showRecordVital = true;
                        "
                        @edit-vital="vitalAction"
                    />

                    <Overview
                        v-if="activeTab === 'Overview' && patientData"
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
                        <div
                            class="flex flex-col items-center gap-4 rounded-2xl bg-white p-4 sm:flex-row sm:items-end sm:justify-between mb-5"
                        >
                            <div
                                class="flex flex-col items-center gap-3 sm:flex-row sm:gap-4"
                            >
                                <BaseInput
                                    v-model="scheduleFrom"
                                    mode="date"
                                    label="From"
                                    class-name="w-full sm:max-w-[180px]"
                                />

                                <div
                                    class="mt-6 hidden h-10 w-10 items-center justify-center text-slate-500 sm:flex"
                                >
                                    <ChevronRight class="h-5 w-5" />
                                </div>

                                <BaseInput
                                    v-model="scheduleTo"
                                    mode="date"
                                    label="To"
                                    class-name="w-full sm:max-w-[180px]"
                                />

                                <button
                                    type="button"
                                    class="h-11 self-end rounded-lg bg-primary/80 px-10 text-sm font-medium uppercase text-white transition hover:bg-primary"
                                    @click="jumpToScheduleToday"
                                >
                                    Today
                                </button>
                            </div>
                        </div>

                        <div class="inline-flex rounded-xl bg-slate-100 p-1">
                            <button
                                type="button"
                                class="rounded-lg px-5 py-2 text-sm font-semibold transition"
                                :class="
                                    scheduleType === 'medical'
                                        ? 'bg-primary text-white shadow-sm'
                                        : 'text-slate-600 hover:text-slate-800'
                                "
                                @click="scheduleType = 'medical'"
                            >
                                Medical Schedule
                            </button>

                            <button
                                type="button"
                                class="rounded-lg px-5 py-2 text-sm font-semibold transition"
                                :class="
                                    scheduleType === 'homecare'
                                        ? 'bg-primary text-white shadow-sm'
                                        : 'text-slate-600 hover:text-slate-800'
                                "
                                @click="scheduleType = 'homecare'"
                            >
                                Homecare Schedule (ADL)
                            </button>
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
                            />
                        </div>
                    </div>
                </div>
            </template>
        </div>

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

        <ConfirmDialog
            :open="conflictConfirm.open"
            title="Assign employee with conflict?"
            :message="conflictMessage"
            :description="conflictDescription"
            confirm-label="Assign Anyway"
            cancel-label="Cancel"
            variant="danger"
            :loading="savingAssignment"
            @confirm="confirmConflictOverride"
            @cancel="cancelConflictOverride"
        />
    </div>
</template>
