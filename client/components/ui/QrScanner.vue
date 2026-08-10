<script setup lang="ts">
import { ref } from "vue";
import { QrcodeStream } from "vue-qrcode-reader";
import { onlineScheduleService } from "~/api/online-schedule/OnlineScheduleService";

const emit = defineEmits<{
    close: [];
}>();

const paused = ref(false);
const error = ref<string | null>(null);

async function onDetect(detectedCodes: { rawValue: string }[]) {
    console.log("DETECT EVENT:", detectedCodes);

    const raw = detectedCodes[0]?.rawValue;

    if (!raw) {
        console.log("No raw value");
        return;
    }

    console.log("QR RAW VALUE:", raw);

    alert(`QR detected:\n${raw}`);
}
function onError(err: Error) {
    error.value = `Camera error: ${err.message}`;
}
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 bg-black">
            <QrcodeStream
                :paused="paused"
                @detect="onDetect"
                @error="onError"
            />

            <button
                type="button"
                class="absolute top-4 right-4 rounded-full bg-white/90 p-3"
                @click="emit('close')"
            >
                ✕
            </button>

            <p
                v-if="error"
                class="absolute bottom-6 left-1/2 w-11/12 max-w-sm -translate-x-1/2 rounded-lg bg-red-600/90 p-3 text-center text-sm text-white"
            >
                {{ error }}
            </p>
        </div>
    </Teleport>
</template>
