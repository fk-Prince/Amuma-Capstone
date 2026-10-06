<script setup lang="ts">
import BrandLogo from "~/components/ui/BrandLogo.vue";
import signinLogo from "~/assets/logo/signinLogo2.png";
import ThemeToggle from "~/components/ui/ThemeToggle.vue";
</script>

<template>
    <div class="relative min-h-dvh w-full overflow-hidden bg-slate-950">
        <div class="absolute inset-0 hidden overflow-hidden md:block">
            <img
                :src="signinLogo"
                alt=""
                class="absolute inset-[-12px] h-[calc(100%+24px)] w-[calc(100%+24px)] scale-105 object-cover blur-[2px]"
            />

            <div class="absolute inset-0 bg-blue-950/40 mix-blend-multiply"></div>

            <div
                class="absolute inset-0 bg-gradient-to-r from-blue-950/65 via-blue-900/30 to-slate-950/80"
            ></div>

            <div class="absolute inset-0 bg-slate-950/40 backdrop-blur-[6px]"></div>
        </div>

        <div class="relative z-10 flex min-h-dvh w-full flex-col">
            <!-- Minimal top bar, the same as the sign-in pages -->
            <div class="flex w-full items-center justify-between px-6 py-6 sm:px-10">
                <NuxtLink to="/" aria-label="AMUMA home">
                    <BrandLogo icon-class="h-10 w-10" />
                </NuxtLink>
                <ThemeToggle class="text-secondary dark:text-white" />
            </div>

            <div
                class="mx-auto flex w-[94%] flex-1 items-center justify-center px-2 pb-8 sm:px-6"
            >
                <div
                    class="w-full max-w-[440px] rounded-[20px] border border-white/10 bg-white/95 px-5 py-8 shadow-[0_30px_70px_-15px_rgba(0,0,0,0.65)] backdrop-blur-xl sm:px-8 dark:bg-secondary/95"
                >
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
