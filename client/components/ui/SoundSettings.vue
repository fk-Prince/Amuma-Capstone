<script setup lang="ts">
import { Volume2 } from "lucide-vue-next";
import ToggleSwitch from "~/components/ui/ToggleSwitch.vue";
import {
    playMessageSound,
    playNotificationSound,
    useSoundPrefs,
} from "~/composables/useSounds";

// Sound switches for the profile page. Add <SoundSettings /> at the top of the
// Notifications tab.
const { notification, message } = useSoundPrefs();

function setNotification(value: boolean) {
    notification.value = value;

    // let the person hear what they just turned on
    if (value) playNotificationSound({ force: true });
}

function setMessage(value: boolean) {
    message.value = value;

    if (value) playMessageSound({ force: true });
}

const testButtonClass =
    "inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-600 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10";
</script>

<template>
    <section
        class="grid gap-6 bg-white p-6 lg:grid-cols-[260px_1fr] dark:border-white/10 dark:bg-secondary"
    >
        <div>
            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                Sounds
            </h2>
            <p
                class="mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400"
            >
                Play a short sound when something new arrives. This is saved on
                this device.
            </p>
        </div>

        <div
            class="w-full max-w-lg divide-y divide-slate-100 dark:divide-white/10"
        >
            <div class="flex items-center gap-4 pb-4">
                <ToggleSwitch
                    class="flex-1"
                    label="Notification sound"
                    description="When you get a new notification."
                    :model-value="notification"
                    @update:model-value="setNotification"
                />
                <button
                    type="button"
                    :class="testButtonClass"
                    @click="playNotificationSound({ force: true })"
                >
                    <Volume2 class="h-3.5 w-3.5" />
                    Test
                </button>
            </div>

            <div class="flex items-center gap-4 pt-4">
                <ToggleSwitch
                    class="flex-1"
                    label="Message sound"
                    description="When a new message arrives."
                    :model-value="message"
                    @update:model-value="setMessage"
                />
                <button
                    type="button"
                    :class="testButtonClass"
                    @click="playMessageSound({ force: true })"
                >
                    <Volume2 class="h-3.5 w-3.5" />
                    Test
                </button>
            </div>
        </div>
    </section>
</template>