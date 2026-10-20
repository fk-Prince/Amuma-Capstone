<script setup lang="ts">
import { Sun, Moon, Monitor } from "lucide-vue-next";
import { THEME_MODES, setThemeMode, useThemeMode } from "~/composables/useTheme";
import type { ThemeMode } from "~/composables/useTheme";

defineOptions({ name: "ThemeModeSwitcher" });

withDefaults(defineProps<{ size?: "sm" | "md" }>(), { size: "md" });

const mode = useThemeMode();

const OPTIONS: Record<ThemeMode, { label: string; icon: typeof Sun }> = {
    light: { label: "Light", icon: Sun },
    dark: { label: "Dark", icon: Moon },
    system: { label: "Device", icon: Monitor },
};
</script>

<template>
    <div
        role="radiogroup"
        aria-label="Theme"
        class="flex w-full items-center justify-between rounded-full bg-slate-100 p-1 dark:bg-white/5"
    >
        <button
            v-for="key in THEME_MODES"
            :key="key"
            type="button"
            role="radio"
            :aria-checked="mode === key"
            class="flex flex-1 items-center justify-center rounded-full font-medium transition-colors"
            :class="[
                size === 'sm' ? 'gap-1.5 py-1.5 text-xs' : 'gap-2 py-2 text-sm',
                mode === key
                    ? 'bg-white text-slate-800 shadow-sm dark:bg-white/10 dark:text-white'
                    : 'text-slate-400 hover:text-slate-600 dark:text-gray-400 dark:hover:text-gray-200',
            ]"
            @click="setThemeMode(key)"
        >
            <component
                :is="OPTIONS[key].icon"
                :class="size === 'sm' ? 'h-3.5 w-3.5' : 'h-4 w-4'"
            />
            {{ OPTIONS[key].label }}
        </button>
    </div>
</template>