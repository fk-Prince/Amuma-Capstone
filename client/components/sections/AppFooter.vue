<template>
    <footer class="bg-[#0f1623] text-white">
        <div :class="[container, 'py-16']">
            <div class="grid grid-cols-1 gap-x-12 gap-y-10 md:grid-cols-4">
                <div class="flex flex-col gap-4">
                    <div class="flex h-8 items-center">
                        <img
                            :src="logoAmuma"
                            alt="AMUMA logo"
                            class="h-8 w-auto object-contain object-left"
                        />
                    </div>
                    <p class="max-w-[240px] text-sm leading-6 text-gray-400">
                        Modern caregiving management for the digital age.
                    </p>
                </div>

                <div class="flex flex-col gap-4">
                    <h4 :class="headingClass">Navigate</h4>
                    <nav class="flex flex-col gap-2">
                        <NuxtLink
                            v-for="item in navList"
                            :key="item.to"
                            :to="item.to"
                            :class="linkClass"
                        >
                            {{ item.label }}
                        </NuxtLink>
                    </nav>
                </div>

                <div class="flex flex-col gap-4">
                    <h4 :class="headingClass">Learn more</h4>
                    <nav class="flex flex-col gap-2">
                        <NuxtLink
                            v-for="item in learnLinks"
                            :key="item.to"
                            :to="item.to"
                            :class="linkClass"
                        >
                            {{ item.label }}
                        </NuxtLink>
                    </nav>
                </div>

                <div class="flex flex-col gap-4">
                    <h4 :class="headingClass">Socials</h4>
                    <nav class="flex flex-col gap-2">
                        <a
                            href="https://instagram.com"
                            target="_blank"
                            rel="noopener noreferrer"
                            :class="[linkClass, 'flex items-center gap-2']"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 shrink-0"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <rect
                                    x="2"
                                    y="2"
                                    width="20"
                                    height="20"
                                    rx="5"
                                    ry="5"
                                />
                                <circle cx="12" cy="12" r="4" />
                                <circle
                                    cx="17.5"
                                    cy="6.5"
                                    r="0.8"
                                    fill="currentColor"
                                    stroke="none"
                                />
                            </svg>
                            Instagram
                        </a>
                        <a
                            href="https://facebook.com"
                            target="_blank"
                            rel="noopener noreferrer"
                            :class="[linkClass, 'flex items-center gap-2']"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4 shrink-0"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                            >
                                <path
                                    d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"
                                />
                            </svg>
                            Facebook
                        </a>
                    </nav>
                </div>
            </div>
        </div>

        <div class="border-t border-white/10">
            <div :class="[container, 'py-6']">
                <p class="text-sm leading-6 text-gray-500">
                    © {{ currentYear }} AMUMA. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</template>

<script setup lang="ts">
import logoAmuma from "~/assets/logo/logoAmuma.png";
import { computed } from "vue";
import { useRoute } from "vue-router";
import { navList } from "~/config/publicMenu";

const route = useRoute();

const currentYear = computed(() => new Date().getFullYear());

const container = computed(() => {
    const variant = route.meta.navVariant ?? 1;

    if (variant === 2 || variant === 3)
        return "mx-auto w-[88%] max-w-[1600px] px-4 sm:px-10";
    if (variant === 1 || variant === 4) return "mx-auto max-w-[100rem] px-6";
    if (variant === 5 || variant === 6)
        return "mx-auto w-[94%] max-w-[1400px] px-6";
    if (variant === 7) return "mx-auto max-w-[100rem] px-6 sm:px-10";

    return "px-6";
});

const headingClass =
    "flex h-8 items-center text-xs font-semibold uppercase tracking-widest text-white";
const linkClass =
    "w-fit text-sm leading-6 text-gray-400 transition-colors hover:text-white";

const learnLinks = [
    { label: "About us", to: "/company" },
    { label: "For agencies", to: "/for-agencies" },
    { label: "For families", to: "/for-families" },
    { label: "How booking works", to: "/how-booking-works" },
    { label: "FAQ", to: "/faq" },
];
</script>