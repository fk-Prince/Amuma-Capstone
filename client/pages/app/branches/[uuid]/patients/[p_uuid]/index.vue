<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import PatientHeader from "~/components/sections/app/Patient/PatientHeader.vue";

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

useHead({
    title: "Patient Information",
});
const { success, error } = useToast();
import type {
    Medication,
    MedicationForm,
    MarkDosePayload,
    Vital,
    VitalFormData,
} from "~/types/medication";
import SchedulePatient from "~/components/sections/app/Patient/SchedulePatient.vue";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

const route = useRoute();

const {
    patientData,
    serviceData,
    scheduleData,
    employeeData,
    loading,
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
} = usePatient();

const uuid = computed(() => route.params.p_uuid as string);
const b_uuid = computed(() => route.params.uuid as string);
onMounted(() => {
    fetchData(uuid.value, b_uuid.value);
});

const tabs = [
    "Overview",
    "Schedule",
    "Service",
    "Medication",
    "Vital Signs",
] as const;

type Tab = (typeof tabs)[number];

const activeTab = ref<Tab>("Overview");

function setActiveTab(tab: Tab) {
    activeTab.value = tab;
}

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

const selectedVital = ref<Vital | null>(null);
const selectedSchedule = ref<ScheduleItem | null>(null);
const selectedMedication = ref<Medication | null>(null);

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
        savingVital.value = false;
        showRecordVital.value = false;
        selectedVital.value = null;
    } catch (err: any) {
        error(err.error);
        console.error(err);
    } finally {
        savingMedication.value = false;
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
        console.error(err);
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
        console.error(err);
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
        console.error(err);
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

async function onAssignSubmit(payload: any) {
    savingAssignment.value = true;
    try {
        const res = await handleAssignment(payload, b_uuid.value);
        success(res.message);
    } catch (err: any) {
        error(err.error);
    } finally {
        savingAssignment.value = false;
    }
}

async function onUpdateSchedule(payload: any) {
    updatingAssignment.value = true;
    try {
        const res = await updateSchedule(payload, b_uuid.value);
        scheduleData.value = res.data;
        success(res.message);
        showScheduleModal.value = false;
    } catch (err: any) {
        error(err.error ?? err.message);
    } finally {
        updatingAssignment.value = false;
    }
}

const assignModalOpen = ref(false);
const assigneSchedule = ref<ScheduleItem>();
const scheduleType = ref<"medical" | "homecare">("medical");
const filteredScheduleData = computed(() => {
    return scheduleData.value.filter((schedule: ScheduleItem) => {
        if (scheduleType.value === "homecare") {
            return schedule.type === "adl";
        }
        return schedule.type === "medical";
    });
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 p-6">
        <div class="m-full space-y-4">
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
                            v-for="tab in tabs"
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
                    <!-- @edit-vital="handleAddMedication" -->

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
                                @view-details="viewSchedule"
                                @assign="handleAssign"
                            />
                            <HomecareADL
                                v-if="
                                    activeTab === 'Schedule' &&
                                    scheduleType === 'homecare'
                                "
                                :logs="filteredScheduleData"
                            />
                        </div>
                    </div>
                </div>

                <!-- <SchedulePatient
                    v-if="activeTab === 'Schedule'"
                    :schedules="scheduleData"
                    @view-details="viewSchedule"
                    @assign="handleAssign"
                /> -->
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
    </div>
</template>
