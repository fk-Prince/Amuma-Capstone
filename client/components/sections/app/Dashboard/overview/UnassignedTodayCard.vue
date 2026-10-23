<template>
    <div
        v-if="loading || items.length"
        class="rounded-xl border border-rose-200/70 bg-white p-3 shadow-sm ring-1 ring-rose-100/60 transition-all hover:shadow-md dark:border-rose-500/20 dark:bg-white/5 dark:ring-white/10"
    >
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <div
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300"
                >
                    <UserX class="h-4 w-4" />
                </div>
                <div>
                    <h2
                        class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                    >
                        Needs assignment today
                    </h2>
                    <p class="text-[11px] text-muted dark:text-gray-400">
                        Visits today with no staff assigned yet
                    </p>
                </div>
            </div>

            <span
                v-if="!loading"
                class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-bold text-rose-600 dark:bg-rose-500/15 dark:text-rose-300"
            >
                {{ items.length }}
            </span>
        </div>

        <div v-if="loading" class="mt-3 space-y-2">
            <div
                v-for="n in 2"
                :key="n"
                class="h-12 animate-pulse rounded-lg bg-slate-100 dark:bg-white/5"
            />
        </div>

        <ul
            v-else
            class="mt-3 divide-y divide-slate-100 dark:divide-white/10"
        >
            <li
                v-for="item in items"
                :key="item.schedule_code"
                class="flex items-center justify-between gap-3 py-2.5"
            >
                <div class="min-w-0">
                    <p
                        class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                    >
                        {{ item.patient_name || "Unnamed patient" }}
                    </p>
                    <p
                        class="truncate text-[11px] text-slate-500 dark:text-gray-400"
                    >
                        {{ item.schedule_code }} ·
                        {{ formatWhen(item.scheduled_at) }} ·
                        {{ item.services.join(", ") }}
                    </p>
                </div>

                <NuxtLink
                    v-if="canViewSchedules"
                    :to="scheduleLink(item)"
                    class="shrink-0 rounded-md border px-2.5 py-1 text-[11px] font-semibold transition"
                    :class="
                        canAssignSchedules
                            ? 'border-rose-600/20 bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-300'
                            : 'border-primary/20 bg-primary/[0.03] text-primary hover:bg-primary/10'
                    "
                >
                    {{ canAssignSchedules ? "Assign" : "View" }}
                </NuxtLink>
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { UserX } from "lucide-vue-next";
import { scheduleService } from "~/api/schedule/ScheduleService";
import { usePermissions } from "~/composables/usePermission";
import { Modules } from "~/types/module";
import { getLocalDateStr } from "~/utils/time";

interface UnassignedSchedule {
    schedule_code: string;
    patient_name: string | null;
    scheduled_at: string;
    status: string;
    type: "adl" | "medical";
    services: string[];
}

const props = defineProps<{ branchUuid: string }>();

const { canAssign, hasModule } = usePermissions();
const canAssignSchedules = computed(() => canAssign(Modules.Schedules));
const canViewSchedules = computed(() => hasModule(Modules.Schedules));

const items = ref<UnassignedSchedule[]>([]);
const loading = ref(false);
const today = getLocalDateStr(new Date());

function formatWhen(value: string) {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return "";

    const time = date.toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
    });

    return getLocalDateStr(date) === today
        ? time
        : `${date.toLocaleDateString("en-US", { month: "short", day: "numeric" })}, ${time}`;
}

function scheduleLink(item: UnassignedSchedule) {
    return {
        path: `/app/branches/${props.branchUuid}/schedules`,
        query: {
            ...(item.type === "adl" ? { type: "adl" } : {}),
            date_from: today,
            date_to: today,
            search: item.schedule_code,
        },
    };
}

onMounted(async () => {
    if (!props.branchUuid) return;

    loading.value = true;

    try {
        const res: any = await scheduleService.action({
            type: "unassigned_today",
            branch_uuid: props.branchUuid,
        });

        items.value = res?.data ?? [];
    } catch {
        items.value = [];
    } finally {
        loading.value = false;
    }
});
</script>
