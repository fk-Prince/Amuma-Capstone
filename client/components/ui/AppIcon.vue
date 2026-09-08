<script setup lang="ts">
import { computed } from "vue";
import * as LucideIcons from "lucide-vue-next";

const props = defineProps<{ name: string }>();

defineOptions({ inheritAttrs: false });

const icon = computed(() => {
    const pascalName = props.name
        .split("-")
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join("");

    return (
        (LucideIcons as Record<string, any>)[pascalName] ||
        LucideIcons.CircleHelp
    );
});
</script>

<template>
    <component :is="icon" v-bind="$attrs" />
</template>
