<script setup lang="ts">
import Subscription from "~/components/sections/subscription/Subscription.vue";

definePageMeta({
    middleware: ["auth-client", "prevent-staff-subscription"],
    navVariant: 3,
    // The wizard fills the screen on its own; no page footer to scroll to.
    footer: false,
});

useHead({ title: "Subscription Details" });
</script>
<template>
    <ClientOnly>
        <div class="h-dvh overflow-hidden bg-slate-50 pt-[124px] dark:bg-secondary">
            <div
                class="mx-auto flex h-full min-h-0 w-[88%] max-w-[1600px] flex-col px-4 py-4 sm:px-10"
            >
                <div class="flex min-h-0 flex-1 flex-col gap-3">
                    <div class="max-w-2xl shrink-0">
                        <!-- <span
                            class="inline-block text-xs font-semibold tracking-wide uppercase text-primary bg-primary-50 px-3 py-1 rounded-full mb-2 mt-2 dark:bg-primary-500/10"
                        >
                            Subscription setup
                        </span> -->

                        <h1
                            class="text-2xl md:text-3xl font-extrabold text-secondary dark:text-white"
                        >
                            Subscription details
                        </h1>

                        <p class="mt-1.5 text-sm text-muted dark:text-gray-400">
                            Complete your subscription by reviewing your plan,
                            agency and branch information, and payment
                            details.
                        </p>
                    </div>

                    <div class="min-h-0 w-full flex-1">
                        <Subscription />
                    </div>
                </div>
            </div>
        </div>
    </ClientOnly>
</template>