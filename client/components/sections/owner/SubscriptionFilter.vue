<template>
    <div class="flex flex-col gap-2.5 sm:flex-row sm:flex-wrap sm:items-center">
        <div class="relative min-w-0 flex-1 sm:min-w-[240px]">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500"
            >
                <circle cx="9" cy="9" r="6" />
                <path stroke-linecap="round" d="m17 17-4-4" />
            </svg>

            <input
                type="text"
                :value="search"
                placeholder="Search by agency or branch name"
                aria-label="Search by agency or branch name"
                class="h-10 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-9 text-sm text-slate-700 transition placeholder:text-slate-400 focus:border-primary-300 focus:outline-none focus:ring-2 focus:ring-primary-100 dark:border-white/10 dark:bg-secondary dark:text-white dark:placeholder:text-gray-500 dark:focus:ring-primary-500/20"
                @input="
                    emit(
                        'update:search',
                        ($event.target as HTMLInputElement).value,
                    )
                "
            />

            <button
                v-if="search"
                type="button"
                aria-label="Clear search"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-gray-500 transition hover:text-slate-600 dark:hover:text-gray-300"
                @click="emit('update:search', '')"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    class="h-4 w-4"
                >
                    <path d="M5 5l10 10M15 5 5 15" />
                </svg>
            </button>
        </div>
        <div
            v-if="views.length > 1"
            class="inline-flex h-10 shrink-0 items-center rounded-xl border border-slate-200 bg-slate-50 p-1 dark:border-white/10 dark:bg-white/5"
            role="tablist"
            aria-label="Subscription view"
        >
            <button
                v-for="option in views"
                :key="option"
                type="button"
                role="tab"
                :aria-selected="view === option"
                class="h-full min-w-[92px] rounded-lg px-4 text-xs font-semibold capitalize transition"
                :class="
                    view === option
                        ? 'bg-white text-primary shadow-sm dark:bg-white/10 dark:text-primary-300'
                        : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-white'
                "
                @click="setView(option)"
            >
                {{ option }}
            </button>
        </div>

        <Combobox
            v-if="view === 'approved'"
            class="shrink-0 sm:w-40"
            :model-value="approvedStatus"
            :items="statusOptions"
            placeholder="Status"
            input-class="h-10 px-3.5 rounded-xl"
            @update:model-value="
                emit('update:approvedStatus', $event as ApprovedStatus)
            "
        />

        <button
            type="button"
            :disabled="loading"
            class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-600 transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:bg-secondary dark:text-gray-300 dark:hover:bg-white/10"
            @click="emit('refresh')"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                class="h-3.5 w-3.5"
                :class="{ 'animate-spin': loading }"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                />
            </svg>
            Refresh
        </button>
    </div>
</template>

<script setup lang="ts">
import Combobox from "~/components/ui/Combobox.vue";

export type SubscriptionView = "requests" | "approved" | "rejected";
export type ApprovedStatus = "active" | "inactive" | "expired";

type ComboboxItem = {
    label: string;
    value: ApprovedStatus;
};

const props = withDefaults(
    defineProps<{
        search: string;
        view: SubscriptionView;
        approvedStatus: ApprovedStatus;
        views?: SubscriptionView[];
        loading?: boolean;
    }>(),
    {
        views: () => ["requests", "approved", "rejected"],
        loading: false,
    },
);

const emit = defineEmits<{
    (e: "update:search", value: string): void;
    (e: "update:view", value: SubscriptionView): void;
    (e: "update:approvedStatus", value: ApprovedStatus): void;
    (e: "refresh"): void;
}>();

const statusOptions: ComboboxItem[] = [
    {
        label: "Active",
        value: "active",
    },
    {
        label: "Inactive",
        value: "inactive",
    },
    {
        label: "Expired",
        value: "expired",
    },
];

function setView(view: SubscriptionView) {
    if (view === props.view) return;

    emit("update:view", view);
}
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
