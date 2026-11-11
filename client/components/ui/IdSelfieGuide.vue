<template>
    <span ref="root" class="relative inline-flex align-middle">
        <button
            type="button"
            class="inline-flex text-slate-400 transition hover:text-primary dark:text-gray-500"
            aria-label="How to hold your ID"
            :aria-expanded="open"
            @click.stop="open = !open"
        >
            <Info class="h-4 w-4" />
        </button>

        <div
            v-if="open"
            role="dialog"
            aria-label="How to hold your ID"
            class="absolute left-1/2 top-full z-30 mt-2 w-72 max-w-[calc(100vw-2rem)] -translate-x-1/2 rounded-xl border border-slate-200 bg-white p-3 text-left font-normal shadow-xl dark:border-white/10 dark:bg-secondary sm:left-0 sm:translate-x-0"
            @click.stop
        >
            <div class="overflow-hidden rounded-lg bg-slate-100 dark:bg-white/5">
                <svg viewBox="0 0 240 180" class="block h-auto w-full" aria-hidden="true">
                    <rect width="240" height="180" class="fill-slate-100 dark:fill-white/5" />
                    <path d="M40 180c4-34 34-52 80-52s76 18 80 52Z" class="fill-sky-200 dark:fill-sky-900" />
                    <rect x="106" y="104" width="28" height="26" rx="8" class="fill-amber-200 dark:fill-amber-300" />
                    <ellipse cx="120" cy="72" rx="30" ry="36" class="fill-amber-200 dark:fill-amber-300" />
                    <path d="M88 66c0-26 16-40 34-40s32 12 32 36c-6-10-18-16-32-16s-26 8-34 20Z" class="fill-slate-800" />
                    <circle cx="109" cy="74" r="2.5" class="fill-slate-800" />
                    <circle cx="131" cy="74" r="2.5" class="fill-slate-800" />
                    <path d="M112 92c5 3 11 3 16 0" stroke-width="2" stroke-linecap="round" fill="none" class="stroke-slate-700" />
                    <rect x="76" y="40" width="88" height="80" rx="6" fill="none" stroke-width="2" stroke-dasharray="5 4" class="stroke-emerald-500" />
                    <g transform="translate(112 128) rotate(-3)">
                        <rect width="76" height="48" rx="5" class="fill-white" />
                        <rect width="76" height="9" rx="3" class="fill-primary" />
                        <rect x="6" y="15" width="20" height="25" rx="2" class="fill-slate-300" />
                        <circle cx="16" cy="24" r="5" class="fill-amber-200" />
                        <path d="M9 40c1-6 4-8 7-8s6 2 7 8Z" class="fill-slate-700" />
                        <rect x="32" y="17" width="36" height="4" rx="2" class="fill-slate-300" />
                        <rect x="32" y="25" width="28" height="4" rx="2" class="fill-slate-300" />
                        <rect x="32" y="33" width="32" height="4" rx="2" class="fill-slate-300" />
                        <rect x="-3" y="-3" width="82" height="54" rx="7" fill="none" stroke-width="2" stroke-dasharray="5 4" class="stroke-emerald-500" />
                    </g>
                    <path d="M180 150c8-4 18-2 20 6s-4 16-14 18l-10 2-4-20Z" class="fill-amber-200 dark:fill-amber-300" />
                </svg>
            </div>

            <p class="mt-3 text-sm font-semibold text-slate-800 dark:text-white">
                How to hold your ID
            </p>

            <ul class="mt-2 space-y-2 text-xs leading-5 text-slate-600 dark:text-gray-300">
                <li v-for="tip in tips" :key="tip.text" class="flex items-start gap-2">
                    <span
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary dark:bg-primary-500/15 dark:text-primary-300"
                    >
                        <component :is="tip.icon" class="h-3.5 w-3.5" />
                    </span>
                    <span class="pt-0.5">{{ tip.text }}</span>
                </li>
            </ul>
        </div>
    </span>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { Hand, IdCard, Info, ScanFace, Sun } from "lucide-vue-next";

const open = ref(false);
const root = ref<HTMLElement | null>(null);

const tips = [
    { icon: ScanFace, text: "Face the camera with your whole face showing. No cap, mask or sunglasses." },
    { icon: IdCard, text: "Hold the same ID you uploaded below your chin, photo side facing the camera." },
    { icon: Hand, text: "Keep your fingers off the ID photo and the details." },
    { icon: Sun, text: "Use good light so both your face and the ID are clear." },
];

function onOutside(event: MouseEvent) {
    if (open.value && root.value && !root.value.contains(event.target as Node)) {
        open.value = false;
    }
}

onMounted(() => document.addEventListener("click", onOutside));
onBeforeUnmount(() => document.removeEventListener("click", onOutside));
</script>
