<template>
    <header
        class="min-h-[64px] sm:min-h-[72px] lg:h-[80px] mx-3 mt-3 sm:mx-4 sm:mt-4 lg:mx-0 lg:mt-4 lg:mr-4 px-3 sm:px-5 lg:px-6 py-2.5 sm:py-3 flex items-center justify-between gap-2 sm:gap-4 shrink-0 rounded-2xl bg-white dark:bg-secondary-900 shadow-[0_10px_28px_-16px_rgba(15,23,42,0.15)] ring-1 ring-black/[0.04] dark:ring-white/[0.06]"
    >
        <button
            type="button"
            class="-ml-1 shrink-0 rounded-lg p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 hover:text-primary-500 lg:hidden"
            aria-label="Open navigation"
            @click="emit('open')"
        >
            <Menu class="h-5 w-5" />
        </button>

        <div class="min-w-0 flex-1">
            <h1
                class="text-base sm:text-lg lg:text-xl font-bold text-gray-900 dark:text-white leading-tight truncate"
            >
                {{ pageTitle }}
            </h1>

            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">
                {{ pageSubtitle }}
            </p>
        </div>

        <div class="flex items-center gap-1 sm:gap-6 lg:gap-8 shrink-0">
            <ThemeToggle class="text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5" />
            <Notification />
            <NavbarProfileDropdown v-if="user" :user="user" :theme-aware="true" />
        </div>
    </header>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import { Menu } from "lucide-vue-next";
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
</script>