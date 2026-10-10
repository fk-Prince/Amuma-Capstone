<script setup lang="ts">
import { computed, ref } from "vue";
import {
    Bell,
    Building2,
    CalendarCheck,
    HeartPulse,
    Receipt,
    Search,
    Wallet,
} from "lucide-vue-next";
import CountUp from "~/components/ui/CountUp.vue";
import Reveal from "~/components/ui/Reveal.vue";

type Audience = "agency" | "family";

const audience = ref<Audience>("agency");

// Facts about the product itself (not marketing claims), so they stay true.
const stats = [
    { value: 3, label: "Care models: Homecare, Facility, Hybrid" },
    { value: 2, label: "Portals: one for agencies, one for families" },
    { value: 1, label: "Platform from booking to billing" },
];

const tabs: { id: Audience; label: string }[] = [
    { id: "agency", label: "Care agencies" },
    { id: "family", label: "Families" },
];

const content = {
    agency: {
        title: "Less time on admin, more time on care.",
        text: "Everything your team does between a booking and a paid invoice lives in one system, with each person seeing only what their role needs.",
        cta: { label: "See AMUMA for agencies", to: "/for-agencies" },
        items: [
            {
                icon: CalendarCheck,
                title: "Schedule without group chats",
                text: "Assign caregivers to shifts, check availability, and let them clock in with a QR scan.",
            },
            {
                icon: HeartPulse,
                title: "Chart care at the bedside",
                text: "Log medications and vital signs in seconds, right where care happens.",
            },
            {
                icon: Receipt,
                title: "Invoices that send themselves",
                text: "Completed care turns into invoices, families pay online, and balances update on their own.",
            },
            {
                icon: Building2,
                title: "Every branch in one view",
                text: "Compare bookings, occupancy and contracts across all your locations.",
            },
        ],
    },
    family: {
        title: "Care you can see, from wherever you are.",
        text: "Find a provider, book a visit, and follow your loved one's care in a portal made for families.",
        cta: { label: "See AMUMA for families", to: "/for-families" },
        items: [
            {
                icon: Search,
                title: "Find a provider you trust",
                text: "Compare agencies by services, location and reviews before you decide.",
            },
            {
                icon: CalendarCheck,
                title: "Book in a few minutes",
                text: "Choose a service, add your loved one's details, and send your request online.",
            },
            {
                icon: Bell,
                title: "Follow every visit",
                text: "See schedules, medications, vital signs and updates in one place.",
            },
            {
                icon: Wallet,
                title: "Pay without the paperwork",
                text: "Check your balance and pay invoices online, any time.",
            },
        ],
    },
} as const;

const active = computed(() => content[audience.value]);
const activeIndex = computed(() =>
    tabs.findIndex((tab) => tab.id === audience.value),
);
</script>

<template>
    <section class="relative overflow-hidden bg-white py-24 dark:bg-secondary">
        <div
            class="pointer-events-none absolute top-1/3 -left-40 h-[420px] w-[420px] rounded-full bg-blue-200/40 blur-[110px] dark:bg-primary/10"
        ></div>

        <div
            class="relative z-10 mx-auto grid w-[94%] max-w-[1600px] gap-16 px-10 max-sm:px-4 lg:grid-cols-[42%_1fr] lg:items-center"
        >
            <Reveal>
                <div
                    role="tablist"
                    aria-label="Choose who you are"
                    class="relative mb-8 inline-grid grid-cols-2 rounded-2xl border border-gray-200 bg-slate-100 p-1 dark:border-white/10 dark:bg-white/5"
                >
                    <span
                        class="absolute inset-y-1 left-1 w-[calc(50%-4px)] rounded-xl bg-white shadow-sm transition-transform duration-300 ease-out dark:bg-primary"
                        :style="{
                            transform: `translateX(${activeIndex * 100}%)`,
                        }"
                    ></span>

                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        role="tab"
                        :aria-selected="audience === tab.id"
                        class="relative z-10 rounded-xl px-6 py-2.5 text-sm font-bold transition-colors"
                        :class="
                            audience === tab.id
                                ? 'text-primary dark:text-white'
                                : 'text-muted hover:text-secondary dark:text-gray-400 dark:hover:text-white'
                        "
                        @click="audience = tab.id"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <Transition name="swap" mode="out-in">
                    <div :key="audience">
                        <h2
                            class="mb-4 max-w-[480px] text-[clamp(2rem,3.2vw,3.2rem)] font-black leading-[1.05] tracking-[-0.03em] text-secondary dark:text-white"
                        >
                            {{ active.title }}
                        </h2>

                        <p
                            class="mb-8 max-w-[460px] text-sm leading-7 text-muted dark:text-gray-400"
                        >
                            {{ active.text }}
                        </p>

                        <NuxtLink
                            :to="active.cta.to"
                            class="group inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-primary-600"
                        >
                            {{ active.cta.label }}
                            <span
                                class="transition-transform duration-200 group-hover:translate-x-1"
                            >
                                →
                            </span>
                        </NuxtLink>
                    </div>
                </Transition>

                <dl
                    class="mt-12 grid max-w-[480px] grid-cols-3 gap-6 border-t border-gray-200 pt-8 dark:border-white/10"
                >
                    <div v-for="stat in stats" :key="stat.label">
                        <dd
                            class="text-4xl font-black text-secondary dark:text-white"
                        >
                            <CountUp :to="stat.value" />
                        </dd>
                        <dt
                            class="mt-1 text-xs leading-5 text-muted dark:text-gray-400"
                        >
                            {{ stat.label }}
                        </dt>
                    </div>
                </dl>

            </Reveal>

            <Transition name="swap" mode="out-in">
                <div :key="audience" class="grid gap-4 sm:grid-cols-2">
                    <div
                        v-for="item in active.items"
                        :key="item.title"
                        class="group rounded-3xl border border-gray-200 bg-white p-7 transition-all duration-300 hover:-translate-y-1 hover:border-primary/30 hover:shadow-[0_16px_40px_rgba(49,130,237,0.12)] dark:border-white/10 dark:bg-white/[0.03] dark:hover:border-primary/40"
                    >
                        <div
                            class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-light text-primary transition-transform duration-300 group-hover:scale-110 dark:bg-primary/15"
                        >
                            <component :is="item.icon" class="h-6 w-6" />
                        </div>

                        <h3
                            class="mb-2 text-lg font-bold text-secondary dark:text-white"
                        >
                            {{ item.title }}
                        </h3>

                        <p
                            class="text-sm leading-7 text-muted dark:text-gray-400"
                        >
                            {{ item.text }}
                        </p>
                    </div>
                </div>
            </Transition>
        </div>
    </section>
</template>

<style scoped>
.swap-enter-active,
.swap-leave-active {
    transition:
        opacity 0.22s ease,
        transform 0.22s ease;
}

.swap-enter-from {
    opacity: 0;
    transform: translateY(10px);
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