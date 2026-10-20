<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { ArrowRight } from "lucide-vue-next";

// Shown once, when the agency's subscription gets approved.
const props = withDefaults(
    defineProps<{
        name?: string;
        agencyName?: string;
        branchName?: string;
        planName?: string;
        validUntil?: string;
        title?: string;
        subtitle?: string;
        buttonLabel?: string;
        autoCloseMs?: number;
    }>(),
    {
        name: "",
        agencyName: "",
        branchName: "",
        planName: "",
        validUntil: "",
        title: "",
        subtitle: "",
        buttonLabel: "Go to dashboard",
        autoCloseMs: 8000,
    },
);

const emit = defineEmits<{
    (e: "continue"): void;
}>();

const headline = computed(
    () =>
        props.title ||
        (props.name ? `Welcome aboard, ${props.name}!` : "Welcome aboard!"),
);

const body = computed(
    () =>
        props.subtitle ||
        `${
            props.agencyName || "Your agency"
        } is now live on AMUMA. Your dashboard is ready for your first branch, staff, and patients.`,
);

const chips = computed(() =>
    [
        { label: "Branch", value: props.branchName },
        { label: "Plan", value: props.planName },
        { label: "Valid until", value: props.validUntil },
    ].filter((c) => c.value),
);

const COLORS = ["#7FB1F8", "#5697F3", "#3182ED", "#6FC3C0"];

const confetti = Array.from({ length: 28 }, (_, i) => ({
    id: i,
    style: {
        left: `${(i * 37 + 11) % 100}%`,
        width: "7px",
        height: i % 3 === 0 ? "7px" : "12px",
        borderRadius: i % 3 === 0 ? "9999px" : "2px",
        backgroundColor: COLORS[i % COLORS.length],
        animationDelay: `${0.9 + (i % 8) * 0.14}s`,
        animationDuration: `${2.8 + (i % 5) * 0.4}s`,
        "--sway": `${((i % 7) - 3) * 20}px`,
        "--rot": `${240 + (i % 6) * 90}deg`,
    } as Record<string, string>,
}));

const buttonRef = ref<HTMLButtonElement | null>(null);
let closed = false;
let autoTimer: ReturnType<typeof setTimeout> | undefined;
let previousOverflow = "";

function close() {
    if (closed) return;
    closed = true;
    emit("continue");
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === "Escape" || e.key === "Enter") close();
}

onMounted(() => {
    previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    window.addEventListener("keydown", onKeydown);
    buttonRef.value?.focus({ preventScroll: true });

    if (props.autoCloseMs > 0) autoTimer = setTimeout(close, props.autoCloseMs);
});

onBeforeUnmount(() => {
    document.body.style.overflow = previousOverflow;
    window.removeEventListener("keydown", onKeydown);
    if (autoTimer) clearTimeout(autoTimer);
});
</script>

<template>
    <ClientOnly>
        <Teleport to="body">
            <div
                class="as-root fixed inset-0 z-[100] flex items-center justify-center overflow-hidden bg-page px-5 dark:bg-secondary-950"
                role="dialog"
                aria-modal="true"
                aria-labelledby="approved-splash-title"
                aria-describedby="approved-splash-desc"
            >
                <div
                    class="pointer-events-none absolute inset-0 overflow-hidden"
                    aria-hidden="true"
                >
                    <div
                        class="absolute inset-0 opacity-60 [background-image:radial-gradient(circle,rgb(var(--primary-500)/0.22)_1px,transparent_1px)] [background-size:26px_26px] [mask-image:radial-gradient(ellipse_70%_60%_at_50%_42%,#000_15%,transparent_75%)] dark:opacity-40"
                    />
                    <div
                        class="absolute left-1/2 top-[40%] h-[420px] w-[420px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary-500/10 blur-3xl dark:bg-primary-500/15"
                    />
                </div>

                <div
                    class="as-confetti pointer-events-none absolute inset-0 overflow-hidden"
                    aria-hidden="true"
                >
                    <span
                        v-for="piece in confetti"
                        :key="piece.id"
                        class="as-piece"
                        :style="piece.style"
                    />
                </div>

                <div
                    class="relative z-10 flex w-full max-w-[460px] flex-col items-center text-center"
                >
                    <div
                        class="as-badge grid h-[84px] w-[84px] place-items-center rounded-full border border-primary-500/25 bg-primary-500/10"
                    >
                        <svg
                            class="h-[46px] w-[46px]"
                            viewBox="0 0 100 100"
                            fill="none"
                            aria-hidden="true"
                        >
                            <circle
                                class="as-draw as-circle"
                                cx="50"
                                cy="50"
                                r="44"
                                pathLength="1"
                                stroke="rgb(49 130 237)"
                                stroke-width="6"
                                stroke-linecap="round"
                            />
                            <path
                                class="as-draw as-tick"
                                d="M30 52 L44 66 L71 36"
                                pathLength="1"
                                stroke="rgb(49 130 237)"
                                stroke-width="7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>

                    <p
                        class="as-rise mt-6 text-xs font-extrabold uppercase tracking-[0.3em] text-primary-500"
                        style="--d: 0.9s"
                    >
                        Subscription approved
                    </p>

                    <h1
                        id="approved-splash-title"
                        class="as-rise mt-2.5 text-[26px] font-extrabold leading-tight tracking-tight text-slate-900 sm:text-[34px] dark:text-white"
                        style="--d: 1.05s"
                    >
                        {{ headline }}
                    </h1>

                    <p
                        id="approved-splash-desc"
                        class="as-rise mt-3 max-w-[400px] text-[15px] leading-relaxed text-slate-500 dark:text-gray-400"
                        style="--d: 1.2s"
                    >
                        {{ body }}
                    </p>

                    <div
                        v-if="chips.length"
                        class="as-rise mt-6 flex flex-wrap justify-center gap-2"
                        style="--d: 1.4s"
                    >
                        <div
                            v-for="chip in chips"
                            :key="chip.label"
                            class="rounded-full border border-slate-900/10 bg-white px-3.5 py-1.5 text-[13px] dark:border-white/10 dark:bg-white/5"
                        >
                            <span class="text-slate-500 dark:text-gray-400">
                                {{ chip.label }}
                            </span>
                            <b class="ml-1 font-semibold text-slate-900 dark:text-white">
                                {{ chip.value }}
                            </b>
                        </div>
                    </div>

                    <button
                        ref="buttonRef"
                        type="button"
                        class="as-rise mt-7 inline-flex items-center gap-2 rounded-xl bg-primary px-8 py-3 text-[15px] font-semibold text-white outline-none transition-colors hover:bg-primary-600 focus-visible:ring-2 focus-visible:ring-primary-300"
                        style="--d: 1.6s"
                        @click="close"
                    >
                        {{ buttonLabel }}
                        <ArrowRight class="h-4 w-4" />
                    </button>

                    <p
                        v-if="autoCloseMs > 0"
                        class="as-rise mt-3.5 text-xs text-slate-500 dark:text-gray-400"
                        style="--d: 1.7s"
                    >
                        Opens automatically in a few seconds
                    </p>
                </div>
            </div>
        </Teleport>
    </ClientOnly>
</template>

<style scoped>
.as-root {
    animation: as-fade 0.4s ease-out both;
}

.as-badge {
    animation: as-pop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

.as-draw {
    stroke-dasharray: 1;
    stroke-dashoffset: 1;
}

.as-circle {
    animation: as-draw 0.7s ease-out 0.3s forwards;
}

.as-tick {
    animation: as-draw 0.45s ease-out 0.9s forwards;
}

.as-rise {
    opacity: 0;
    animation: as-rise 0.55s ease-out var(--d, 0s) forwards;
}

.as-piece {
    position: absolute;
    top: -4vh;
    opacity: 0;
    animation-name: as-fall;
    animation-timing-function: linear;
    animation-fill-mode: both;
}

@keyframes as-fade {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes as-pop {
    from {
        opacity: 0;
        transform: scale(0.5);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes as-draw {
    to {
        stroke-dashoffset: 0;
    }
}

@keyframes as-rise {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes as-fall {
    0% {
        opacity: 0;
        transform: translate3d(0, 0, 0) rotate(0deg);
    }
    10%,
    80% {
        opacity: 1;
    }
    100% {
        opacity: 0;
        transform: translate3d(var(--sway), 108vh, 0) rotate(var(--rot));
    }
}

@media (prefers-reduced-motion: reduce) {
    .as-confetti {
        display: none;
    }

    .as-root,
    .as-badge {
        animation: none;
    }

    .as-rise {
        animation: none;
        opacity: 1;
        transform: none;
    }

    .as-draw {
        animation: none;
        stroke-dashoffset: 0;
    }
}
</style>