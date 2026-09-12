<script setup lang="ts">
import { ref } from "vue";
import BaseInput from "../ui/BaseInput.vue";
import BaseButton from "../ui/BaseButton.vue";
import AlertMessage from "../ui/AlertMessage.vue";

import { useAuthUser, fetchAuthUser } from "~/composables/useAuthUser";
import { authService } from "~/api/auth/AuthService";
import { useSplashScreen } from "~/composables/useSplashScreen";
import { getPostLoginRoute } from "~/composables/usePostLoginRoute";
import type { Alert } from "~/types/alert.js";
import type { SigninRequest } from "~/types/auth.js";
import { useBranchStore } from "#imports";

const route = useRoute();
const branch = useBranchStore();
const user = useAuthUser();
const splash = useSplashScreen();
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
        user.value = res.user;

        splash.show({
            title: `Welcome back, ${res.user?.first_name ?? "there"}!`,
            subtitle: "Taking you to your dashboard…",
        });

        // Work out the destination while the splash is already showing, so
        // there's no flash of the landing page in between.
        const destination = await getPostLoginRoute(res.user);

        setTimeout(async () => {
            loading.value = true;
            await navigateTo(destination);

            // Small buffer after navigation so the splash doesn't vanish
            // before the new page has actually painted.
            setTimeout(() => splash.hide(), 500);
        }, 700);
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
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
        >
            <AlertMessage
                v-if="alert.show"
                :type="alert.type"
                :message="alert.message"
                class="mb-4"
            />
        </Transition>

        <form class="flex flex-col gap-4" @submit.prevent="handleSignIn">
            <BaseInput
                v-model="signinData.email"
                label="Email"
                placeholder="you@example.com"
                mode="text"
                :error="errors.email"
            >
                <template #prefix>
                    <svg
                        class="w-[1.05rem] h-[1.05rem]"
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
                        class="w-[1.05rem] h-[1.05rem]"
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
                        tabindex="-1"
                        class="flex items-center px-3.5 text-slate-400 hover:text-primary-500 transition-colors outline-none rounded-md focus-visible:ring-2 focus-visible:ring-primary-500/40"
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

            <div class="flex justify-end -mt-1.5">
                <NuxtLink
                    to="/forgot-password"
                    class="text-xs font-semibold text-primary-600 hover:text-primary-700 hover:underline outline-none rounded focus-visible:ring-2 focus-visible:ring-primary-500/40"
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
                buttonClass="shadow-lg shadow-primary-500/25 hover:shadow-xl hover:shadow-primary-500/30 active:scale-[0.98]"
            >
                <span>{{ loading ? "Signing in…" : "Sign in" }}</span>
            </BaseButton>

            <div class="flex items-center gap-3 py-1">
                <span class="flex-1 h-px bg-slate-200" />
                <span
                    class="text-[11px] text-slate-400 font-semibold uppercase tracking-widest"
                    >or continue with</span
                >
                <span class="flex-1 h-px bg-slate-200" />
            </div>

            <BaseButton
                type="button"
                variant="outline"
                size="lg"
                :disabled="loading || redirecting"
                :full="true"
                buttonClass="border-[1.5px] border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300 active:scale-[0.98]"
                @click="googleUrl()"
            >
                <img
                    src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                    alt="Google"
                    class="w-5 h-5"
                />
                Continue with Google
            </BaseButton>

            <p class="text-center text-sm text-slate-500 pt-1 dark:text-gray-400">
                Don't have an account?
                <NuxtLink
                    to="/auth/signup"
                    class="text-primary-600 font-semibold hover:text-primary-700 hover:underline outline-none rounded focus-visible:ring-2 focus-visible:ring-primary-500/40"
                >
                    Sign up
                </NuxtLink>
            </p>
        </form>
    </div>
</template>