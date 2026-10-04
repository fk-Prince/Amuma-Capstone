<template>
    <header
        class="min-h-[88px] sm:min-h-[104px] lg:h-[120px] px-3 sm:px-6 lg:px-8 py-4 sm:py-5 flex items-center justify-between gap-2 sm:gap-4 shrink-0 border-b border-gray-100 dark:border-white/10 bg-white dark:bg-secondary lg:rounded-2xl lg:border lg:shadow-sm"
    >
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
                class="hidden h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-primary-50 ring-2 ring-primary-100 dark:bg-white/10 dark:ring-white/10 sm:flex sm:h-16 sm:w-16 lg:h-20 lg:w-20"
            >
                <img
                    v-if="
                        branchStore.activeBranch?.image &&
                        !brokenImages.has(branchStore.activeBranch?.uuid ?? '')
                    "
                    :src="getBranchImage(branchStore.activeBranch.image)"
                    :alt="branchStore.activeBranch.name"
                    class="h-full w-full object-cover"
                    @error="
                        brokenImages.add(branchStore.activeBranch?.uuid ?? '')
                    "
                />
                <Building2 v-else class="h-8 w-8 text-primary-400" />
            </div>

            <div class="min-w-0 flex-1">
                <h1
                    class="flex items-center gap-1.5 truncate text-lg sm:text-2xl lg:text-[26px] font-bold text-gray-900 dark:text-white leading-tight"
                >
                    {{ branchStore.activeBranch?.name || "Select a branch" }}

                    <ChevronDown
                        v-if="branchStore.branches.length"
                        class="h-4 w-4 shrink-0 text-gray-400 dark:text-white/40"
                    />
                </h1>

                <p
                    class="text-xs sm:text-sm text-gray-400 mt-0.5 truncate dark:text-gray-500"
                >
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
                        <Clock
                            class="w-3.5 h-3.5 text-primary-500 shrink-0 dark:text-primary-300"
                        />
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
                <MessageBell
                    v-if="branchStore.activeBranch?.status === 'verified'"
                />
                <Notification />

                <div class="hidden lg:block">
                    <ClientOnly>
                        <ThemeToggle
                            class="text-gray-500 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/10"
                        />
                    </ClientOnly>
                </div>

                <ClientOnly>
                    <NavbarProfileDropdown
                        v-if="user"
                        :user="user"
                        :role="branchStore.activeBranch?.role_name"
                        :theme-aware="true"
                    />
                </ClientOnly>
            </template>

            <button
                type="button"
                class="shrink-0 rounded-lg p-2 text-gray-600 hover:bg-gray-50 hover:text-primary-500 dark:text-white/70 dark:hover:bg-white/10 lg:hidden dark:hover:text-primary-300"
                aria-label="Open navigation"
                @click="$emit('open')"
            >
                <Menu class="h-5 w-5" />
            </button>
        </div>
    </header>

    <BranchSelectModal />

</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref } from "vue";
import Notification from "../ui/Notification.vue";
import MessageBell from "../ui/MessageBell.vue";
import NavbarProfileDropdown from "../ui/NavbarProfileDropdown.vue";
import ThemeToggle from "../ui/ThemeToggle.vue";
import BranchSelectModal from "./BranchSelectModal.vue";
import { Building2, Calendar, ChevronDown, Clock, Menu } from "lucide-vue-next";

import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";
import { getBranchImage } from "~/types/branch.js";

const user = useAuthUser();

defineEmits<{ open: [] }>();

const branchStore = useBranchStore();

const brokenImages = reactive(new Set<string>());

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

.dark .skeleton-shimmer {
    background: linear-gradient(
        90deg,
        rgba(255, 255, 255, 0.04) 25%,
        rgba(255, 255, 255, 0.12) 50%,
        rgba(255, 255, 255, 0.04) 75%
    );
    background-size: 200% 100%;
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
