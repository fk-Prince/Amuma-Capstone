<template>
    <div class="mt-20">
        <h2
            class="font-display font-bold text-2xl text-center text-gray-900 mb-2"
        >
            Compare Features
        </h2>
        <p class="text-center text-gray-500 text-sm mb-8">
            See what's included in each plan
        </p>

        <div
            class="overflow-x-auto rounded-2xl border border-gray-100 shadow-sm"
        >
            <table class="w-full text-sm min-w-[560px]">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th
                            class="text-left px-6 py-4 font-semibold text-gray-600 w-1/2"
                        >
                            Features
                        </th>
                        <th
                            class="text-center px-4 py-4 font-semibold text-gray-600"
                        >
                            Homecare
                        </th>
                        <th
                            class="text-center px-4 py-4 font-semibold text-gray-600"
                        >
                            Facility
                        </th>
                        <th class="text-center px-4 py-4">
                            <div class="flex flex-col items-center gap-1">
                                <span
                                    class="text-[11px] font-bold tracking-wide bg-brand-blue text-primary px-2 py-0.5 rounded-full"
                                >
                                    Most Popular
                                </span>
                                <span class="font-semibold text-brand-blue">
                                    Hybrid
                                </span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(row, i) in features"
                        :key="row.name"
                        :class="[
                            'group border-b border-gray-50 last:border-b-0 transition-colors hover:bg-blue-50/40',
                            i % 2 === 0 ? 'bg-white' : 'bg-gray-50/50',
                        ]"
                    >
                        <td class="px-6 py-4 font-medium text-gray-700">
                            {{ row.name }}
                        </td>

                        <td class="text-center px-4 py-4">
                            <svg
                                v-if="row.homecare"
                                class="w-5 h-5 mx-auto transition-transform duration-200 group-hover:scale-110"
                                viewBox="0 0 20 20"
                                fill="none"
                            >
                                <circle cx="10" cy="10" r="9" fill="#16a34a" />
                                <path
                                    d="M6.5 10.2l2.2 2.2 4.8-4.8"
                                    stroke="white"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-4 h-4 mx-auto text-gray-300"
                                viewBox="0 0 16 16"
                                fill="none"
                            >
                                <path
                                    d="M4 8h8"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </td>

                        <td class="text-center px-4 py-4">
                            <svg
                                v-if="row.facility"
                                class="w-5 h-5 mx-auto transition-transform duration-200 group-hover:scale-110"
                                viewBox="0 0 20 20"
                                fill="none"
                            >
                                <circle cx="10" cy="10" r="9" fill="#16a34a" />
                                <path
                                    d="M6.5 10.2l2.2 2.2 4.8-4.8"
                                    stroke="white"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-4 h-4 mx-auto text-gray-300"
                                viewBox="0 0 16 16"
                                fill="none"
                            >
                                <path
                                    d="M4 8h8"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </td>

                        <td
                            class="text-center px-4 py-4 bg-brand-blue/[0.04] group-hover:bg-brand-blue/[0.07] transition-colors"
                        >
                            <svg
                                v-if="row.hybrid"
                                class="w-5 h-5 mx-auto transition-transform duration-200 group-hover:scale-110"
                                viewBox="0 0 20 20"
                                fill="none"
                            >
                                <circle cx="10" cy="10" r="9" fill="#2563eb" />
                                <path
                                    d="M6.5 10.2l2.2 2.2 4.8-4.8"
                                    stroke="white"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-4 h-4 mx-auto text-gray-300"
                                viewBox="0 0 16 16"
                                fill="none"
                            >
                                <path
                                    d="M4 8h8"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
const features = [
    {
        name: "Home visits & caregivers",
        homecare: true,
        facility: false,
        hybrid: true,
    },
    {
        name: "Admissions & rooms",
        homecare: false,
        facility: true,
        hybrid: true,
    },
    {
        name: "Online Bookings",
        homecare: true,
        facility: true,
        hybrid: true,
    },
    {
        name: "Electronic Medication Administration Record (eMAR) & Vitals Signs",
        homecare: true,
        facility: true,
        hybrid: true,
    },
    {
        name: "Family portal",
        homecare: true,
        facility: true,
        hybrid: true,
    },
    {
        name: "Multiple branches",
        homecare: true,
        facility: true,
        hybrid: true,
    },
    {
        name: "Services",
        homecare: true,
        facility: true,
        hybrid: true,
    },
    {
        name: "VIP CCTV access",
        homecare: false,
        facility: true,
        hybrid: true,
    },
    {
        name: "Billing & invoices",
        homecare: true,
        facility: true,
        hybrid: true,
    },
];
</script>
