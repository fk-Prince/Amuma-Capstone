<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import { MailCheck, Clock, TimerReset } from "lucide-vue-next";
import BaseButton from "../ui/BaseButton.vue";

const props = withDefaults(
    defineProps<{
        loading?: boolean;
        error?: string | null;
        resendCooldownSeconds?: number;
        expiresInSeconds?: number;
    }>(),
    {
        loading: false,
        error: null,
        resendCooldownSeconds: 60,
        expiresInSeconds: 300,
    },
);

const emit = defineEmits<{
    verify: [otp: string];
    close: [];
    resend: [];
}>();

const otp = ref(["", "", "", "", "", ""]);

function focusInput(index: number) {
    const input = document.getElementById(
        `otp-${index}`,
    ) as HTMLInputElement | null;
    input?.focus();
    input?.select();
}

watch(
    () => props.error,
    (value) => {
        if (!value) return;

        otp.value = ["", "", "", "", "", ""];
        focusInput(0);
    },
);

function handleInput(index: number, event: Event) {
    const target = event.target as HTMLInputElement;

    otp.value[index] = target.value.replace(/\D/g, "").slice(-1);

    if (otp.value[index] && index < otp.value.length - 1) {
        focusInput(index + 1);
    }

    if (otp.value.every((digit) => digit) && !props.loading) {
        verifyOtp();
    }
}

function handleBackspace(index: number, event: KeyboardEvent) {
    if (event.key !== "Backspace") return;

    if (!otp.value[index] && index > 0) {
        focusInput(index - 1);
    }
}

function handlePaste(event: ClipboardEvent) {
    const pasted = event.clipboardData?.getData("text") ?? "";
    const digits = pasted
        .replace(/\D/g, "")
        .slice(0, otp.value.length)
        .split("");

    if (!digits.length) return;

    event.preventDefault();

    digits.forEach((digit, i) => {
        otp.value[i] = digit;
    });

    const nextEmpty = otp.value.findIndex((digit) => !digit);
    focusInput(nextEmpty === -1 ? otp.value.length - 1 : nextEmpty);

    if (otp.value.every((digit) => digit) && !props.loading) {
        verifyOtp();
    }
}

function verifyOtp() {
    emit("verify", otp.value.join(""));
}

const resendSecondsLeft = ref(props.resendCooldownSeconds);
let resendTimer: ReturnType<typeof setInterval> | null = null;

const canResend = computed(() => resendSecondsLeft.value <= 0);

const resendCountdownLabel = computed(() => {
    const minutes = Math.floor(resendSecondsLeft.value / 60);
    const seconds = resendSecondsLeft.value % 60;

    return `${minutes}:${String(seconds).padStart(2, "0")}`;
});

function startResendCooldown() {
    resendSecondsLeft.value = props.resendCooldownSeconds;

    if (resendTimer) clearInterval(resendTimer);

    resendTimer = setInterval(() => {
        if (resendSecondsLeft.value <= 0) {
            if (resendTimer) clearInterval(resendTimer);
            return;
        }

        resendSecondsLeft.value -= 1;
    }, 1000);
}

function handleResend() {
    if (!canResend.value) return;

    emit("resend");
    startResendCooldown();
}

const expirySecondsLeft = ref(props.expiresInSeconds);
let expiryTimer: ReturnType<typeof setInterval> | null = null;

const isExpired = computed(() => expirySecondsLeft.value <= 0);

const expiryCountdownLabel = computed(() => {
    const minutes = Math.floor(expirySecondsLeft.value / 60);
    const seconds = expirySecondsLeft.value % 60;

    return `${minutes}:${String(seconds).padStart(2, "0")}`;
});

function startExpiryCountdown() {
    expirySecondsLeft.value = props.expiresInSeconds;

    if (expiryTimer) clearInterval(expiryTimer);

    expiryTimer = setInterval(() => {
        if (expirySecondsLeft.value <= 0) {
            if (expiryTimer) clearInterval(expiryTimer);
            return;
        }

        expirySecondsLeft.value -= 1;
    }, 1000);
}

watch(() => props.expiresInSeconds, startExpiryCountdown);

onMounted(() => {
    startResendCooldown();
    startExpiryCountdown();
});
onBeforeUnmount(() => {
    if (resendTimer) clearInterval(resendTimer);
    if (expiryTimer) clearInterval(expiryTimer);
});
</script>

<template>
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/50 backdrop-blur-sm px-4"
    >
        <div class="w-full max-w-md rounded-2xl bg-white dark:bg-secondary p-8 shadow-2xl">
            <div class="text-center">
                <div
                    class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary-50 dark:bg-primary-500/10 text-primary"
                >
                    <MailCheck class="h-7 w-7" />
                </div>

                <h2 class="text-2xl font-bold text-secondary dark:text-white">Verify Email</h2>

                <p class="mt-2 text-sm text-muted dark:text-gray-400">
                    Enter the 6-digit verification code sent to your email
                    address.
                </p>

                <p
                    v-if="isExpired"
                    class="mt-3 text-sm font-medium text-red-500"
                >
                    This code has expired. Resend to get a new one.
                </p>
                <div
                    v-else
                    class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium"
                    :class="
                        expirySecondsLeft <= 30 ? 'text-red-500' : 'text-muted dark:text-gray-400'
                    "
                >
                    <TimerReset class="h-3.5 w-3.5" />
                    Code expires in {{ expiryCountdownLabel }}
                </div>
            </div>

            <div class="mt-8 flex justify-center gap-2" @paste="handlePaste">
                <input
                    v-for="(_, index) in otp"
                    :key="index"
                    :id="`otp-${index}`"
                    :value="otp[index]"
                    maxlength="1"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    type="text"
                    :disabled="loading || isExpired"
                    class="h-12 w-12 rounded-xl border-[1.5px] bg-transparent text-center text-lg font-semibold text-slate-800 outline-none transition focus:ring-2 disabled:opacity-50 dark:text-white"
                    :class="
                        error
                            ? 'border-red-400 focus:border-red-500 focus:ring-red-200'
                            : 'border-slate-200 dark:border-white/10 focus:border-primary focus:ring-primary-100'
                    "
                    @input="handleInput(index, $event)"
                    @keydown="handleBackspace(index, $event)"
                />
            </div>

            <p v-if="error" class="mt-3 text-center text-xs text-red-500">
                {{ error }}
            </p>

            <div class="mt-8 space-y-3">
                <BaseButton
                    variant="primary"
                    size="lg"
                    :full="true"
                    :loading="loading"
                    :disabled="isExpired"
                    @click="verifyOtp"
                >
                    {{ loading ? "Verifying..." : "Verify Code" }}
                </BaseButton>

                <div class="flex items-center justify-center gap-2 text-sm">
                    <span class="text-muted dark:text-gray-400">Didn't get the code?</span>

                    <button
                        type="button"
                        class="font-semibold transition"
                        :class="
                            canResend
                                ? 'text-primary hover:underline'
                                : 'cursor-not-allowed text-muted dark:text-gray-400'
                        "
                        :disabled="!canResend"
                        @click="handleResend"
                    >
                        Resend Code
                    </button>

                    <span
                        v-if="!canResend"
                        class="inline-flex items-center gap-1 rounded-full bg-light dark:bg-primary-500/10 px-2.5 py-1 text-xs font-semibold tabular-nums text-primary"
                    >
                        <Clock class="h-3 w-3" />
                        {{ resendCountdownLabel }}
                    </span>
                </div>

                <button
                    type="button"
                    class="w-full text-sm text-muted dark:text-gray-400 transition hover:text-secondary dark:hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="loading"
                    @click="emit('close')"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>
</template>
