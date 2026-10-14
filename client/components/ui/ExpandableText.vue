<template>
    <div>
        <p
            ref="el"
            class="text-xs leading-5 text-slate-500 dark:text-gray-400"
            :class="{ 'line-clamp-2': !expanded }"
        >
            {{ text }}
        </p>

        <button
            v-if="truncated || expanded"
            type="button"
            class="mt-0.5 text-xs font-medium text-primary hover:underline"
            @click="expanded = !expanded"
        >
            {{ expanded ? "See less" : "See more" }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";

const props = defineProps<{
    text: string;
}>();

const el = ref<HTMLElement | null>(null);
const expanded = ref(false);
const truncated = ref(false);

let observer: ResizeObserver | null = null;

function measure() {
    if (!el.value || expanded.value) return;

    truncated.value = el.value.scrollHeight > el.value.clientHeight + 1;
}

onMounted(() => {
    measure();

    if (el.value && typeof ResizeObserver !== "undefined") {
        observer = new ResizeObserver(measure);
        observer.observe(el.value);
    }
});

onBeforeUnmount(() => observer?.disconnect());

watch(
    () => props.text,
    async () => {
        expanded.value = false;
        await nextTick();
        measure();
    },
);
</script>
