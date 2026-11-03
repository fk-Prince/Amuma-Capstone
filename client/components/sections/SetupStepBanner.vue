<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
    >
        <!-- A slim one-line tip: which setup step this page is, and what's next -->
        <div
            v-if="current && !dismissed"
            data-setup-hint
            role="status"
            class="flex items-center gap-2.5 rounded-xl border px-3 py-2 text-xs shadow-sm sm:gap-3"
            :class="
                allDone
                    ? 'border-emerald-200 bg-emerald-50/80 dark:border-emerald-500/25 dark:bg-emerald-500/10'
                    : 'border-slate-200 bg-white dark:border-white/10 dark:bg-secondary'
            "
        >
            <span
                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                :class="
                    allDone
                        ? 'bg-emerald-500 text-white'
                        : 'bg-primary-50 text-primary dark:bg-primary-500/15 dark:text-primary-300'
                "
            >
                <PartyPopper v-if="allDone" class="h-3.5 w-3.5" />
                <component :is="current.icon" v-else class="h-3.5 w-3.5" />
            </span>

            <p class="min-w-0 flex-1 truncate text-slate-600 dark:text-gray-300">
                <template v-if="allDone">
                    <span class="font-semibold text-emerald-700 dark:text-emerald-300">Setup complete.</span>
                    That's everything. You're all set!
                </template>
                <template v-else>
                    <span class="font-semibold text-primary dark:text-primary-300">
                        Step {{ position }}/{{ steps.length }}
                    </span>
                    <span class="mx-1 text-slate-300 dark:text-gray-600">·</span>
                    <span class="font-semibold text-slate-800 dark:text-white">{{ current.title }}</span>
                    <span v-if="current.tip" class="hidden md:inline">
                        <span class="mx-1 text-slate-300 dark:text-gray-600">—</span>{{ current.tip }}
                    </span>
                </template>
            </p>

            <NuxtLink
                :to="guideLink"
                class="hidden shrink-0 font-semibold text-slate-500 transition hover:text-primary sm:inline dark:text-gray-400"
            >
                {{ allDone ? "Dashboard" : "Guide" }}
            </NuxtLink>

            <NuxtLink
                v-if="!allDone && following"
                :to="following.to"
                class="inline-flex shrink-0 items-center gap-1 font-semibold text-primary transition hover:text-primary-600 dark:text-primary-300"
                :title="`Next: ${following.title}`"
            >
                <span class="hidden lg:inline">Next: {{ following.title }}</span>
                <span class="lg:hidden">Next</span>
                <ArrowRight class="h-3.5 w-3.5" />
            </NuxtLink>

            <button
                type="button"
                aria-label="Hide this tip"
                class="-mr-1 shrink-0 rounded-md p-1 text-slate-400 transition hover:bg-black/5 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-white"
                @click="dismissed = true"
            >
                <X class="h-3.5 w-3.5" />
            </button>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { ArrowRight, PartyPopper, X } from "lucide-vue-next";
import { useSetupSteps, type SetupContext } from "~/composables/useSetupSteps";
import { useSetupBranchUuid, useSetupVisits } from "~/composables/useSetupGuide";

/**
 * Shown on a page that is part of the setup guide: says what to do here and
 * links back to the guide or on to the next step.
 */
const props = defineProps<{ context: SetupContext }>();

const route = useRoute();

// Branch pages carry it in the address; shared pages (My Profile) in ?branch=.
const branchUuid = useSetupBranchUuid();

const { steps, allDone, loadFacts, loadVisits } = useSetupSteps({
    context: () => props.context,
    branchUuid: () => branchUuid.value,
});

const { freshVisit } = useSetupVisits();
const dismissed = ref(false);
// Only celebrate on the page that finished the guide, not on every later visit.
const wasIncomplete = ref(false);

const path = computed(() => route.path.replace(/\/+$/, ""));

const current = computed(() => {
    const step = steps.value.find((s) => s.to === path.value);
    if (!step) return null;
    if (allDone.value) return wasIncomplete.value ? step : null;

    // "Open this page" steps get the tip on the first visit; data-based steps
    // keep it until they're actually done.
    if (step.byVisit) return freshVisit.value === path.value ? step : null;
    return step.done ? null : step;
});

const position = computed(() =>
    current.value
        ? steps.value.findIndex((s) => s.key === current.value!.key) + 1
        : 0,
);

const following = computed(() => {
    const list = steps.value;
    const from = current.value
        ? list.findIndex((s) => s.key === current.value!.key)
        : -1;

    return (
        list.slice(from + 1).find((s) => !s.done) ??
        list.slice(0, Math.max(from, 0)).find((s) => !s.done) ??
        null
    );
});

const guideLink = computed(() => {
    if (props.context === "portal") return "/portal/overview";
    if (props.context === "owner") return "/app/owner/dashboard";
    return `/app/branches/${branchUuid.value}/dashboard`;
});

watch(path, () => {
    dismissed.value = false;
});

watch(
    allDone,
    (done, before) => {
        if (!done) wasIncomplete.value = true;
        else if (before === undefined) wasIncomplete.value = false;
    },
    { immediate: true },
);

onMounted(() => {
    loadVisits();
    loadFacts(false).catch(() => {});
});

watch(branchUuid, () => loadFacts(false).catch(() => {}));
</script>
