<template>
    <Teleport to="body">
        <template v-if="next && box && !dismissed">
            <!-- Pulsing ring on the menu item of the next setup step -->
            <span
                class="pointer-events-none fixed z-[45] rounded-xl ring-2 ring-primary/70"
                :style="ringStyle"
                aria-hidden="true"
            >
                <span
                    class="absolute inset-0 animate-ping rounded-xl ring-2 ring-primary/50"
                />
            </span>

            <!-- Small popup beside it -->
            <div
                data-setup-hint
                class="fixed z-[46] w-60 rounded-xl border border-primary-100 bg-white p-3 shadow-xl shadow-primary/15 dark:border-primary-500/30 dark:bg-secondary"
                :style="bubbleStyle"
                role="status"
            >
                <span
                    class="absolute h-3 w-3 rotate-45 border-primary-100 bg-white dark:border-primary-500/30 dark:bg-secondary"
                    :class="arrowClass"
                    :style="arrowStyle"
                    aria-hidden="true"
                />

                <div class="relative flex items-start gap-2">
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-[10px] font-bold uppercase tracking-wider text-primary dark:text-primary-300"
                        >
                            Next step · {{ position }} of {{ steps.length }}
                        </p>
                        <p
                            class="mt-0.5 text-sm font-semibold leading-snug text-secondary dark:text-white"
                        >
                            {{ next.title }}
                        </p>
                    </div>

                    <button
                        type="button"
                        aria-label="Hide this hint"
                        class="-mr-1 -mt-1 rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-white"
                        @click="dismiss"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </div>

                <NuxtLink
                    :to="next.to"
                    class="relative mt-2 inline-flex items-center gap-1 rounded-lg bg-primary px-2.5 py-1 text-xs font-semibold text-white transition hover:bg-primary-600"
                >
                    {{ next.action ?? "Open" }}
                    <ArrowRight class="h-3.5 w-3.5" />
                </NuxtLink>
            </div>
        </template>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import { ArrowRight, X } from "lucide-vue-next";
import { useAuthUser } from "~/composables/useAuthUser";
import { useSetupSteps, type SetupContext } from "~/composables/useSetupSteps";
import { useSetupBranchUuid } from "~/composables/useSetupGuide";

/**
 * Points at the menu item (sidebar, or bottom bar on phones) of the next
 * setup step with a pulsing ring and a small popup, until it's done.
 */
const props = defineProps<{ context: SetupContext }>();

const route = useRoute();
const user = useAuthUser();

// Branch pages carry it in the address; shared pages (My Profile) in ?branch=.
const branchUuid = useSetupBranchUuid();

const { steps, allDone, nextKey, loadFacts, loadVisits } = useSetupSteps({
    context: () => props.context,
    branchUuid: () => branchUuid.value,
});

const next = computed(() => {
    if (allDone.value) return null;
    const step = steps.value.find((s) => s.key === nextKey.value) ?? null;

    // Already on that page: the tip bar there takes over.
    if (!step || step.to === route.path.replace(/\/+$/, "")) return null;
    return step;
});

const position = computed(() =>
    next.value ? steps.value.findIndex((s) => s.key === next.value!.key) + 1 : 0,
);

// Hidden per step for this browser session once closed.
const dismissKey = () => `amuma_setup_hint:${user.value?.uuid}:${next.value?.key}`;
const dismissed = ref(false);

function readDismissed() {
    try {
        dismissed.value = sessionStorage.getItem(dismissKey()) === "1";
    } catch {
        dismissed.value = false;
    }
}

function dismiss() {
    dismissed.value = true;
    try {
        sessionStorage.setItem(dismissKey(), "1");
    } catch {
        // Fine; it just comes back after a reload.
    }
}

// Where the menu link is on screen (only if it's actually visible).
const box = ref<DOMRect | null>(null);

function findLink(): HTMLElement | null {
    if (!next.value) return null;

    const links = Array.from(
        document.querySelectorAll<HTMLElement>(`a[href="${next.value.to}"]`),
    );

    return (
        links.find((el) => {
            // Never the guide's own cards or this popup's own button.
            if (el.closest('[data-tour="setup-guide"], [data-setup-hint]')) return false;
            if (el.checkVisibility && !el.checkVisibility({ visibilityProperty: true, opacityProperty: true })) return false;
            const r = el.getBoundingClientRect();
            const onScreen =
                r.width > 0 &&
                r.height > 0 &&
                r.left >= 0 &&
                r.right <= window.innerWidth &&
                r.top >= 0 &&
                r.bottom <= window.innerHeight;
            if (!onScreen) return false;

            // Not scrolled out of the sidebar or covered by something else.
            const hit = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
            return !hit || el.contains(hit);
        }) ?? null
    );
}

function measure() {
    // Stay out of the way while a walkthrough is running, or while the
    // checklist itself is on the page (it already shows what's next).
    if (
        document.body.classList.contains("driver-active") ||
        document.querySelector('[data-tour="setup-guide"]')
    ) {
        box.value = null;
        return;
    }

    const link = findLink();
    box.value = link ? link.getBoundingClientRect() : null;
}

const BUBBLE_W = 240;

// Bottom bar (phones): above it. Sidebar: to the right, or below when there's no room.
const placement = computed<"above" | "right" | "below">(() => {
    const b = box.value;
    if (!b) return "right";
    if (b.top > window.innerHeight - 140) return "above";
    if (b.right + 14 + BUBBLE_W <= window.innerWidth - 8) return "right";
    return "below";
});

const clampLeft = (center: number) =>
    Math.min(Math.max(8, center - BUBBLE_W / 2), window.innerWidth - BUBBLE_W - 8);

const ringStyle = computed(() => {
    const b = box.value!;
    return {
        left: `${b.left - 3}px`,
        top: `${b.top - 3}px`,
        width: `${b.width + 6}px`,
        height: `${b.height + 6}px`,
    };
});

const bubbleStyle = computed(() => {
    const b = box.value!;
    const center = b.left + b.width / 2;

    if (placement.value === "above") {
        return { left: `${clampLeft(center)}px`, bottom: `${window.innerHeight - b.top + 12}px` };
    }
    if (placement.value === "below") {
        return { left: `${clampLeft(center)}px`, top: `${b.bottom + 12}px` };
    }
    return { left: `${b.right + 14}px`, top: `${Math.max(8, b.top + b.height / 2 - 40)}px` };
});

const arrowClass = computed(() => {
    if (placement.value === "above") return "-bottom-1.5 border-b border-r";
    if (placement.value === "below") return "-top-1.5 border-l border-t";
    return "-left-1.5 border-b border-l";
});

const arrowStyle = computed(() => {
    const b = box.value!;
    const center = b.left + b.width / 2;

    if (placement.value !== "right") {
        return { left: `${center - clampLeft(center) - 6}px` };
    }

    const top = Math.max(8, b.top + b.height / 2 - 40);
    return { top: `${b.top + b.height / 2 - top - 6}px` };
});

let timer: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    loadVisits();
    loadFacts(false).catch(() => {});
    readDismissed();

    // The sidebar slides and expands on hover, so keep following the link.
    timer = setInterval(measure, 300);
    window.addEventListener("resize", measure);
});

onBeforeUnmount(() => {
    clearInterval(timer);
    window.removeEventListener("resize", measure);
});

watch(
    () => next.value?.key,
    () => {
        readDismissed();
        measure();
    },
);

watch(branchUuid, () => loadFacts(false).catch(() => {}));
</script>
