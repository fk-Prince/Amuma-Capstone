<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";

const props = withDefaults(
    defineProps<{
        to: number;
        duration?: number;
        decimals?: number;
        prefix?: string;
        suffix?: string;
    }>(),
    { duration: 1200, decimals: 0, prefix: "", suffix: "" },
);

const el = ref<HTMLElement | null>(null);
const current = ref(props.to);

let raf = 0;
let observer: IntersectionObserver | null = null;
let started = false;
let reduceMotion = false;

const easeOut = (t: number) => 1 - Math.pow(1 - t, 3);

function tween(from: number, to: number) {
    cancelAnimationFrame(raf);

    if (reduceMotion) {
        current.value = to;
        return;
    }

    const startedAt = performance.now();

    const step = (now: number) => {
        const t = Math.min((now - startedAt) / props.duration, 1);
        current.value = from + (to - from) * easeOut(t);
        if (t < 1) raf = requestAnimationFrame(step);
    };

    raf = requestAnimationFrame(step);
}

onMounted(() => {
    reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    if (reduceMotion || !("IntersectionObserver" in window)) return;

    // Count up from zero the first time the number scrolls into view.
    current.value = 0;

    observer = new IntersectionObserver(
        (entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                started = true;
                tween(0, props.to);
                observer?.disconnect();
            }
        },
        { threshold: 0.4 },
    );

    if (el.value) observer.observe(el.value);
});

watch(
    () => props.to,
    (next) => {
        // Before it first scrolls into view, the pending tween will pick up the new target.
        if (started || reduceMotion || !observer) tween(current.value, next);
    },
);

onBeforeUnmount(() => {
    cancelAnimationFrame(raf);
    observer?.disconnect();
});

const display = computed(
    () =>
        props.prefix +
        current.value.toLocaleString("en-US", {
            minimumFractionDigits: props.decimals,
            maximumFractionDigits: props.decimals,
        }) +
        props.suffix,
);
</script>

<template>
    <span ref="el" class="tabular-nums">{{ display }}</span>
</template>