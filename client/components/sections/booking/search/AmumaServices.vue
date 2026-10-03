<template>
    <section class="py-20 font-sans">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-16">
                <div class="flex items-center justify-center gap-2.5">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"
                        ></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-primary"></span>
                    </span>
                    <span
                        class="text-xs font-bold tracking-[0.16em] uppercase text-primary dark:text-primary-300"
                    >
                        Simple process
                    </span>
                </div>

                <h2
                    class="mt-3 text-2xl md:text-3xl font-bold text-secondary dark:text-white"
                >
                    Book care in 4 steps
                </h2>

                <p
                    class="mt-4 text-sm text-muted max-w-xl mx-auto leading-relaxed dark:text-gray-400"
                >
                    Easily find caregivers, book services, monitor care
                    activities, and manage payments all in one simple and secure
                    platform.
                </p>
            </div>

            <div class="relative">
                <div
                    class="hidden lg:block absolute top-7 left-[12.5%] right-[12.5%] border-t-2 border-primary-200 dark:border-primary-500/25"
                    aria-hidden="true"
                ></div>

                <div class="relative grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-10">
                    <div v-for="(module, index) in modules" :key="module.title">
                        <div class="relative inline-flex">
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-full border border-muted-light bg-white dark:bg-secondary dark:border-white/10"
                            >
                                <component
                                    :is="module.icon"
                                    class="h-5 w-5 text-primary dark:text-primary-300"
                                />
                            </div>

                            <span
                                class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white ring-2 ring-white dark:ring-secondary"
                            >
                                {{ index + 1 }}
                            </span>
                        </div>

                        <h3 class="mt-4 text-sm font-bold text-secondary dark:text-white">
                            {{ module.title }}
                        </h3>

                        <p class="mt-1.5 text-xs text-muted leading-relaxed dark:text-gray-400">
                            {{ module.description }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { Search, CalendarCheck, Bell, CreditCard } from "lucide-vue-next";

const modules = [
    {
        icon: Search,
        title: "Find care services",
        description:
            "Search for caregivers, agencies, and nearby care facilities based on your needs.",
    },
    {
        icon: CalendarCheck,
        title: "Book appointments",
        description:
            "Schedule homecare visits or reserve available care services quickly and easily.",
    },
    {
        icon: Bell,
        title: "Stay updated",
        description:
            "Receive updates about care activities, schedules, and patient information.",
    },
    {
        icon: CreditCard,
        title: "Payments",
        description:
            "View invoices and manage secure payments for care services.",
    },
];
</script>