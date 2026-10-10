<script setup lang="ts">
import { computed, ref } from "vue";
import {
    Bell,
    CalendarCheck,
    CalendarDays,
    CreditCard,
    HeartPulse,
    MessageCircle,
    Pill,
    ShieldCheck,
    Search,
    UsersRound,
} from "lucide-vue-next";
import PageHero from "~/components/sections/marketing/PageHero.vue";
import FaqSection from "~/components/sections/landing/FaqSection.vue";
import CtaSection from "~/components/sections/landing/CtaSection.vue";
import Reveal from "~/components/ui/Reveal.vue";
import { heroImage } from "~/composables/useHeroImages";

useHead({ title: "For families - AMUMA" });

definePageMeta({
    layout: "default",
    navVariant: 3,
});

const showcase = {
    light: heroImage("family", "light"),
    dark: heroImage("family", "dark"),
    alt: "AMUMA family portal showing a loved one's profile and care records",
};

const heroFloats = [
    {
        icon: CalendarCheck,
        title: "Visit confirmed",
        text: "See who is coming and when",
    },
    {
        icon: ShieldCheck,
        title: "Shared only with family",
        text: "Access you control, per person",
    },
];

const portalItems = [
    {
        id: "loved-ones",
        icon: UsersRound,
        label: "Your loved ones",
        text: "Keep each person's profile in one place: personal details, diagnosis, allergies and emergency contacts.",
        points: ["Profile and care records", "Who has access, and what kind", "Book again in one tap"],
    },
    {
        id: "schedule",
        icon: CalendarDays,
        label: "Schedule",
        text: "See upcoming visits and appointments so nobody has to ask who is coming and when.",
        points: ["Upcoming and past visits", "Medical appointments", "Clear status for every booking"],
    },
    {
        id: "medications",
        icon: Pill,
        label: "Medications",
        text: "Follow the medications recorded for your loved one as caregivers log them.",
        points: ["Latest medications recorded", "Updated by the care team", "Always in the portal"],
    },
    {
        id: "monitoring",
        icon: HeartPulse,
        label: "Vital signs",
        text: "Check vital signs charted during visits without waiting for a phone call.",
        points: ["Vitals logged by caregivers", "History you can look back on", "Shared only with the family"],
    },
    {
        id: "updates",
        icon: Bell,
        label: "Updates",
        text: "Care updates and notifications arrive in the portal as things happen.",
        points: ["Visit and care updates", "Notifications for bookings", "One feed, not scattered messages"],
    },
    {
        id: "messages",
        icon: MessageCircle,
        label: "Messages",
        text: "Message the care team directly instead of hunting for the right phone number.",
        points: ["Direct line to the agency", "Conversations kept in one thread", "Reach the right person"],
    },
    {
        id: "balance",
        icon: CreditCard,
        label: "Balance",
        text: "See what's been billed and pay invoices online.",
        points: ["Invoices and balance in one view", "Pay online", "Statements when you need them"],
    },
];

const activeItemId = ref(portalItems[0]!.id);
const activeItem = computed(
    () => portalItems.find((item) => item.id === activeItemId.value) ?? portalItems[0]!,
);

// "Which kind of care?" picker
const place = ref<"home" | "facility" | "unsure" | null>(null);
const frequency = ref<"visits" | "daily" | "round-the-clock" | null>(null);

const placeOptions = [
    { id: "home", label: "At home" },
    { id: "facility", label: "In a care facility" },
    { id: "unsure", label: "I'm not sure yet" },
] as const;

const frequencyOptions = [
    { id: "visits", label: "A few visits a week" },
    { id: "daily", label: "Help every day" },
    { id: "round-the-clock", label: "Around the clock" },
] as const;

const recommendation = computed(() => {
    if (!place.value || !frequency.value) return null;

    if (
        place.value === "facility" ||
        (place.value === "unsure" && frequency.value === "round-the-clock")
    ) {
        return {
            title: "A care facility may suit best",
            text: "A facility gives your loved one a room and a care team close by, with admission, monitoring and daily care handled in one place. Look for providers that offer in-house care.",
            to: "/solutions/facility",
            label: "About in-house facility care",
        };
    }

    if (place.value === "home" && frequency.value === "round-the-clock") {
        return {
            title: "Look for live-in home care",
            text: "Some home-care agencies offer live-in caregivers so help is there around the clock without leaving home.",
            to: "/solutions/home-care",
            label: "About home care",
        };
    }

    return {
        title: "Home care visits could be a good fit",
        text: "Caregivers come to your loved one on a schedule, from a few visits a week to daily support.",
        to: "/solutions/home-care",
        label: "About home care",
    };
});

function resetPicker() {
    place.value = null;
    frequency.value = null;
}

const reasons = [
    {
        title: "You choose the provider",
        text: "Compare agencies by services, location and reviews, then book the one that feels right.",
    },
    {
        title: "You stay in the loop",
        text: "Visits, medications, vital signs and updates are in your portal, so you're not left guessing.",
    },
    {
        title: "Payment is clear",
        text: "See invoices and your balance, and pay online when you're ready.",
    },
];
</script>

<template>
    <div class="min-h-screen w-full overflow-x-hidden">
        <PageHero
            eyebrow="For families"
            title="Know how your loved one is doing, wherever you are."
            highlight="loved one"
            subtitle="Find a care provider you trust, book online, and follow every visit, medication and bill from one family portal."
            :primary="{ label: 'Find a care provider', to: '/booking/search' }"
            :secondary="{ label: 'See how booking works', to: '/how-booking-works' }"
            :points="[
                'Compare providers and book online',
                'Visits and schedule at a glance',
                'Medications and vital signs',
                'Messages and online payments',
            ]"
            :showcase="showcase"
            :floats="heroFloats"
        />
        <section class="bg-white py-24 dark:bg-secondary">
            <div class="mx-auto w-[94%] max-w-[1200px] px-10 max-sm:px-4">
                <Reveal class="mb-12 text-center">
                    <h2
                        class="mb-3 text-[clamp(1.8rem,2.8vw,2.8rem)] font-black leading-tight tracking-[-0.025em] text-secondary dark:text-white"
                    >
                        What you'll find in the family portal
                    </h2>
                    <p
                        class="mx-auto max-w-[540px] text-sm leading-7 text-muted dark:text-gray-400"
                    >
                        Choose a section to see what it does.
                    </p>
                </Reveal>

                <Reveal :delay="100">
                    <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
                        <div
                            role="tablist"
                            aria-label="Family portal sections"
                            class="flex gap-2 overflow-x-auto pb-2 scrollbar-none lg:flex-col lg:overflow-visible lg:pb-0"
                        >
                            <button
                                v-for="item in portalItems"
                                :key="item.id"
                                type="button"
                                role="tab"
                                :aria-selected="activeItemId === item.id"
                                class="flex shrink-0 items-center gap-3 rounded-2xl border px-4 py-3 text-left text-sm font-semibold transition-all duration-200"
                                :class="
                                    activeItemId === item.id
                                        ? 'border-primary bg-primary text-white shadow-md shadow-primary/25'
                                        : 'border-gray-200 bg-white text-muted-dark hover:border-primary/40 hover:text-primary dark:border-white/10 dark:bg-white/5 dark:text-gray-300'
                                "
                                @click="activeItemId = item.id"
                            >
                                <component :is="item.icon" class="h-5 w-5" />
                                {{ item.label }}
                            </button>
                        </div>

                        <Transition name="swap" mode="out-in">
                            <div
                                :key="activeItem.id"
                                role="tabpanel"
                                class="rounded-3xl border border-gray-200 bg-slate-50 p-8 dark:border-white/10 dark:bg-white/[0.03] md:p-10"
                            >
                                <div
                                    class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                                >
                                    <component
                                        :is="activeItem.icon"
                                        class="h-7 w-7"
                                    />
                                </div>

                                <h3
                                    class="mb-3 text-2xl font-black text-secondary dark:text-white"
                                >
                                    {{ activeItem.label }}
                                </h3>

                                <p
                                    class="mb-6 max-w-[520px] text-sm leading-7 text-muted dark:text-gray-400"
                                >
                                    {{ activeItem.text }}
                                </p>

                                <ul class="grid gap-3 sm:grid-cols-3">
                                    <li
                                        v-for="point in activeItem.points"
                                        :key="point"
                                        class="rounded-2xl bg-white px-4 py-4 text-sm font-semibold text-secondary shadow-sm dark:bg-white/5 dark:text-gray-100 dark:shadow-none"
                                    >
                                        {{ point }}
                                    </li>
                                </ul>
                            </div>
                        </Transition>
                    </div>
                </Reveal>
            </div>
        </section>

        <section class="bg-slate-50 py-24 dark:bg-secondary">
            <div class="mx-auto w-[94%] max-w-[900px] px-10 max-sm:px-4">
                <Reveal class="mb-10 text-center">
                    <h2
                        class="mb-3 text-[clamp(1.8rem,2.8vw,2.8rem)] font-black leading-tight tracking-[-0.025em] text-secondary dark:text-white"
                    >
                        Not sure what kind of care to look for?
                    </h2>
                    <p
                        class="mx-auto max-w-[520px] text-sm leading-7 text-muted dark:text-gray-400"
                    >
                        Answer two quick questions for a starting point. It's a
                        general guide, so talk with the provider about your
                        loved one's needs.
                    </p>
                </Reveal>

                <Reveal :delay="100">
                    <div
                        class="rounded-3xl border border-gray-200 bg-white p-8 dark:border-white/10 dark:bg-white/[0.03] md:p-10"
                    >
                        <fieldset class="mb-8">
                            <legend
                                class="mb-3 text-sm font-bold text-secondary dark:text-white"
                            >
                                Where would your loved one be most comfortable?
                            </legend>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="option in placeOptions"
                                    :key="option.id"
                                    type="button"
                                    :aria-pressed="place === option.id"
                                    class="rounded-full border px-5 py-2.5 text-sm font-semibold transition-all duration-200"
                                    :class="
                                        place === option.id
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-gray-200 text-muted-dark hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-gray-300'
                                    "
                                    @click="place = option.id"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </fieldset>

                        <fieldset class="mb-2">
                            <legend
                                class="mb-3 text-sm font-bold text-secondary dark:text-white"
                            >
                                How much help is needed?
                            </legend>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="option in frequencyOptions"
                                    :key="option.id"
                                    type="button"
                                    :aria-pressed="frequency === option.id"
                                    class="rounded-full border px-5 py-2.5 text-sm font-semibold transition-all duration-200"
                                    :class="
                                        frequency === option.id
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-gray-200 text-muted-dark hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-gray-300'
                                    "
                                    @click="frequency = option.id"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </fieldset>

                        <Transition name="swap" mode="out-in">
                            <div
                                v-if="recommendation"
                                :key="recommendation.title"
                                class="mt-8 rounded-2xl border border-primary/30 bg-primary/5 p-6"
                                aria-live="polite"
                            >
                                <p
                                    class="mb-2 text-lg font-bold text-secondary dark:text-white"
                                >
                                    {{ recommendation.title }}
                                </p>
                                <p
                                    class="mb-5 text-sm leading-7 text-muted dark:text-gray-400"
                                >
                                    {{ recommendation.text }}
                                </p>

                                <div class="flex flex-wrap items-center gap-3">
                                    <NuxtLink
                                        to="/booking/search"
                                        class="flex items-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-primary-600"
                                    >
                                        <Search class="h-4 w-4" />
                                        Find providers
                                    </NuxtLink>

                                    <NuxtLink
                                        :to="recommendation.to"
                                        class="text-sm font-semibold text-primary underline-offset-4 hover:underline"
                                    >
                                        {{ recommendation.label }}
                                    </NuxtLink>

                                    <button
                                        type="button"
                                        class="ml-auto text-sm text-muted hover:text-primary dark:text-gray-400"
                                        @click="resetPicker"
                                    >
                                        Start over
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </Reveal>
            </div>
        </section>

        <section class="bg-white py-24 dark:bg-secondary">
            <div
                class="mx-auto grid w-[94%] max-w-[1200px] gap-6 px-10 max-sm:px-4 md:grid-cols-3"
            >
                <Reveal
                    v-for="(reason, i) in reasons"
                    :key="reason.title"
                    :delay="i * 110"
                >
                    <div
                        class="h-full rounded-3xl border border-gray-200 p-8 transition-all duration-300 hover:-translate-y-1 hover:border-primary/30 hover:shadow-[0_16px_40px_rgba(49,130,237,0.1)] dark:border-white/10 dark:bg-white/[0.03]"
                    >
                        <h3
                            class="mb-3 text-lg font-bold text-secondary dark:text-white"
                        >
                            {{ reason.title }}
                        </h3>
                        <p
                            class="text-sm leading-7 text-muted dark:text-gray-400"
                        >
                            {{ reason.text }}
                        </p>
                    </div>
                </Reveal>
            </div>
        </section>

        <section class="bg-slate-50 py-20 dark:bg-secondary">
            <Reveal class="mx-auto w-[94%] max-w-[800px] px-10 text-center max-sm:px-4">
                <p
                    class="mb-6 text-[clamp(1.4rem,2.2vw,2rem)] font-bold leading-snug text-secondary dark:text-white"
                >
                    “My mother is well taken care of. I can check her status,
                    upcoming visits, and balance online anytime.”
                </p>
                <p class="text-sm font-semibold text-primary">
                    Jack, family member
                </p>
            </Reveal>
        </section>

        <FaqSection :limit="4" />

        <CtaSection
            title="Ready to find care you can trust?"
            text="Create a family account to book a provider and follow your loved one's care in one place."
            :primary="{ label: 'Create a family account', to: '/auth/signup' }"
            :secondary="{ label: 'Search providers', to: '/booking/search' }"
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