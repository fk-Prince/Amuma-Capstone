<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { ChevronLeft, ChevronRight, Pause, Play } from "lucide-vue-next";
import PageHero from "~/components/sections/marketing/PageHero.vue";
import FaqSection from "~/components/sections/landing/FaqSection.vue";
import CtaSection from "~/components/sections/landing/CtaSection.vue";
import Reveal from "~/components/ui/Reveal.vue";

useHead({ title: "How booking works - AMUMA" });

definePageMeta({
    layout: "default",
    navVariant: 3,
});

const STEP_MS = 5000;

const steps = [
    {
        title: "Search for a provider",
        text: "Look up agencies by name or location and compare what each one offers.",
        link: { label: "Search providers", to: "/booking/search" },
        mock: {
            title: "Search providers",
            rows: [
                { label: "Where", value: "Davao City" },
                { label: "Sunrise Home Care", value: "Homecare", chip: "Open" },
                { label: "Golden Years Residence", value: "In-house care", chip: "Open" },
            ],
        },
    },
    {
        title: "Choose a service",
        text: "Pick home visits or a facility stay. Each provider lists the services it offers.",
        mock: {
            title: "Choose a service",
            rows: [
                { label: "Daily living assistance", value: "Homecare", chip: "Selected" },
                { label: "Medication management", value: "Homecare" },
                { label: "Private room", value: "Facility" },
            ],
        },
    },
    {
        title: "Add your loved one's details",
        text: "Enter their details and your contact information. AMUMA creates their patient profile for you.",
        mock: {
            title: "Patient details",
            rows: [
                { label: "Patient name", value: "Your loved one" },
                { label: "Age and blood type", value: "Add details" },
                { label: "Your contact", value: "Phone and email" },
            ],
        },
    },
    {
        title: "Review and send",
        text: "Check the summary, then send the request to the agency.",
        mock: {
            title: "Review your request",
            rows: [
                { label: "Provider", value: "Sunrise Home Care" },
                { label: "Service", value: "Daily living assistance" },
                { label: "Request", value: "Ready to send", chip: "Review" },
            ],
        },
    },
    {
        title: "The agency responds",
        text: "Admission staff review the request and assign a caregiver or a room. You follow the outcome in your family portal.",
        link: { label: "Explore the family portal", to: "/for-families" },
        mock: {
            title: "Booking status",
            rows: [
                { label: "Request", value: "Sent to the agency", chip: "Pending" },
                { label: "Admission staff", value: "Review and assign" },
                { label: "Your portal", value: "Shows the result", chip: "Approved" },
            ],
        },
    },
];

const active = ref(0);
const playing = ref(true);
const hovering = ref(false);
const running = computed(() => playing.value && !hovering.value);
const current = computed(() => steps[active.value] ?? steps[0]!);

let timer: ReturnType<typeof setTimeout> | null = null;

function clearTimer() {
    if (timer) {
        clearTimeout(timer);
        timer = null;
    }
}

function schedule() {
    clearTimer();
    if (!running.value) return;

    timer = setTimeout(() => {
        active.value = (active.value + 1) % steps.length;
    }, STEP_MS);
}

function go(index: number) {
    active.value = (index + steps.length) % steps.length;
}

onMounted(() => {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        playing.value = false;
    }
    schedule();
});

watch([active, running], schedule);
onBeforeUnmount(clearTimer);
</script>

<template>
    <div class="min-h-screen w-full overflow-x-hidden">
        <PageHero
            title="From search to first visit in five steps."
            highlight="five steps"
            subtitle="Booking care with AMUMA happens online. Here is exactly what to expect, from finding a provider to following the visit in your portal."
            :primary="{ label: 'Find a care provider', to: '/booking/search' }"
            :secondary="{ label: 'Explore the family portal', to: '/for-families' }"
        />

        <section class="bg-white py-24 dark:bg-secondary">
            <div class="mx-auto w-[94%] max-w-[1200px] px-10 max-sm:px-4">
                <Reveal>
                    <div
                        class="grid gap-8 lg:grid-cols-[1fr_1fr] lg:items-stretch"
                        @mouseenter="hovering = true"
                        @mouseleave="hovering = false"
                        @focusin="hovering = true"
                        @focusout="hovering = false"
                    >
                        <ol class="space-y-3">
                            <li v-for="(step, i) in steps" :key="step.title">
                                <button
                                    type="button"
                                    :aria-current="active === i ? 'step' : undefined"
                                    class="relative w-full overflow-hidden rounded-2xl border px-6 py-5 text-left transition-all duration-300"
                                    :class="
                                        active === i
                                            ? 'border-primary/40 bg-white shadow-[0_12px_32px_rgba(49,130,237,0.12)] dark:bg-white/[0.06]'
                                            : 'border-gray-200 bg-white hover:border-primary/30 dark:border-white/10 dark:bg-white/[0.03]'
                                    "
                                    @click="go(i)"
                                >
                                    <div class="flex items-start gap-4">
                                        <span
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-black transition-colors duration-300"
                                            :class="
                                                active === i
                                                    ? 'bg-primary text-white'
                                                    : i < active
                                                      ? 'bg-primary/15 text-primary'
                                                      : 'bg-slate-100 text-muted dark:bg-white/10 dark:text-gray-400'
                                            "
                                        >
                                            {{ i + 1 }}
                                        </span>

                                        <div>
                                            <p
                                                class="font-bold text-secondary dark:text-white"
                                            >
                                                {{ step.title }}
                                            </p>

                                            <div
                                                class="grid transition-[grid-template-rows] duration-300 ease-out"
                                                :class="
                                                    active === i
                                                        ? 'grid-rows-[1fr]'
                                                        : 'grid-rows-[0fr]'
                                                "
                                            >
                                                <div class="overflow-hidden">
                                                    <p
                                                        class="pt-2 text-sm leading-7 text-muted dark:text-gray-400"
                                                    >
                                                        {{ step.text }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <span
                                        v-if="active === i"
                                        :key="`${active}-${running}`"
                                        class="absolute inset-x-0 bottom-0 h-[3px] origin-left bg-primary"
                                        :style="
                                            running
                                                ? `animation: progressFill ${STEP_MS}ms linear both`
                                                : ''
                                        "
                                    ></span>
                                </button>

                                <NuxtLink
                                    v-if="active === i && step.link"
                                    :to="step.link.to"
                                    class="group mt-2 ml-6 inline-flex items-center gap-2 text-sm font-semibold text-primary"
                                >
                                    {{ step.link.label }}
                                    <span
                                        class="transition-transform duration-200 group-hover:translate-x-1"
                                    >
                                        →
                                    </span>
                                </NuxtLink>
                            </li>
                        </ol>

                        <div class="flex flex-col gap-4">
                            <div
                                class="flex-1 rounded-3xl border border-gray-200 bg-slate-50 p-5 dark:border-white/10 dark:bg-white/[0.03]"
                            >
                                <div class="mb-4 flex items-center justify-between">
                                    <div class="flex gap-1.5">
                                        <span class="h-2.5 w-2.5 rounded-full bg-red-300"></span>
                                        <span class="h-2.5 w-2.5 rounded-full bg-yellow-300"></span>
                                        <span class="h-2.5 w-2.5 rounded-full bg-green-300"></span>
                                    </div>
                                    <span class="text-xs text-muted dark:text-gray-400">
                                        Illustration
                                    </span>
                                </div>

                                <Transition name="swap" mode="out-in">
                                    <div :key="active">
                                        <p
                                            class="mb-4 text-lg font-bold text-secondary dark:text-white"
                                        >
                                            {{ current.mock.title }}
                                        </p>

                                        <div class="space-y-3">
                                            <div
                                                v-for="row in current.mock.rows"
                                                :key="row.label"
                                                class="flex items-center justify-between gap-3 rounded-2xl bg-white px-4 py-4 shadow-sm dark:bg-white/5 dark:shadow-none"
                                            >
                                                <div>
                                                    <p
                                                        class="text-sm font-semibold text-secondary dark:text-white"
                                                    >
                                                        {{ row.label }}
                                                    </p>
                                                    <p
                                                        class="text-xs text-muted dark:text-gray-400"
                                                    >
                                                        {{ row.value }}
                                                    </p>
                                                </div>

                                                <span
                                                    v-if="row.chip"
                                                    class="shrink-0 rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary"
                                                >
                                                    {{ row.chip }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                            <div class="flex items-center justify-between">
                                <p class="text-sm text-muted dark:text-gray-400">
                                    Step {{ active + 1 }} of {{ steps.length }}
                                </p>

                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        aria-label="Previous step"
                                        class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-secondary transition hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-white"
                                        @click="go(active - 1)"
                                    >
                                        <ChevronLeft class="h-5 w-5" />
                                    </button>

                                    <button
                                        type="button"
                                        :aria-label="playing ? 'Pause autoplay' : 'Play autoplay'"
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-white transition hover:bg-primary-600"
                                        @click="playing = !playing"
                                    >
                                        <Pause v-if="playing" class="h-4 w-4" />
                                        <Play v-else class="h-4 w-4" />
                                    </button>

                                    <button
                                        type="button"
                                        aria-label="Next step"
                                        class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-secondary transition hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-white"
                                        @click="go(active + 1)"
                                    >
                                        <ChevronRight class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </Reveal>
            </div>
        </section>

        <section class="bg-slate-50 py-20 dark:bg-secondary">
            <div class="mx-auto w-[94%] max-w-[1200px] px-10 max-sm:px-4">
                <Reveal class="mb-10 text-center">
                    <h2
                        class="text-[clamp(1.8rem,2.8vw,2.6rem)] font-black leading-tight tracking-[-0.025em] text-secondary dark:text-white"
                    >
                        Have these ready before you start
                    </h2>
                </Reveal>

                <div class="grid gap-6 md:grid-cols-3">
                    <Reveal
                        v-for="(item, i) in [
                            {
                                title: 'Your loved one\'s details',
                                text: 'Name, age and any information the provider should know.',
                            },
                            {
                                title: 'Your contact information',
                                text: 'A phone number and email so the agency can reach you.',
                            },
                            {
                                title: 'A family account',
                                text: 'It lets you follow the booking, visits and balance in your portal.',
                            },
                        ]"
                        :key="item.title"
                        :delay="i * 110"
                    >
                        <div
                            class="h-full rounded-3xl border border-gray-200 bg-white p-8 dark:border-white/10 dark:bg-white/[0.03]"
                        >
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
                    </Reveal>
                </div>
            </div>
        </section>

        <FaqSection :limit="4" />

        <CtaSection
            title="Ready to book care?"
            text="Search for a provider and send your first request in a few minutes."
            :primary="{ label: 'Find a care provider', to: '/booking/search' }"
            :secondary="{ label: 'Create a family account', to: '/auth/signup' }"
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