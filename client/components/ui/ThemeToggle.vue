<script setup lang="ts">
import { computed } from "vue";
import { Sun, Moon } from "lucide-vue-next";
import { setThemeMode, useThemeMode } from "~/composables/useTheme";
import type { ThemeMode } from "~/composables/useTheme";

defineOptions({ name: "ThemeToggle" });

// Switches between Light and Dark. The icon shows the mode that is currently selected.
const ORDER: ThemeMode[] = ["light", "dark"];
const LABELS: Record<ThemeMode, string> = {
    light: "Light",
    dark: "Dark",
};

const mode = useThemeMode();
const next = computed(() => ORDER[(ORDER.indexOf(mode.value) + 1) % ORDER.length]);
const label = computed(
    () => `Theme: ${LABELS[mode.value]}. Switch to ${LABELS[next.value]}`,
);
</script>

<template>
    <button
        type="button"
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg transition-colors"
        :aria-label="label"
        :title="label"
        @click="setThemeMode(next)"
    >
        <Sun v-if="mode === 'light'" class="h-5 w-5" />
        <Moon v-else class="h-5 w-5" />
    </button>
</template>