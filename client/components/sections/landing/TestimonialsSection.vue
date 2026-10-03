<template>
    <section class="relative overflow-hidden bg-slate-50 py-24 dark:bg-secondary">
        <!-- Blob -->

        <div
            class="absolute top-[20%] right-[-150px] h-[500px] w-[500px] rounded-full bg-blue-200/30 blur-[120px]"
        ></div>

        <div class="relative z-10 mx-auto w-[94%] max-w-[1600px] px-10">
            <!-- Header -->

            <div class="mb-12 flex flex-wrap items-end justify-between gap-6">
                <div>
                    <h2
                        class="mb-3 text-[clamp(2rem,3vw,3rem)] font-black leading-tight text-secondary dark:text-white"
                    >
                        Trusted by

                        <span
                            class="bg-gradient-to-br from-primary to-blue-700 bg-clip-text text-transparent"
                        >
                            Care Teams
                        </span>

                        Across the Philippines
                    </h2>

                    <p class="text-muted dark:text-gray-400">
                        — here's what agencies say after switching to AMUMA.
                    </p>
                </div>

                <div class="hidden shrink-0 items-center gap-2 sm:flex">
                    <button
                        type="button"
                        aria-label="Previous testimonials"
                        class="flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-secondary shadow-sm transition-colors hover:bg-primary-50 hover:text-primary dark:border-white/10 dark:bg-secondary dark:text-white dark:hover:bg-white/10"
                        @click="go(-1)"
                    >
                        <ChevronLeft class="h-5 w-5" />
                    </button>

                    <button
                        type="button"
                        aria-label="Next testimonials"
                        class="flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-secondary shadow-sm transition-colors hover:bg-primary-50 hover:text-primary dark:border-white/10 dark:bg-secondary dark:text-white dark:hover:bg-white/10"
                        @click="go(1)"
                    >
                        <ChevronRight class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Cards -->

            <div
                ref="track"
                class="flex gap-5 overflow-x-auto pb-4 scrollbar-none"
                :class="[
                    isDragging
                        ? 'cursor-grabbing select-none'
                        : 'cursor-grab snap-x snap-mandatory',
                ]"
                @pointerdown="onPointerDown"
                @pointermove="onPointerMove"
                @pointerup="onPointerUp"
                @pointercancel="onPointerUp"
                @mouseenter="stopAutoplay"
                @mouseleave="onMouseLeave"
            >
                <ReviewCard
                    v-for="t in testimonials"
                    :key="t.name"
                    class="w-[320px] shrink-0 snap-start"
                    :name="t.name"
                    :rate="5"
                    :text="t.quote"
                    :role="t.role"
                    :role-tone="t.tone"
                    :organization="t.organization"
                />
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import ReviewCard from "~/components/sections/reviews/ReviewCard.vue";

const testimonials: {
    name: string;
    role: string;
    tone: "owner" | "staff" | "client";
    organization: string | null;
    quote: string;
}[] = [
    {
        name: "Dr. Barbie Santos",
        role: "Agency Owner",
        tone: "owner",
        organization: "CareFirst Home Services",
        quote: "AMUMA completely transformed how we run our home care operations. Scheduling used to take hours — now it takes minutes.",
    },
    {
        name: "Aytisa Winner",
        role: "Branch Manager",
        tone: "staff",
        organization: "Golden Year Home",
        quote: "AMUMA gives me a live dashboard for every location. Billing, admissions, staff — all in one place.",
    },
    {
        name: "Azriel Makinis",
        role: "Nurse",
        tone: "staff",
        organization: "Malasakit Care Center",
        quote: "AMUMA makes vitals logging and care notes incredibly simple, even during busy shifts.",
    },
    {
        name: "Jack Reyes",
        role: "Client",
        tone: "client",
        organization: null,
        quote: "I can check my mother's status, visits, and balances online anytime. It gives our family peace of mind.",
    },
    {
        name: "Maria Dela Cruz",
        role: "Admission Staff",
        tone: "staff",
        organization: "SunCare Agency",
        quote: "The QR check-in alone saved us so much paperwork. AMUMA made our team significantly faster.",
    },
    {
        name: "Renz Paglinawan",
        role: "Caregiver",
        tone: "staff",
        organization: "HomeHeart Services",
        quote: "eMAR is fast, clean, and easy to understand. I feel much more confident giving care.",
    },
    {
        name: "Lorna Bautista",
        role: "Cashier",
        tone: "staff",
        organization: "Malasakit Care Center",
        quote: "Invoices are automatic now. Families pay online and our month-end workload is much lighter.",
    },
    {
        name: "Engr. Ramon Sy",
        role: "Agency Owner",
        tone: "owner",
        organization: "Tahanan Elderly Home",
        quote: "ROI became visible within two months. Less admin work, fewer errors, and happier staff.",
    },
];

const AUTOPLAY_INTERVAL = 4000;

const track = ref<HTMLElement | null>(null);
const isDragging = ref(false);

let dragStartX = 0;
let dragStartScroll = 0;
let autoplayTimer: ReturnType<typeof setInterval> | null = null;

function cardStep() {
    const el = track.value;
    if (!el) return 0;

    const card = el.firstElementChild as HTMLElement | null;
    if (!card) return el.clientWidth;

    const gap = parseFloat(getComputedStyle(el).columnGap || "0");
    return card.offsetWidth + gap;
}

function go(direction: number) {
    const el = track.value;
    if (!el) return;

    const atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4;
    const atStart = el.scrollLeft <= 4;

    if (direction > 0 && atEnd) {
        el.scrollTo({ left: 0, behavior: "smooth" });
    } else if (direction < 0 && atStart) {
        el.scrollTo({ left: el.scrollWidth, behavior: "smooth" });
    } else {
        el.scrollBy({ left: direction * cardStep(), behavior: "smooth" });
    }
}

function startAutoplay() {
    stopAutoplay();
    autoplayTimer = setInterval(() => go(1), AUTOPLAY_INTERVAL);
}

function stopAutoplay() {
    if (autoplayTimer) {
        clearInterval(autoplayTimer);
        autoplayTimer = null;
    }
}

function onMouseLeave() {
    if (!isDragging.value) startAutoplay();
}

function onPointerDown(e: PointerEvent) {
    if (e.pointerType !== "mouse") return;

    const el = track.value;
    if (!el) return;

    isDragging.value = true;
    dragStartX = e.clientX;
    dragStartScroll = el.scrollLeft;
    el.setPointerCapture(e.pointerId);
    stopAutoplay();
    e.preventDefault();
}

function onPointerMove(e: PointerEvent) {
    if (!isDragging.value) return;

    const el = track.value;
    if (!el) return;

    el.scrollLeft = dragStartScroll - (e.clientX - dragStartX);
}

function onPointerUp(e: PointerEvent) {
    const el = track.value;
    if (el?.hasPointerCapture(e.pointerId)) {
        el.releasePointerCapture(e.pointerId);
    }

    isDragging.value = false;
    startAutoplay();
}

onMounted(startAutoplay);
onBeforeUnmount(stopAutoplay);
</script>
