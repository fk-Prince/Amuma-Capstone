<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                Online Payments
            </h2>

            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                Receive customer payments straight into your branch's own
                Xendit account.
            </p>
        </div>

        <div
            class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-6 py-10 text-center dark:border-white/15 dark:bg-white/5"
        >
            <div
                class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-slate-400 shadow-sm ring-1 ring-slate-200 dark:bg-secondary dark:text-gray-500 dark:ring-white/10"
            >
                <Lock class="h-5 w-5" />
            </div>

            <span
                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[11px] font-bold text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
            >
                <span class="h-1.5 w-1.5 rounded-full bg-current" />
                Coming soon
            </span>

            <p class="text-base font-semibold text-slate-900 dark:text-white">
                Connecting your own Xendit account is coming soon
            </p>

            <p
                class="max-w-md text-sm leading-6 text-slate-500 dark:text-gray-400"
            >
                Customers can already book and pay online. Until this opens,
                AMUMA collects those payments and records each one under this
                branch.
            </p>

            <button
                type="button"
                disabled
                class="mt-1 inline-flex cursor-not-allowed items-center gap-1.5 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white opacity-50"
            >
                <Lock class="h-4 w-4" />
                Connect Xendit
            </button>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div
                v-for="(item, index) in upcoming"
                :key="item.title"
                class="flex flex-col gap-1.5 rounded-xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-secondary"
            >
                <span
                    class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-50 text-xs font-bold text-primary dark:bg-primary-500/15 dark:text-primary-300"
                >
                    {{ index + 1 }}
                </span>

                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                    {{ item.title }}
                </p>

                <p class="text-xs leading-5 text-slate-500 dark:text-gray-400">
                    {{ item.body }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Lock } from "lucide-vue-next";

const upcoming = [
    {
        title: "Your own Xendit account",
        body: "Booking and portal payments go to your Xendit balance, not AMUMA's.",
    },
    {
        title: "Withdraw to your bank",
        body: "Move your balance to your branch's bank or e-wallet from Xendit.",
    },
    {
        title: "Subscription stays the same",
        body: "Your AMUMA plan is still billed to the card in Subscription & Renewal.",
    },
];
</script>
