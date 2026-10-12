<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";

const props = withDefaults(
    defineProps<{
        text: string;
        lines?: number;
    }>(),
    { lines: 3 },
);

const el = ref<HTMLElement | null>(null);
const expanded = ref(false);
const overflows = ref(false);

const measure = () => {
    if (!el.value || expanded.value) return;
    overflows.value = el.value.scrollHeight > el.value.clientHeight + 1;
};

const clampStyle = () =>
    expanded.value
        ? undefined
        : {
              display: "-webkit-box",
              WebkitBoxOrient: "vertical" as const,
              WebkitLineClamp: props.lines,
              overflow: "hidden",
          };

watch(
    () => props.text,
    () => {
        expanded.value = false;
        nextTick(measure);
    },
);

onMounted(() => {
    nextTick(measure);
    window.addEventListener("resize", measure);
});

onBeforeUnmount(() => window.removeEventListener("resize", measure));
</script>

<template>
    <div>
        <p ref="el" :style="clampStyle()">{{ text }}</p>

        <span
            v-if="overflows || expanded"
            role="button"
            tabindex="0"
            class="mt-1 inline-block cursor-pointer text-xs font-semibold text-primary underline-offset-2 hover:underline"
            @click.stop.prevent="expanded = !expanded"
            @keydown.enter.stop.prevent="expanded = !expanded"
            @keydown.space.stop.prevent="expanded = !expanded"
        >
            {{ expanded ? "Show less" : "Show more" }}
        </span>
    </div>
</template>
