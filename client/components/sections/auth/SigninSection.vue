<script setup lang="ts">
import BrandLogo from "~/components/ui/BrandLogo.vue";
import SigninForm from "~/components/forms/SigninForm.vue";
import ThemeToggle from "~/components/ui/ThemeToggle.vue";
import signinLogo from "~/assets/logo/signinLogo2.png";
import { Heart, ShieldCheck, Users } from "lucide-vue-next";

const highlights = [
    { icon: ShieldCheck, label: "Better Healthcare Access" },
    { icon: Users, label: "Stronger Communities" },
    { icon: Heart, label: "Healthier Tomorrows" },
];
</script>

<template>
    <div class="relative min-h-dvh w-full overflow-hidden bg-[#EEF3FB] dark:bg-slate-950">
        <div class="absolute inset-0 hidden md:block overflow-hidden">
            <img
                :src="signinLogo"
                alt=""
                class="absolute inset-[-12px] h-[calc(100%+24px)] w-[calc(100%+24px)] scale-105 object-cover blur-[1px] dark:blur-[2px]"
            />

            <!-- Light mode wash: dimmed evenly so the photo reads as a soft backdrop, not a busy image -->
            <div class="absolute inset-0 bg-[#EEF3FB]/70 dark:hidden"></div>
            <div
                class="absolute inset-0 bg-gradient-to-r from-[#EEF3FB]/35 via-transparent to-[#EEF3FB]/90 dark:hidden"
            ></div>
            <div
                class="absolute inset-y-0 right-0 w-[48%] bg-gradient-to-r from-transparent via-[#EEF3FB]/75 to-[#EEF3FB]/95 backdrop-blur-[6px] dark:hidden"
            ></div>
            <div
                class="absolute inset-x-0 bottom-0 h-56 bg-gradient-to-t from-[#EEF3FB]/90 via-[#EEF3FB]/40 to-transparent dark:hidden"
            ></div>

            <!-- Dark mode wash: moody navy (original look) -->
            <div
                class="absolute inset-0 hidden dark:block dark:bg-blue-950/40 dark:mix-blend-multiply"
            ></div>
            <div
                class="absolute inset-0 hidden dark:block dark:bg-gradient-to-r dark:from-blue-950/65 dark:via-blue-900/30 dark:to-slate-950/80"
            ></div>
            <div
                class="absolute inset-y-0 right-0 hidden w-[48%] dark:block dark:bg-gradient-to-r dark:from-transparent dark:via-slate-950/60 dark:to-slate-950/95 dark:backdrop-blur-[6px]"
            ></div>
            <div
                class="absolute inset-x-0 bottom-0 hidden h-56 dark:block dark:bg-gradient-to-t dark:from-slate-950/90 dark:via-slate-950/30 dark:to-transparent"
            ></div>
            <div
                class="absolute inset-0 hidden dark:block dark:bg-white/[0.02] dark:backdrop-blur-[1px]"
            ></div>
        </div>

        <div class="relative z-10 flex min-h-dvh w-full flex-col">
            <!-- Minimal top bar: replaces the site header on auth pages -->
            <div class="flex w-full items-center justify-between px-6 py-6 sm:px-10">
                <NuxtLink to="/" aria-label="AMUMA home">
                    <BrandLogo icon-class="h-10 w-10" />
                </NuxtLink>
                <ThemeToggle class="text-secondary dark:text-white" />
            </div>

            <div
                class="mx-auto flex w-[94%] max-w-[1400px] flex-1 items-center justify-center px-6 pb-8 sm:pb-12 lg:justify-start"
            >
                <div
                    class="grid w-full grid-cols-1 items-center justify-items-center gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,480px)] lg:justify-items-stretch lg:gap-16"
                >
                    <div class="hidden w-full max-w-[520px] flex-col gap-10 lg:flex">
                        <div class="space-y-4">
                            <p
                                class="inline-block border-b-2 border-primary-400 pb-1.5 text-xs text-primary-600 dark:border-blue-500 dark:text-blue-300"
                            >
                                Welcome back
                            </p>

                            <h1
                                class="text-3xl font-extrabold leading-[1.15] tracking-tight text-secondary md:text-4xl xl:text-[2.75rem] dark:text-white dark:[text-shadow:0_2px_16px_rgba(0,0,0,0.45)]"
                            >
                                We're here, whenever
                                <span class="text-primary-600 dark:text-blue-400">you need us.</span>
                            </h1>

                            <p class="text-sm leading-relaxed text-muted dark:text-white/70">
                                Sign in to your AMUMA account to continue accessing
                                your AMUMA dashboard.
                            </p>
                        </div>

                        <div class="space-y-5">
                            <div class="h-px w-full bg-secondary-100 dark:bg-white/20"></div>

                            <div class="flex w-full flex-nowrap items-center">
                                <div
                                    v-for="(item, index) in highlights"
                                    :key="item.label"
                                    class="flex min-w-0 flex-1 items-center gap-3 pr-5"
                                    :class="
                                        index > 0
                                            ? 'border-l border-muted-light pl-5 dark:border-white/15'
                                            : ''
                                    "
                                >
                                    <span
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-50 backdrop-blur-sm dark:bg-white/10"
                                    >
                                        <component
                                            :is="item.icon"
                                            class="h-4 w-4 text-primary-600 dark:text-white/80"
                                        />
                                    </span>

                                    <p
                                        class="min-w-0 text-xs font-semibold leading-tight text-secondary dark:text-white"
                                    >
                                        {{ item.label }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex w-full max-w-[480px] flex-col lg:ml-auto">
                    <div
                        class="w-full max-w-[480px] shrink-0 rounded-[20px] border border-muted-light bg-white px-5 py-8 shadow-sm sm:px-8 lg:ml-auto dark:border-white/10 dark:bg-secondary/95 dark:shadow-[0_30px_70px_-15px_rgba(0,0,0,0.65)] dark:backdrop-blur-xl"
                    >
                        <div class="mb-7 text-center">
                            <h2
                                class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white"
                            >
                                Welcome Back
                            </h2>

                            <p
                                class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                            >
                                Sign in to your
                                <span
                                    class="font-semibold text-blue-600 dark:text-blue-400"
                                >
                                    AMUMA
                                </span>
                                account
                            </p>
                        </div>

                        <SigninForm />
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</template>