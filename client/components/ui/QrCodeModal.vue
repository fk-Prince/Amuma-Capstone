<script setup lang="ts">
import QrcodeVue from "qrcode.vue";
import { computed, onUnmounted, ref, watch } from "vue";
import { LogIn, LogOut, ScanLine, X } from "lucide-vue-next";
import ActionButton from "./ActionButton.vue";
import { onlineScheduleService } from "~/api/online-schedule/OnlineScheduleService";
import { useToast } from "~/composables/useToast";

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
    scanned: [];
}>();

const { $echo } = useNuxtApp() as any;

const modePresets: Record<
    QrMode,
    { title: string; description: string; icon: any; accent: string }
> = {
    "check-in": {
        title: "Check-in QR Code",
        description: "Scan this QR code to check in",
        icon: LogIn,
        accent: "accent",
    },
    "clock-in": {
        title: "Clock-in QR Code",
        description: "Scan this QR code to clock in",
        icon: LogIn,
        accent: "accent",
    },
    "clock-out": {
        title: "Clock-out QR Code",
        description: "Scan this QR code to clock out",
        icon: LogOut,
        accent: "amber",
    },
    custom: {
        title: "QR Code",
        description: "Scan this QR code",
        icon: ScanLine,
        accent: "primary",
    },
};

const resolvedTitle = computed(
    () => props.title ?? modePresets[props.mode].title,
);

const resolvedDescription = computed(
    () => props.description ?? modePresets[props.mode].description,
);

const resolvedIcon = computed(() => modePresets[props.mode].icon);
const accent = computed(() => modePresets[props.mode].accent);

const accentClasses = computed(() => {
    const map: Record<string, { chip: string; ring: string; text: string }> = {
        accent: {
            chip: "bg-accent-50 text-accent-600",
            ring: "border-accent-200",
            text: "text-accent-600",
        },
        amber: {
            chip: "bg-amber-50 text-amber-600",
            ring: "border-amber-200",
            text: "text-amber-600",
        },
        primary: {
            chip: "bg-primary-50 text-primary-600",
            ring: "border-primary-200",
            text: "text-primary-600",
        },
    };

    return map[accent.value];
});

const qrValue = computed(() => {
    if (!props.token) return "";

    return JSON.stringify({
        token: props.token,
        type: props.mode,
        ...props.extraData,
    });
});

let channel: any = null;
let subscribedToken: string | null = null;

function unbindChannel() {
    if (!channel || !subscribedToken) return;

    channel.stopListening(".qr.scanned");
    $echo.leave(`private-qr.${subscribedToken}`);

    channel = null;
    subscribedToken = null;
}

function bindChannel(token: string) {
    unbindChannel();

    subscribedToken = token;

    channel = $echo
        .private(`qr.${token}`)
        .listen(".qr.scanned", () => {
            emit("scanned");
            emit("close");
        });
}

watch(
    () => [props.show, props.token] as const,
    ([show, token]) => {
        if (show && token) {
            bindChannel(token as string);
        } else {
            unbindChannel();
        }
    },
    { immediate: true },
);

const { success, error: toastError } = useToast();
const demoing = ref(false);

function toApiType(mode: QrMode): "in" | "out" {
    return mode === "clock-out" ? "out" : "in";
}

async function demoVerify() {
    if (!props.token || demoing.value) return;

    demoing.value = true;

    try {
        await onlineScheduleService.verifyQr({
            token: props.token,
            type: toApiType(props.mode),
        });

        success(
            toApiType(props.mode) === "in"
                ? "Clocked in successfully."
                : "Clocked out successfully.",
        );

        emit("scanned");
        emit("close");
    } catch (err: any) {
        toastError(
            err?.response?.data?.message ??
                err?.message ??
                "Unable to verify this QR code.",
        );
    } finally {
        demoing.value = false;
    }
}

onUnmounted(() => {
    unbindChannel();
});
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="show"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
                @click.self="emit('close')"
            >
                <div
                    class="w-full max-w-md rounded-2xl border border-slate-100 bg-white p-6 shadow-2xl"
                >
                    <div class="mb-6 flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div
                                :class="accentClasses.chip"
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                            >
                                <component :is="resolvedIcon" class="h-5 w-5" />
                            </div>

                            <div>
                                <h2
                                    class="text-lg font-semibold text-gray-900"
                                >
                                    {{ resolvedTitle }}
                                </h2>

                                <p
                                    class="mt-0.5 text-sm text-gray-500"
                                >
                                    {{ resolvedDescription }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="shrink-0 rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                            @click="emit('close')"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="flex justify-center">
                        <div
                            v-if="qrValue"
                            :class="accentClasses.ring"
                            class="rounded-xl border-2 bg-white p-5"
                        >
                            <QrcodeVue :value="qrValue" :size="240" level="H" />
                        </div>

                        <div
                            v-else
                            class="flex h-[280px] w-[280px] flex-col items-center justify-center gap-3 rounded-xl bg-gray-50"
                        >
                            <svg
                                class="h-6 w-6 animate-spin text-gray-400"
                                viewBox="0 0 24 24"
                                fill="none"
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

                            <span
                                class="text-sm text-gray-500"
                            >
                                Generating QR...
                            </span>
                        </div>
                    </div>

                    <p
                        class="mt-5 text-center text-xs text-gray-400"
                    >
                        This code expires once scanned or when you close this
                        window.
                    </p>

                    <div class="mt-6 flex justify-end gap-2">
                        <ActionButton
                            v-if="qrValue"
                            variant="outline"
                            :loading="demoing"
                            @click="demoVerify"
                        >
                            Demo
                        </ActionButton>

                        <ActionButton variant="primary" @click="emit('close')"
                            >Close
                        </ActionButton>
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
