<script setup lang="ts">
import SigninForm from "~/components/forms/SigninForm.vue";
import signinLogo from "~/assets/logo/signinLogo2.png";
import { Heart, ShieldCheck, Users } from "lucide-vue-next";

const highlights = [
    { icon: ShieldCheck, label: "Better Healthcare Access" },
    { icon: Users, label: "Stronger Communities" },
    { icon: Heart, label: "Healthier Tomorrows" },
];
</script>

<template>
    <div
        class="relative -mt-[90px] min-h-dvh w-full overflow-hidden bg-slate-950"
    >
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
            class="relative z-10 mx-auto flex min-h-screen w-[94%] max-w-[1400px] items-center justify-center px-6 pt-32 pb-12 sm:px-15 lg:justify-start"
        >
            <div
                class="grid w-full grid-cols-1 items-center gap-17 md:grid-cols-[1fr_560px]"
            >
                <div
                    class="hidden w-full max-w-[520px] flex-col gap-10 text-white lg:flex"
                >
                    <div class="space-y-4">
                        <p
                            class="inline-block border-b-2 border-blue-500 pb-1.5 text-xs text-blue-300"
                        >
                            Welcome back
                        </p>

                        <h1
                            class="text-3xl font-extrabold leading-[1.15] tracking-tight [text-shadow:0_2px_16px_rgba(0,0,0,0.45)] md:text-4xl xl:text-[2.75rem]"
                        >
                            We're here, whenever
                            <span class="text-blue-400">you need us.</span>
                        </h1>

                        <p class="text-sm leading-relaxed text-white/70">
                            Sign in to your AMUMA account to continue accessing
                            your AMUMA dashboard.
                        </p>
                    </div>

                    <div class="space-y-5">
                        <div class="h-px w-full bg-white/20"></div>

                        <div class="flex w-full flex-nowrap items-center">
                            <div
                                v-for="(item, index) in highlights"
                                :key="item.label"
                                class="flex min-w-0 flex-1 items-center gap-3 pr-5"
                                :class="
                                    index > 0
                                        ? 'border-l border-white/15 pl-5'
                                        : ''
                                "
                            >
                                <span
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 backdrop-blur-sm"
                                >
                                    <component
                                        :is="item.icon"
                                        class="h-4 w-4 text-white/80"
                                    />
                                </span>

                                <p
                                    class="min-w-0 text-xs font-semibold leading-tight"
                                >
                                    {{ item.label }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="w-full max-w-[480px] shrink-0 rounded-[20px] border border-white/10 bg-white/95 px-6 py-8 shadow-[0_30px_70px_-15px_rgba(0,0,0,0.65)] backdrop-blur-xl sm:px-8 dark:bg-secondary/95"
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
</template>
