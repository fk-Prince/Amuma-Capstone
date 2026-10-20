<script setup lang="ts">
import { computed, ref } from "vue";
import { CalendarCheck, Check, QrCode } from "lucide-vue-next";
import PageHero from "~/components/sections/marketing/PageHero.vue";
import PricingSection from "~/components/sections/landing/PricingSection.vue";
import FaqSection from "~/components/sections/landing/FaqSection.vue";
import CtaSection from "~/components/sections/landing/CtaSection.vue";
import Reveal from "~/components/ui/Reveal.vue";
import { heroImage } from "~/composables/useHeroImages";
import scheduleImg from "~/assets/images/Rectangle_12.png";
import billingImg from "~/assets/images/Rectangle_13.png";
import securityImg from "~/assets/images/Rectangle_15.png";

useHead({ title: "For agencies - AMUMA" });

definePageMeta({
    layout: "default",
    navVariant: 3,
});

const showcase = {
    light: heroImage("agency", "light"),
    dark: heroImage("agency", "dark"),
    alt: "AMUMA agency owner dashboard showing occupancy, bookings and contracts",
};

const heroFloats = [
    {
        icon: QrCode,
        title: "QR clock-in",
        text: "Visits and shifts, on record",
    },
    {
        icon: CalendarCheck,
        title: "No double-booked shifts",
        text: "Conflicts flagged before the day starts",
    },
];

const features = [
    {
        id: "scheduling",
        title: "Know who's where before the day starts",
        text: "Assign caregivers to home visits and facility shifts, spot clashes early, and let your team clock in with a QR scan.",
        image: scheduleImg,
        points: [
            "Caregiver assignment with availability",
            "Warnings when a schedule conflicts",
            "QR clock-in for visits and shifts",
            "A shift board for facility teams",
        ],
    },
    {
        id: "billing",
        title: "From finished visit to paid invoice",
        text: "Completed care turns into invoices without a month-end scramble, and families can pay online.",
        image: billingImg,
        points: [
            "Invoices generated from completed care",
            "Family balances and online payments",
            "Adjustments, refunds and write-offs on record",
            "Plans and contracts for Homecare and Facility",
        ],
    },
    {
        id: "security",
        title: "Records that stay with the patient",
        text: "Chart medications and vital signs where care happens, and keep admissions, discharges and room details in the same place.",
        image: securityImg,
        points: [
            "eMAR and vital signs charting",
            "Admission and discharge records",
            "Room and bed tracking for facilities",
            "VIP room CCTV access on the Facility plan",
        ],
    },
];

const roles = [
    {
        id: "owner",
        label: "Branch owner",
        summary: "The whole branch at a glance, with the controls to run it.",
        sees: [
            "Dashboard with occupancy, bookings and contracts",
            "Bookings, patients, schedules and admissions",
            "Rooms, beds and services",
            "Employees, billing and reports",
        ],
    },
    {
        id: "nurse",
        label: "Nurse and caregiver",
        summary: "Only their patients and their shifts, with nothing extra on screen.",
        sees: [
            "Assigned patients and today's schedule",
            "eMAR and vital signs charting",
            "QR clock-in and shift details",
            "Care notes for each patient",
        ],
    },
    {
        id: "accounting",
        label: "Accounting staff",
        summary: "Money matters across branches, without access to clinical records.",
        sees: [
            "Invoices, balances and payments",
            "Adjustments, refunds and write-offs",
            "Balance statements for families",
            "Subscription and contract billing",
        ],
    },
    {
        id: "family",
        label: "Family member",
        summary: "A clear window into their own loved one's care, and no one else's.",
        sees: [
            "Their loved one's profile and records",
            "Upcoming schedule and visit updates",
            "Medications and vital signs",
            "Messages and balance",
        ],
    },
];

const activeRoleId = ref(roles[0]!.id);
const activeRole = computed(
    () => roles.find((role) => role.id === activeRoleId.value) ?? roles[0]!,
);

const comparison = [
    {
        area: "Scheduling",
        before: "Group chats and paper rosters",
        after: "One schedule with assignments and conflict warnings",
    },
    {
        area: "Charting",
        before: "Paper logs and notebooks",
        after: "eMAR and vital signs recorded on the device",
    },
    {
        area: "Billing",
        before: "Invoices built by hand at month-end",
        after: "Invoices generated from completed care",
    },
    {
        area: "Family updates",
        before: "Phone calls and text messages",
        after: "A family portal with schedules, updates and messages",
    },
    {
        area: "Multiple branches",
        before: "Separate spreadsheets for each location",
        after: "One account that switches between branches",
    },
];
</script>

<template>
    <div class="min-h-screen w-full overflow-x-hidden">
        <PageHero
            eyebrow="For agencies"
            title="Spend your shifts on care, not on paperwork."
            highlight="care"
            subtitle="AMUMA gives home-care agencies and care facilities one place for bookings, caregiver schedules, patient charts and billing."
            :primary="{ label: 'Create agency account', to: '/auth/agency/signup' }"
            :secondary="{ label: 'See plans', to: '#plans' }"
            :points="[
                'Bookings and admissions',
                'Caregiver scheduling',
                'eMAR and vital signs',
                'Invoices and online payments',
            ]"
            :showcase="showcase"
            :floats="heroFloats"
        />
        <section
            id="features"
            class="scroll-mt-32 bg-white py-24 dark:bg-secondary"
        >
            <div
                class="mx-auto flex w-[94%] max-w-[1400px] flex-col gap-28 px-10 max-sm:px-4"
            >
                <div
                    v-for="(feature, i) in features"
                    :id="feature.id"
                    :key="feature.id"
                    class="scroll-mt-32 grid items-center gap-14 lg:grid-cols-2"
                >
                    <Reveal :class="i % 2 ? 'lg:order-2' : ''">
                        <h2
                            class="mb-4 max-w-[500px] text-[clamp(1.8rem,2.8vw,2.8rem)] font-black leading-[1.08] tracking-[-0.025em] text-secondary dark:text-white"
                        >
                            {{ feature.title }}
                        </h2>

                        <p
                            class="mb-7 max-w-[500px] text-sm leading-7 text-muted dark:text-gray-400"
                        >
                            {{ feature.text }}
                        </p>

                        <ul class="space-y-3">
                            <li
                                v-for="point in feature.points"
                                :key="point"
                                class="flex items-start gap-3 text-sm text-secondary dark:text-gray-200"
                            >
                                <span
                                    class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
                                >
                                    <Check class="h-3 w-3" :stroke-width="3" />
                                </span>
                                {{ point }}
                            </li>
                        </ul>
                    </Reveal>

                    <Reveal :delay="120" :class="i % 2 ? 'lg:order-1' : ''">
                        <div
                            class="mx-auto max-w-[480px] overflow-hidden rounded-3xl border border-gray-200 bg-slate-50 p-3 shadow-[0_16px_40px_rgba(15,23,42,0.06)] dark:border-white/10 dark:bg-white/[0.03]"
                        >
                            <img
                                :src="feature.image"
                                :alt="`${feature.id} preview`"
                                class="w-full rounded-2xl object-cover"
                            />
                        </div>
                    </Reveal>
                </div>
            </div>
        </section>

        <section
            id="roles"
            class="scroll-mt-32 bg-slate-50 py-24 dark:bg-secondary"
        >
            <div class="mx-auto w-[94%] max-w-[1200px] px-10 max-sm:px-4">
                <Reveal class="mb-10 text-center">
                    <h2
                        class="mb-3 text-[clamp(1.8rem,2.8vw,2.8rem)] font-black leading-tight tracking-[-0.025em] text-secondary dark:text-white"
                    >
                        Everyone sees what they need, and nothing more.
                    </h2>
                    <p
                        class="mx-auto max-w-[560px] text-sm leading-7 text-muted dark:text-gray-400"
                    >
                        Pick a role to see what that person gets when they sign
                        in.
                    </p>
                </Reveal>

                <Reveal :delay="100">
                    <div
                        role="tablist"
                        aria-label="Roles"
                        class="mb-6 flex flex-wrap justify-center gap-2"
                    >
                        <button
                            v-for="role in roles"
                            :key="role.id"
                            type="button"
                            role="tab"
                            :aria-selected="activeRoleId === role.id"
                            class="rounded-full border px-5 py-2.5 text-sm font-semibold transition-all duration-200"
                            :class="
                                activeRoleId === role.id
                                    ? 'border-primary bg-primary text-white shadow-md shadow-primary/25'
                                    : 'border-gray-200 bg-white text-muted-dark hover:border-primary/40 hover:text-primary dark:border-white/10 dark:bg-white/5 dark:text-gray-300'
                            "
                            @click="activeRoleId = role.id"
                        >
                            {{ role.label }}
                        </button>
                    </div>

                    <Transition name="swap" mode="out-in">
                        <div
                            :key="activeRole.id"
                            role="tabpanel"
                            class="rounded-3xl border border-gray-200 bg-white p-8 dark:border-white/10 dark:bg-white/[0.03] md:p-10"
                        >
                            <p
                                class="mb-6 text-lg font-bold text-secondary dark:text-white"
                            >
                                {{ activeRole.summary }}
                            </p>

                            <ul class="grid gap-3 sm:grid-cols-2">
                                <li
                                    v-for="item in activeRole.sees"
                                    :key="item"
                                    class="flex items-start gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm text-secondary dark:bg-white/5 dark:text-gray-200"
                                >
                                    <span
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
                                    >
                                        <Check
                                            class="h-3 w-3"
                                            :stroke-width="3"
                                        />
                                    </span>
                                    {{ item }}
                                </li>
                            </ul>
                        </div>
                    </Transition>
                </Reveal>
            </div>
        </section>

        <section class="bg-white py-24 dark:bg-secondary">
            <div class="mx-auto w-[94%] max-w-[1100px] px-10 max-sm:px-4">
                <Reveal class="mb-10 text-center">
                    <h2
                        class="text-[clamp(1.8rem,2.8vw,2.8rem)] font-black leading-tight tracking-[-0.025em] text-secondary dark:text-white"
                    >
                        What changes when you switch
                    </h2>
                </Reveal>

                <div
                    class="hidden grid-cols-[180px_1fr_1fr] gap-4 px-6 pb-3 text-sm font-semibold text-muted dark:text-gray-400 md:grid"
                >
                    <span></span>
                    <span>Without AMUMA</span>
                    <span class="text-primary">With AMUMA</span>
                </div>

                <div class="space-y-3">
                    <Reveal
                        v-for="(row, i) in comparison"
                        :key="row.area"
                        :delay="i * 70"
                    >
                        <div
                            class="grid gap-2 rounded-2xl border border-gray-200 bg-white px-6 py-5 dark:border-white/10 dark:bg-white/[0.03] md:grid-cols-[180px_1fr_1fr] md:items-center md:gap-4"
                        >
                            <p
                                class="font-bold text-secondary dark:text-white"
                            >
                                {{ row.area }}
                            </p>
                            <p
                                class="text-sm text-muted line-through decoration-gray-300 dark:text-gray-500 dark:decoration-white/20"
                            >
                                {{ row.before }}
                            </p>
                            <p
                                class="text-sm font-semibold text-secondary dark:text-gray-100"
                            >
                                {{ row.after }}
                            </p>
                        </div>
                    </Reveal>
                </div>
            </div>
        </section>


        <div id="plans" class="scroll-mt-32">
            <PricingSection />
        </div>

        <FaqSection :limit="5" />

        <CtaSection
            title="Ready to run your agency on AMUMA?"
            text="Create your agency account, set up your branch, and choose the plan that fits how you care."
            :primary="{ label: 'Create agency account', to: '/auth/agency/signup' }"
            :secondary="{ label: 'Compare plans', to: '/product' }"
        />
    </div>
</template>

<style scoped>
.swap-enter-active,
.swap-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.swap-enter-from {
    opacity: 0;
    transform: translateY(8px);
}

.swap-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

@media (prefers-reduced-motion: reduce) {
    .swap-enter-active,
    .swap-leave-active {
        transition: none;
    }
}
</style>