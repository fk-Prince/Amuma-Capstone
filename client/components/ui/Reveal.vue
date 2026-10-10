<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";

const props = withDefaults(
    defineProps<{
        delay?: number;
        tag?: string;
    }>(),
    { delay: 0, tag: "div" },
);

const el = ref<HTMLElement | null>(null);
const shown = ref(false);
let observer: IntersectionObserver | null = null;

onMounted(() => {
    const reduceMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)",
    ).matches;

    if (reduceMotion || !("IntersectionObserver" in window)) {
        shown.value = true;
        return;
    }

    observer = new IntersectionObserver(
        (entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                shown.value = true;
                observer?.disconnect();
            }
        },
        { threshold: 0.12, rootMargin: "0px 0px -6% 0px" },
    );

    if (el.value) observer.observe(el.value);
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <component
        :is="props.tag"
        ref="el"
        class="transition-[opacity,transform] duration-700 ease-out"
        :class="
            shown
                ? 'translate-y-0 opacity-100'
                : 'translate-y-6 opacity-0 will-change-transform'
        "
        :style="{ transitionDelay: `${props.delay}ms` }"
    >
        <slot />
    </component>
</template>