<template>
    <div class="min-h-[calc(100vh-90px)] bg-slate-100 p-6">
        <div
            class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-4 max-w-8xl min-h-[calc(100vh-90px-3rem)] lg:h-[calc(100vh-90px-3rem)]"
        >
            <div class="flex min-w-0 min-h-0 flex-col">
                <ScheduleFilter />

                <div class="mt-4 flex-1 max-w-5xl mx-auto rounded-lg">
                    <ScheduleMedical
                        v-if="scheduleType === 'medical'"
                        :schedules="filteredScheduleData"
                        :loading="pending"
                        @view-details="viewSchedule"
                        @assign="handleAssign"
                    />

                    <HomecareADL
                        v-else
                        :logs="filteredScheduleData"
                        :loading="pending"
                        @view-details="viewSchedule"
                        @assign="handleAssign"
                    />

                    <div v-if="hasMore" class="flex justify-center mt-4">
                        <button
                            type="button"
                            class="px-5 py-2.5 rounded-xl border border-[#EDF4F3] bg-white text-sm font-medium text-[#16302E] hover:border-primary/40 hover:text-primary transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="loadingMore"
                            @click="loadMore"
                        >
                            {{ loadingMore ? "Loading..." : "Load More" }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="min-w-0 lg:flex hidden">
                <ScheduleOverview />
            </div>
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

        <ScheduleDetails
            :open="showScheduleModal"
            :schedule="selectedSchedule"
            :employees="employeeData"
            :is-fetching-employees="isFetchingEmployee"
            :submit-loading="updatingAssignment"
            @close="showScheduleModal = false"
            @schedule="onUpdateSchedule"
        />
    </div>
</template>

<script setup lang="ts">
import ScheduleFilter from "~/components/sections/app/Schedule/ScheduleFilter.vue";
import ScheduleOverview from "~/components/sections/app/Schedule/ScheduleOverview.vue";
import { usePatient } from "~/composables/usePatient";
import { useRoute } from "vue-router";
import type { ScheduleItem } from "~/types/schedule";
import ScheduleMedical from "~/components/sections/app/Schedule/ScheduleMedical.vue";
import HomecareADL from "~/components/sections/app/Patient/HomecareADL.vue";
import ScheduleDetails from "~/components/sections/app/Patient/ScheduleDetails.vue";
import { useToast } from "~/composables/useToast";
import AssignEmployeeModal from "~/components/sections/app/Patient/AssignEmployeeModal.vue";
import { scheduleService } from "~/api/schedule/ScheduleService";

const route = useRoute();
const uuid = computed(() => route.params.uuid as string);
const { success, error } = useToast();
const {
    scheduleData,
    employeeData,
    fetchEmployee,
    updateSchedule,
    handleAssignment,
} = usePatient();

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Schedules",
});

// Driven by the ScheduleFilter component (scheduleType query param)
const scheduleType = computed<"medical" | "homecare">(() => {
    const value = route.query.scheduleType;
    return value === "homecare" ? "homecare" : "medical";
});

function isHomecareSchedule(schedule: ScheduleItem) {
    const type = ((schedule as any).type ?? "").toLowerCase();
    return type === "adl";
}

const filteredScheduleData = computed(() => {
    if (!Array.isArray(scheduleData.value)) return [];

    return scheduleData.value.filter((schedule: ScheduleItem) =>
        scheduleType.value === "homecare"
            ? isHomecareSchedule(schedule)
            : !isHomecareSchedule(schedule),
    );
});

const savingAssignment = ref(false);
const showScheduleModal = ref(false);
const updatingAssignment = ref(false);
const isFetchingEmployee = ref(false);
const selectedSchedule = ref<ScheduleItem | null>(null);
const assignModalOpen = ref(false);
const assigneSchedule = ref<ScheduleItem>();

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
        await fetchEmployee(uuid.value, s.schedule_id);
    } catch (err: any) {
        error(err.error);
    } finally {
        isFetchingEmployee.value = false;
    }
}

async function onUpdateSchedule(payload: any) {
    updatingAssignment.value = true;
    try {
        const res = await updateSchedule(payload, uuid.value);
        success(res.message);
        showScheduleModal.value = false;
    } catch (err: any) {
        error(err.error ?? err.message);
    } finally {
        updatingAssignment.value = false;
    }
}

async function onAssignSubmit(payload: any) {
    savingAssignment.value = true;

    try {
        const res = await handleAssignment(payload, uuid.value);
        success(res.message);
        assignModalOpen.value = false;
    } catch (err: any) {
        error(err.error);
    } finally {
        savingAssignment.value = false;
    }
}

const pending = ref(false);
const loadingMore = ref(false);
const page = ref(1);
const perPage = 1;
const currentPage = ref(1);
const lastPage = ref(1);

const hasMore = computed(() => currentPage.value < lastPage.value);

async function loadSchedules(opts: { append?: boolean } = {}) {
    const { append = false } = opts;

    if (append) {
        loadingMore.value = true;
    } else {
        pending.value = true;
        page.value = 1;
    }

    try {
        const res = await scheduleService.list({
            ...route.query,
            branch_uuid: route.params.uuid as string,
            per_page: perPage,
            page: page.value,
        });

        const items = res?.data ?? res ?? [];
        currentPage.value =
            res?.meta?.current_page ?? res?.current_page ?? page.value;
        lastPage.value = res?.meta?.last_page ?? res?.last_page ?? 1;
        if (append) {
            scheduleData.value = [...(scheduleData.value ?? []), ...items];
        } else {
            scheduleData.value = items;
        }
    } catch (err: any) {
        error(err.error ?? err.message);
    } finally {
        pending.value = false;
        loadingMore.value = false;
    }
}

async function loadMore() {
    if (loadingMore.value || !hasMore.value) return;
    page.value += 1;
    await loadSchedules({ append: true });
}

watch(
    () => route.query,
    () => {
        loadSchedules({ append: false });
    },
);
</script>
