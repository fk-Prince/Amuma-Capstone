```vue
<script setup lang="ts">
import { ref } from "vue";
import BaseButton from "../ui/BaseButton.vue";

const props = withDefaults(
    defineProps<{
        loading?: boolean;
    }>(),
    {
        loading: false,
    },
);

const emit = defineEmits<{
    verify: [otp: string];
    close: [];
    resend: [];
}>();

const otp = ref(["", "", "", "", "", ""]);

function handleInput(index: number, event: Event) {
    const target = event.target as HTMLInputElement;

    otp.value[index] = target.value.replace(/\D/g, "").slice(-1);

    if (otp.value[index] && index < 5) {
        const next = document.getElementById(
            `otp-${index + 1}`,
        ) as HTMLInputElement;

        next?.focus();
    }
}

function handleBackspace(index: number, event: KeyboardEvent) {
    if (event.key !== "Backspace") return;

    if (!otp.value[index] && index > 0) {
        const prev = document.getElementById(
            `otp-${index - 1}`,
        ) as HTMLInputElement;

        prev?.focus();
    }
}

function verifyOtp() {
    emit("verify", otp.value.join(""));
}
</script>

<template>
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4"
    >
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl">
            <div class="text-center">
                <div
                    class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-blue-100"
                >
                    <svg
                        class="h-7 w-7 text-blue-600"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 1.62 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72"
                        />
                    </svg>
                </div>

                <h2 class="text-2xl font-bold text-slate-900">Verify Email</h2>

                <p class="mt-2 text-sm text-slate-500">
                    Enter the 6-digit verification code sent to your email
                    address.
                </p>
            </div>

            <div class="mt-8 flex justify-center gap-2">
                <input
                    v-for="(_, index) in otp"
                    :key="index"
                    :id="`otp-${index}`"
                    :value="otp[index]"
                    maxlength="1"
                    type="text"
                    :disabled="loading"
                    class="h-12 w-12 rounded-xl border border-slate-300 text-center text-lg font-semibold outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200 disabled:opacity-50"
                    @input="handleInput(index, $event)"
                    @keydown="handleBackspace(index, $event)"
                />
            </div>

            <div class="mt-8 space-y-3">
                <BaseButton
                    variant="primary"
                    size="lg"
                    :full="true"
                    :loading="loading"
                    @click="verifyOtp"
                >
                    {{ loading ? "Verifying..." : "Verify Code" }}
                </BaseButton>
                <!-- 
                <button
                    class="w-full text-sm font-medium text-blue-600 hover:underline"
                    @click="emit('resend')"
                >
                    Resend Code
                </button> -->

                <button
                    class="w-full text-sm text-slate-500 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="loading"
                    @click="emit('close')"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>
</template>
```
