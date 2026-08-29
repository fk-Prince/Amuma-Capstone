<script setup lang="ts">
import { ref, watch } from "vue";
import BaseInput from "../ui/BaseInput.vue";
import BaseButton from "../ui/BaseButton.vue";
import OtpDialog from "../ui/OtpDialog.vue";
import { useToast } from "~/composables/useToast";
import { otpService } from "~/api/otp/OtpService";

defineOptions({ name: "SignupForm" });

const { success, error } = useToast();

const signupData = ref({
    firstName: "",
    lastName: "",
    email: "",
    password: "",
    confirmPassword: "",
});

const errors = ref({
    firstName: "",
    lastName: "",
    email: "",
    password: "",
    confirmPassword: "",
});

const showPassword = ref(false);
const loading = ref(false);
const otpLoading = ref(false);
const showOtpDialog = ref(false);
const otpExpiresIn = ref(300);

watch(
    () => signupData.value.email,
    () => {
        errors.value.email = "";
    },
);

watch(
    () => signupData.value.firstName,
    () => {
        errors.value.firstName = "";
    },
);

watch(
    () => signupData.value.lastName,
    () => {
        errors.value.lastName = "";
    },
);

watch(
    () => [signupData.value.password, signupData.value.confirmPassword],
    ([password, confirmPassword]) => {
        errors.value.password = "";

        if (!confirmPassword) {
            errors.value.confirmPassword = "";
            return;
        }

        errors.value.confirmPassword =
            password === confirmPassword ? "" : "Passwords do not match.";
    },
);

async function handleSignUp() {
    errors.value = {
        firstName: "",
        lastName: "",
        email: "",
        password: "",
        confirmPassword: "",
    };

    if (!signupData.value.firstName.trim()) {
        errors.value.firstName = "First name is required.";
    }

    if (!signupData.value.lastName.trim()) {
        errors.value.lastName = "Last name is required.";
    }

    if (!signupData.value.email) {
        errors.value.email = "Email is required.";
    }

    if (!signupData.value.password) {
        errors.value.password = "Password is required.";
    }

    if (!signupData.value.confirmPassword) {
        errors.value.confirmPassword = "Please confirm your password.";
    }

    if (
        signupData.value.password &&
        signupData.value.confirmPassword &&
        signupData.value.password !== signupData.value.confirmPassword
    ) {
        errors.value.confirmPassword = "Passwords do not match.";
    }

    if (
        errors.value.firstName ||
        errors.value.lastName ||
        errors.value.email ||
        errors.value.password ||
        errors.value.confirmPassword
    ) {
        return;
    }

    loading.value = true;

    try {
        const res = await otpService.createOtp({
            email: signupData.value.email,
        });

        if (res?.otp_key) {
            localStorage.setItem("otp_key", res.otp_key);
        }

        otpExpiresIn.value = res?.expires_in ?? 300;
        showOtpDialog.value = true;
        success("OTP sent to your email.");
    } catch (err: any) {
        error(err?.message || "Failed to send OTP.");
    } finally {
        loading.value = false;
    }
}

async function verifyOtp(code: string) {
    otpLoading.value = true;

    try {
        const otp_key = localStorage.getItem("otp_key");

        if (!otp_key) {
            otpLoading.value = false;
            error("OTP session expired. Please try again.");
            return;
        }

        const res = await otpService.validateOtp({
            otp_key,
            user: {
                first_name: signupData.value.firstName.trim(),
                last_name: signupData.value.lastName.trim(),
                email: signupData.value.email,
                password: signupData.value.password,
            },
            otp_value: code,
        });

        success(res.message);

        localStorage.removeItem("otp_key");

        showOtpDialog.value = false;

        await navigateTo("/auth/signin");
    } catch (err: any) {
        error(err?.message || "Invalid OTP.");
    } finally {
        otpLoading.value = false;
    }
}

async function resendOtp() {
    try {
        const res = await otpService.createOtp({
            email: signupData.value.email,
        });

        if (res?.otp_key) {
            localStorage.setItem("otp_key", res.otp_key);
        }

        otpExpiresIn.value = res?.expires_in ?? 300;
        success("OTP resent.");
    } catch (err: any) {
        error(err?.message || "Failed to resend OTP.");
    }
}
</script>

<template>
    <div
        class="w-full max-w-[460px] rounded-2xl bg-white px-10 py-11 shadow-xl"
    >
        <OtpDialog
            v-if="showOtpDialog"
            :loading="otpLoading"
            :expires-in-seconds="otpExpiresIn"
            @verify="verifyOtp"
            @resend="resendOtp"
            @close="showOtpDialog = false"
        />

        <div class="mb-10 text-center">
            <h2 class="text-[1.85rem] font-extrabold text-slate-900">
                Create an Account
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Join
                <span class="font-semibold text-blue-600">AMUMA</span> today
            </p>
        </div>

        <div class="flex flex-col gap-5">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <BaseInput
                    v-model="signupData.firstName"
                    label="First Name"
                    placeholder="Enter your first name"
                    :error="errors.firstName"
                />

                <BaseInput
                    v-model="signupData.lastName"
                    label="Last Name"
                    placeholder="Enter your last name"
                    :error="errors.lastName"
                />
            </div>

            <BaseInput
                v-model="signupData.email"
                label="Email"
                placeholder="Enter your email address"
                mode="email"
                :error="errors.email"
            >
                <template #prefix>
                    <svg
                        class="w-[1.05rem] h-[1.05rem] text-slate-400"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <rect x="2" y="4" width="20" height="16" rx="2" />
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                    </svg>
                </template>
            </BaseInput>

            <BaseInput
                v-model="signupData.password"
                label="Password"
                placeholder="Create a password"
                :mode="showPassword ? 'text' : 'password'"
                :error="errors.password"
            >
                <template #prefix>
                    <svg
                        class="w-[1.05rem] h-[1.05rem] text-slate-400"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <rect
                            x="3"
                            y="11"
                            width="18"
                            height="11"
                            rx="2"
                            ry="2"
                        />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                </template>
                <template #suffix>
                    <button
                        type="button"
                        class="flex items-center px-3 text-slate-400 hover:text-blue-500 transition-colors"
                        @click="showPassword = !showPassword"
                    >
                        <svg
                            v-if="showPassword"
                            class="w-[1.05rem] h-[1.05rem]"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"
                            />
                            <path
                                d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"
                            />
                            <line x1="1" y1="1" x2="23" y2="23" />
                        </svg>
                        <svg
                            v-else
                            class="w-[1.05rem] h-[1.05rem]"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
                            />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>
                </template>
            </BaseInput>

            <BaseInput
                v-model="signupData.confirmPassword"
                label="Confirm Password"
                placeholder="Confirm your password"
                :mode="showPassword ? 'text' : 'password'"
                :error="errors.confirmPassword"
            >
                <template #prefix>
                    <svg
                        class="w-[1.05rem] h-[1.05rem] text-slate-400"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <rect
                            x="3"
                            y="11"
                            width="18"
                            height="11"
                            rx="2"
                            ry="2"
                        />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                </template>
                <template #suffix>
                    <button
                        type="button"
                        class="flex items-center px-3 text-slate-400 hover:text-blue-500 transition-colors"
                        @click="showPassword = !showPassword"
                    >
                        <svg
                            v-if="showPassword"
                            class="w-[1.05rem] h-[1.05rem]"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"
                            />
                            <path
                                d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"
                            />
                            <line x1="1" y1="1" x2="23" y2="23" />
                        </svg>
                        <svg
                            v-else
                            class="w-[1.05rem] h-[1.05rem]"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
                            />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>
                </template>
            </BaseInput>

            <BaseButton
                type="submit"
                variant="primary"
                size="lg"
                :full="true"
                :loading="loading"
                class="mt-3"
                @click="handleSignUp"
            >
                {{ loading ? "Sign Up..." : "Sign Up" }}
            </BaseButton>

            <p class="mt-2 text-center text-sm text-slate-500">
                Already have an account?
                <NuxtLink
                    to="/auth/signin"
                    class="font-semibold text-blue-600 hover:underline"
                >
                    Sign in
                </NuxtLink>
            </p>
        </div>
    </div>
</template>
