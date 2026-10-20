<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { ArrowRight } from "lucide-vue-next";
import ThemedLogo from "~/components/ui/ThemedLogo.vue";

// Shown right after the agency subscribes and pays. Approval comes later,
// so this screen says "in review", not "welcome".
const props = withDefaults(
    defineProps<{
        name?: string;
        agencyName?: string;
        title?: string;
        subtitle?: string;
        buttonLabel?: string;
        autoCloseMs?: number;
    }>(),
    {
        name: "",
        agencyName: "",
        title: "Your subscription is in review",
        subtitle: "",
        buttonLabel: "Continue",
        autoCloseMs: 0,
    },
);

const emit = defineEmits<{
    (e: "continue"): void;
}>();

const body = computed(
    () =>
        props.subtitle ||
        `We received your payment${
            props.agencyName ? ` for ${props.agencyName}` : ""
        }. Our team is checking your details, usually within 1 to 2 business days.`,
);

const steps = [
    { label: "Payment received", hint: "", state: "done" },
    {
        label: "Reviewing your subscription",
        hint: "We will email you once it is approved",
        state: "now",
    },
    { label: "Dashboard access", hint: "", state: "next" },
] as const;

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
    <Teleport to="body">
        <div
            class="ws-root fixed inset-0 z-[100] flex items-center justify-center overflow-hidden bg-page px-5 dark:bg-secondary-950"
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
                    class="absolute inset-0 opacity-60 [background-image:radial-gradient(circle,rgb(var(--primary-500)/0.22)_1px,transparent_1px)] [background-size:26px_26px] [mask-image:radial-gradient(ellipse_70%_60%_at_50%_42%,#000_15%,transparent_75%)] dark:opacity-40"
                />
                <div
                    class="absolute left-1/2 top-[40%] h-[420px] w-[420px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary-500/10 blur-3xl dark:bg-primary-500/15"
                />
            </div>

            <div
                class="relative z-10 flex w-full max-w-[460px] flex-col items-center text-center"
            >
                <ThemedLogo class="ws-beat h-14 w-14" />

                <p
                    class="ws-rise mt-6 text-xs font-extrabold uppercase tracking-[0.3em] text-primary-500"
                    style="--d: 0.1s"
                >
                    Thank you{{ name ? `, ${name}` : "" }}
                </p>

                <h1
                    id="welcome-splash-title"
                    class="ws-rise mt-2.5 text-[26px] font-extrabold leading-tight tracking-tight text-slate-900 sm:text-[34px] dark:text-white"
                    style="--d: 0.25s"
                >
                    {{ title }}
                </h1>

                <p
                    id="welcome-splash-desc"
                    class="ws-rise mt-3 max-w-[400px] text-[15px] leading-relaxed text-slate-500 dark:text-gray-400"
                    style="--d: 0.4s"
                >
                    {{ body }}
                </p>

                <ul
                    class="ws-rise mt-7 w-full rounded-2xl border border-slate-900/10 bg-white px-4 py-1.5 text-left dark:border-white/10 dark:bg-white/5"
                    style="--d: 0.6s"
                >
                    <li
                        v-for="(s, i) in steps"
                        :key="s.label"
                        class="flex items-center gap-3 py-3 text-sm"
                        :class="[
                            i > 0
                                ? 'border-t border-slate-900/10 dark:border-white/10'
                                : '',
                            s.state === 'next'
                                ? 'text-slate-400 dark:text-gray-500'
                                : 'text-slate-900 dark:text-white',
                        ]"
                    >
                        <span
                            class="relative grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-bold"
                            :class="{
                                'bg-primary-500 text-white': s.state === 'done',
                                'border-2 border-primary-500':
                                    s.state === 'now',
                                'border-2 border-dashed border-slate-300 dark:border-white/20':
                                    s.state === 'next',
                            }"
                        >
                            <template v-if="s.state === 'done'">✓</template>
                            <span
                                v-else-if="s.state === 'now'"
                                class="ws-beat h-2 w-2 rounded-full bg-primary-500"
                            />
                        </span>
                        <div>
                            {{ s.label }}
                            <p
                                v-if="s.hint"
                                class="mt-px text-xs text-slate-500 dark:text-gray-400"
                            >
                                {{ s.hint }}
                            </p>
                        </div>
                    </li>
                </ul>

                <div
                    class="ws-rise mt-6 flex items-center gap-1.5"
                    style="--d: 0.8s"
                    aria-hidden="true"
                >
                    <span
                        v-for="i in 3"
                        :key="i"
                        class="h-1.5 w-1.5 animate-bounce rounded-full bg-primary-500 motion-reduce:animate-none dark:bg-primary-400"
                        :style="{ animationDelay: `${(i - 1) * 0.15}s` }"
                    />
                </div>

                <button
                    ref="buttonRef"
                    type="button"
                    class="ws-rise mt-7 inline-flex items-center gap-2 rounded-xl bg-primary px-8 py-3 text-[15px] font-semibold text-white outline-none transition-colors hover:bg-primary-600 focus-visible:ring-2 focus-visible:ring-primary-300"
                    style="--d: 0.95s"
                    @click="close"
                >
                    {{ buttonLabel }}
                    <ArrowRight class="h-4 w-4" />
                </button>

                <p
                    class="ws-rise mt-3.5 text-xs text-slate-500 dark:text-gray-400"
                    style="--d: 1.05s"
                >
                    You can close this page. We will notify you.
                </p>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.ws-root {
    animation: ws-fade 0.4s ease-out both;
}

.ws-beat {
    animation: ws-beat 1.8s ease-in-out infinite;
}

.ws-rise {
    opacity: 0;
    animation: ws-rise 0.55s ease-out var(--d, 0s) forwards;
}

@keyframes ws-fade {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes ws-beat {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.08);
    }
}

@keyframes ws-rise {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (prefers-reduced-motion: reduce) {
    .ws-root,
    .ws-beat {
        animation: none;
    }

    .ws-rise {
        animation: none;
        opacity: 1;
        transform: none;
    }
}
</style>