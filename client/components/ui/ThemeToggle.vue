<script setup lang="ts">
import { Sun, Moon } from "lucide-vue-next";

defineOptions({ name: "ThemeToggle" });

const isDark = useIsDark();
</script>

<template>
    <button
        type="button"
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg transition-colors"
        :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        @click="setTheme(!isDark)"
    >
        <Sun v-if="isDark" class="h-5 w-5" />
        <Moon v-else class="h-5 w-5" />
    </button>
</template>
