<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { ArrowRight } from "lucide-vue-next";
import BrandLogo from "~/components/ui/BrandLogo.vue";

const props = withDefaults(
    defineProps<{
        name?: string;
        title?: string;
        subtitle?: string;
        buttonLabel?: string;
        autoCloseMs?: number;
    }>(),
    {
        name: "",
        title: "",
        subtitle:
            "Your payment was received. We will review your subscription and notify you within 1-2 business days.",
        buttonLabel: "Continue",
        autoCloseMs: 9000,
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

const CONFETTI_COLORS = ["#7FB1F8", "#5697F3", "#3182ED", "#6FC3C0", "#FFFFFF"];

const confetti = Array.from({ length: 36 }, (_, i) => {
    const size = 6 + (i % 4) * 3;
    return {
        id: i,
        style: {
            left: `${(i * 37 + 11) % 100}%`,
            width: `${size}px`,
            height: i % 3 === 0 ? `${size}px` : `${size * 1.8}px`,
            borderRadius: i % 3 === 0 ? "9999px" : "2px",
            backgroundColor: CONFETTI_COLORS[i % CONFETTI_COLORS.length],
            animationDelay: `${0.5 + (i % 9) * 0.14}s`,
            animationDuration: `${2.6 + (i % 5) * 0.4}s`,
            "--sway": `${((i % 7) - 3) * 22}px`,
            "--rot": `${240 + (i % 6) * 90}deg`,
        } as Record<string, string>,
    };
});

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

    if (props.autoCloseMs > 0) {
        autoTimer = setTimeout(close, props.autoCloseMs);
    }
});

onBeforeUnmount(() => {
    document.body.style.overflow = previousOverflow;
    window.removeEventListener("keydown", onKeydown);
    if (autoTimer) clearTimeout(autoTimer);
});
</script>

<template>
    <Teleport to="body">
        <div
            class="ws-root fixed inset-0 z-[100] flex items-center justify-center overflow-hidden bg-secondary-950"
            role="dialog"
            aria-modal="true"
            aria-labelledby="welcome-splash-title"
            aria-describedby="welcome-splash-desc"
        >
            <div
                class="pointer-events-none absolute inset-0 overflow-hidden"
                aria-hidden="true"
            >
                <div
                    class="absolute -top-40 left-1/2 h-[560px] w-[560px] -translate-x-1/2 rounded-full bg-primary-500/20 blur-[150px]"
                />
                <div
                    class="absolute -bottom-40 -right-40 h-[440px] w-[440px] rounded-full bg-accent-500/15 blur-[140px]"
                />
                <div
                    class="absolute -bottom-52 -left-40 h-[440px] w-[440px] rounded-full bg-primary-400/10 blur-[140px]"
                />
            </div>

            <div
                class="pointer-events-none absolute inset-0 overflow-hidden ws-confetti"
                aria-hidden="true"
            >
                <span
                    v-for="piece in confetti"
                    :key="piece.id"
                    class="ws-piece"
                    :style="piece.style"
                />
            </div>

            <div class="absolute left-6 top-6 z-10">
                <BrandLogo icon-class="h-8 w-8" text-class="text-lg" />
            </div>

            <div
                class="relative z-10 flex max-w-lg flex-col items-center px-6 text-center"
            >
                <div class="relative flex h-28 w-28 items-center justify-center">
                    <span class="ws-pulse" aria-hidden="true" />
                    <span class="ws-pulse ws-pulse-late" aria-hidden="true" />

                    <div
                        class="ws-badge relative flex h-24 w-24 items-center justify-center rounded-full border border-white/10 bg-white/5 shadow-[0_20px_60px_-15px_rgba(49,130,237,0.55)]"
                    >
                        <svg
                            class="h-14 w-14"
                            viewBox="0 0 100 100"
                            fill="none"
                            aria-hidden="true"
                        >
                            <circle
                                class="ws-draw ws-circle"
                                cx="50"
                                cy="50"
                                r="44"
                                pathLength="1"
                                stroke="#5697F3"
                                stroke-width="6"
                                stroke-linecap="round"
                            />
                            <path
                                class="ws-draw ws-tick"
                                d="M30 52 L44 66 L71 36"
                                pathLength="1"
                                stroke="#FFFFFF"
                                stroke-width="7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                </div>

                <p
                    class="ws-rise mt-6 text-sm font-extrabold tracking-[0.3em] text-primary-300"
                    style="--d: 0.9s"
                >
                    AMUMA
                </p>

                <h1
                    id="welcome-splash-title"
                    class="ws-rise mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl"
                    style="--d: 1.05s"
                >
                    {{ headline }}
                </h1>

                <p
                    id="welcome-splash-desc"
                    class="ws-rise mt-3 max-w-md text-sm leading-relaxed text-gray-300 sm:text-base"
                    style="--d: 1.25s"
                >
                    {{ subtitle }}
                </p>

                <button
                    ref="buttonRef"
                    type="button"
                    class="ws-rise mt-8 inline-flex items-center gap-2 rounded-xl bg-primary px-8 py-3 text-[15px] font-semibold text-white outline-none transition-colors hover:bg-primary-600 focus-visible:ring-2 focus-visible:ring-primary-300/60"
                    style="--d: 1.6s"
                    @click="close"
                >
                    {{ buttonLabel }}
                    <ArrowRight class="h-4 w-4" />
                </button>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.ws-root {
    animation: ws-fade 0.35s ease-out both;
}

@keyframes ws-fade {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.ws-badge {
    animation: ws-pop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.15s both;
}

@keyframes ws-pop {
    from {
        opacity: 0;
        transform: scale(0.5);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.ws-draw {
    stroke-dasharray: 1;
    stroke-dashoffset: 1;
}

.ws-circle {
    animation: ws-draw 0.7s ease-out 0.35s forwards;
}

.ws-tick {
    animation: ws-draw 0.45s ease-out 0.95s forwards;
}

@keyframes ws-draw {
    to {
        stroke-dashoffset: 0;
    }
}

.ws-pulse {
    position: absolute;
    inset: 0;
    border-radius: 9999px;
    border: 2px solid rgba(86, 151, 243, 0.55);
    opacity: 0;
    animation: ws-pulse 2.2s ease-out 1.1s 2 both;
}

.ws-pulse-late {
    animation-delay: 1.8s;
}

@keyframes ws-pulse {
    from {
        opacity: 0.7;
        transform: scale(0.85);
    }
    to {
        opacity: 0;
        transform: scale(1.7);
    }
}

.ws-rise {
    opacity: 0;
    animation: ws-rise 0.55s ease-out var(--d, 0s) forwards;
}

@keyframes ws-rise {
    from {
        opacity: 0;
        transform: translateY(14px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.ws-piece {
    position: absolute;
    top: -6vh;
    opacity: 0;
    animation-name: ws-fall;
    animation-timing-function: cubic-bezier(0.25, 0.6, 0.35, 1);
    animation-fill-mode: both;
}

@keyframes ws-fall {
    0% {
        opacity: 0;
        transform: translate3d(0, 0, 0) rotate(0deg);
    }
    10% {
        opacity: 1;
    }
    85% {
        opacity: 1;
    }
    100% {
        opacity: 0;
        transform: translate3d(var(--sway), 108vh, 0) rotate(var(--rot));
    }
}

@media (prefers-reduced-motion: reduce) {
    .ws-confetti,
    .ws-pulse {
        display: none;
    }

    .ws-root,
    .ws-badge,
    .ws-rise {
        animation: none;
        opacity: 1;
        transform: none;
    }

    .ws-draw {
        animation: none;
        stroke-dashoffset: 0;
    }
}
</style>