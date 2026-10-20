<template>
    <div class="flex shrink-0 items-center gap-2 sm:gap-3 lg:block">
        <header
<<<<<<< main
=======
            :class="{ 'di-header': intro }"
>>>>>>> branch
            class="min-w-0 flex-1 min-h-[64px] sm:min-h-[96px] lg:h-[120px] px-3 sm:px-6 lg:px-8 py-3 sm:py-5 flex items-center justify-between gap-2 sm:gap-4 rounded-3xl lg:rounded-2xl border border-gray-100 dark:border-white/10 bg-white dark:bg-secondary max-lg:bg-gradient-to-br max-lg:from-white max-lg:to-primary-50/70 dark:max-lg:from-secondary dark:max-lg:to-secondary-800 shadow-[0_8px_30px_rgba(15,23,42,0.06)] lg:shadow-sm"
        >
            <div class="min-w-0 flex-1">
                <h1
                    class="text-lg sm:text-2xl lg:text-[26px] font-bold text-gray-900 dark:text-white leading-tight truncate"
                >
                    {{ pageTitle }}
                </h1>

                <p class="text-xs sm:text-sm text-gray-400 mt-0.5 truncate dark:text-gray-400">
                    {{ pageSubtitle }}
                </p>

                <div
                    class="hidden sm:flex items-center gap-2 sm:gap-3 mt-2 text-[11px] sm:text-xs text-gray-500 dark:text-gray-400"
                >
                    <span class="flex items-center gap-1.5 whitespace-nowrap">
                        <Calendar
                            class="w-3.5 h-3.5 text-primary-500 shrink-0 dark:text-primary-300"
                        />
                        <span class="hidden sm:inline">{{ formattedDate }}</span>
                        <span class="sm:hidden">{{ formattedShortDate }}</span>
                    </span>

                    <span class="w-px h-3 bg-gray-200 dark:bg-white/10 shrink-0" />

                    <span class="flex items-center gap-1.5 whitespace-nowrap">
                        <Clock
                            class="w-3.5 h-3.5 text-primary-500 shrink-0 dark:text-primary-300"
                        />
                        {{ formattedTime }}
                    </span>
                </div>
            </div>

            <div
                class="flex items-center gap-2 sm:gap-6 lg:gap-8 shrink-0 max-lg:[&>div>button]:h-10 max-lg:[&>div>button]:w-10 max-lg:[&>div>button]:rounded-full max-lg:[&>div>button]:bg-gray-100/80 dark:max-lg:[&>div>button]:bg-white/10 max-lg:[&>div>button]:ring-1 max-lg:[&>div>button]:ring-black/5 dark:max-lg:[&>div>button]:ring-white/10 max-lg:[&>button]:h-10 max-lg:[&>button]:w-10 max-lg:[&>button]:rounded-full max-lg:[&>button]:bg-gray-100/80 dark:max-lg:[&>button]:bg-white/10 max-lg:[&>button]:ring-1 max-lg:[&>button]:ring-black/5 dark:max-lg:[&>button]:ring-white/10"
            >
                <Notification />

                <div class="hidden lg:block">
                    <ClientOnly>
                        <ThemeToggle
                            class="text-gray-500 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/10"
                        />
                    </ClientOnly>
                </div>

                <div class="hidden lg:block">
                    <ClientOnly>
                        <NavbarProfileDropdown
                            v-if="user"
                            :user="user"
                            :theme-aware="true"
                        />
                    </ClientOnly>
                </div>
            </div>
        </header>

        <!-- Profile: its own floating circle next to the header on phones and tablets -->
<<<<<<< main
        <div v-if="user" class="shrink-0 lg:hidden">
=======
        <div v-if="user" class="shrink-0 lg:hidden" :class="{ 'di-pop': intro }">
>>>>>>> branch
            <ClientOnly>
                <NavbarProfileDropdown
                    :user="user"
                    :theme-aware="true"
                    avatar-only
                />
            </ClientOnly>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useDashboardIntro } from "~/composables/useDashboardIntro";
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRoute } from "vue-router";
import { Calendar, Clock } from "lucide-vue-next";
import NavbarProfileDropdown from "~/components/ui/NavbarProfileDropdown.vue";
import Notification from "~/components/ui/Notification.vue";
import ThemeToggle from "~/components/ui/ThemeToggle.vue";
import { useAuthUser } from "~/composables/useAuthUser";

<<<<<<< main
=======
const intro = useDashboardIntro();
>>>>>>> branch

const user = useAuthUser();

const emit = defineEmits<{
    open: [];
}>();

const route = useRoute();

const pageTitles: Record<string, { title: string; subtitle: string }> = {
    "/app/owner/dashboard": {
        title: "Dashboard",
        subtitle:
            "Subscriptions, verification, payments, and recent activity across every agency.",
    },
    "/app/owner/verification": {
        title: "Verification",
        subtitle: "Review agency and branch documents before a subscription goes live.",
    },
    "/app/owner/branches": {
        title: "Subscription",
        subtitle: "Browse every subscription already approved, and its billing status.",
    },
    "/app/owner/plans": {
        title: "Plans",
        subtitle: "Manage the subscription plans agencies can choose from.",
    },
    "/profile": {
        title: "My Profile",
        subtitle: "Update your personal details, contact information, and preferences.",
    },
};

const currentPath = computed(() =>
    route.path.length > 1 ? route.path.replace(/\/+$/, "") : route.path,
);

const pageTitle = computed(
    () => pageTitles[currentPath.value]?.title ?? "Dashboard",
);

const pageSubtitle = computed(
    () =>
        pageTitles[currentPath.value]?.subtitle ??
        "Manage AMUMA agencies, branches, and subscriptions.",
);

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

const formattedShortDate = computed(() =>
    now.value.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
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