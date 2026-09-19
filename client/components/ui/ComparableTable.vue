<template>
    <div class="mt-20">
        <h2
            class="font-display font-bold text-2xl text-center text-gray-900 mb-2 dark:text-white"
        >
            Compare Features
        </h2>
        <p class="text-center text-gray-500 text-sm mb-8 dark:text-gray-400">
            See what's included in each plan
        </p>

        <!-- Mobile: one card per feature, since a 4-column table doesn't fit
             a phone width without forcing a horizontal scroll. -->
        <div class="sm:hidden space-y-3">
            <div
                v-for="row in features"
                :key="row.name"
                class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <p class="font-medium text-gray-700 dark:text-gray-300">
                    {{ row.name }}
                </p>

                <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                    <div class="flex flex-col items-center gap-1">
                        <FeatureIcon :active="row.homecare" />
                        <span class="text-gray-500 dark:text-gray-400">Homecare</span>
                    </div>

                    <div class="flex flex-col items-center gap-1">
                        <FeatureIcon :active="row.facility" />
                        <span class="text-gray-500 dark:text-gray-400">Facility</span>
                    </div>

                    <div class="flex flex-col items-center gap-1 rounded-lg bg-primary/[0.04] py-1">
                        <FeatureIcon :active="row.hybrid" variant="hybrid" />
                        <span class="font-semibold text-primary">Hybrid</span>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="hidden sm:block overflow-x-auto rounded-2xl border border-gray-100 shadow-sm dark:border-white/10"
        >
            <table class="w-full text-sm min-w-[560px]">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 dark:bg-white/5 dark:border-white/10">
                        <th
                            class="text-left px-6 py-4 font-semibold text-gray-600 w-1/2 dark:text-gray-300"
                        >
                            Features
                        </th>
                        <th
                            class="text-center px-4 py-4 font-semibold text-gray-600 dark:text-gray-300"
                        >
                            Homecare
                        </th>
                        <th
                            class="text-center px-4 py-4 font-semibold text-gray-600 dark:text-gray-300"
                        >
                            Facility
                        </th>
                        <th class="text-center px-4 py-4">
                            <div class="flex flex-col items-center gap-1">
                                <span
                                    class="text-[11px] font-bold tracking-wide bg-primary/10 text-primary px-2 py-0.5 rounded-full"
                                >
                                    Most Popular
                                </span>
                                <span class="font-semibold text-primary">
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
                            'group border-b border-gray-50 last:border-b-0 transition-colors hover:bg-blue-50/40 dark:border-white/10 dark:hover:bg-white/5',
                            i % 2 === 0
                                ? 'bg-white dark:bg-secondary'
                                : 'bg-gray-50/50 dark:bg-white/[0.03]',
                        ]"
                    >
                        <td class="px-6 py-4 font-medium text-gray-700 dark:text-gray-300">
                            {{ row.name }}
                        </td>

                        <td class="text-center px-4 py-4">
                            <FeatureIcon
                                :active="row.homecare"
                                class="mx-auto transition-transform duration-200 group-hover:scale-110"
                            />
                        </td>

                        <td class="text-center px-4 py-4">
                            <FeatureIcon
                                :active="row.facility"
                                class="mx-auto transition-transform duration-200 group-hover:scale-110"
                            />
                        </td>

                        <td
                            class="text-center px-4 py-4 bg-primary/[0.04] group-hover:bg-primary/[0.07] transition-colors"
                        >
                            <FeatureIcon
                                :active="row.hybrid"
                                variant="hybrid"
                                class="mx-auto transition-transform duration-200 group-hover:scale-110"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { h } from "vue";

const FeatureIcon = (props) =>
    props.active
        ? h(
              "svg",
              {
                  class: ["w-5 h-5", props.class],
                  viewBox: "0 0 20 20",
                  fill: "none",
              },
              [
                  h("circle", {
                      cx: "10",
                      cy: "10",
                      r: "9",
                      fill: props.variant === "hybrid" ? "#2563eb" : "#16a34a",
                  }),
                  h("path", {
                      d: "M6.5 10.2l2.2 2.2 4.8-4.8",
                      stroke: "white",
                      "stroke-width": "2",
                      "stroke-linecap": "round",
                      "stroke-linejoin": "round",
                  }),
              ],
          )
        : h(
              "svg",
              {
                  class: [
                      "w-4 h-4 text-gray-300 dark:text-gray-600",
                      props.class,
                  ],
                  viewBox: "0 0 16 16",
                  fill: "none",
              },
              [
                  h("path", {
                      d: "M4 8h8",
                      stroke: "currentColor",
                      "stroke-width": "1.8",
                      "stroke-linecap": "round",
                  }),
              ],
          );

const features = [
    {
        name: "Homecare visits scheduling",
        homecare: true,
        facility: false,
        hybrid: true,
    },
    {
        name: "Admissions, rooms & beds",
        homecare: false,
        facility: true,
        hybrid: true,
    },
    {
        name: "Online & walk-in bookings",
        homecare: true,
        facility: true,
        hybrid: true,
    },
    {
        name: "Caregiver QR clock-in tracking",
        homecare: true,
        facility: false,
        hybrid: true,
    },
    {
        name: "Electronic Medication Administration Record (eMAR) & vital signs",
        homecare: true,
        facility: true,
        hybrid: true,
    },
    {
        name: "Family portal & messaging",
        homecare: true,
        facility: true,
        hybrid: true,
    },
    {
        name: "VIP & Common room contracts",
        homecare: false,
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
        name: "Up to 5 branches on one subscription",
        homecare: true,
        facility: true,
        hybrid: true,
    },
    {
        name: "Per-branch staff, services & settings",
        homecare: true,
        facility: true,
        hybrid: true,
    },
    {
        name: "Billing, invoices & online payments",
        homecare: true,
        facility: true,
        hybrid: true,
    },
];
</script>
