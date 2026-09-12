<script setup lang="ts">
import logo from "~/assets/logo/logo.png";

withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
    }>(),
    {
        title: "Signing you out",
        subtitle: "See you again soon",
    },
);

const currentYear = new Date().getFullYear();
</script>

<template>
    <Teleport to="body">
    <Transition
        enter-active-class="transition-opacity duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            class="fixed inset-0 z-[100] flex items-center justify-center overflow-hidden bg-secondary-950"
        >
            <div
                class="pointer-events-none absolute inset-0 overflow-hidden"
                aria-hidden="true"
            >
                <div
                    class="absolute -top-40 left-1/2 h-[520px] w-[520px] -translate-x-1/2 rounded-full bg-primary-500/15 blur-[140px]"
                />
                <div
                    class="absolute -bottom-40 -right-40 h-[420px] w-[420px] rounded-full bg-accent-500/10 blur-[140px]"
                />
            </div>

            <div
                class="relative z-10 flex flex-col items-center gap-4 px-6 text-center"
            >
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl border border-white/10 bg-white/5 shadow-[0_20px_50px_-15px_rgba(0,0,0,0.6)]"
                >
                    <img :src="logo" alt="" class="h-9 w-9 object-contain" />
                </div>

                <p
                    class="text-lg font-extrabold tracking-tight text-primary-300"
                >
                    AMUMA
                </p>

                <div class="space-y-1">
                    <p class="text-base font-semibold text-white">
                        {{ title }}
                    </p>
                    <p class="text-sm text-gray-400">{{ subtitle }}</p>
                </div>

                <div class="mt-2 flex items-center gap-1.5">
                    <span
                        v-for="i in 3"
                        :key="i"
                        class="h-1.5 w-1.5 animate-bounce rounded-full bg-primary-400"
                        :style="{ animationDelay: `${(i - 1) * 0.15}s` }"
                    />
                </div>
            </div>

            <p
                class="absolute bottom-6 left-1/2 -translate-x-1/2 text-xs text-gray-500"
            >
                © {{ currentYear }} AMUMA. All rights reserved.
            </p>
        </div>
    </Transition>
    </Teleport>
</template>
