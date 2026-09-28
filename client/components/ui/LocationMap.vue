<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(
    defineProps<{
        lat?: number | null;
        lng?: number | null;
        label?: string | null;
        zoom?: number;
        heightClass?: string;
    }>(),
    {
        zoom: 16,
        heightClass: "h-[220px]",
    },
);

const embedUrl = computed(() => {
    const query =
        props.lat != null && props.lng != null
            ? `${props.lat},${props.lng}`
            : encodeURIComponent(props.label ?? "");

    return `https://maps.google.com/maps?q=${query}&z=${props.zoom}&output=embed`;
});
</script>

<template>
    <iframe
        class="w-full overflow-hidden rounded-xl border border-[#E4EFED] z-0 dark:border-white/10"
        :class="heightClass"
        :src="embedUrl"
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
    />
</template>
