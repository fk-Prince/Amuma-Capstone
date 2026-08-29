<template>
    <footer class="bg-[#0f1623] text-white">
        <div class="mx-auto max-w-7xl px-6 py-16">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <img
                            :src="logoAmuma"
                            alt="AMUMA logo"
                            class="w-[200px] h-[50px] object-contain"
                        />
                    </div>
                    <p
                        class="text-gray-400 text-sm leading-relaxed max-w-[220px]"
                    >
                        Modern caregiving management for the digital age.
                    </p>
                </div>

                <div class="flex flex-col gap-4">
                    <h4
                        class="text-xs font-semibold tracking-widest text-white uppercase"
                    >
                        Navigate
                    </h4>
                    <nav class="flex flex-col gap-3">
                        <NuxtLink
                            v-for="item in navList"
                            :key="item.to"
                            :to="item.to"
                            class="text-gray-400 text-sm hover:text-white transition-colors"
                        >
                            {{ item.label }}
                        </NuxtLink>
                    </nav>
                </div>

                <div class="flex flex-col gap-4">
                    <h4
                        class="text-xs font-semibold tracking-widest text-white uppercase"
                    >
                        Company
                    </h4>
                    <nav class="flex flex-col gap-3">
                        <NuxtLink
                            v-for="item in companyLinks"
                            :key="item.to"
                            :to="item.to"
                            class="text-gray-400 text-sm hover:text-white transition-colors"
                        >
                            {{ item.label }}
                        </NuxtLink>
                    </nav>
                </div>

                <div class="flex flex-col gap-4">
                    <h4
                        class="text-xs font-semibold tracking-widest text-white uppercase"
                    >
                        Socials
                    </h4>
                    <nav class="flex flex-col gap-3">
                        <a
                            href="https://instagram.com"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-center gap-2.5 text-gray-400 text-sm hover:text-white transition-colors"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5"
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
                            class="flex items-center gap-2.5 text-gray-400 text-sm hover:text-white transition-colors"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5"
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
            <div class="mx-auto max-w-7xl px-6 py-6">
                <p class="text-gray-500 text-sm">
                    © {{ currentYear }} AMUMA. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</template>

<script setup lang="ts">
import logoAmuma from "~/assets/logo/logoAmuma.png";
import { computed } from "vue";
import { navList } from "~/config/publicMenu";

const currentYear = computed(() => new Date().getFullYear());

const companyLinks = [
    { label: "About", to: "/" },
    { label: "Contact", to: "/" },
];
</script>
