<template>
    <button
        v-if="total > 0"
        type="button"
        aria-haspopup="dialog"
        class="flex w-full flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-left shadow-sm transition hover:border-amber-400 dark:border-amber-500/30 dark:bg-amber-500/10 dark:hover:border-amber-400/60"
        @click="open = true"
    >
        <div class="flex min-w-0 items-center gap-3">
            <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-600 dark:bg-white/10 dark:text-amber-300"
            >
                <TriangleAlert class="h-5 w-5" />
            </div>

            <div class="min-w-0">
                <h2
                    class="text-sm font-bold tracking-tight text-secondary dark:text-white"
                >
                    {{ total }} {{ total === 1 ? "item needs" : "items need" }}
                    attention
                </h2>

                <div class="mt-1 flex flex-wrap gap-1.5">
                    <span
                        v-for="chip in chips"
                        :key="chip.label"
                        class="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                    >
                        <b class="tabular-nums" :class="chip.countClass">{{
                            chip.count
                        }}</b>
                        {{ chip.label }}
                    </span>
                </div>
            </div>
        </div>

        <span
            class="shrink-0 text-xs font-bold text-amber-700 dark:text-amber-300"
        >
            Review →
        </span>
    </button>

    <AttentionModal
        :open="open"
        :data="data"
        :branch-uuid="branchUuid"
        @close="open = false"
    />
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { TriangleAlert } from "lucide-vue-next";
import AttentionModal from "~/components/sections/app/Dashboard/overview/AttentionModal.vue";
import { branchService } from "~/api/branch/BranchService";
import type { AttentionData } from "~/types/attention";

const props = defineProps<{ branchUuid: string }>();

const open = ref(false);
const data = ref<AttentionData>({ discharges: [], services: [], schedules: [] });

const total = computed(
    () =>
        data.value.discharges.length +
        data.value.services.length +
        data.value.schedules.length,
);

const chips = computed(() =>
    [
        {
            count: data.value.discharges.length,
            label: "discharging soon",
            countClass: "text-primary-600 dark:text-primary-300",
        },
        {
            count: data.value.services.length,
            label: "services without staff",
            countClass: "text-amber-600 dark:text-amber-300",
        },
        {
            count: data.value.schedules.length,
            label: "unassigned schedules",
            countClass: "text-rose-600 dark:text-rose-300",
        },
    ].filter((chip) => chip.count > 0),
);

onMounted(async () => {
    if (!props.branchUuid) return;

    try {
        const res: any = await branchService.attention({
            branch_uuid: props.branchUuid,
        });

        data.value = {
            discharges: res?.data?.discharges ?? [],
            services: res?.data?.services ?? [],
            schedules: res?.data?.schedules ?? [],
        };
    } catch (err) {
        console.error("Failed to fetch attention items:", err);
    }
});
</script>
