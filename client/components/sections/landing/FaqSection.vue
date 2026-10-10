<script setup lang="ts">
import { computed, ref } from "vue";
import { ChevronDown, Search } from "lucide-vue-next";
import Reveal from "~/components/ui/Reveal.vue";

type Faq = {
    q: string;
    a: string;
    link?: { label: string; to: string };
};

const props = withDefaults(
    defineProps<{
        limit?: number;
        searchable?: boolean;
        title?: string;
        subtitle?: string;
    }>(),
    {
        limit: 0,
        searchable: false,
        title: "Questions before you decide?",
        subtitle: "",
    },
);

const faqs: Faq[] = [
    {
        q: "How do families book a visit?",
        a: "Search for a provider, choose a service, add your loved one's details, and send the request. The agency reviews it and approves or replies, and you can follow the result from your family portal.",
        link: { label: "See the booking steps", to: "/how-booking-works" },
    },
    {
        q: "What's the difference between Homecare, Facility and Hybrid?",
        a: "Homecare covers home visits, caregiver assignment and charting. Facility covers admissions, rooms and beds, and VIP room CCTV. Hybrid includes everything in both under one subscription.",
        link: { label: "Compare the three", to: "/solutions/hybrid" },
    },
    {
        q: "Can I manage more than one branch?",
        a: "Yes. Each branch has its own dashboard, bookings, patients and contracts, and you can switch between them from one account.",
    },
    {
        q: "Who sees what inside AMUMA?",
        a: "Access follows roles. Nurses and caregivers see their patients and schedules, accounting sees invoices and balances, branch owners see the whole branch, and a family member sees only their own loved one.",
        link: { label: "See each role's view", to: "/for-agencies#roles" },
    },
    {
        q: "How do payments work?",
        a: "Completed care becomes an invoice automatically. Families can check their balance and pay online from the family portal, and the balance updates when payment is received.",
    },
    {
        q: "What can a family see after booking?",
        a: "The family portal shows your loved one's profile, upcoming schedule, medications, vital signs, care updates, messages and balance.",
        link: { label: "Explore the family portal", to: "/for-families" },
    },
    {
        q: "How does an agency get started?",
        a: "Create an agency account, set up your branch, and choose the plan that fits your care model. Your branch is submitted for verification before it appears in provider search.",
        link: { label: "Create an agency account", to: "/auth/agency/signup" },
    },
    {
        q: "Do I need to subscribe to both modules?",
        a: "No. Pick Homecare or Facility on its own, or choose Hybrid if you run both. You can compare plans and prices on the plans page.",
        link: { label: "View plans", to: "/product" },
    },
];

const query = ref("");
const openIndex = ref<number | null>(0);

const visible = computed(() => {
    const term = query.value.trim().toLowerCase();
    const base = props.limit > 0 ? faqs.slice(0, props.limit) : faqs;

    if (!term) return base;

    return faqs.filter(
        (item) =>
            item.q.toLowerCase().includes(term) ||
            item.a.toLowerCase().includes(term),
    );
});

function toggle(index: number) {
    openIndex.value = openIndex.value === index ? null : index;
}

function clearSearch() {
    query.value = "";
    openIndex.value = 0;
}
</script>

<template>
    <section class="relative bg-slate-50 py-24 dark:bg-secondary">
        <div class="relative z-10 mx-auto w-[94%] max-w-[900px] px-6">
            <Reveal class="mb-10 text-center">
                <h2
                    class="mb-3 text-[clamp(2rem,3.2vw,3rem)] font-black leading-tight tracking-[-0.02em] text-secondary dark:text-white"
                >
                    {{ title }}
                </h2>

                <p
                    v-if="subtitle"
                    class="mx-auto max-w-[520px] text-sm leading-7 text-muted dark:text-gray-400"
                >
                    {{ subtitle }}
                </p>
            </Reveal>

            <label
                v-if="searchable"
                class="mb-6 flex h-12 items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-muted shadow-sm transition focus-within:border-primary/50 focus-within:ring-2 focus-within:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
            >
                <Search class="h-4 w-4 shrink-0" />
                <span class="sr-only">Search questions</span>
                <input
                    v-model="query"
                    type="search"
                    placeholder="Search questions, like “payment” or “branch”"
                    class="min-w-0 flex-1 bg-transparent text-sm text-secondary outline-none placeholder:text-muted dark:text-white dark:placeholder:text-gray-400"
                    @input="openIndex = 0"
                />
            </label>

            <div v-if="visible.length" class="space-y-3">
                <div
                    v-for="(item, i) in visible"
                    :key="item.q"
                    class="overflow-hidden rounded-2xl border bg-white transition-colors duration-300 dark:bg-white/[0.03]"
                    :class="
                        openIndex === i
                            ? 'border-primary/40 shadow-[0_10px_30px_rgba(49,130,237,0.08)]'
                            : 'border-gray-200 dark:border-white/10'
                    "
                >
                    <h3>
                        <button
                            type="button"
                            :id="`faq-q-${i}`"
                            :aria-expanded="openIndex === i"
                            :aria-controls="`faq-a-${i}`"
                            class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left"
                            @click="toggle(i)"
                        >
                            <span
                                class="text-base font-bold text-secondary dark:text-white"
                            >
                                {{ item.q }}
                            </span>

                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-light text-primary transition-transform duration-300 dark:bg-primary/15"
                                :class="openIndex === i ? 'rotate-180' : ''"
                                aria-hidden="true"
                            >
                                <ChevronDown class="h-4 w-4" :stroke-width="2.5" />
                            </span>
                        </button>
                    </h3>

                    <div
                        :id="`faq-a-${i}`"
                        role="region"
                        :aria-labelledby="`faq-q-${i}`"
                        class="grid transition-[grid-template-rows] duration-300 ease-out"
                        :class="openIndex === i ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
                    >
                        <div class="overflow-hidden">
                            <p
                                class="px-6 text-sm leading-7 text-muted dark:text-gray-400"
                                :class="item.link ? '' : 'pb-6'"
                            >
                                {{ item.a }}
                            </p>

                            <NuxtLink
                                v-if="item.link"
                                :to="item.link.to"
                                class="group inline-flex items-center gap-2 px-6 pt-3 pb-6 text-sm font-semibold text-primary"
                            >
                                {{ item.link.label }}
                                <span
                                    class="transition-transform duration-200 group-hover:translate-x-1"
                                >
                                    →
                                </span>
                            </NuxtLink>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center dark:border-white/15 dark:bg-white/[0.03]"
            >
                <p class="mb-1 font-bold text-secondary dark:text-white">
                    No answers match “{{ query }}”
                </p>
                <p class="mb-5 text-sm text-muted dark:text-gray-400">
                    Try a shorter word, or browse all questions.
                </p>
                <button
                    type="button"
                    class="rounded-xl border border-primary px-5 py-2 text-sm font-bold text-primary transition hover:bg-primary hover:text-white"
                    @click="clearSearch"
                >
                    Show all questions
                </button>
            </div>

            <p
                v-if="limit > 0 && !query"
                class="mt-8 text-center text-sm text-muted dark:text-gray-400"
            >
                Still curious?
                <NuxtLink
                    to="/faq"
                    class="font-semibold text-primary underline-offset-4 hover:underline"
                >
                    Browse every question
                </NuxtLink>
            </p>
        </div>
    </section>
</template>