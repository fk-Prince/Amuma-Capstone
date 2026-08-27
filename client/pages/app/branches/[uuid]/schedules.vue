<template>
    <div class="min-h-screen-header bg-light p-3 sm:p-4 lg:p-6 font-sans">
        <div
            class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-4 w-full lg:h-[calc(100dvh-var(--header-h)-3rem)]"
        >
            <div class="flex min-w-0 min-h-0 flex-col">
                <ScheduleFilter />

                <div class="flex-1 w-full mx-auto rounded-b-2xl bg-white">
                    <div v-if="pending" class="p-4 space-y-3">
                        <div
                            v-for="n in 6"
                            :key="n"
                            class="flex items-center gap-4 rounded-xl border border-slate-100 p-4 animate-pulse"
                        >
                            <div
                                class="h-10 w-10 shrink-0 rounded-full bg-slate-200"
                            />

                            <div class="flex-1 space-y-2">
                                <div class="h-3 w-1/3 rounded bg-slate-200" />
                                <div class="h-2.5 w-1/2 rounded bg-slate-100" />
                            </div>

                            <div
                                class="h-6 w-20 shrink-0 rounded-full bg-slate-200"
                            />
                            <div
                                class="h-6 w-16 shrink-0 rounded-full bg-slate-100"
                            />
                        </div>
                    </div>

                    <div
                        v-else-if="!filteredScheduleData.length"
                        class="flex flex-col items-center justify-center gap-3 px-6 py-24 text-center"
                    >
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400"
                        >
                            <svg
                                width="26"
                                height="26"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <rect
                                    x="3"
                                    y="5"
                                    width="18"
                                    height="16"
                                    rx="2"
                                />
                                <path d="M3 10h18M8 3v4M16 3v4" />
                            </svg>
                        </div>

                        <p class="text-sm font-semibold text-slate-600">
                            No schedules found
                        </p>

                        <p class="max-w-xs text-sm text-slate-400">
                            Try adjusting your filters or check back later for
                            new schedules.
                        </p>
                    </div>

                    <template v-else>
                        <ScheduleMedical
                            v-if="scheduleType === 'medical'"
                            :schedules="filteredScheduleData"
                            :loading="pending"
                            :date="route.query.date_from as string"
                            :range-end="route.query.date_to as string"
                            @view-details="viewSchedule"
                            @assign="handleAssign"
                        />

                        <HomecareADL
                            v-else
                            :logs="filteredScheduleData"
                            :loading="pending"
                            @view-details="viewSchedule"
                            @assign="handleAssign"
                            :variant="2"
                        />
                    </template>

                    <div
                        v-if="hasMore && !pending"
                        class="flex justify-center mt-4"
                    >
                        <button
                            type="button"
                            class="px-5 py-2.5 rounded-xl border border-muted-light bg-white text-sm font-medium text-secondary hover:border-primary/40 hover:text-primary-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="loadingMore"
                            @click="loadMore"
                        >
                            {{ loadingMore ? "Loading..." : "Load More" }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="min-w-0 lg:flex hidden">
                <ScheduleOverview
                    :overview="overviewData"
                    @new-schedule="handleNewScheduleEvent"
                />
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

function toTypeArray(value: unknown): string[] {
    if (Array.isArray(value)) return value.map(String).filter(Boolean);
    if (typeof value === "string" && value.length) return value.split(",");
    return [];
}

const scheduleType = computed<"medical" | "homecare">(() => {
    return toTypeArray(route.query.type).includes("adl")
        ? "homecare"
        : "medical";
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
        await loadSchedules({ append: false });
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
        await loadSchedules({ append: false });
    } catch (err: any) {
        error(err.error);
    } finally {
        savingAssignment.value = false;
    }
}

const pending = ref(false);
const loadingMore = ref(false);
const page = ref(1);
const perPage = 15;
const currentPage = ref(1);
const lastPage = ref(1);

const overviewData = ref<any>(null);
const overviewLoading = ref(false);

const hasMore = computed(() => currentPage.value < lastPage.value);

function handleNewScheduleEvent(schedule: any) {
    if (overviewData.value?.schedule) {
        overviewData.value.schedule.upcoming =
            (overviewData.value.schedule.upcoming ?? 0) + 1;
        overviewData.value.schedule.today =
            (overviewData.value.schedule.today ?? 0) + 1;
    }
}

async function fetchScheduleList(params: Record<string, any>) {
    const res = await scheduleService.list(params);

    const items = res?.data ?? res ?? [];
    currentPage.value =
        res?.meta?.current_page ?? res?.current_page ?? page.value;
    lastPage.value = res?.meta?.last_page ?? res?.last_page ?? 1;

    return items;
}

async function fetchScheduleOverview(branchUuid: string) {
    return scheduleService.action({
        type: "overview",
        branch_uuid: branchUuid,
        date: new Date().toISOString().slice(0, 10),
    });
}

async function loadSchedules(opts: { append?: boolean } = {}) {
    const { append = false } = opts;

    if (append) {
        loadingMore.value = true;
    } else {
        pending.value = true;
        overviewLoading.value = true;
        page.value = 1;
    }

    const { assignment, ...restQuery } = route.query;

    const listParams = {
        ...restQuery,
        branch_uuid: route.params.uuid as string,
        per_page: perPage,
        page: page.value,
        ...(assignment === "mine" && { assigned_only: 1 }),
    };

    try {
        if (append) {
            const items = await fetchScheduleList(listParams);
            scheduleData.value = [...(scheduleData.value ?? []), ...items];
        } else {
            const [items, overview] = await Promise.all([
                fetchScheduleList(listParams),
                fetchScheduleOverview(route.params.uuid as string),
            ]);

            scheduleData.value = items;
            overviewData.value = overview?.data ?? overview ?? null;
        }
    } catch (err: any) {
        error(err.error ?? err.message);
    } finally {
        pending.value = false;
        loadingMore.value = false;
        overviewLoading.value = false;
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
    { immediate: true },
);
</script>
