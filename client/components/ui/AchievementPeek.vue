<template>
    <span
        ref="anchor"
        class="relative inline-flex shrink-0"
        @mouseenter="show"
        @mouseleave="hideSoon"
        @focusin="show"
        @focusout="hideSoon"
    >
        <slot />

        <span
            v-if="titles.length"
            class="pointer-events-none absolute -bottom-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-amber-400 text-white ring-2 ring-white dark:ring-secondary"
            aria-hidden="true"
        >
            <Award class="h-2.5 w-2.5" />
        </span>

        <Teleport to="body">
            <div
                v-if="open && titles.length"
                role="tooltip"
                class="fixed z-[70] w-60 rounded-xl border border-slate-200 bg-white p-3 shadow-xl dark:border-white/10 dark:bg-secondary"
                :style="position"
                @mouseenter="cancelHide"
                @mouseleave="hideSoon"
                @click.stop
            >
                <p
                    class="mb-1.5 flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide text-amber-600 dark:text-amber-300"
                >
                    <Award class="h-3.5 w-3.5" />
                    Achievements
                    <span class="font-semibold text-slate-400 dark:text-gray-500">
                        ({{ titles.length }})
                    </span>
                </p>

                <ul
                    class="space-y-1"
                    :class="expanded ? 'max-h-48 overflow-y-auto pr-1' : ''"
                >
                    <li
                        v-for="title in visible"
                        :key="title"
                        class="text-xs font-medium leading-snug text-slate-700 dark:text-gray-200"
                        :class="expanded ? '' : 'truncate'"
                    >
                        {{ title }}
                    </li>
                </ul>

                <button
                    v-if="titles.length > MAX"
                    type="button"
                    class="mt-1.5 text-[11px] font-semibold text-primary hover:underline dark:text-primary-300"
                    @click.stop="expanded = !expanded"
                >
                    {{ expanded ? "Show less" : `+${titles.length - MAX} more` }}
                </button>
            </div>
        </Teleport>
    </span>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from "vue";
import { Award } from "lucide-vue-next";

const props = defineProps<{ titles: string[] }>();

const MAX = 4;
const WIDTH = 240;

const anchor = ref<HTMLElement | null>(null);
const open = ref(false);
const expanded = ref(false);
const position = ref<Record<string, string>>({});

const visible = computed(() => (expanded.value ? props.titles : props.titles.slice(0, MAX)));

let hideTimer: ReturnType<typeof setTimeout> | undefined;

function cancelHide() {
    clearTimeout(hideTimer);
}

function hideSoon() {
    cancelHide();
    hideTimer = setTimeout(() => {
        open.value = false;
        expanded.value = false;
    }, 150);
}

function show() {
    cancelHide();
    if (!props.titles.length || !anchor.value || open.value) return;

    const box = anchor.value.getBoundingClientRect();
    const left = Math.min(Math.max(8, box.left), window.innerWidth - WIDTH - 8);
    const below = box.bottom + 6;
    const fitsBelow = below + 260 < window.innerHeight;

    position.value = fitsBelow
        ? { left: `${left}px`, top: `${below}px` }
        : { left: `${left}px`, bottom: `${window.innerHeight - box.top + 6}px` };

    open.value = true;
}

onBeforeUnmount(cancelHide);
</script>
