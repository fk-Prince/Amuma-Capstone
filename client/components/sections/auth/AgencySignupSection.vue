<script setup lang="ts">
import SignupForm from "~/components/forms/SignupForm.vue";
import ThemeToggle from "~/components/ui/ThemeToggle.vue";
import { Building2, Rocket, ShieldCheck, GitBranch } from "lucide-vue-next";

const highlights = [
    { icon: ShieldCheck, label: "Secure & encrypted" },
    { icon: Rocket, label: "Set up in minutes" },
    { icon: GitBranch, label: "Multi-branch ready" },
];
</script>

<template>
    <div
        class="relative flex min-h-dvh w-full flex-col overflow-hidden bg-[#EEF3FB] dark:bg-secondary-950"
    >
        <div
            class="pointer-events-none absolute inset-0 overflow-hidden"
            aria-hidden="true"
        >
            <div
                class="absolute inset-0 opacity-[0.55] [background-image:radial-gradient(circle,#a9c0e8_1.5px,transparent_1.5px)] [background-size:26px_26px] [mask-image:radial-gradient(ellipse_90%_90%_at_50%_10%,#000_25%,transparent_85%)] dark:opacity-[0.07] dark:[background-image:radial-gradient(circle,#5b6b8c_1px,transparent_1px)]"
            ></div>

            <div
                class="absolute -top-36 -left-28 h-96 w-96 rounded-full bg-primary-200/50 blur-3xl dark:bg-primary-500/15"
            ></div>
            <div
                class="absolute -bottom-36 -right-24 h-[26rem] w-[26rem] rounded-full bg-accent-200/50 blur-3xl dark:bg-accent-500/10"
            ></div>
            <div
                class="absolute top-1/4 right-[8%] h-64 w-64 rounded-full bg-primary-100/60 blur-3xl dark:bg-primary-500/10"
            ></div>
            <div
                class="absolute bottom-1/4 left-[8%] h-64 w-64 rounded-full bg-accent-100/60 blur-3xl dark:bg-accent-500/10"
            ></div>
        </div>

        <div
            class="relative z-10 flex w-full items-center justify-between px-6 py-6 sm:px-10"
        >
            <NuxtLink to="/" aria-label="AMUMA home">
                <BrandLogo icon-class="h-8 w-8" text-class="text-lg" />
            </NuxtLink>
            <ThemeToggle class="text-secondary dark:text-white" />
        </div>

        <div
            class="relative z-10 flex w-full flex-1 items-center justify-center px-6 pb-16"
        >
            <div class="flex w-full max-w-[500px] flex-col items-center">
                <div
                    class="w-full rounded-[20px] border border-muted-light bg-white px-6 py-9 shadow-sm sm:px-8 dark:border-white/10 dark:bg-secondary"
                >
                    <div class="mb-7 flex flex-col items-center text-center">
                        <span
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary dark:bg-primary-500/10 dark:text-primary-300"
                        >
                            <Building2 class="h-6 w-6" />
                        </span>

                        <p
                            class="mb-1.5 text-xs font-semibold uppercase tracking-widest text-primary dark:text-primary-300"
                        >
                            Agency Registration
                        </p>

                        <h1
                            class="text-2xl font-extrabold tracking-tight text-secondary dark:text-white"
                        >
                            Register your agency
                        </h1>

                        <p class="mt-1.5 text-sm text-muted dark:text-gray-400">
                            Create your account, then choose a plan to set up
                            your caregiving agency on AMUMA.
                        </p>
                    </div>

                    <SignupForm portal="agency" />
                </div>

                <div
                    class="mt-6 flex flex-wrap items-center justify-center gap-x-6 gap-y-2"
                >
                    <div
                        v-for="item in highlights"
                        :key="item.label"
                        class="flex items-center gap-1.5 text-xs font-medium text-muted dark:text-gray-500"
                    >
                        <component
                            :is="item.icon"
                            class="h-3.5 w-3.5 text-primary-500 dark:text-primary-400"
                        />
                        {{ item.label }}
                    </div>
                </div>

                <NuxtLink
                    to="/auth/signup"
                    class="mt-4 flex items-center gap-1.5 text-xs font-medium text-muted outline-none hover:text-secondary hover:underline dark:text-gray-400 dark:hover:text-white"
                >
                    Looking for family access? Create a family account
                </NuxtLink>
            </div>
        </div>
    </div>
</template>