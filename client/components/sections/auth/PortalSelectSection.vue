<script setup lang="ts">
import portalBg from "~/assets/images/portalselection-bg.png";
import {
    Stethoscope,
    HeartHandshake,
    ArrowRight,
    Sparkles,
} from "lucide-vue-next";

const portals = [
    {
        key: "staff",
        title: "Staff Portal",
        description:
            "For agency owners, branch managers, admission staff, cashiers, nurses, and caregivers.",
        icon: Stethoscope,
        to: "/auth/staff/signin",
        signup: "/auth/agency/signup",
        signupText: "Want to subscribe your agency?",
        signupLabel: "Create an account",
        chips: [
            "Agency owners",
            "Branch managers",
            "Admission",
            "Cashiers",
            "Nurses",
            "Caregivers",
        ],
        badge: "bg-accent-50 text-accent-600 group-hover:bg-accent group-hover:text-white group-focus-visible:bg-accent group-focus-visible:text-white dark:bg-accent-500/10 dark:text-accent-400 dark:group-hover:bg-accent-500",
        chipHover:
            "group-hover:bg-accent-50 group-hover:text-accent-700 dark:group-hover:bg-accent-500/10 dark:group-hover:text-accent-300",
        border: "hover:border-accent-400 focus-visible:border-accent-400 dark:hover:border-accent-500/60",
        spotlight: "bg-accent-300/50 dark:bg-accent-500/20",
        cta: "text-accent-600 dark:text-accent-400",
        ctaBg: "bg-accent-50 group-hover:bg-accent group-hover:text-white dark:bg-accent-500/10",
    },
    {
        key: "family",
        title: "Client Portal",
        description:
            "For clients and regular users booking care and staying connected with their loved one's care.",
        icon: HeartHandshake,
        to: "/auth/client/signin",
        chips: [],
        signup: "/auth/signup",
        badge: "bg-light text-primary group-hover:bg-primary group-hover:text-white group-focus-visible:bg-primary group-focus-visible:text-white dark:bg-primary-500/10 dark:text-primary-300 dark:group-hover:bg-primary-500",
        chipHover:
            "group-hover:bg-light group-hover:text-primary-700 dark:group-hover:bg-primary-500/10 dark:group-hover:text-primary-300",
        border: "hover:border-primary-400 focus-visible:border-primary-400 dark:hover:border-primary-500/60",
        spotlight: "bg-primary-300/50 dark:bg-primary-500/20",
        cta: "text-primary-600 dark:text-primary-400",
        ctaBg: "bg-light group-hover:bg-primary group-hover:text-white dark:bg-primary-500/10",
    },
];
</script>

<template>
    <div
        class="relative flex min-h-dvh w-full items-center justify-center overflow-hidden bg-[#EEF3FB] px-6 py-20 dark:bg-secondary-950"
    >
        <!-- Decorative layer: background photo, already carries the brand's blue waves -->
        <div
            class="pointer-events-none absolute inset-0 overflow-hidden"
            aria-hidden="true"
        >
            <img
                :src="portalBg"
                alt=""
                class="absolute inset-0 h-full w-full object-cover object-center dark:opacity-25"
            />

            <!-- Soft fade at the very edges so it blends into the page on any screen size -->
            <div
                class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-[#EEF3FB] dark:to-secondary-950"
            ></div>
            <div
                class="absolute inset-0 bg-gradient-to-t from-transparent via-transparent to-[#EEF3FB]/70 dark:to-secondary-950/60"
            ></div>
        </div>

        <div
            class="relative z-10 flex w-full max-w-[920px] flex-col items-center text-center"
        >
            <span
                class="inline-flex items-center gap-1.5 rounded-full bg-light px-3 py-1 text-xs font-semibold text-primary dark:bg-primary-500/10 dark:text-primary-300"
            >
                <Sparkles class="h-3.5 w-3.5" />
                Welcome to AMUMA
            </span>

            <h1
                class="mt-4 font-display text-3xl font-bold tracking-tight text-secondary md:text-4xl dark:text-white"
            >
                Which portal do you need?
            </h1>

            <p
                class="mt-3 max-w-lg text-sm leading-relaxed text-muted dark:text-gray-400"
            >
                Choose the portal that matches your account so we can take you
                to the right sign in.
            </p>

            <div class="mt-12 grid w-full grid-cols-1 gap-6 sm:grid-cols-2">
                <NuxtLink
                    v-for="portal in portals"
                    :key="portal.key"
                    :to="portal.to"
                    class="group relative flex flex-col items-start gap-4 overflow-hidden rounded-2xl border-2 border-transparent bg-white p-8 text-left shadow-sm outline-none ring-1 ring-muted-light transition-all duration-300 hover:shadow-xl focus-visible:shadow-xl dark:bg-secondary dark:ring-white/10"
                    :class="portal.border"
                >
                    <!-- Corner spotlight, fades in on hover/focus -->
                    <span
                        class="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full opacity-0 blur-2xl transition-opacity duration-500 group-hover:opacity-100 group-focus-visible:opacity-100"
                        :class="portal.spotlight"
                        aria-hidden="true"
                    ></span>

                    <span
                        class="relative flex h-12 w-12 items-center justify-center rounded-xl transition-all duration-300 group-hover:scale-110 group-hover:rotate-3 group-focus-visible:scale-110"
                        :class="portal.badge"
                    >
                        <component :is="portal.icon" class="h-6 w-6" />
                    </span>

                    <div class="relative">
                        <h2
                            class="font-display text-lg font-bold text-secondary dark:text-white"
                        >
                            {{ portal.title }}
                        </h2>
                        <p
                            class="mt-1.5 text-sm leading-relaxed text-muted dark:text-gray-400"
                        >
                            {{ portal.description }}
                        </p>
                    </div>

                    <p
                        v-if="portal.signup"
                        class="relative text-xs text-muted dark:text-gray-400"
                    >
                        {{ portal.signupText ?? "Don't have an account?" }}
                        <span
                            role="link"
                            tabindex="0"
                            class="cursor-pointer font-semibold text-primary hover:underline dark:text-primary-300"
                            @click.stop.prevent="navigateTo(portal.signup)"
                            @keydown.enter.stop.prevent="
                                navigateTo(portal.signup)
                            "
                        >
                            {{ portal.signupLabel ?? "Sign up" }}
                        </span>
                    </p>

                    <div
                        v-if="portal.chips.length"
                        class="relative flex flex-wrap gap-1.5"
                    >
                        <span
                            v-for="chip in portal.chips"
                            :key="chip"
                            class="rounded-full bg-muted-light px-2.5 py-1 text-[11px] font-medium text-muted transition-colors duration-300 dark:bg-white/5 dark:text-gray-400"
                            :class="portal.chipHover"
                        >
                            {{ chip }}
                        </span>
                    </div>

                    <span
                        class="relative mt-auto flex items-center gap-1.5 rounded-full py-1.5 pl-3 pr-2 text-sm font-semibold transition-colors duration-300"
                        :class="[portal.cta, portal.ctaBg]"
                    >
                        Continue
                        <ArrowRight
                            class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1"
                        />
                    </span>
                </NuxtLink>
            </div>
        </div>
    </div>
</template>
