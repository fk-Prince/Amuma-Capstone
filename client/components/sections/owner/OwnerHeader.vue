<template>
    <header
        class="min-h-[88px] sm:min-h-[104px] lg:h-[120px] px-3 sm:px-6 lg:px-8 py-4 sm:py-5 flex items-center justify-between gap-2 sm:gap-4 shrink-0 border-b border-gray-100 dark:border-white/10 bg-white dark:bg-secondary"
    >
        <button
            type="button"
            class="-ml-1 shrink-0 rounded-lg p-2 text-gray-600 hover:bg-gray-50 hover:text-primary-500 dark:text-white/70 dark:hover:bg-white/10 lg:hidden"
            aria-label="Open navigation"
            @click="emit('open')"
        >
            <Menu class="h-5 w-5" />
        </button>

        <div class="min-w-0 flex-1">
            <h1
                class="text-lg sm:text-2xl lg:text-[26px] font-bold text-gray-900 dark:text-white leading-tight truncate"
            >
                {{ pageTitle }}
            </h1>

            <p class="text-xs sm:text-sm text-gray-400 mt-0.5 truncate">
                {{ pageSubtitle }}
            </p>

            <div
                class="flex items-center gap-2 sm:gap-3 mt-2 text-[11px] sm:text-xs text-gray-500 dark:text-gray-400"
            >
                <span class="flex items-center gap-1.5 whitespace-nowrap">
                    <Calendar class="w-3.5 h-3.5 text-primary-500 shrink-0" />
                    <span class="hidden sm:inline">{{ formattedDate }}</span>
                    <span class="sm:hidden">{{ formattedShortDate }}</span>
                </span>

                <span class="w-px h-3 bg-gray-200 dark:bg-white/10 shrink-0" />

                <span class="flex items-center gap-1.5 whitespace-nowrap">
                    <Clock class="w-3.5 h-3.5 text-primary-500 shrink-0" />
                    {{ formattedTime }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1 sm:gap-6 lg:gap-8 shrink-0">
            <ClientOnly>
                <ThemeToggle
                    class="text-gray-500 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/10"
                />
            </ClientOnly>

            <Notification />
            <NavbarProfileDropdown v-if="user" :user="user" :theme-aware="true" />
        </div>
    </header>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRoute } from "vue-router";
import { Calendar, Clock, Menu } from "lucide-vue-next";
import NavbarProfileDropdown from "~/components/ui/NavbarProfileDropdown.vue";
import Notification from "~/components/ui/Notification.vue";
import ThemeToggle from "~/components/ui/ThemeToggle.vue";
import { useAuthUser } from "~/composables/useAuthUser";

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
    "/app/owner/subscription": {
        title: "Subscriptions",
        subtitle: "Review and manage subscription requests across every branch.",
    },
    "/app/owner/plans": {
        title: "Plans",
        subtitle: "Manage the subscription plans agencies can choose from.",
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
