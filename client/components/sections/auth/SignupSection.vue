<script setup lang="ts">
import BrandLogo from "~/components/ui/BrandLogo.vue";
import SignupForm from "~/components/forms/SignupForm.vue";
import ThemeToggle from "~/components/ui/ThemeToggle.vue";
import signinLogo from "~/assets/logo/signinLogo2.png";
import { ShieldCheck, Sparkles, UsersRound } from "lucide-vue-next";

const features = [
    {
        icon: Sparkles,
        title: "Quick & Easy Registration",
        description: "Set up your account in just a few simple steps.",
    },
    {
        icon: UsersRound,
        title: "Dedicated Support",
        description: "Get assistance whenever you need help.",
    },
    {
        icon: ShieldCheck,
        title: "Trusted & Secure",
        description:
            "Your personal information is protected with enterprise-grade security.",
    },
];
</script>

<template>
    <div class="fit-screen-height relative min-h-dvh w-full overflow-hidden bg-[#EEF3FB] dark:bg-slate-950">
        <div class="absolute inset-0 hidden md:block overflow-hidden">
            <img
                :src="signinLogo"
                alt=""
                class="absolute inset-[-12px] h-[calc(100%+24px)] w-[calc(100%+24px)] scale-105 object-cover blur-[1px] dark:blur-[2px]"
            />

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
            <div class="flex w-full items-center justify-between px-6 py-6 sm:px-10">
                <NuxtLink to="/" aria-label="AMUMA home">
                    <BrandLogo icon-class="h-10 w-10" />
                </NuxtLink>
                <ThemeToggle class="text-secondary dark:text-white" />
            </div>

            <div
                class="mx-auto flex w-[94%] max-w-[1400px] flex-1 items-center justify-center px-6 pb-8 lg:justify-between lg:gap-10 lg:pb-6"
            >
                <div
                    class="hidden w-full max-w-[520px] flex-col gap-8 text-secondary lg:flex dark:text-white"
                >
                    <div class="space-y-4">
                        <p
                            class="inline-block border-b-2 border-primary-400 pb-1.5 text-xs text-primary-600 dark:border-blue-500 dark:text-blue-300"
                        >
                            AMUMA account
                        </p>

                        <h1
                            class="text-3xl font-extrabold leading-[1.15] tracking-tight md:text-4xl xl:text-[2.75rem] dark:[text-shadow:0_2px_16px_rgba(0,0,0,0.45)]"
                        >
                            Join
                            <span class="text-primary-600 dark:text-blue-400">AMUMA</span>
                            today.
                        </h1>

                        <p class="text-sm leading-relaxed text-muted dark:text-white/70">
                            Create your AMUMA account to book care and stay
                            connected with your loved one's care.
                        </p>
                    </div>

                    <ul class="space-y-4">
                        <li
                            v-for="item in features"
                            :key="item.title"
                            class="flex items-start gap-3.5"
                        >
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-primary-100 bg-primary-50 dark:border-white/20 dark:bg-white/10"
                            >
                                <component
                                    :is="item.icon"
                                    class="h-4 w-4 text-primary-600 dark:text-blue-300"
                                />
                            </span>

                            <div>
                                <p class="text-sm font-bold">
                                    {{ item.title }}
                                </p>
                                <p
                                    class="mt-0.5 text-xs leading-snug text-muted dark:text-white/60"
                                >
                                    {{ item.description }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div
                    class="w-full max-w-[480px] shrink-0 rounded-[20px] border border-muted-light bg-white px-5 py-8 shadow-sm sm:px-8 dark:border-white/10 dark:bg-secondary/95 dark:shadow-[0_30px_70px_-15px_rgba(0,0,0,0.65)] dark:backdrop-blur-xl"
                >
                    <div class="mb-7 text-center">
                        <h2
                            class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white"
                        >
                            Create an AMUMA Account
                        </h2>
                        <p
                            class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                        >
                            Join
                            <span
                                class="font-semibold text-blue-600 dark:text-blue-400"
                                >AMUMA</span
                            >
                            today
                        </p>
                    </div>

                    <SignupForm />

                </div>
                <!-- <SignupForm /> -->
            </div>
        </div>
    </div>
</template>