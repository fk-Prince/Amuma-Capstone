<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-350 ease-out"
            enter-from-class="opacity-0 scale-[0.98]"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-700 ease-in-out"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-[1.02]"
        >
            <div
                v-if="visible"
                class="amuma-splash fixed inset-0 z-[999] flex items-center justify-center overflow-hidden bg-[var(--splash-bg)]"
                :data-theme="forcedTheme"
                role="status"
                aria-live="polite"
            >
                <!-- Single soft glow behind the mark -->
                <div
                    class="pointer-events-none absolute inset-0 flex items-center justify-center"
                    aria-hidden="true"
                >
                    <div
                        class="h-[420px] w-[420px] rounded-full blur-[110px]"
                        style="background: var(--splash-glow)"
                    />
                </div>

                <div class="relative flex flex-col items-center gap-7 px-6 text-center">
                    <!-- Logo mark -->
                    <div
                        class="flex h-20 w-20 items-center justify-center rounded-2xl bg-[var(--splash-surface)] shadow-[var(--splash-shadow)] ring-1 ring-[var(--splash-ring)] animate-splash-breathe"
                    >
                        <img :src="logo" alt="AMUMA" class="h-11 w-11 object-contain" />
                    </div>

                    <div class="space-y-1.5">
                        <p
                            class="text-2xl font-bold tracking-wide text-[var(--splash-brand)]"
                        >
                            AMUMA
                        </p>
                        <h1 class="text-base font-medium text-[var(--splash-text)]">
                            {{ title }}
                        </h1>
                        <p v-if="subtitle" class="text-sm text-[var(--splash-subtext)]">
                            {{ subtitle }}
                        </p>
                    </div>

                    <!-- Dot loader -->
                    <div class="flex items-center gap-2" aria-hidden="true">
                        <span
                            v-for="i in 3"
                            :key="i"
                            class="h-1.5 w-1.5 rounded-full bg-[var(--splash-brand)] animate-splash-dot"
                            :style="{ animationDelay: `${(i - 1) * 0.16}s` }"
                        />
                    </div>
                </div>

                <p class="absolute bottom-8 text-[11px] text-[var(--splash-footer)]">
                    &copy; {{ currentYear }} AMUMA &middot; STI College Davao
                </p>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import logo from "assets/logo/logo.png";
import { useSplashScreen } from "~/composables/useSplashScreen";

const { visible, title, subtitle } = useSplashScreen();
const currentYear = new Date().getFullYear();

// TEMP PREVIEW ONLY — lets you force the dark variant without an OS
// toggle or app-wide theme system. Visit the page with ?theme=dark
// or ?theme=light appended to the URL. Remove this block (and the
// :data-theme binding above) once you're done previewing.
const forcedTheme = computed(() => {
    if (import.meta.client) {
        const param = new URLSearchParams(window.location.search).get("theme");
        if (param === "dark" || param === "light") return param;
    }
    return undefined;
});
</script>

<style scoped>
.amuma-splash {
    /* Light mode (default) */
    --splash-bg: #f8fafc;
    --splash-surface: #ffffff;
    --splash-brand: #3182ed;
    --splash-text: #1e293b;
    --splash-subtext: #64748b;
    --splash-footer: #94a3b8;
    --splash-ring: rgba(15, 23, 42, 0.06);
    --splash-glow: rgba(49, 130, 237, 0.14);
    --splash-shadow: 0 18px 40px -14px rgba(15, 23, 42, 0.22);
}

/* Real OS preference (used when ?theme= isn't set) */
@media (prefers-color-scheme: dark) {
    .amuma-splash:not([data-theme="light"]) {
        --splash-bg: #0b1120;
        --splash-surface: #131c2e;
        --splash-brand: #5b9df2;
        --splash-text: #e2e8f0;
        --splash-subtext: #8895a7;
        --splash-footer: #5c6b80;
        --splash-ring: rgba(255, 255, 255, 0.06);
        --splash-glow: rgba(91, 157, 242, 0.18);
        --splash-shadow: 0 18px 40px -14px rgba(0, 0, 0, 0.6);
    }
}

/* TEMP PREVIEW — forces dark regardless of OS, via ?theme=dark */
.amuma-splash[data-theme="dark"] {
    --splash-bg: #0b1120;
    --splash-surface: #131c2e;
    --splash-brand: #5b9df2;
    --splash-text: #e2e8f0;
    --splash-subtext: #8895a7;
    --splash-footer: #5c6b80;
    --splash-ring: rgba(255, 255, 255, 0.06);
    --splash-glow: rgba(91, 157, 242, 0.18);
    --splash-shadow: 0 18px 40px -14px rgba(0, 0, 0, 0.6);
}

@keyframes splash-breathe {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.045);
    }
}
.animate-splash-breathe {
    animation: splash-breathe 2.6s ease-in-out infinite;
}

@keyframes splash-dot {
    0%,
    80%,
    100% {
        transform: scale(0.6);
        opacity: 0.35;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}
.animate-splash-dot {
    animation: splash-dot 1.1s ease-in-out infinite;
}
</style>