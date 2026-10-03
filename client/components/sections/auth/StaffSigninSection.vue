<script setup lang="ts">
import SigninForm from "~/components/forms/SigninForm.vue";
import ThemeToggle from "~/components/ui/ThemeToggle.vue";
import { Stethoscope, ShieldCheck, ClipboardList, Users } from "lucide-vue-next";

const route = useRoute();

// Someone signing in to subscribe is an agency, so the family-portal switch
// would only confuse them.
const subscribing = computed(() =>
    isSubscribeFlowPath(
        safeRedirect(route.query.redirect) ?? peekAuthRedirect(),
    ),
);

const highlights = [
    { icon: ShieldCheck, label: "Secure & encrypted" },
    { icon: ClipboardList, label: "Real-time records" },
    { icon: Users, label: "Role-based access" },
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
            <!-- Dot-grid texture -->
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

        <!-- Minimal top bar: replaces the site header on auth pages -->
        <div class="relative z-10 flex w-full items-center justify-between px-6 py-6 sm:px-10">
            <NuxtLink to="/" aria-label="AMUMA home">
                <BrandLogo icon-class="h-8 w-8" text-class="text-lg" />
            </NuxtLink>
            <ThemeToggle class="text-secondary dark:text-white" />
        </div>

        <div class="relative z-10 flex w-full flex-1 items-center justify-center px-6 pb-16">
            <div class="flex w-full max-w-[440px] flex-col items-center">
                <div
                    class="w-full rounded-[20px] border border-muted-light bg-white px-6 py-9 shadow-sm sm:px-8 dark:border-white/10 dark:bg-secondary"
                >
                    <div class="mb-7 flex flex-col items-center text-center">
                        <span
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-accent-50 text-accent-600 dark:bg-accent-500/10 dark:text-accent-400"
                        >
                            <Stethoscope class="h-6 w-6" />
                        </span>

                        <p
                            class="mb-1.5 text-xs font-semibold uppercase tracking-widest text-accent-600 dark:text-accent-400"
                        >
                            Staff Portal
                        </p>

                        <h1
                            class="text-2xl font-extrabold tracking-tight text-secondary dark:text-white"
                        >
                            Sign in to your dashboard
                        </h1>

                        <p class="mt-1.5 text-sm text-muted dark:text-gray-400">
                            For branch owners, nurses, caregivers, accounting,
                            and admins.
                        </p>
                    </div>

                    <SigninForm portal="staff" />
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
                    <div
                        v-for="item in highlights"
                        :key="item.label"
                        class="flex items-center gap-1.5 text-xs font-medium text-muted dark:text-gray-500"
                    >
                        <component
                            :is="item.icon"
                            class="h-3.5 w-3.5 text-accent-500 dark:text-accent-400"
                        />
                        {{ item.label }}
                    </div>
                </div>

                <NuxtLink
                    v-if="!subscribing"
                    to="/auth/select"
                    class="mt-4 flex items-center gap-1.5 text-xs font-medium text-muted outline-none hover:text-secondary hover:underline dark:text-gray-400 dark:hover:text-white"
                >
                    ← Not staff? Switch to Family Portal
                </NuxtLink>
            </div>
        </div>
    </div>
</template>s