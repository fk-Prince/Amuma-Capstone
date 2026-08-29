<script setup lang="ts">
import { ref } from "vue";
import BaseInput from "../ui/BaseInput.vue";
import BaseButton from "../ui/BaseButton.vue";
import AlertMessage from "../ui/AlertMessage.vue";

import { useAuthUser, fetchAuthUser } from "~/composables/useAuthUser";
import { authService } from "~/api/auth/AuthService";
import type { Alert } from "~/types/alert.js";
import type { SigninRequest } from "~/types/auth.js";
import { useBranchStore } from "#imports";

const route = useRoute();
const branch = useBranchStore();
const user = useAuthUser();
const redirecting = ref(false);

const signinData = ref<SigninRequest>({
    email: "prince.sestoso@gmail.com",
    password: "password",
});

const showPassword = ref(false);
const loading = ref(false);

const errors = ref({
    email: "",
    password: "",
});

const alert = ref<Alert>({
    show: false,
    type: "info",
    message: "",
});

async function handleSignIn() {
    errors.value = {
        email: "",
        password: "",
    };

    alert.value.show = false;

    if (!signinData.value.email || !signinData.value.password) {
        showAlert(alert, "error", "Invalid credentials", 0);
        return;
    }

    loading.value = true;

    try {
        const res = await authService.login(signinData.value);
        showAlert(alert, "success", res.message);
        redirecting.value = true;
        setTimeout(async () => {
            loading.value = true;
            user.value = res.user;
            await navigateTo("/");
            await branch.refreshBranch();
        }, 1500);
    } catch (err: any) {
        showAlert(
            alert,
            "error",
            err?.message || "Invalid email or password.",
            0,
        );
    } finally {
        loading.value = false;
    }
}

async function googleUrl() {
    loading.value = true;

    try {
        const res = await authService.googleUrl();
        window.location.href = res.url;
    } catch (err: any) {
        showAlert(alert, "error", err?.message || "Internal Server Error", 0);
    } finally {
        loading.value = false;
    }
}
</script>
<template>
    <div>
        <AlertMessage
            v-if="alert.show"
            :type="alert.type"
            :message="alert.message"
            class="mb-3"
        />

        <form class="flex flex-col gap-3 sm:gap-4">
            <BaseInput
                v-model="signinData.email"
                label="Email"
                placeholder="Enter your email address"
                mode="text"
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
                v-model="signinData.password"
                label="Password"
                placeholder="Enter your password"
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

            <div class="flex justify-end -mt-1">
                <NuxtLink
                    to="/forgot-password"
                    class="text-xs font-medium text-blue-600 hover:underline"
                >
                    Forgot Password?
                </NuxtLink>
            </div>

            <BaseButton
                type="submit"
                variant="primary"
                size="lg"
                :full="true"
                :loading="loading"
                :disabled="loading || redirecting"
                @click="handleSignIn"
            >
                <span>{{ loading ? "Signing in…" : "Sign In" }}</span>
            </BaseButton>

            <div class="flex items-center gap-3">
                <span class="flex-1 h-px bg-slate-200" />
                <span
                    class="text-xs text-slate-400 font-medium uppercase tracking-widest"
                    >or</span
                >
                <span class="flex-1 h-px bg-slate-200" />
            </div>

            <BaseButton
                @click="googleUrl()"
                variant="secondary"
                size="lg"
                :disabled="loading || redirecting"
                :full="true"
            >
                <img
                    src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                    alt="Google"
                    class="w-5 h-5"
                />
                Continue with Google
            </BaseButton>

            <p class="text-center text-sm text-slate-500">
                Dont have an account?
                <NuxtLink
                    to="/auth/signup"
                    class="text-blue-600 font-semibold hover:underline"
                >
                    Sign up
                </NuxtLink>
            </p>
        </form>
    </div>
</template>
