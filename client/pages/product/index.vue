<template>
    <div class="min-h-screen pt-[50px] bg-white dark:bg-surface">
        <main class="max-w-6xl mx-auto px-6 py-16">
            <div class="text-center mb-10">
                <p
                    class="text-xs font-bold tracking-[0.2em] text-primary uppercase mb-4"
                >
                    Pricing
                </p>

                <h1
                    class="font-display font-extrabold text-4xl md:text-5xl text-gray-900 leading-tight dark:text-white"
                >
                    One calm price. All the
                    <span class="text-primary">features</span> you need.
                </h1>
            </div>

            <div class="flex justify-center mb-12">
                <BillingToggle v-model="billingCycle" />
            </div>

            <div
                v-if="loading"
                class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch"
            >
                <div
                    v-for="i in 3"
                    :key="i"
                    class="rounded-3xl border border-gray-200 p-8 animate-pulse dark:border-white/10 dark:bg-secondary"
                >
                    <div
                        class="h-4 w-20 bg-gray-200 rounded mb-6 dark:bg-white/10"
                    ></div>

                    <div
                        class="h-8 w-40 bg-gray-200 rounded mb-4 dark:bg-white/10"
                    ></div>

                    <div class="space-y-2 mb-8">
                        <div class="h-4 bg-gray-200 rounded dark:bg-white/10"></div>
                        <div
                            class="h-4 w-5/6 bg-gray-200 rounded dark:bg-white/10"
                        ></div>
                    </div>

                    <div
                        class="h-10 w-28 bg-gray-200 rounded mb-8 dark:bg-white/10"
                    ></div>

                    <div
                        class="h-12 bg-gray-200 rounded-xl mb-8 dark:bg-white/10"
                    ></div>

                    <div class="space-y-4">
                        <div
                            v-for="j in 5"
                            :key="j"
                            class="flex items-center gap-3"
                        >
                            <div
                                class="w-5 h-5 rounded-full bg-gray-200 flex-shrink-0 dark:bg-white/10"
                            ></div>
                            <div
                                class="h-4 flex-1 bg-gray-200 rounded dark:bg-white/10"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
            <div
                v-else
                class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch"
            >
                <PricingCard
                    v-for="plan in formattedPlans"
                    :key="plan.title"
                    v-bind="plan"
                    :billingInterval="billingCycle"
                    @select="checkout.setSelectedPlan(plan)"
                />
            </div>

            <ComparableTable />
        </main>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import BillingToggle from "~/components/ui/BillingToggle.vue";
import PricingCard from "~/components/ui/PricingCard.vue";
import ComparableTable from "~/components/ui/ComparableTable.vue";
import { usePlanCards } from "~/composables/usePlanCards";

useHead({ title: "Product" });

definePageMeta({
    layout: "default",
    navVariant: 1,
    navTheme: "light",
});

const billingCycle = ref<"monthly" | "yearly">("monthly");
const { checkout, loading, formattedPlans } = usePlanCards(billingCycle);
</script>
