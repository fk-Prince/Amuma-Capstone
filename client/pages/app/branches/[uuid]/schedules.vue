<template>
    <div class="min-h-screen-header bg-light font-sans dark:bg-surface">
        <div
            class="grid grid-cols-1 gap-4 w-full lg:h-[calc(100dvh-var(--header-h)-3rem)]"
            :class="showOverview && isDesktop ? 'lg:grid-cols-[1fr_400px]' : ''"
        >
            <div class="flex min-w-0 min-h-0 flex-col">
                <ScheduleFilter
                    :overview-visible="showOverview"
                    @toggle-overview="showOverview = !showOverview"
                />

                <div
                    class="flex-1 w-full mx-auto rounded-b-lg bg-white dark:bg-secondary"
                >
                    <div v-if="pending" class="p-4 space-y-3">
                        <div
                            v-for="n in 6"
                            :key="n"
                            class="flex items-center gap-4 rounded-xl border border-slate-100 p-4 animate-pulse dark:border-white/10"
                        >
                            <div
                                class="h-10 w-10 shrink-0 rounded-full bg-slate-200 dark:bg-white/15"
                            />

                            <div class="flex-1 space-y-2">
                                <div
                                    class="h-3 w-1/3 rounded bg-slate-200 dark:bg-white/15"
                                />
                                <div
                                    class="h-2.5 w-1/2 rounded bg-slate-100 dark:bg-white/10"
                                />
                            </div>

                            <div
                                class="h-6 w-20 shrink-0 rounded-full bg-slate-200 dark:bg-white/15"
                            />
                            <div
                                class="h-6 w-16 shrink-0 rounded-full bg-slate-100 dark:bg-white/10"
                            />
                        </div>
                    </div>

                    <div
                        v-else-if="!filteredScheduleData.length"
                        class="flex flex-col items-center justify-center gap-3 px-6 py-24 text-center"
                    >
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-white/10 dark:text-gray-500"
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

                        <p
                            class="text-sm font-semibold text-slate-600 dark:text-gray-400"
                        >
                            No schedules found
                        </p>

                        <p
                            class="max-w-xs text-sm text-slate-400 dark:text-gray-500"
                        >
                            Try adjusting your filters or check back later for
                            new schedules.
                        </p>
                    </div>

                    <template v-else>
                        <template v-if="scheduleType === 'medical'">
                            <ScheduleMedicalCards
                                v-if="medicalView === 'cards'"
                                :schedules="filteredScheduleData"
                                :loading="pending"
                                :date="route.query.date_from as string"
                                :range-end="route.query.date_to as string"
                                @view-details="viewSchedule"
                                @assign="handleAssign"
                            />

                            <ScheduleMedical
                                v-else
                                :schedules="filteredScheduleData"
                                :loading="pending"
                                :date="route.query.date_from as string"
                                :range-end="route.query.date_to as string"
                                @view-details="viewSchedule"
                                @assign="handleAssign"
                            />
                        </template>

                        <HomecareADL
                            v-else
                            :logs="filteredScheduleData"
                            :loading="pending"
                            @view-details="viewSchedule"
                            @assign="handleAssign"
                            @update="onAdlScheduleUpdated"
                            :variant="2"
                        />
                    </template>

                    <div
                        v-if="hasMore && !pending"
                        class="flex justify-center mt-4"
                    >
                        <button
                            type="button"
                            class="px-5 py-2.5 rounded-xl border border-muted-light bg-white text-sm font-medium text-secondary hover:border-primary/40 hover:text-primary-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed dark:bg-secondary dark:hover:text-primary-300 dark:border-white/10 dark:text-white"
                            :disabled="loadingMore"
                            @click="loadMore"
                        >
                            {{ loadingMore ? "Loading..." : "Load More" }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="showOverview && isDesktop" class="min-w-0 flex">
                <ScheduleOverview
                    :overview="overviewData"
                    @new-schedule="handleNewScheduleEvent"
                />
            </div>
        </div>

        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showOverview && !isDesktop"
                    class="fixed inset-0 z-50"
                >
                    <div
                        class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm"
                        @click="showOverview = false"
                    />

                    <aside
                        class="absolute inset-y-0 right-0 flex w-[min(400px,88vw)] flex-col overflow-y-auto bg-[#EEF3FB] p-3 shadow-2xl dark:bg-surface"
                    >
                        <button
                            type="button"
                            aria-label="Hide schedule overview"
                            class="mb-2 ml-auto flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-black/5 dark:text-gray-400 dark:hover:bg-white/10"
                            @click="showOverview = false"
                        >
                            <X class="h-4 w-4" />
                        </button>

                        <ScheduleOverview
                            :overview="overviewData"
                            @new-schedule="handleNewScheduleEvent"
                        />
                    </aside>
                </div>
            </Transition>
        </Teleport>

        <AssignEmployeeModal
            :open="assignModalOpen"
            :schedule="assigneSchedule"
            :employees="employeeData"
            :isFetching="isFetchingEmployee"
            :isSaving="savingAssignment"
            :conflicts="assignConflicts"
            @close="
                assignModalOpen = false;
                assignConflicts = [];
            "
            @confirm="onAssignSubmit"
        />

        <ScheduleDetails
            :open="showScheduleModal"
            :schedule="selectedSchedule"
            :employees="employeeData"
            :is-fetching-employees="isFetchingEmployee"
            :submit-loading="updatingAssignment"
            :save-conflicts="saveConflicts"
            :conflict-messages="updateConflicts"
            @close="
                showScheduleModal = false;
                saveConflicts = null;
                updateConflicts = [];
            "
            @schedule="onUpdateSchedule"
            @reschedule-preview="onReschedulePreview"
            @clear-save-conflicts="saveConflicts = null"
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
import ScheduleMedicalCards from "~/components/sections/app/Schedule/ScheduleMedicalCards.vue";
import HomecareADL from "~/components/sections/app/Patient/HomecareADL.vue";
import ScheduleDetails from "~/components/sections/app/Patient/ScheduleDetails.vue";
import { useToast } from "~/composables/useToast";
import AssignEmployeeModal from "~/components/sections/app/Patient/AssignEmployeeModal.vue";
import { scheduleService } from "~/api/schedule/ScheduleService";
import { X } from "lucide-vue-next";

const OVERVIEW_WIDTH = 1280;
const DESKTOP_WIDTH = 1024;

const showOverview = ref(true);
const isDesktop = ref(true);

function syncViewport() {
    isDesktop.value = window.innerWidth >= DESKTOP_WIDTH;

    if (!isDesktop.value) showOverview.value = false;
}

onMounted(() => {
    isDesktop.value = window.innerWidth >= DESKTOP_WIDTH;
    showOverview.value = window.innerWidth >= OVERVIEW_WIDTH;
    window.addEventListener("resize", syncViewport);
});

onUnmounted(() => {
    window.removeEventListener("resize", syncViewport);
});

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

const medicalView = computed(() =>
    route.query.view === "cards" ? "cards" : "timeline",
);

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
const saveConflicts = ref<any[] | null>(null);
const updateConflicts = ref<string[]>([]);
const assignConflicts = ref<string[]>([]);

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

async function onReschedulePreview(payload: {
    schedule_id: number;
    date: string;
    preferred_time: string;
}) {
    isFetchingEmployee.value = true;

    try {
        await fetchEmployee(
            uuid.value,
            payload.schedule_id,
            payload.date,
            payload.preferred_time,
        );
    } catch (err: any) {
        error(err.error);
    } finally {
        isFetchingEmployee.value = false;
    }
}

function onAdlScheduleUpdated(updated: ScheduleItem) {
    const index = scheduleData.value.findIndex(
        (s) => s.schedule_id === updated.schedule_id,
    );

    if (index !== -1) {
        scheduleData.value[index] = updated;
    }
}

async function onUpdateSchedule(payload: any) {
    updatingAssignment.value = true;
    updateConflicts.value = [];
    try {
        const res = await updateSchedule(payload, uuid.value);

        if (res?.has_conflicts) {
            saveConflicts.value = res.conflicts ?? [];
            error("Schedule conflict");
            return;
        }

        saveConflicts.value = null;
        success(res.message);
        showScheduleModal.value = false;
    } catch (err: any) {
        if (err?.status === 409) {
            updateConflicts.value = [err.message];
            error("Schedule conflict");
        } else {
            error(err.error ?? err.message);
        }
    } finally {
        updatingAssignment.value = false;
    }
}

async function onAssignSubmit(payload: any) {
    savingAssignment.value = true;
    assignConflicts.value = [];

    try {
        const res = await handleAssignment(payload, uuid.value);
        success(res.message);
        assignModalOpen.value = false;
    } catch (err: any) {
        if (err?.status === 409) {
            assignConflicts.value = [err.message];
            error("Schedule conflict");
        } else {
            error(err.error ?? err.message);
        }
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

    const { assignment, view, ...restQuery } = route.query;

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
    () => {
        const { view, ...rest } = route.query;
        return JSON.stringify(rest);
    },
    () => {
        loadSchedules({ append: false });
    },
    { immediate: true },
);
</script>
