<template>
    <div
        class="flex items-center justify-center px-6 py-16"
        :class="
            fullPage
                ? 'fixed inset-0 z-50 bg-[#EEF3FB] dark:bg-surface'
                : 'flex-1 rounded-lg bg-white dark:bg-secondary'
        "
    >
        <NuxtLink
            v-if="fullPage"
            to="/"
            class="absolute left-6 top-6 inline-flex"
            aria-label="AMUMA home"
        >
            <BrandLogo />
        </NuxtLink>

        <div class="max-w-md text-center">
            <div
                class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-amber-50 text-amber-500 dark:bg-amber-500/10 dark:text-amber-400"
            >
                <Lock class="h-7 w-7" />
            </div>

            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ title ?? "Unauthorized" }}
            </h1>

            <p class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                {{
                    message ??
                    (fullPage
                        ? "You don't have access to this page."
                        : "You don't have permission to view this page. Ask your branch owner for access if you need it.")
                }}
            </p>

            <NuxtLink
                v-if="!hideAction"
                :to="
                    fullPage ? '/' : `/app/branches/${route.params.uuid}/dashboard`
                "
                class="mt-6 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-600"
            >
                {{ fullPage ? "Home" : "Back to dashboard" }}
            </NuxtLink>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Lock } from "lucide-vue-next";
import BrandLogo from "~/components/ui/BrandLogo.vue";

defineProps<{
    fullPage?: boolean;
    title?: string;
    message?: string;
    hideAction?: boolean;
}>();

const route = useRoute();
</script>
