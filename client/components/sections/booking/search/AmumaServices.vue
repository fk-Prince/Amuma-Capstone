<template>
    <section class="py-20 font-sans">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-14">
                <div class="flex items-center justify-center gap-2.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-accent"></span>
                    <span
                        class="text-xs font-medium tracking-[0.16em] uppercase text-muted"
                    >
                        Care services
                    </span>
                </div>

                <h2
                    class="mt-3 text-2xl md:text-3xl font-medium text-secondary"
                >
                    Everything you need for quality homecare
                </h2>

                <p
                    class="mt-4 text-sm text-muted max-w-xl mx-auto leading-relaxed"
                >
                    Easily find caregivers, book services, monitor care
                    activities, and manage payments all in one simple and secure
                    platform.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    v-for="module in modules"
                    :key="module.title"
                    class="group border border-muted-light rounded-2xl p-5 bg-white hover:border-primary-200 hover:shadow-md hover:-translate-y-1 transition-all"
                >
                    <div
                        class="w-10 h-10 rounded-lg flex items-center justify-center"
                        :class="module.iconBg"
                    >
                        <component
                            :is="module.icon"
                            class="w-5 h-5"
                            :class="module.iconColor"
                        />
                    </div>

                    <div class="mt-4">
                        <h3 class="text-sm font-medium text-secondary">
                            {{ module.title }}
                        </h3>

                        <p class="text-xs text-muted mt-1.5 leading-relaxed">
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
        iconBg: "bg-primary-50",
        iconColor: "text-primary-600",
        title: "Find care services",
        description:
            "Search for caregivers, agencies, and nearby care facilities based on your needs.",
    },
    {
        icon: CalendarCheck,
        iconBg: "bg-accent-50",
        iconColor: "text-accent-600",
        title: "Book appointments",
        description:
            "Schedule homecare visits or reserve available care services quickly and easily.",
    },
    {
        icon: Bell,
        iconBg: "bg-primary-50",
        iconColor: "text-primary-600",
        title: "Stay updated",
        description:
            "Receive updates about care activities, schedules, and patient information.",
    },
    {
        icon: CreditCard,
        iconBg: "bg-accent-50",
        iconColor: "text-accent-600",
        title: "Payments",
        description:
            "View invoices and manage secure payments for care services.",
    },
];
</script>
