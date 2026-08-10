<script setup lang="ts">
import QrcodeVue from "qrcode.vue";

type QrMode = "check-in" | "clock-in" | "clock-out" | "custom";

const props = withDefaults(
    defineProps<{
        show: boolean;
        token: string | null;
        mode?: QrMode;
        title?: string;
        description?: string;
        extraData?: Record<string, unknown>;
    }>(),
    {
        mode: "check-in",
        title: undefined,
        description: undefined,
        extraData: () => ({}),
    },
);

const emit = defineEmits<{
    close: [];
}>();

const modePresets: Record<QrMode, { title: string; description: string }> = {
    "check-in": {
        title: "Check-in QR Code",
        description: "Scan this QR code to check in",
    },
    "clock-in": {
        title: "Clock-in QR Code",
        description: "Scan this QR code to clock in",
    },
    "clock-out": {
        title: "Clock-out QR Code",
        description: "Scan this QR code to clock out",
    },
    custom: {
        title: "QR Code",
        description: "Scan this QR code",
    },
};

const resolvedTitle = computed(
    () => props.title ?? modePresets[props.mode].title,
);

const resolvedDescription = computed(
    () => props.description ?? modePresets[props.mode].description,
);

const qrValue = computed(() => {
    if (!props.token) return "";

    return JSON.stringify({
        token: props.token,
        type: props.mode,
        ...props.extraData,
    });
});
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="emit('close')"
            >
                <div
                    class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
                >
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">
                                {{ resolvedTitle }}
                            </h2>

                            <p class="mt-1 text-sm text-gray-500">
                                {{ resolvedDescription }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                            @click="emit('close')"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="flex justify-center">
                        <div
                            v-if="qrValue"
                            class="rounded-xl border border-gray-200 bg-white p-5"
                        >
                            <QrcodeVue :value="qrValue" :size="260" level="H" />
                        </div>

                        <div
                            v-else
                            class="flex h-[260px] w-[260px] items-center justify-center rounded-xl bg-gray-100"
                        >
                            <span class="text-sm text-gray-500">
                                Generating QR...
                            </span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <p
                            class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500"
                        >
                            Token
                        </p>

                        <div
                            class="rounded-lg bg-gray-50 p-3 font-mono text-xs break-all text-gray-600"
                        >
                            {{ token }}
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button
                            type="button"
                            class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-800"
                            @click="emit('close')"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
