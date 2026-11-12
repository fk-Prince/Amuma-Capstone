<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { LoaderCircle } from "lucide-vue-next";
import { employeeService } from "~/api/employee/EmployeeService";
import type { EmployeeWorkload, WorkloadItem } from "~/types/employee";
import { formatDate, stringToDateTime } from "~/utils/time";

const props = defineProps<{
    open: boolean;
    employeeUuid?: string | null;
    branchUuid: string;
    name: string;
    mode: "inactive" | "leave";
    until?: string | null;
    unassign?: boolean;
}>();

const emit = defineEmits<{
    "update:unassign": [value: boolean];
    state: [state: { loading: boolean; blocked: boolean; count: number }];
}>();

const workload = ref<EmployeeWorkload | null>(null);
const loading = ref(false);
let requestId = 0;

async function load() {
    if (!props.open || !props.employeeUuid) return;

    const current = ++requestId;
    loading.value = true;

    try {
        const res: any = await employeeService.workload(props.employeeUuid, {
            branch_uuid: props.branchUuid,
            ...(props.mode === "leave" && props.until ? { until: props.until } : {}),
        });

        if (current === requestId) {
            workload.value = (res?.data ?? null) as EmployeeWorkload | null;
        }
    } catch {
        if (current === requestId) workload.value = null;
    } finally {
        if (current === requestId) loading.value = false;
    }
}

watch(
    () => [props.open, props.until] as const,
    ([open]) => {
        if (open) {
            load();
        } else {
            workload.value = null;
        }
    },
    { immediate: true },
);

const inProgress = computed(() => workload.value?.in_progress ?? []);
const upcoming = computed(() => workload.value?.upcoming ?? []);

watch(
    [loading, inProgress, upcoming],
    () =>
        emit("state", {
            loading: loading.value,
            blocked: inProgress.value.length > 0,
            count: upcoming.value.length,
        }),
    { immediate: true },
);

const describe = (item: WorkloadItem) => {
    if (item.kind === "shift") {
        return [
            item.on_duty ? "On duty now" : item.title,
            item.patient,
            item.room ? `Room ${item.room}` : null,
        ]
            .filter(Boolean)
            .join(" – ");
    }

    return [
        item.title,
        item.patient,
        item.scheduled_at ? stringToDateTime(item.scheduled_at) : null,
    ]
        .filter(Boolean)
        .join(" – ");
};

const leaveScope = computed(() =>
    props.until
        ? `Work from ${formatDate(`${props.until}T00:00:00`)} onward stays with ${props.name}.`
        : "There's no back-on date, so all upcoming work is affected.",
);
</script>

<template>
    <div
        v-if="loading"
        class="flex items-center gap-2 text-xs text-slate-400 dark:text-gray-500"
    >
        <LoaderCircle class="h-3.5 w-3.5 animate-spin" />
        Checking {{ name }}'s schedules…
    </div>

    <div
        v-else-if="inProgress.length"
        class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300"
    >
        <p class="font-semibold">{{ name }} is checked in to a visit right now</p>
        <p class="mt-1 text-xs leading-5">
            Finish or hand over this visit first.
        </p>
        <ul class="mt-2 list-disc space-y-0.5 pl-5 text-xs leading-5">
            <li v-for="(item, index) in inProgress" :key="index">
                {{ describe(item) }}
            </li>
        </ul>
    </div>

    <div
        v-else-if="upcoming.length"
        class="space-y-3"
    >
        <div
            class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"
        >
            <p class="font-semibold">
                <template v-if="mode === 'inactive'">
                    {{ name }} still has upcoming work
                </template>
                <template v-else>
                    {{ upcoming.length }}
                    {{ upcoming.length === 1 ? "assignment falls" : "assignments fall" }}
                    within this leave
                </template>
            </p>
            <p class="mt-1 text-xs leading-5">
                <template v-if="mode === 'inactive'">
                    Marking {{ name }} inactive removes them from these. Staff
                    who can assign schedules will be notified to reassign them.
                </template>
                <template v-else>{{ leaveScope }}</template>
            </p>
            <ul class="mt-2 list-disc space-y-0.5 pl-5 text-xs leading-5">
                <li
                    v-for="(item, index) in upcoming"
                    :key="index"
                    :class="item.on_duty ? 'font-semibold' : ''"
                >
                    {{ describe(item) }}
                </li>
            </ul>
        </div>

        <label
            v-if="mode === 'leave'"
            class="flex cursor-pointer select-none items-start gap-2.5 text-sm text-slate-700 dark:text-gray-200"
        >
            <input
                type="checkbox"
                class="mt-0.5 h-4 w-4 cursor-pointer rounded border-gray-300 text-primary focus:ring-primary/30 dark:border-white/20 dark:bg-white/5"
                :checked="unassign"
                @change="
                    emit(
                        'update:unassign',
                        ($event.target as HTMLInputElement).checked,
                    )
                "
            />
            <span>Unassign these and notify staff to reassign them</span>
        </label>
    </div>
</template>
