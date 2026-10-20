<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Check, Pipette, RotateCcw } from "lucide-vue-next";
import {
    CUSTOM_PALETTE_ID,
    DEFAULT_PALETTE_ID,
    PALETTE_PRESETS,
} from "~/config/palettes";
import {
    resetPalette,
    setCustomPalette,
    setPalettePreset,
    usePaletteSelection,
} from "~/composables/useTheme";
import { isHexColor } from "~/utils/colorScale";

defineOptions({ name: "PalettePicker" });

const selection = usePaletteSelection();
const isCustom = computed(() => selection.value.id === CUSTOM_PALETTE_ID);
const isDefault = computed(() => selection.value.id === DEFAULT_PALETTE_ID);

// Local draft so typing a half-finished hex doesn't apply a broken color.
const primaryDraft = ref(selection.value.primary);
const accentDraft = ref(selection.value.accent);

watch(selection, (s) => {
    primaryDraft.value = s.primary;
    accentDraft.value = s.accent;
});

const onInput = (which: "primary" | "accent", event: Event) => {
    const value = (event.target as HTMLInputElement).value.trim();
    if (which === "primary") primaryDraft.value = value;
    else accentDraft.value = value;

    if (isHexColor(primaryDraft.value) && isHexColor(accentDraft.value)) {
        setCustomPalette(primaryDraft.value, accentDraft.value);
    }
};

const startCustom = () => {
    // Begin from whatever is showing now so nothing jumps.
    setCustomPalette(selection.value.primary, selection.value.accent);
};

const swatchStyle = (primary: string, accent: string) => ({
    background: `linear-gradient(135deg, ${primary} 0 55%, ${accent} 55% 100%)`,
});
</script>

<template>
    <div class="space-y-4">
        <div
            role="radiogroup"
            aria-label="Color combo"
            class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
        >
            <button
                v-for="preset in PALETTE_PRESETS"
                :key="preset.id"
                type="button"
                role="radio"
                :aria-checked="selection.id === preset.id"
                class="group flex items-center gap-3 rounded-xl border p-3 text-left transition-colors"
                :class="
                    selection.id === preset.id
                        ? 'border-primary-500 bg-primary-50 dark:border-primary-400 dark:bg-primary-500/10'
                        : 'border-slate-200 hover:border-slate-300 dark:border-white/10 dark:hover:border-white/20'
                "
                @click="setPalettePreset(preset.id)"
            >
                <span
                    class="relative h-9 w-9 shrink-0 rounded-full ring-1 ring-black/10"
                    :style="swatchStyle(preset.primary, preset.accent)"
                    aria-hidden="true"
                />
                <span
                    class="flex-1 text-sm font-medium text-slate-800 dark:text-white"
                >
                    {{ preset.label }}
                </span>
                <Check
                    v-if="selection.id === preset.id"
                    class="h-4 w-4 text-primary-600 dark:text-primary-300"
                />
            </button>

            <button
                type="button"
                role="radio"
                :aria-checked="isCustom"
                class="flex items-center gap-3 rounded-xl border border-dashed p-3 text-left transition-colors"
                :class="
                    isCustom
                        ? 'border-primary-500 bg-primary-50 dark:border-primary-400 dark:bg-primary-500/10'
                        : 'border-slate-300 hover:border-slate-400 dark:border-white/15 dark:hover:border-white/25'
                "
                @click="startCustom"
            >
                <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-gray-300"
                    aria-hidden="true"
                >
                    <Pipette class="h-4 w-4" />
                </span>
                <span class="flex-1 text-sm font-medium text-slate-800 dark:text-white">
                    Custom
                </span>
                <Check
                    v-if="isCustom"
                    class="h-4 w-4 text-primary-600 dark:text-primary-300"
                />
            </button>
        </div>

        <div
            v-if="isCustom"
            class="grid gap-4 rounded-xl border border-slate-200 p-4 sm:grid-cols-2 dark:border-white/10"
        >
            <label class="block">
                <span class="text-xs font-medium text-slate-500 dark:text-gray-400">
                    Primary (buttons, links, highlights)
                </span>
                <span class="mt-1.5 flex items-center gap-2">
                    <input
                        :value="primaryDraft"
                        type="color"
                        class="h-10 w-12 shrink-0 cursor-pointer rounded-lg border border-slate-200 bg-transparent p-1 dark:border-white/10"
                        aria-label="Primary color"
                        @input="onInput('primary', $event)"
                    />
                    <input
                        :value="primaryDraft"
                        type="text"
                        maxlength="7"
                        spellcheck="false"
                        class="h-10 w-full rounded-lg border border-slate-200 bg-transparent px-3 font-mono text-sm uppercase text-slate-800 dark:border-white/10 dark:text-white"
                        aria-label="Primary color hex"
                        @input="onInput('primary', $event)"
                    />
                </span>
            </label>

            <label class="block">
                <span class="text-xs font-medium text-slate-500 dark:text-gray-400">
                    Accent (secondary highlights)
                </span>
                <span class="mt-1.5 flex items-center gap-2">
                    <input
                        :value="accentDraft"
                        type="color"
                        class="h-10 w-12 shrink-0 cursor-pointer rounded-lg border border-slate-200 bg-transparent p-1 dark:border-white/10"
                        aria-label="Accent color"
                        @input="onInput('accent', $event)"
                    />
                    <input
                        :value="accentDraft"
                        type="text"
                        maxlength="7"
                        spellcheck="false"
                        class="h-10 w-full rounded-lg border border-slate-200 bg-transparent px-3 font-mono text-sm uppercase text-slate-800 dark:border-white/10 dark:text-white"
                        aria-label="Accent color hex"
                        @input="onInput('accent', $event)"
                    />
                </span>
            </label>

            <p class="text-xs text-slate-400 sm:col-span-2 dark:text-gray-500">
                Very light or very dark colors are nudged automatically so text
                and buttons stay readable.
            </p>
        </div>

        <button
            v-if="!isDefault"
            type="button"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-800 dark:text-gray-400 dark:hover:text-white"
            @click="resetPalette()"
        >
            <RotateCcw class="h-3.5 w-3.5" />
            Reset to default
        </button>
    </div>
</template>