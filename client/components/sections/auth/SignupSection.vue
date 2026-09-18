<script setup lang="ts">
import SignupForm from "~/components/forms/SignupForm.vue";
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
    <div class="relative min-h-dvh w-full bg-slate-900">
        <div class="absolute inset-0 hidden md:block overflow-hidden">
            <img
                :src="signinLogo"
                alt=""
                class="absolute inset-[-12px] h-[calc(100%+24px)] w-[calc(100%+24px)] scale-105 object-cover blur-[2px]"
            />

            <div
                class="absolute inset-0 bg-blue-950/40 mix-blend-multiply"
            ></div>

            <div
                class="absolute inset-0 bg-gradient-to-r from-blue-950/65 via-blue-900/30 to-slate-950/80"
            ></div>

            <div
                class="absolute inset-y-0 right-0 w-[48%] bg-gradient-to-r from-transparent via-slate-950/60 to-slate-950/95 backdrop-blur-[6px]"
            ></div>

            <div
                class="absolute inset-x-0 bottom-0 h-56 bg-gradient-to-t from-slate-950/90 via-slate-950/30 to-transparent"
            ></div>

            <div
                class="absolute inset-0 bg-white/[0.02] backdrop-blur-[1px]"
            ></div>
        </div>

        <div
            class="relative z-10 flex min-h-dvh w-full items-start pt-[130px] pb-8 lg:items-center lg:py-14"
        >
            <div
                class="mx-auto flex w-[94%] max-w-[1400px] items-center justify-center px-6 lg:justify-between lg:gap-10"
            >
                <div
                    class="hidden w-full max-w-[520px] flex-col gap-8 text-white lg:flex"
                >
                    <div class="space-y-4">
                        <p
                            class="inline-block border-b-2 border-blue-500 pb-1.5 text-xs text-blue-300"
                        >
                            Create account
                        </p>

                        <h1
                            class="text-3xl font-extrabold leading-[1.15] tracking-tight [text-shadow:0_2px_16px_rgba(0,0,0,0.45)] md:text-4xl xl:text-[2.75rem]"
                        >
                            Join <span class="text-blue-400">AMUMA</span> today.
                        </h1>

                        <p class="text-sm leading-relaxed text-white/70">
                            Create your account and start accessing all features
                            designed to help you manage and monitor your
                            services with ease.
                        </p>
                    </div>

                    <ul class="space-y-4">
                        <li
                            v-for="item in features"
                            :key="item.title"
                            class="flex items-start gap-3.5"
                        >
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/20 bg-white/10"
                            >
                                <component
                                    :is="item.icon"
                                    class="h-4 w-4 text-blue-300"
                                />
                            </span>

                            <div>
                                <p class="text-sm font-bold">
                                    {{ item.title }}
                                </p>
                                <p
                                    class="mt-0.5 text-xs leading-snug text-white/60"
                                >
                                    {{ item.description }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div
                    class="w-full max-w-[480px] shrink-0 rounded-[20px] border border-white/10 bg-white/95 px-6 py-8 shadow-[0_30px_70px_-15px_rgba(0,0,0,0.65)] backdrop-blur-xl sm:px-8 dark:bg-secondary/95"
                >
                    <div class="mb-7 text-center">
                        <h2
                            class="text-[1.85rem] font-extrabold text-slate-900 dark:text-white"
                        >
                            Create an Account
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
