<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { Eye, EyeOff, LoaderCircle, Lock, Mail } from "lucide-vue-next";
import AlertMessage from "../ui/AlertMessage.vue";
import AuthTransitionScreen from "../ui/AuthTransitionScreen.vue";
import TermsModal from "../ui/TermsModal.vue";

import { useAuthUser } from "~/composables/useAuthUser";
import { authService } from "~/api/auth/AuthService";
import type { Alert } from "~/types/alert.js";
import type { SigninRequest } from "~/types/auth.js";
import { useBranchStore } from "#imports";

const route = useRoute();
const branch = useBranchStore();
const user = useAuthUser();
const redirecting = ref(false);
const welcomeName = ref("");
const showTerms = ref(false);

const welcomeTitle = computed(() =>
    welcomeName.value
        ? `Welcome back, ${welcomeName.value}!`
        : "Welcome back!",
);

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
        const res = await authService.login(signinData.value);
        showAlert(alert, "success", res.message);
        welcomeName.value = res.user?.first_name ?? "";
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

onMounted(() => {
    const providerError = route.query.error;

    if (typeof providerError === "string" && providerError) {
        showAlert(alert, "error", providerError, 0);
    }
});

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
            class="mb-4"
        />

        <form @submit.prevent="handleSignIn">
            <label for="signin-email" :class="labelClass">Email</label>

            <div class="relative">
                <span :class="affixClass">
                    <Mail class="h-[1.05rem] w-[1.05rem]" />
                </span>

                <input
                    id="signin-email"
                    v-model="signinData.email"
                    type="email"
                    autocomplete="email"
                    placeholder="Enter your email address"
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
                    to="/forgot-password"
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
                    to="/auth/signup"
                    class="rounded font-semibold text-blue-600 outline-none hover:underline focus-visible:ring-2 focus-visible:ring-primary-500/40 dark:text-blue-400"
                >
                    Sign up
                </NuxtLink>
            </p>

            <p
                class="mt-3 text-center text-xs leading-5 text-slate-400 dark:text-gray-500"
            >
                By signing in you agree to AMUMA's
                <button
                    type="button"
                    class="font-semibold text-blue-600 hover:underline dark:text-blue-400"
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
