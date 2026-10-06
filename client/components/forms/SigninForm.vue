<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Eye, EyeOff, LoaderCircle, Lock, Mail } from "lucide-vue-next";
import AlertMessage from "../ui/AlertMessage.vue";
import AuthTransitionScreen from "../ui/AuthTransitionScreen.vue";
import TermsModal from "../ui/TermsModal.vue";

import { useAuthUser, useAuthReady } from "~/composables/useAuthUser";
import { authService } from "~/api/auth/AuthService";
import type { Alert } from "~/types/alert.js";
import type { SigninRequest } from "~/types/auth.js";
import { authLandingPath } from "~/utils/authLanding";

const user = useAuthUser();
const authReady = useAuthReady();
const route = useRoute();
const redirecting = ref(false);
const welcomeName = ref("");
const showTerms = ref(false);

const welcomeTitle = computed(() =>
    welcomeName.value ? `Welcome back, ${welcomeName.value}!` : "Welcome back!",
);

const signinData = ref({
    email: "",
    password: "",
});

const signupRedirect = computed(
    () => safeRedirect(route.query.redirect) ?? peekAuthRedirect(),
);

function buildCredentials(): SigninRequest {
    const identifier = signinData.value.email.trim();

    if (!identifier.includes("@")) {
        return {
            employee_code: identifier.toUpperCase(),
            password: signinData.value.password,
        };
    }

    return { email: identifier, password: signinData.value.password };
}

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

const oauthErrors: Record<string, string> = {
    google_failed: "Google sign-in failed. Please try again.",
    provider_mismatch:
        "This email is registered with email and password. Please sign in with your email and password instead.",
};

onMounted(() => {
    const queryError = route.query.error;
    const code = Array.isArray(queryError) ? queryError[0] : queryError;
    if (!code) return;

    const message =
        oauthErrors[code] ?? "Unable to sign in with Google. Please try again.";

    showAlert(alert, "error", message, 0);
    const { error: _error, ...rest } = route.query;
    navigateTo({ path: route.path, query: rest }, { replace: true });
});

const fieldClass =
    "h-[47px] w-full rounded-xl border bg-slate-50 pl-11 pr-4 text-sm text-slate-900 outline-none transition-colors placeholder:text-slate-400 focus:border-primary focus:ring-2 focus:ring-primary-500/30 dark:bg-white/[0.04] dark:text-white dark:placeholder:text-gray-500";

const affixClass =
    "pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-slate-400 dark:text-gray-500";

const labelClass =
    "mb-2 block text-[13px] font-semibold text-slate-700 dark:text-white";

const borderClass = (error: string) =>
    error
        ? "border-danger focus:border-danger focus:ring-danger/30"
        : "border-slate-200 dark:border-white/10";

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
        const res = await authService.login(buildCredentials());

        showAlert(alert, "success", res.message);
        welcomeName.value = res.user?.first_name ?? "";
        redirecting.value = true;

        // Where this person was headed (for example checkout), if anywhere.
        const redirectTo = consumeAuthRedirect(route.query.redirect);

        setTimeout(async () => {
            loading.value = true;
            user.value = res.user;
            // The welcome screen above already covered the wait, so the dashboard
            // layout must not show its own "Setting things up" screen after it.
            authReady.value = true;

            await navigateTo(redirectTo ?? (await authLandingPath()));
        }, 1500);
    } catch (err: any) {
        showAlert(
            alert,
            "error",
            err?.message || "Invalid employee ID, email, or password.",
            0,
        );
    } finally {
        loading.value = false;
    }
}

async function googleUrl() {
    loading.value = true;

    try {
        saveAuthRedirect(route.query.redirect);
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
            class="mb-4"
        />

        <form @submit.prevent="handleSignIn">
            <label for="signin-email" :class="labelClass">
                Employee ID or email
            </label>

            <div class="relative">
                <span :class="affixClass">
                    <Mail class="h-[1.05rem] w-[1.05rem]" />
                </span>

                <input
                    id="signin-email"
                    v-model="signinData.email"
                    type="text"
                    autocomplete="username"
                    placeholder="Enter your employee ID or email"
                    :class="[fieldClass, borderClass(errors.email)]"
                />
            </div>

            <p v-if="errors.email" class="mt-1.5 text-xs text-danger">
                {{ errors.email }}
            </p>

            <label for="signin-password" :class="labelClass" class="mt-[22px]">
                Password
            </label>

            <div class="relative">
                <span :class="affixClass">
                    <Lock class="h-[1.05rem] w-[1.05rem]" />
                </span>

                <input
                    id="signin-password"
                    v-model="signinData.password"
                    :type="showPassword ? 'text' : 'password'"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    :class="[fieldClass, borderClass(errors.password), 'pr-11']"
                />

                <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-slate-400 outline-none transition-colors hover:text-blue-500 focus-visible:ring-2 focus-visible:ring-primary-500/40 dark:text-gray-500"
                    :aria-label="
                        showPassword ? 'Hide password' : 'Show password'
                    "
                    @click="showPassword = !showPassword"
                >
                    <EyeOff
                        v-if="showPassword"
                        class="h-[1.05rem] w-[1.05rem]"
                    />
                    <Eye v-else class="h-[1.05rem] w-[1.05rem]" />
                </button>
            </div>

            <p v-if="errors.password" class="mt-1.5 text-xs text-danger">
                {{ errors.password }}
            </p>

            <div class="mt-3.5 flex justify-end">
                <NuxtLink
                    to="/auth/forgot-password"
                    class="rounded text-xs font-medium text-blue-600 outline-none hover:underline focus-visible:ring-2 focus-visible:ring-primary-500/40 dark:text-blue-400"
                >
                    Forgot Password?
                </NuxtLink>
            </div>

            <button
                type="submit"
                :disabled="loading || redirecting"
                class="mt-6 flex h-[46px] w-full items-center justify-center gap-2 rounded-xl bg-primary text-[15px] font-semibold text-white outline-none transition-colors hover:bg-primary-600 focus-visible:ring-2 focus-visible:ring-primary-500/40 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                {{ loading ? "Signing in…" : "Sign in" }}
            </button>

            <div>
                <div class="mt-5 flex items-center gap-3">
                    <span class="h-px flex-1 bg-slate-200 dark:bg-white/10" />
                    <span
                        class="text-xs font-medium uppercase tracking-widest text-slate-400 dark:text-gray-500"
                    >
                        or
                    </span>
                    <span class="h-px flex-1 bg-slate-200 dark:bg-white/10" />
                </div>

                <button
                    type="button"
                    :disabled="loading || redirecting"
                    class="mt-5 flex h-12 w-full items-center justify-center gap-3 rounded-xl border border-slate-200 bg-slate-100 text-[15px] font-semibold text-slate-700 outline-none transition-colors hover:bg-slate-200 focus-visible:ring-2 focus-visible:ring-primary-500/40 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-white/[0.06] dark:text-white dark:hover:bg-white/[0.12]"
                    @click="googleUrl()"
                >
                    <img
                        src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                        alt=""
                        class="h-5 w-5"
                    />
                    Sign in with Google
                </button>

                <p
                    class="mt-7 text-center text-sm text-slate-500 dark:text-gray-400"
                >
                    Don't have an account?
                    <NuxtLink
                        :to="withRedirect('/auth/signup', signupRedirect)"
                        class="rounded font-semibold text-blue-600 outline-none hover:underline focus-visible:ring-2 focus-visible:ring-primary-500/40 dark:text-blue-400"
                    >
                        Sign up
                    </NuxtLink>
                </p>
            </div>

            <p
                class="mt-3 text-center text-xs leading-5 text-slate-400 dark:text-gray-500"
            >
                By signing in you agree to AMUMA's
                <button
                    type="button"
                    class="font-semibold text-blue-600 hover:underline"
                    @click.prevent="showTerms = true"
                >
                    Terms and Conditions
                </button>
            </p>
        </form>

        <TermsModal v-if="showTerms" @close="showTerms = false" />

        <AuthTransitionScreen
            v-if="redirecting"
            :title="welcomeTitle"
            subtitle=""
        />
    </div>
</template>
