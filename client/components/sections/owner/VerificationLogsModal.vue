<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[1000] flex items-center justify-center bg-slate-900/50 p-4"
                @click.self="emit('close')"
            >
                <div
                    class="flex max-h-[85vh] min-h-[360px] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Verification logs"
                >
                    <div
                        class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <h3
                                class="text-sm font-semibold text-secondary dark:text-white"
                            >
                                {{ title ?? "Verification logs" }}
                            </h3>
                            <p
                                class="mt-0.5 truncate text-xs text-slate-500 dark:text-gray-400"
                            >
                                {{ branchName }}
                            </p>
                        </div>

                        <button
                            type="button"
                            aria-label="Close"
                            class="shrink-0 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                            @click="emit('close')"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                        <div v-if="loading" class="space-y-4">
                            <div
                                v-for="n in 3"
                                :key="n"
                                class="flex animate-pulse gap-3"
                            >
                                <div
                                    class="h-7 w-7 shrink-0 rounded-full bg-slate-200 dark:bg-white/10"
                                />
                                <div class="flex-1 space-y-2">
                                    <div
                                        class="h-3 w-1/3 rounded bg-slate-200 dark:bg-white/10"
                                    />
                                    <div
                                        class="h-3 w-3/4 rounded bg-slate-100 dark:bg-white/5"
                                    />
                                </div>
                            </div>
                        </div>

                        <p
                            v-else-if="loadError"
                            class="rounded-lg border border-danger/20 bg-danger/5 px-4 py-2 text-sm text-danger dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400"
                        >
                            {{ loadError }}
                        </p>

                        <p
                            v-else-if="!entries.length"
                            class="py-8 text-center text-xs text-muted dark:text-gray-500"
                        >
                            No verification decisions have been recorded yet.
                        </p>

                        <ol v-else class="relative space-y-5">
                            <li
                                v-for="(log, index) in entries"
                                :key="index"
                                class="relative flex gap-3"
                            >
                                <span
                                    v-if="index < entries.length - 1"
                                    class="absolute left-3.5 top-8 h-[calc(100%-0.75rem)] w-px bg-slate-200 dark:bg-white/10"
                                />

                                <span
                                    class="relative flex h-7 w-7 shrink-0 items-center justify-center rounded-full"
                                    :class="
                                        log.action === 'approved'
                                            ? 'bg-accent-50 text-accent-600 dark:bg-accent-500/10 dark:text-accent-300'
                                            : 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400'
                                    "
                                >
                                    <Check
                                        v-if="log.action === 'approved'"
                                        class="h-3.5 w-3.5"
                                    />
                                    <X v-else class="h-3.5 w-3.5" />
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div
                                        class="flex flex-wrap items-center gap-x-2 gap-y-1"
                                    >
                                        <p
                                            class="text-xs font-semibold text-secondary dark:text-white"
                                        >
                                            {{
                                                log.action === "approved"
                                                    ? "Approved"
                                                    : "Rejected"
                                            }}
                                        </p>

                                        <span
                                            class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500 dark:bg-white/10 dark:text-gray-300"
                                        >
                                            {{ scopeLabel(log.scope) }}
                                        </span>
                                    </div>

                                    <p
                                        v-if="showBranch && log.branch_name"
                                        class="mt-0.5 truncate text-[11px] font-medium text-slate-600 dark:text-gray-300"
                                    >
                                        {{ log.branch_name }}
                                    </p>

                                    <p
                                        class="mt-0.5 text-[11px] text-muted dark:text-gray-400"
                                    >
                                        {{ stringToDateTime(log.created_at) }}
                                        <template v-if="log.reviewed_by">
                                            · by {{ log.reviewed_by }}
                                        </template>
                                    </p>

                                    <p
                                        v-if="log.reason"
                                        class="mt-2 rounded-lg bg-rose-50/70 px-3 py-2 text-xs leading-5 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300"
                                    >
                                        {{ log.reason }}
                                    </p>
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Check, X } from "lucide-vue-next";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { stringToDateTime } from "~/utils/time";
import type { VerificationLogRecord } from "~/types/subscription";

const props = defineProps<{
    open: boolean;
    branchName: string;
    subscriptionUuid?: string;
    logs?: VerificationLogRecord[];
    title?: string;
    showBranch?: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const fetched = ref<VerificationLogRecord[]>([]);
const loading = ref(false);
const loadError = ref<string | null>(null);

const entries = computed(() => props.logs ?? fetched.value);

const scopeLabel = (scope: VerificationLogRecord["scope"]) =>
    scope === "both"
        ? "Agency & branch"
        : scope === "agency"
          ? "Agency"
          : "Branch";

const load = async () => {
    loading.value = true;
    loadError.value = null;

    try {
        const res = await subscriptionService.action({
            action: "logs",
            branch_subscription_uuid: props.subscriptionUuid,
        });

        fetched.value = res?.data ?? [];
    } catch (err: any) {
        loadError.value = err?.message ?? "Failed to load the logs.";
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.open,
    (open) => {
        if (open && !props.logs && props.subscriptionUuid) load();
    },
);
</script>
