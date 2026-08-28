<script setup lang="ts">
import { computed, ref, watch } from "vue";

const props = withDefaults(
    defineProps<{
        src?: string | null;
        name?: string | null;
        size?: "sm" | "md";
    }>(),
    {
        size: "sm",
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

const initials = computed(() => {
    const parts = (props.name ?? "")
        .split(" ")
        .map((part) => part.trim())
        .filter(Boolean);

    if (!parts.length) return "?";

    return parts
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? "")
        .join("");
});

const boxClass = computed(() =>
    props.size === "md" ? "h-10 w-10 text-xs" : "h-9 w-9 text-[11px]",
);
</script>

<template>
    <div
        class="flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary-50 font-bold text-primary-700"
        :class="boxClass"
    >
        <img
            v-if="showImage"
            :src="src as string"
            :alt="name ?? ''"
            class="h-full w-full object-cover"
            @error="failed = true"
        />

        <span v-else>{{ initials }}</span>
    </div>
</template>
