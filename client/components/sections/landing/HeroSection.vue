<script setup lang="ts">
import { ref } from "vue";
import { Star } from "lucide-vue-next";
import { heroImage } from "~/composables/useHeroImages";

const reviewers = [
    { initials: "BS", tone: "bg-rose-400" },
    { initials: "AW", tone: "bg-primary" },
    { initials: "AM", tone: "bg-teal-500" },
    { initials: "JR", tone: "bg-violet-400" },
];


const lightImg = heroImage("agency", "light");
const darkImg = heroImage("family", "dark");
const saved = ref(false);

const badges = [
    {
        label: "eMAR & Vitals",
        icon: '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />',
    },
    {
        label: "QR Check-in",
        icon: '<rect x="3" y="3" width="7" height="7" /><rect x="14" y="3" width="7" height="7" /><rect x="14" y="14" width="7" height="7" /><rect x="3" y="14" width="7" height="7" />',
    },
    {
        label: "HIPAA Compliant",
        icon: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />',
    },
    {
        label: "24/7 Support",
        icon: '<circle cx="12" cy="12" r="10" /><polyline points="12 6 12 12 16 14" />',
    },
];
</script>

<template>
    <section
        class="relative flex min-h-screen items-center overflow-visible bg-slate-50 pt-36 pb-32 transition-colors duration-500 dark:bg-secondary"
    >
        <div
            class="pointer-events-none absolute -top-[120px] -right-[80px] h-[520px] w-[520px] rounded-full bg-blue-300 opacity-35 blur-[70px] dark:opacity-15"
        ></div>

        <div
            class="pointer-events-none absolute -left-[60px] bottom-0 h-[320px] w-[320px] rounded-full bg-indigo-200 opacity-35 blur-[70px] dark:opacity-10"
        ></div>

        <div
            class="mx-auto flex w-[94%] max-w-[1600px] items-center gap-12 px-10 max-sm:px-4 max-lg:flex-col"
        >
            <div class="max-w-[680px] flex-[0_0_50%]">
                <div
                    class="relative h-[10px] w-[10px] animate-pulse rounded-full bg-primary shadow-[0_0_25px_rgba(49,130,237,0.8)]"
                >
                    <div
                        class="absolute -inset-3 rounded-full bg-primary/25 blur-[9px]"
                    ></div>
                </div>

                <h1
                    class="mt-8 mb-4 text-[clamp(3.2rem,4.1vw,4.1rem)] font-black leading-[0.85] tracking-[-0.045em] text-secondary motion-safe:animate-fadeInUp lg:whitespace-nowrap dark:text-white"
                >
                    <span class="block">
                        All-in-One
                        <span
                            class="bg-gradient-to-br from-primary to-blue-700 bg-clip-text text-transparent dark:to-primary-300"
                        >
                            Caregiving
                        </span>
                    </span>
                    <span class="block">Management System</span>
                </h1>

                <p
                    class="max-w-[640px] text-[0.95rem] leading-8 text-muted motion-safe:animate-fadeInUp dark:text-gray-400"
                    style="animation-delay: 120ms"
                >
                    Streamline home-care bookings, facility management, and
                    patient monitoring — all from one unified platform built for
                    modern care agencies.
                </p>

                <span
                    class="mt-4 mb-7 block h-[3px] w-20 rounded bg-primary motion-safe:animate-fadeInUp"
                    style="animation-delay: 180ms"
                ></span>

                <div
                    class="mb-6 flex flex-wrap gap-3 motion-safe:animate-fadeInUp"
                    style="animation-delay: 240ms"
                >
                    <NuxtLink
                        to="/booking/search"
                        class="flex items-center gap-2 rounded-xl bg-primary px-7 py-3.5 text-[0.95rem] font-bold text-white transition hover:bg-blue-700"
                    >
                        <svg
                            width="15"
                            height="15"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>

                        Search Provider
                    </NuxtLink>

                    <NuxtLink
                        to="/agencies"
                        class="rounded-xl border border-primary px-7 py-3.5 text-[0.95rem] font-bold text-primary transition hover:bg-primary hover:text-white"
                    >
                        Scale Your Agency Today
                    </NuxtLink>
                </div>

                <div
                    class="mb-8 flex flex-wrap items-center gap-x-4 gap-y-3 motion-safe:animate-fadeInUp"
                    style="animation-delay: 300ms"
                >
                    <NuxtLink
                        v-for="badge in badges"
                        :key="badge.label"
                        to="/agencies#features"
                        class="group flex items-center gap-2 text-xs text-muted transition-colors hover:text-primary dark:text-gray-400"
                    >
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-white shadow-md transition-transform duration-300 group-hover:scale-110 dark:bg-secondary-800"
                        >
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="#3182ED"
                                stroke-width="2"
                                v-html="badge.icon"
                            />
                        </span>

                        {{ badge.label }}
                    </NuxtLink>
                </div>

                <a
                    href="#testimonials"
                    class="group flex w-fit items-center gap-4 motion-safe:animate-fadeInUp"
                    style="animation-delay: 360ms"
                    @click.prevent="scrollToId('testimonials')"
                >
                    <div class="flex items-center">
                        <span
                            v-for="(person, i) in reviewers"
                            :key="person.initials"
                            class="flex h-10 w-10 items-center justify-center rounded-full border-2 border-white text-[11px] font-bold text-white shadow-sm ring-1 ring-black/5 transition-transform duration-300 group-hover:-translate-y-0.5 dark:border-secondary"
                            :class="[person.tone, i > 0 ? '-ml-3' : '']"
                            :style="{ transitionDelay: `${i * 40}ms` }"
                        >
                            {{ person.initials }}
                        </span>

                        <span
                            class="-ml-3 flex h-10 min-w-10 items-center justify-center rounded-full border-2 border-white bg-slate-100 px-1.5 text-[11px] font-bold text-muted-dark shadow-sm ring-1 ring-black/5 dark:border-secondary dark:bg-secondary-800 dark:text-gray-300"
                        >
                            +124
                        </span>
                    </div>

                    <div class="h-10 w-px bg-gray-200 dark:bg-white/10"></div>

                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-xl font-black leading-none text-secondary dark:text-white"
                            >
                                4.9
                            </span>

                            <div
                                class="flex gap-0.5"
                                role="img"
                                aria-label="Rated 4.9 out of 5 stars"
                            >
                                <Star
                                    v-for="n in 5"
                                    :key="n"
                                    class="h-4 w-4 fill-amber-400 text-amber-400"
                                />
                            </div>
                        </div>

                        <p
                            class="mt-1 text-xs text-muted-dark dark:text-gray-300"
                        >
                            from 128+ reviews
                            <span class="text-muted dark:text-gray-500">·</span>
                            <span
                                class="text-muted transition-colors group-hover:text-primary dark:text-gray-400"
                            >
                                Loved by caregivers and agencies
                            </span>
                        </p>
                    </div>
                </a>
            </div>

            <div
                class="flex min-w-0 flex-1 justify-start max-lg:mt-0 max-md:hidden"
            >
                <div
                    class="pointer-events-none relative w-full max-w-none shrink-0 motion-safe:animate-fadeInUp lg:w-[104%] xl:-ml-32 xl:w-[122%]"
                    style="animation-delay: 200ms"
                >
                    <!-- Screenshot (tilt pivots on the right edge so it stays on screen) -->
                    <div
                        class="relative w-full origin-right overflow-hidden rounded-xl bg-white shadow-[0_30px_80px_rgba(49,130,237,0.18)] ring-1 ring-black/5 transition-[background-color,box-shadow] duration-500 [transform:perspective(1800px)_rotateY(-12deg)_rotateX(3deg)] dark:bg-secondary-900 dark:shadow-[0_30px_80px_rgba(0,0,0,0.5)] dark:ring-white/10"
                    >
                        <!-- macOS-style window bar -->
                        <div
                            class="flex items-center gap-3 border-b border-gray-100 bg-slate-50/90 px-4 py-2.5 transition-colors duration-500 dark:border-white/10 dark:bg-secondary-800"
                        >
                            <div class="flex gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-red-300"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-300"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                            </div>
                            <div
                                class="mx-auto h-5 w-1/2 rounded-md bg-white shadow-inner dark:bg-white/5"
                            ></div>
                            <span class="w-[42px]"></span>
                        </div>

                        <div class="relative grid overflow-hidden">
                            <img
                                v-if="lightImg"
                                :src="lightImg"
                                alt="AMUMA agency owner dashboard"
                                decoding="async"
                                class="hero-shot col-start-1 row-start-1 block h-auto w-full opacity-100 dark:scale-[1.03] dark:opacity-0"
                            />

                            <img
                                v-if="darkImg"
                                :src="darkImg"
                                alt="AMUMA family member portal"
                                decoding="async"
                                class="hero-shot col-start-1 row-start-1 block h-auto w-full scale-[0.97] opacity-0 dark:scale-100 dark:opacity-100"
                            />
                        </div>
                    </div>

                    <!-- Floating cards -->
                    <div
                        class="pointer-events-auto absolute -bottom-24 left-4 flex items-end gap-3 xl:left-32"
                    >
                        <div
                            class="relative flex h-[210px] w-[190px] flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-br from-primary to-blue-600 p-5 shadow-[0_20px_50px_rgba(37,99,235,0.3)] motion-safe:animate-floatA"
                        >
                            <div
                                class="absolute -top-2.5 -right-10 h-[140px] w-[140px] rounded-full bg-white/10"
                            ></div>

                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur-md"
                            >
                                <svg
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="white"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"
                                    />
                                </svg>
                            </div>

                            <div>
                                <h3 class="text-lg font-extrabold text-white">
                                    AMUMA
                                </h3>

                                <p class="text-xs text-white/70">
                                    To nurture, to care, to love
                                </p>
                            </div>
                        </div>

                        <div
                            class="w-[280px] rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_18px_40px_rgba(15,23,42,0.14)] motion-safe:animate-floatB dark:border-white/10 dark:bg-secondary-800"
                        >
                            <div class="mb-3 flex items-center gap-3">
                                <div
                                    class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-orange-600 text-sm font-bold text-white shadow-md shadow-amber-500/25"
                                >
                                    BW

                                    <span
                                        class="absolute right-0 bottom-0 h-3.5 w-3.5 rounded-full border-2 border-white bg-green-500 dark:border-secondary-800"
                                    >
                                        <span
                                            class="absolute inset-0 rounded-full bg-green-500 opacity-60 motion-safe:animate-ping"
                                        ></span>
                                    </span>
                                </div>

                                <div class="min-w-0">
                                    <h4
                                        class="truncate text-sm font-bold text-secondary dark:text-white"
                                    >
                                        Bunny Wawa
                                    </h4>

                                    <p
                                        class="text-xs text-muted dark:text-gray-400"
                                    >
                                        Caregiver
                                    </p>
                                </div>

                                <span
                                    class="ml-auto inline-flex shrink-0 items-center gap-1 rounded-full bg-green-50 px-2 py-0.5 text-[11px] font-bold text-green-600 dark:bg-green-500/15 dark:text-green-400"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-green-500"
                                    ></span>
                                    Available
                                </span>
                            </div>

                            <div
                                class="mb-3 flex items-center gap-1.5 text-xs text-muted-dark dark:text-gray-300"
                            >
                                <Star
                                    class="h-3.5 w-3.5 fill-amber-400 text-amber-400"
                                />
                                <strong class="text-secondary dark:text-white"
                                    >4.9</strong
                                >
                                <span class="text-muted dark:text-gray-400"
                                    >(128 reviews)</span
                                >
                            </div>

                            <div class="flex gap-2">
                                <NuxtLink
                                    to="/booking/search"
                                    class="flex-1 rounded-lg bg-primary py-2 text-center text-xs font-bold text-white transition hover:bg-primary-600"
                                >
                                    View Agency
                                </NuxtLink>

                                <button
                                    type="button"
                                    :aria-pressed="saved"
                                    aria-label="Save this agency"
                                    class="flex h-9 w-9 items-center justify-center rounded-lg border text-lg transition active:scale-90"
                                    :class="
                                        saved
                                            ? 'border-primary bg-primary/10 text-primary'
                                            : 'border-gray-200 text-primary dark:border-white/10'
                                    "
                                    @click="saved = !saved"
                                >
                                    {{ saved ? "♥" : "♡" }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.hero-shot {
    transition:
        opacity 0.6s ease,
        transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
}

@media (prefers-reduced-motion: reduce) {
    .hero-shot {
        transition: none;
    }
}
</style>