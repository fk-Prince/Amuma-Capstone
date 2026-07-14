<template>
    <section class="py-20">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-14">
                <span
                    class="text-xs font-semibold tracking-widest uppercase text-gray-500"
                >
                    Care Services
                </span>

                <h2
                    class="mt-2 text-2xl md:text-3xl font-semibold text-gray-900"
                >
                    Everything you need for quality homecare
                </h2>

                <p
                    class="mt-4 text-sm text-gray-600 max-w-xl mx-auto leading-relaxed"
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
                    class="border border-gray-200 rounded-xl p-5 hover:shadow-md transition bg-white"
                >
                    <div class="flex items-center justify-between">
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
                    </div>

                    <div class="mt-4">
                        <h3 class="text-sm font-semibold text-gray-900">
                            {{ module.title }}
                        </h3>

                        <p class="text-xs text-gray-600 mt-1 leading-relaxed">
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
        iconBg: "bg-indigo-100",
        iconColor: "text-indigo-600",
        title: "Find Care Services",
        description:
            "Search for caregivers, agencies, and nearby care facilities based on your needs.",
    },
    {
        icon: CalendarCheck,
        iconBg: "bg-emerald-100",
        iconColor: "text-emerald-600",
        title: "Book Appointments",
        description:
            "Schedule homecare visits or reserve available care services quickly and easily.",
    },
    {
        icon: Bell,
        iconBg: "bg-amber-100",
        iconColor: "text-amber-600",
        title: "Stay Updated",
        description:
            "Receive updates about care activities, schedules, and patient information.",
    },
    {
        icon: CreditCard,
        iconBg: "bg-blue-100",
        iconColor: "text-blue-600",
        title: "Payments",
        description:
            "View invoices and manage secure payments for care services.",
    },
];
</script>
