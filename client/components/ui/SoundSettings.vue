<script setup lang="ts">
import { BellRing, Volume2, VolumeX } from "lucide-vue-next";
import {
    playMessageSound,
    playNotificationSound,
    useSoundPrefs,
} from "~/composables/useSounds";

const { notification, message } = useSoundPrefs();

function previewNotification() {
    playNotificationSound({ force: true });
}

function previewMessage() {
    playMessageSound({ force: true });
}

const testButtonClass =
    "inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-600 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10";

const rangeClass =
    "h-1.5 w-full cursor-pointer appearance-none rounded-full bg-slate-200 accent-primary-500 dark:bg-white/15";
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
    >
        <header
            class="flex items-center gap-3 border-b border-slate-100 px-4 py-4 sm:px-6 dark:border-white/10"
        >
            <span
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary dark:bg-primary-500/15 dark:text-primary-300"
            >
                <BellRing class="h-[18px] w-[18px]" />
            </span>
            <div class="min-w-0">
                <h2 class="text-sm font-bold text-secondary sm:text-base dark:text-white">
                    Sounds
                </h2>
                <p class="text-xs leading-5 text-slate-500 sm:text-sm dark:text-gray-400">
                    Play a short sound when something new arrives. Set the volume to
                    0 to turn it off. This is saved on this device.
                </p>
            </div>
        </header>

        <div
            class="w-full max-w-xl divide-y divide-slate-100 p-4 sm:p-6 dark:divide-white/10"
        >
            <div class="pb-4">
                <label
                    for="sound-notification"
                    class="text-sm font-medium text-slate-900 dark:text-white"
                >
                    Notification sound
                </label>
                <p class="text-xs text-slate-500 dark:text-gray-400">
                    When you get a new notification.
                </p>

                <div class="mt-3 flex items-center gap-3">
                    <component
                        :is="notification > 0 ? Volume2 : VolumeX"
                        class="h-4 w-4 shrink-0 text-slate-400"
                    />
                    <input
                        id="sound-notification"
                        v-model.number="notification"
                        type="range"
                        min="0"
                        max="100"
                        step="5"
                        :class="rangeClass"
                        @change="previewNotification"
                    />
                    <span
                        class="w-10 shrink-0 text-right text-xs font-semibold tabular-nums text-slate-600 dark:text-gray-300"
                    >
                        {{ notification }}%
                    </span>
                    <button
                        type="button"
                        :class="testButtonClass"
                        @click="previewNotification"
                    >
                        Test
                    </button>
                </div>
            </div>

            <div class="pt-4">
                <label
                    for="sound-message"
                    class="text-sm font-medium text-slate-900 dark:text-white"
                >
                    Message sound
                </label>
                <p class="text-xs text-slate-500 dark:text-gray-400">
                    When a new message arrives.
                </p>

                <div class="mt-3 flex items-center gap-3">
                    <component
                        :is="message > 0 ? Volume2 : VolumeX"
                        class="h-4 w-4 shrink-0 text-slate-400"
                    />
                    <input
                        id="sound-message"
                        v-model.number="message"
                        type="range"
                        min="0"
                        max="100"
                        step="5"
                        :class="rangeClass"
                        @change="previewMessage"
                    />
                    <span
                        class="w-10 shrink-0 text-right text-xs font-semibold tabular-nums text-slate-600 dark:text-gray-300"
                    >
                        {{ message }}%
                    </span>
                    <button
                        type="button"
                        :class="testButtonClass"
                        @click="previewMessage"
                    >
                        Test
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
