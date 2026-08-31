<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
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
                class="w-full rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-secondary py-2.5 pl-9 pr-9 text-sm text-slate-700 dark:text-white placeholder:text-slate-400 dark:placeholder:text-gray-500 transition focus:border-primary-300 focus:outline-none focus:ring-2 focus:ring-primary-100 dark:focus:ring-primary-500/20"
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
            class="relative inline-flex shrink-0 rounded-full bg-slate-100 dark:bg-white/5 p-1"
            role="tablist"
            aria-label="Subscription view"
        >
            <span
                class="absolute inset-y-1 left-1 w-[104px] rounded-full bg-white dark:bg-white/10 shadow-sm transition-transform duration-200 ease-out"
                :style="{
                    transform:
                        view === 'approved'
                            ? 'translateX(104px)'
                            : view === 'rejected'
                              ? 'translateX(208px)'
                              : 'translateX(0)',
                }"
            />

            <button
                type="button"
                role="tab"
                :aria-selected="view === 'requests'"
                class="relative z-10 w-[104px] rounded-full py-1.5 text-xs font-semibold transition-colors"
                :class="
                    view === 'requests'
                        ? 'text-primary-600 dark:text-primary-300'
                        : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-white'
                "
                @click="setView('requests')"
            >
                Requests
            </button>

            <button
                type="button"
                role="tab"
                :aria-selected="view === 'approved'"
                class="relative z-10 w-[104px] rounded-full py-1.5 text-xs font-semibold transition-colors"
                :class="
                    view === 'approved'
                        ? 'text-primary-600 dark:text-primary-300'
                        : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-white'
                "
                @click="setView('approved')"
            >
                Approved
            </button>

            <button
                type="button"
                role="tab"
                :aria-selected="view === 'rejected'"
                class="relative z-10 w-[104px] rounded-full py-1.5 text-xs font-semibold transition-colors"
                :class="
                    view === 'rejected'
                        ? 'text-danger'
                        : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-white'
                "
                @click="setView('rejected')"
            >
                Rejected
            </button>
        </div>
        <div
            v-if="view === 'approved'"
            class="flex shrink-0 items-center gap-2"
        >
            <Combobox
                :model-value="approvedStatus"
                :items="statusOptions"
                placeholder="Status"
                input-class=" px-3 py-1.5"
                @update:model-value="
                    emit('update:approvedStatus', $event as ApprovedStatus)
                "
            />
        </div>
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

const props = defineProps<{
    search: string;
    view: SubscriptionView;
    approvedStatus: ApprovedStatus;
}>();

const emit = defineEmits<{
    (e: "update:search", value: string): void;
    (e: "update:view", value: SubscriptionView): void;
    (e: "update:approvedStatus", value: ApprovedStatus): void;
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
