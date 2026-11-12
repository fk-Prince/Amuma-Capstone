<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { ChevronDown, ChevronUp } from "lucide-vue-next";
import Subscription from "~/components/sections/subscription/Subscription.vue";

const COMPACT_KEY = "amuma_subscription_compact";

const compact = ref(false);

onMounted(() => {
    try {
        compact.value = localStorage.getItem(COMPACT_KEY) === "1";
    } catch {}
});

watch(compact, (value) => {
    try {
        localStorage.setItem(COMPACT_KEY, value ? "1" : "0");
    } catch {}
});

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
                class="mx-auto flex h-full min-h-0 w-full sm:w-[88%] max-w-[1600px] flex-col px-4 py-4 sm:px-10"
            >
                <div class="flex min-h-0 flex-1 flex-col gap-3">
                    <div class="max-w-2xl shrink-0">
                        <!-- <span
                            class="inline-block text-xs font-semibold tracking-wide uppercase text-primary bg-primary-50 px-3 py-1 rounded-full mb-2 mt-2 dark:bg-primary-500/10"
                        >
                            Subscription setup
                        </span> -->

                        <div class="flex items-center justify-between gap-3">
                            <h1
                                class="font-extrabold text-secondary dark:text-white md:text-3xl"
                                :class="compact ? 'text-lg lg:text-2xl' : 'text-2xl'"
                            >
                                Subscription details
                            </h1>

                            <button
                                type="button"
                                class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-primary transition hover:bg-primary-50 lg:hidden dark:hover:bg-primary-500/10"
                                :aria-expanded="!compact"
                                @click="compact = !compact"
                            >
                                {{ compact ? "Show details" : "Hide details" }}
                                <component
                                    :is="compact ? ChevronDown : ChevronUp"
                                    class="h-3.5 w-3.5"
                                />
                            </button>
                        </div>

                        <p
                            class="mt-1.5 text-sm text-muted dark:text-gray-400"
                            :class="compact ? 'hidden lg:block' : ''"
                        >
                            Complete your subscription by reviewing your plan,
                            agency and branch information, and payment
                            details.
                        </p>
                    </div>

                    <div class="min-h-0 w-full flex-1">
                        <Subscription :compact="compact" />
                    </div>
                </div>
            </div>
        </div>
    </ClientOnly>
</template>