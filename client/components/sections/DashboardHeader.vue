<template>
    <header
        class="min-h-[88px] sm:min-h-[104px] lg:h-[120px] px-3 sm:px-6 lg:px-8 py-4 sm:py-5 flex items-center justify-between gap-2 sm:gap-4 shrink-0 border-b border-gray-100 dark:border-white/10 bg-white dark:bg-secondary"
    >
        <button
            type="button"
            class="-ml-1 shrink-0 rounded-lg p-2 text-gray-600 hover:bg-gray-50 hover:text-primary-500 dark:text-white/70 dark:hover:bg-white/10 lg:hidden dark:hover:text-primary-300"
            aria-label="Open navigation"
            @click="$emit('open')"
        >
            <Menu class="h-5 w-5" />
        </button>

        <div v-if="!isMounted" class="min-w-0 flex-1 space-y-2">
            <div class="h-6 w-40 rounded-md skeleton-shimmer sm:h-7" />
            <div class="h-3 w-56 rounded-md skeleton-shimmer" />
        </div>

        <button
            v-else
            type="button"
            class="flex min-w-0 flex-1 items-center gap-2.5 rounded-lg py-1 text-left transition-colors duration-200 hover:bg-gray-50 dark:hover:bg-white/5 sm:-ml-2 sm:px-2"
            @click="branchStore.openModal"
        >
            <div
                class="hidden h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-primary-50 ring-2 ring-primary-100 dark:bg-white/10 dark:ring-white/10 sm:flex"
            >
                <img
                    v-if="branchStore.activeBranch?.image"
                    :src="getBranchImage(branchStore.activeBranch.image)"
                    :alt="branchStore.activeBranch.name"
                    class="h-full w-full object-cover"
                />
                <Building2 v-else class="h-5 w-5 text-primary-400" />
            </div>

            <div class="min-w-0 flex-1">
                <h1
                    class="flex items-center gap-1.5 truncate text-lg sm:text-2xl lg:text-[26px] font-bold text-gray-900 dark:text-white leading-tight"
                >
                    {{ branchStore.activeBranch?.name || "Select a branch" }}

                    <ChevronDown
                        v-if="branchStore.hasMultipleBranches"
                        class="h-4 w-4 shrink-0 text-gray-400 dark:text-white/40"
                    />
                </h1>

                <p class="text-xs sm:text-sm text-gray-400 mt-0.5 truncate dark:text-gray-500">
                    {{
                        branchStore.activeBranch?.location?.address ||
                        "No branch selected"
                    }}
                </p>

                <div
                    class="hidden items-center gap-2 sm:gap-3 mt-2 text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 sm:flex"
                >
                    <span class="flex items-center gap-1.5 whitespace-nowrap">
                        <Calendar
                            class="w-3.5 h-3.5 text-primary-500 shrink-0 dark:text-primary-300"
                        />
                        {{ formattedDate }}
                    </span>

                    <span
                        class="w-px h-3 bg-gray-200 dark:bg-white/10 shrink-0"
                    />

                    <span class="flex items-center gap-1.5 whitespace-nowrap">
                        <Clock class="w-3.5 h-3.5 text-primary-500 shrink-0 dark:text-primary-300" />
                        {{ formattedTime }}
                    </span>
                </div>
            </div>
        </button>

        <div class="flex items-center gap-1 sm:gap-6 lg:gap-8 shrink-0">
            <div v-if="!isMounted" class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full skeleton-shimmer" />
            </div>

            <template v-else>
                <ClientOnly>
                    <ThemeToggle
                        class="text-gray-500 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/10"
                    />
                </ClientOnly>

                <MessageBell />
                <Notification />
                <NavbarProfileDropdown
                    v-if="user"
                    :user="user"
                    :theme-aware="true"
                />
            </template>
        </div>
    </header>

    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="branchStore.showModal"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-primary-900/50 p-3 backdrop-blur-sm sm:items-center sm:p-4"
                @click.self="branchStore.closeModal"
            >
                <Transition
                    appear
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-2 scale-95 opacity-0"
                    enter-to-class="translate-y-0 scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="scale-100 opacity-100"
                    leave-to-class="scale-95 opacity-0"
                >
                    <div
                        v-if="branchStore.showModal"
                        class="w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-[0_0_40px_rgba(10,40,87,0.15)] ring-1 ring-primary-100/60 dark:bg-secondary dark:ring-primary-500/20"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Select a branch"
                    >
                        <div
                            class="flex items-start justify-between gap-3 border-b border-primary-100/80 bg-primary-50/40 px-4 py-3.5 sm:px-5 sm:py-4 dark:border-primary-500/20 dark:bg-primary-500/10"
                        >
                            <div
                                class="flex min-w-0 items-center gap-2.5 sm:gap-3"
                            >
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary sm:h-10 sm:w-10"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        class="h-4.5 w-4.5 sm:h-5 sm:w-5"
                                    >
                                        <path
                                            d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01"
                                        />
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <h2
                                        class="truncate text-sm font-semibold leading-tight text-primary-900 sm:text-base"
                                    >
                                        Select a branch
                                    </h2>
                                    <p
                                        class="mt-0.5 truncate text-[11px] text-muted sm:text-xs"
                                    >
                                        Choose which branch you want to manage
                                    </p>
                                </div>
                            </div>

                            <button
                                v-if="branchStore.activeBranch"
                                type="button"
                                aria-label="Close dialog"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-primary-400 transition-colors duration-200 hover:bg-primary-100 hover:text-primary-700 dark:hover:bg-primary-500/15 dark:hover:text-primary-300"
                                @click="branchStore.closeModal"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="h-4 w-4"
                                >
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                        </div>

                        <div
                            v-if="branchStore.branches?.length"
                            class="branch-scroll grid max-h-[calc(100vh-8rem)] gap-2.5 overflow-y-auto p-2.5 sm:max-h-[30rem] sm:grid-cols-2 sm:p-3"
                        >
                            <button
                                v-for="branch in branchStore.branches"
                                :key="branch.uuid"
                                type="button"
                                class="group w-full min-w-0 rounded-xl border p-3 text-left transition-all duration-200 sm:p-3.5"
                                :class="
                                    branch.uuid ===
                                    branchStore.activeBranch?.uuid
                                        ? 'border-primary-300 bg-primary-50 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:ring-primary-500/20'
                                        : 'border-transparent hover:border-primary-100 hover:bg-primary-50/70 dark:hover:border-primary-500/20 dark:hover:bg-primary-500/10'
                                "
                                @click="branchStore.selectBranch(branch)"
                            >
                                <div
                                    class="flex w-full min-w-0 items-start gap-2.5 sm:gap-3"
                                >
                                    <div
                                        class="relative h-10 w-10 shrink-0 overflow-hidden rounded-xl bg-primary-50 ring-2 ring-primary-100 transition-transform duration-200 group-hover:scale-[1.03] sm:h-11 sm:w-11 dark:bg-primary-500/10 dark:ring-primary-500/20"
                                    >
                                        <img
                                            v-if="branch.image"
                                            :src="getBranchImage(branch.image)"
                                            :alt="branch.name"
                                            class="h-full w-full object-cover"
                                        />

                                        <div
                                            v-else
                                            class="flex h-full w-full items-center justify-center"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.5"
                                                class="h-5 w-5 text-primary-400"
                                            >
                                                <path
                                                    d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01"
                                                />
                                            </svg>
                                        </div>

                                        <span
                                            v-if="
                                                branch.uuid ===
                                                branchStore.activeBranch?.uuid
                                            "
                                            class="absolute bottom-0 right-0 h-3.5 w-3.5 rounded-full border-2 border-white bg-emerald-400"
                                        />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="flex min-w-0 items-start justify-between gap-2"
                                        >
                                            <p
                                                class="flex min-w-0 flex-1 items-center gap-1 text-[13px] font-semibold text-primary-900 sm:text-sm"
                                            >
                                                <span class="min-w-0 truncate">
                                                    {{ branch.name }}
                                                </span>

                                                <BadgeCheck
                                                    v-if="branch.is_verified"
                                                    class="h-3.5 w-3.5 shrink-0 text-emerald-500 dark:text-emerald-300"
                                                />
                                            </p>

                                            <span
                                                v-if="
                                                    branch.uuid ===
                                                    branchStore.activeBranch
                                                        ?.uuid
                                                "
                                                class="shrink-0 rounded-full bg-primary-100 px-1.5 py-0.5 text-[9px] font-semibold text-primary-600 sm:px-2 sm:text-[10px] dark:bg-primary-500/15 dark:text-primary-300"
                                            >
                                                Active
                                            </span>
                                        </div>

                                        <p
                                            v-if="branch.location?.address"
                                            class="mt-1 flex w-full min-w-0 items-start gap-1.5 text-[11px] leading-4 text-muted sm:text-xs"
                                        >
                                            <Location
                                                class="mt-0.5 h-3.5 w-3.5 shrink-0"
                                            />

                                            <span
                                                class="min-w-0 flex-1 overflow-hidden text-ellipsis line-clamp-2"
                                            >
                                                {{ branch.location.address }}
                                            </span>
                                        </p>

                                        <div
                                            class="mt-2 flex min-w-0 items-start justify-between gap-2"
                                        >
                                            <div
                                                class="flex min-w-0 flex-1 flex-wrap gap-1"
                                            >
                                                <span
                                                    v-for="plan in branch.plan"
                                                    :key="plan.plan_code"
                                                    class="rounded-full border px-1.5 py-0.5 text-[10px] font-medium sm:px-2 sm:text-[11px]"
                                                    :class="{
                                                        'border-primary-200 bg-primary-50 text-primary dark:border-primary-500/20 dark:bg-primary-500/10':
                                                            plan.plan_code ===
                                                            'A',
                                                        'border-green-200 bg-green-50 text-accent':
                                                            plan.plan_code ===
                                                            'B',
                                                        'border-orange-200 bg-orange-50 text-secondary':
                                                            plan.plan_code ===
                                                            'C',
                                                    }"
                                                >
                                                    {{ plan.name }}
                                                </span>
                                            </div>

                                            <span
                                                class="max-w-[45%] shrink-0 truncate rounded-full border px-1.5 py-0.5 text-[10px] font-medium sm:max-w-[50%] sm:px-2 sm:text-[11px]"
                                                :class="
                                                    roleMeta[
                                                        branch?.role_name ?? ''
                                                    ]?.class ||
                                                    'bg-primary-50 text-primary-600 border-primary-200 dark:bg-primary-500/10 dark:text-primary-300 dark:border-primary-500/20'
                                                "
                                            >
                                                {{
                                                    formatRole(
                                                        branch?.role_name ?? "",
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        class="mt-1 hidden h-4 w-4 shrink-0 text-primary-300 opacity-0 transition-all duration-200 group-hover:translate-x-0 group-hover:opacity-100 sm:block sm:-translate-x-1"
                                    >
                                        <polyline points="9 18 15 12 9 6" />
                                    </svg>
                                </div>

                                <div
                                    class="mt-2.5 grid w-full min-w-0 grid-cols-1 gap-x-3 gap-y-1 border-t border-primary-100/70 pt-2.5 text-[10px] text-muted sm:grid-cols-2 sm:text-[11px] dark:border-primary-500/20"
                                >
                                    <span
                                        v-if="branch.contact_number"
                                        class="flex min-w-0 items-center gap-1.5"
                                    >
                                        <Phone
                                            class="h-3 w-3 shrink-0 text-primary-300"
                                        />
                                        <span class="min-w-0 truncate">
                                            {{ branch.contact_number }}
                                        </span>
                                    </span>

                                    <span
                                        v-if="branch.email"
                                        class="flex min-w-0 items-center gap-1.5"
                                    >
                                        <Mail
                                            class="h-3 w-3 shrink-0 text-primary-300"
                                        />
                                        <span class="min-w-0 truncate">
                                            {{ branch.email }}
                                        </span>
                                    </span>
                                </div>

                                <div
                                    v-if="branch.agency?.name"
                                    class="mt-2.5 flex w-full min-w-0 items-center gap-2 rounded-lg bg-primary-50/60 px-2 py-1.5 sm:px-2.5 sm:py-2 dark:bg-primary-500/10"
                                >
                                    <div
                                        class="flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden rounded-md bg-white ring-1 ring-primary-100 sm:h-7 sm:w-7 dark:bg-secondary dark:ring-primary-500/20"
                                    >
                                        <img
                                            v-if="branch.agency.image"
                                            :src="
                                                getBranchImage(
                                                    branch.agency.image,
                                                )
                                            "
                                            :alt="branch.agency.name"
                                            class="h-full w-full object-cover"
                                        />

                                        <Building2
                                            v-else
                                            class="h-3.5 w-3.5 text-primary-400"
                                        />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-[8px] font-semibold uppercase tracking-wide text-primary-400 sm:text-[9px]"
                                        >
                                            Agency
                                        </p>

                                        <p
                                            class="truncate text-[11px] font-medium text-primary-900 sm:text-xs"
                                        >
                                            {{ branch.agency.name }}
                                        </p>
                                    </div>

                                    <BadgeCheck
                                        v-if="branch.agency.is_verified"
                                        class="h-4 w-4 shrink-0 text-emerald-500 dark:text-emerald-300"
                                    />
                                </div>
                            </button>
                        </div>

                        <div
                            v-else
                            class="flex flex-col items-center justify-center gap-2 px-6 py-10 text-center"
                        >
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary-400 dark:bg-primary-500/10"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    class="h-5 w-5"
                                >
                                    <path
                                        d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01"
                                    />
                                </svg>
                            </div>

                            <p class="text-sm font-medium text-primary-900">
                                No branches yet
                            </p>

                            <p class="max-w-[220px] text-xs text-muted">
                                You don't have access to any branches at the
                                moment.
                            </p>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import Notification from "../ui/Notification.vue";
import MessageBell from "../ui/MessageBell.vue";
import NavbarProfileDropdown from "../ui/NavbarProfileDropdown.vue";
import ThemeToggle from "../ui/ThemeToggle.vue";
import Location from "../icons/location.vue";
import {
    BadgeCheck,
    Building2,
    Calendar,
    ChevronDown,
    Clock,
    Mail,
    Menu,
    Phone,
} from "lucide-vue-next";

import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";
import { formatRole, roleMeta } from "~/utils/user";
import { getBranchImage } from "~/types/branch.js";

const user = useAuthUser();

defineEmits<{ open: [] }>();

const branchStore = useBranchStore();

const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
});

const now = ref(new Date());

let clockTimer: ReturnType<typeof setInterval> | undefined;

const formattedDate = computed(() =>
    now.value.toLocaleDateString("en-US", {
        weekday: "long",
        month: "long",
        day: "2-digit",
        year: "numeric",
    }),
);

const formattedTime = computed(() =>
    now.value.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
    }),
);

onMounted(() => {
    clockTimer = setInterval(() => {
        now.value = new Date();
    }, 1000 * 30);
});

onUnmounted(() => {
    if (clockTimer) {
        clearInterval(clockTimer);
    }
});
</script>

<style scoped>
.skeleton-shimmer {
    background: linear-gradient(
        90deg,
        theme("colors.primary.50") 25%,
        theme("colors.primary.100") 50%,
        theme("colors.primary.50") 75%
    );
    background-size: 200% 100%;
    animation: shimmer 1.4s ease-in-out infinite;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

.branch-scroll {
    scrollbar-width: thin;
    scrollbar-color: theme("colors.primary.300") transparent;
}

.branch-scroll::-webkit-scrollbar {
    width: 5px;
}

.branch-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.branch-scroll::-webkit-scrollbar-thumb {
    background-color: theme("colors.primary.300");
    border-radius: 999px;
}

.branch-scroll::-webkit-scrollbar-thumb:hover {
    background-color: theme("colors.primary.500");
}
</style>
