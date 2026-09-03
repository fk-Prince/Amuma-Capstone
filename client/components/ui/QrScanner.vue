<script setup lang="ts">
import { ref } from "vue";
import { QrcodeStream } from "vue-qrcode-reader";
import { onlineScheduleService } from "~/api/online-schedule/OnlineScheduleService";
import { useToast } from "~/composables/useToast";

const emit = defineEmits<{
    close: [];
    scanned: [];
}>();

const { success, error: toastError } = useToast();

const paused = ref(false);
const verifying = ref(false);
const error = ref<string | null>(null);

function toApiType(type: unknown): "in" | "out" | null {
    if (type === "in" || type === "clock-in") return "in";
    if (type === "out" || type === "clock-out") return "out";
    return null;
}

async function onDetect(detectedCodes: { rawValue: string }[]) {
    if (paused.value || verifying.value) return;

    const raw = detectedCodes[0]?.rawValue;

    if (!raw) return;

    let parsed: { token?: string; type?: string } = {};

    try {
        parsed = JSON.parse(raw);
    } catch {
        error.value = "This isn't a valid attendance QR code.";
        return;
    }

    const type = toApiType(parsed.type);

    if (!parsed.token || !type) {
        error.value = "This isn't a valid attendance QR code.";
        return;
    }

    paused.value = true;
    verifying.value = true;
    error.value = null;

    try {
        await onlineScheduleService.verifyQr({
            token: parsed.token,
            type,
        });

        success(
            type === "in"
                ? "Clocked in successfully."
                : "Clocked out successfully.",
        );

        emit("scanned");
        emit("close");
    } catch (err: any) {
        const message =
            err?.response?.data?.message ??
            err?.message ??
            "Unable to verify this QR code. Please try again.";

        error.value = message;

        setTimeout(() => {
            paused.value = false;
        }, 1500);
    } finally {
        verifying.value = false;
    }
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

            <div
                class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-4"
            >
                <div class="relative h-64 w-64">
                    <span
                        class="absolute -top-0.5 -left-0.5 h-10 w-10 rounded-tl-2xl border-l-4 border-t-4 border-white"
                    />
                    <span
                        class="absolute -top-0.5 -right-0.5 h-10 w-10 rounded-tr-2xl border-r-4 border-t-4 border-white"
                    />
                    <span
                        class="absolute -bottom-0.5 -left-0.5 h-10 w-10 rounded-bl-2xl border-b-4 border-l-4 border-white"
                    />
                    <span
                        class="absolute -bottom-0.5 -right-0.5 h-10 w-10 rounded-br-2xl border-b-4 border-r-4 border-white"
                    />
                </div>

                <p
                    v-if="!verifying"
                    class="rounded-full bg-black/50 px-4 py-1.5 text-sm text-white"
                >
                    Align the QR code within the frame
                </p>
            </div>

            <div
                v-if="verifying"
                class="absolute inset-0 flex items-center justify-center bg-black/50"
            >
                <div
                    class="flex items-center gap-2 rounded-full bg-white/90 dark:bg-secondary/95 px-4 py-2 text-sm font-medium text-gray-800 dark:text-white"
                >
                    <svg
                        class="h-4 w-4 animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                        />
                    </svg>
                    Verifying...
                </div>
            </div>

            <button
                type="button"
                class="absolute top-4 right-4 rounded-full bg-white/90 dark:bg-secondary/95 p-3"
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
