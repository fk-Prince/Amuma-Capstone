<template>
    <!-- Header button: progress ring around a checklist icon; opens the full guide -->
    <div v-if="ready && steps.length && !allDone" class="relative">
        <button
            ref="launcherEl"
            type="button"
            class="relative flex h-9 w-9 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/10"
            :class="{
                'bg-primary-50 text-primary dark:bg-primary-500/15 dark:text-primary-300':
                    open,
            }"
            :aria-expanded="open"
            aria-controls="setup-guide-panel"
            :aria-label="`Setup guide, ${doneCount} of ${steps.length} done`"
            :title="`Setup guide · ${doneCount} of ${steps.length} done`"
            data-tour="setup-launcher"
            @click="toggle"
        >
            <svg
                class="pointer-events-none absolute inset-0 h-full w-full -rotate-90"
                viewBox="0 0 36 36"
                aria-hidden="true"
            >
                <circle
                    cx="18"
                    cy="18"
                    r="16"
                    fill="none"
                    stroke-width="2.5"
                    class="stroke-slate-200 dark:stroke-white/15"
                />
                <circle
                    cx="18"
                    cy="18"
                    r="16"
                    fill="none"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    class="stroke-primary transition-[stroke-dashoffset] duration-700"
                    :stroke-dasharray="RING"
                    :stroke-dashoffset="RING - (RING * percent) / 100"
                />
            </svg>

            <ListChecks class="h-[18px] w-[18px]" />

            <span
                class="absolute -right-1.5 -top-1.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white dark:ring-secondary"
                aria-hidden="true"
            >
                {{ steps.length - doneCount }}
            </span>
        </button>

        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
            >
                <section
                    v-if="open"
                    id="setup-guide-panel"
                    ref="panelEl"
                    class="fixed z-[60] flex max-h-[min(36rem,calc(100dvh-8rem))] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/20 dark:border-white/10 dark:bg-secondary"
                    :style="panelStyle"
                    aria-label="Setup guide"
                    data-tour="setup-guide"
                >
                    <header
                        class="border-b border-slate-100 p-4 dark:border-white/10"
                    >
                        <div class="flex items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-bold text-secondary dark:text-white"
                                >
                                    {{ heading }}
                                </p>
                                <p
                                    class="mt-0.5 text-xs leading-5 text-slate-500 dark:text-gray-400"
                                >
                                    {{ subheading }}
                                </p>
                            </div>
                            <button
                                type="button"
                                aria-label="Close setup guide"
                                class="-mr-1 -mt-1 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-white"
                                @click="open = false"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="mt-3">
                            <div
                                class="mb-1 flex items-center justify-between text-[11px] font-semibold"
                            >
                                <span class="text-slate-500 dark:text-gray-400">
                                    {{ doneCount }} of {{ steps.length }} done
                                </span>
                                <span class="text-primary dark:text-primary-300"
                                    >{{ percent }}%</span
                                >
                            </div>
                            <div
                                class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10"
                            >
                                <div
                                    class="h-full rounded-full bg-primary transition-[width] duration-700"
                                    :style="{ width: `${percent}%` }"
                                />
                            </div>
                        </div>
                    </header>

                    <ol class="min-h-0 flex-1 space-y-1 overflow-y-auto p-2">
                        <li
                            v-for="(step, index) in steps"
                            :key="step.key"
                            class="relative"
                            :data-tour="`setup-step-${step.key}`"
                        >
                            <!-- Line to the next step: green once this one is done -->
                            <span
                                v-if="index < steps.length - 1"
                                class="pointer-events-none absolute left-[23px] top-[38px] z-[1] -bottom-[14px] w-0.5 rounded-full"
                                :class="lineClass(step, steps[index + 1])"
                                aria-hidden="true"
                            />

                            <NuxtLink
                                :to="step.to"
                                class="group relative flex items-start gap-3 rounded-xl px-3 py-2.5 transition"
                                :class="
                                    step.key === nextKey
                                        ? 'bg-primary-50 dark:bg-primary-500/10'
                                        : 'hover:bg-slate-50 dark:hover:bg-white/5'
                                "
                                @click="open = false"
                            >
                                <span
                                    class="relative z-[2] mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold"
                                    :class="
                                        step.done
                                            ? 'bg-emerald-500 text-white'
                                            : step.key === nextKey
                                              ? 'bg-primary text-white'
                                              : 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-gray-300'
                                    "
                                >
                                    <Check
                                        v-if="step.done"
                                        class="h-3.5 w-3.5"
                                        :stroke-width="3"
                                    />
                                    <template v-else>{{ index + 1 }}</template>
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="min-w-0 truncate text-sm"
                                            :class="
                                                step.done
                                                    ? 'text-slate-400 line-through decoration-slate-300 dark:text-gray-500 dark:decoration-gray-600'
                                                    : step.key === nextKey
                                                      ? 'font-semibold text-primary dark:text-primary-300'
                                                      : 'font-medium text-slate-700 dark:text-gray-200'
                                            "
                                        >
                                            {{ step.title }}
                                        </span>
                                        <span
                                            v-if="step.key === nextKey"
                                            class="shrink-0 rounded-full bg-primary px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white"
                                        >
                                            Next
                                        </span>
                                        <span
                                            v-else-if="
                                                step.optional && !step.done
                                            "
                                            class="shrink-0 text-[9px] font-semibold uppercase tracking-wide text-slate-400"
                                        >
                                            Optional
                                        </span>
                                    </span>
                                    <span
                                        v-if="!step.done"
                                        class="mt-0.5 block text-xs leading-5 text-slate-500 dark:text-gray-400"
                                    >
                                        {{ step.description }}
                                    </span>
                                </span>
                            </NuxtLink>
                        </li>
                    </ol>

                    <footer
                        class="flex items-center gap-2 border-t border-slate-100 p-3 dark:border-white/10"
                    >
                        <button
                            type="button"
                            class="rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-white"
                            @click="skip"
                        >
                            {{
                                context === "branch"
                                    ? "Hide for this branch"
                                    : "Don't show again"
                            }}
                        </button>

                        <NuxtLink
                            v-if="nextStep"
                            :to="nextStep.to"
                            class="ml-auto inline-flex items-center gap-1 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-600"
                            @click="open = false"
                        >
                            {{ nextStep.action ?? "Continue" }}
                            <ArrowRight class="h-3.5 w-3.5" />
                        </NuxtLink>
                    </footer>
                </section>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from "vue";
import { useRoute, useRouter } from "vue-router";
import { driver } from "driver.js";
import "driver.js/dist/driver.css";
import "~/assets/css/tour.css";
import { ArrowRight, Check, ListChecks, X } from "lucide-vue-next";
import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";
import { useSetupSteps, type SetupContext } from "~/composables/useSetupSteps";
import {
    useFirstTimeUser,
    useSetupBranchUuid,
} from "~/composables/useSetupGuide";

/**
 * First-time checklist on the dashboard. Right after signing in it also walks
 * the user to the next thing to do (once a day, until everything is done).
 */
const props = defineProps<{
    context: SetupContext;
    branchUuid?: string;
}>();

const router = useRouter();
const route = useRoute();

// In the header the branch comes from the address (or ?branch= / the open
// branch on shared pages like My Profile).
const routeBranch = useSetupBranchUuid();
const branchUuid = computed(() => props.branchUuid ?? routeBranch.value);
const user = useAuthUser();
const branchStore = useBranchStore();

const {
    steps,
    doneCount,
    allDone,
    percent,
    nextKey,
    heading,
    subheading,
    role,
    loadFacts,
    skip,
} = useSetupSteps({
    context: () => props.context,
    branchUuid: () => branchUuid.value,
});

const scope = () =>
    props.context === "branch" ? `branch:${branchUuid.value}` : props.context;

// The floating checklist: closed until the button is clicked (or the
// walkthrough opens it).
const open = ref(false);
const launcherEl = ref<HTMLElement | null>(null);
const panelEl = ref<HTMLElement | null>(null);

// Where the panel sits: just under the header button, kept on screen.
const anchor = ref<{ top: number; right: number } | null>(null);

function measure() {
    const rect = launcherEl.value?.getBoundingClientRect();
    if (!rect) return;
    anchor.value = {
        top: rect.bottom + 12,
        right: Math.max(12, window.innerWidth - rect.right - 8),
    };
}

const panelStyle = computed(() => {
    const width = Math.min(368, window.innerWidth - 24);
    const top = anchor.value?.top ?? 88;
    const right = Math.min(
        anchor.value?.right ?? 12,
        window.innerWidth - width - 12,
    );
    return { top: `${top}px`, right: `${right}px`, width: `${width}px` };
});

function toggle() {
    if (!open.value) measure();
    open.value = !open.value;
}

function onDocumentClick(event: MouseEvent) {
    if (!open.value || document.body.classList.contains("driver-active"))
        return;
    const target = event.target as Node;
    if (launcherEl.value?.contains(target) || panelEl.value?.contains(target))
        return;
    open.value = false;
}

function onResize() {
    if (open.value) measure();
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === "Escape") open.value = false;
}

const ready = ref(false);

// Progress ring (circumference of r=16).
const RING = 2 * Math.PI * 16;

// Connecting line between two steps: done → done is green, done → the next
// step fades from green to blue, anything after is still grey.
function lineClass(
    step: { done: boolean; key: string },
    following?: { done: boolean; key: string },
) {
    if (step.done && following?.done)
        return "bg-emerald-400 dark:bg-emerald-500/70";
    if (step.done)
        return "bg-gradient-to-b from-emerald-400 to-primary dark:from-emerald-500/70";
    return "bg-slate-200 dark:bg-white/10";
}

const nextStep = computed(
    () => steps.value.find((step) => step.key === nextKey.value) ?? null,
);

const { tourSeenToday, markTourSeen } = useFirstTimeUser();

function menuLinkFor(to: string): HTMLElement | null {
    const links = Array.from(
        document.querySelectorAll<HTMLElement>(`a[href="${to}"]`),
    );

    return (
        links.find((el) => {
            if (el.closest('[data-tour="setup-guide"], [data-setup-hint]'))
                return false;
            if (
                el.checkVisibility &&
                !el.checkVisibility({
                    visibilityProperty: true,
                    opacityProperty: true,
                })
            )
                return false;
            const box = el.getBoundingClientRect();
            const onScreen =
                box.width > 0 &&
                box.height > 0 &&
                box.left >= 0 &&
                box.right <= window.innerWidth &&
                box.top >= 0 &&
                box.bottom <= window.innerHeight;
            if (!onScreen) return false;

            const hit = document.elementFromPoint(
                box.left + box.width / 2,
                box.top + box.height / 2,
            );
            return !hit || el.contains(hit);
        }) ?? null
    );
}

// Waits for the "Select a branch" window to close before starting the tour.
function whenBranchPickerClosed() {
    if (!branchStore.showModal) return Promise.resolve();

    return new Promise<void>((resolve) => {
        const stop = watch(
            () => branchStore.showModal,
            (open) => {
                if (open) return;
                stop();
                resolve();
            },
        );
    });
}

// Points at the guide, then the next step, then where it lives in the menu.
async function startTour() {
    const next = steps.value.find((step) => step.key === nextKey.value);
    if (!next || allDone.value || !user.value?.uuid || tourSeenToday(scope()))
        return;

    // The family portal has its own first-visit tour; let that one go first.
    if (props.context === "portal" && !user.value?.onboarding?.portal?.main)
        return;

    await whenBranchPickerClosed();

    markTourSeen(scope());
    measure();
    open.value = true;
    await nextTick();
    await new Promise((resolve) => setTimeout(resolve, 300));

    const position = steps.value.findIndex((step) => step.key === next.key) + 1;

    const tourSteps: any[] = [
        {
            element: '[data-tour="setup-guide"]',
            popover: {
                title: "Let's get you set up",
                description: `Your checklist lives in the checklist button at the top, next to your notifications. ${doneCount.value} of ${steps.value.length} done so far; each step ticks off as you go.`,
                side: "left",
                align: "start",
            },
        },
        {
            element: `[data-tour="setup-step-${next.key}"]`,
            popover: {
                title: `Next: step ${position}, ${next.title}`,
                description: next.tip ?? next.description,
                side: "left",
                align: "center",
            },
        },
    ];

    const menuLink = menuLinkFor(next.to);

    if (menuLink) {
        tourSteps.push({
            element: menuLink,
            popover: {
                title: "You'll find it here too",
                description: `${next.title} is also in the menu, any time you need it.`,
                side: window.innerWidth < 1024 ? "top" : "right",
                align: "center",
            },
        });
    }

    const last = tourSteps[tourSteps.length - 1];
    last.popover.doneBtnText = "Let's go";
    last.popover.onNextClick = () => {
        tour.destroy();
        open.value = false;
        router.push(next.to);
    };

    const tour = driver({
        steps: tourSteps,
        showProgress: true,
        progressText: "{{current}} of {{total}}",
        nextBtnText: "Next",
        prevBtnText: "Back",
        doneBtnText: "Let's go",
        popoverClass: "amuma-tour",
        stagePadding: 4,
        stageRadius: 12,
        overlayClickBehavior: () => {},
    });

    tour.drive();
}

onBeforeUnmount(() => {
    document.removeEventListener("click", onDocumentClick, true);
    document.removeEventListener("keydown", onKeydown);
    window.removeEventListener("resize", onResize);
});

onMounted(async () => {
    document.addEventListener("click", onDocumentClick, true);
    document.addEventListener("keydown", onKeydown);
    window.addEventListener("resize", onResize);

    await loadFacts().catch(() => {});
    ready.value = true;
});

// The guide now sits in the layout, so it can mount before the signed-in user
// and the steps are known; start the walkthrough once they are.
let tourTried = false;

watch(
    () => [ready.value, user.value?.uuid, steps.value.length] as const,
    async ([isReady, uuid, count]) => {
        if (tourTried || !isReady || !uuid || !count) return;
        tourTried = true;
        await nextTick();
        startTour();
    },
    { immediate: true },
);

watch(
    () => [branchUuid.value, role.value],
    async () => {
        await loadFacts().catch(() => {});
    },
);
</script>
