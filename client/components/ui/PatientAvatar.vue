<script setup lang="ts">
import { computed, ref, watch } from "vue";

function initials(name?: string | null) {
    const parts = (name ?? "").trim().split(/\s+/).filter(Boolean);

    if (!parts.length) return "?";

    const first = parts[0]?.[0] ?? "";
    const last = parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? "") : "";

    return (first + last).toUpperCase();
}

const props = withDefaults(
    defineProps<{
        src?: string | null;
        name?: string | null;
        sizeClass?: string;
        roundedClass?: string;
    }>(),
    {
        src: null,
        name: null,
        sizeClass: "h-10 w-10 text-sm",
        roundedClass: "rounded-full",
    },
);

const failed = ref(false);

watch(
    () => props.src,
    () => {
        failed.value = false;
    },
);

const showImage = computed(() => !!props.src && !failed.value);
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center justify-center overflow-hidden bg-primary/10 font-semibold text-primary"
        :class="[sizeClass, roundedClass]"
    >
        <img
            v-if="showImage"
            :src="src!"
            :alt="name ?? ''"
            class="h-full w-full object-cover"
            @error="failed = true"
        />

        <template v-else>{{ initials(name) }}</template>
    </span>
</template>
