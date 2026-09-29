<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import {
    Eye,
    EyeOff,
    KeyRound,
    LoaderCircle,
    Lock,
    TriangleAlert,
} from "lucide-vue-next";
import { authService } from "~/api/auth/AuthService";
import { useToast } from "~/composables/useToast";

defineOptions({ name: "ResetPasswordForm" });

const route = useRoute();
const { success, error } = useToast();

const INVALID_LINK_MESSAGE = "This link has expired or has already been used.";

const token = typeof route.query.token === "string" ? route.query.token : "";
const userUuid = typeof route.query.user === "string" ? route.query.user : "";

const state = ref<"checking" | "invalid" | "ready">("checking");
const invalidMessage = ref(INVALID_LINK_MESSAGE);

const password = ref("");
const confirmPassword = ref("");
const showPassword = ref(false);
const loading = ref(false);

const errors = ref({
    password: "",
    confirmPassword: "",
});

const fieldClass =
    "h-[47px] w-full rounded-xl border bg-slate-50 pl-11 pr-11 text-sm text-slate-900 outline-none transition-colors placeholder:text-slate-400 focus:border-primary focus:ring-2 focus:ring-primary-500/30 dark:bg-white/[0.04] dark:text-white dark:placeholder:text-gray-500";

const labelClass =
    "mb-2 block text-[13px] font-semibold text-slate-700 dark:text-white";

const affixClass =
    "pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-slate-400 dark:text-gray-500";

const primaryButtonClass =
    "flex h-[46px] w-full items-center justify-center gap-2 rounded-xl bg-primary text-[15px] font-semibold text-white outline-none transition-colors hover:bg-primary-600 focus-visible:ring-2 focus-visible:ring-primary-500/40 disabled:cursor-not-allowed disabled:opacity-60";

const borderClass = (message: string) =>
    message
        ? "border-danger focus:border-danger focus:ring-danger/30"
        : "border-slate-200 dark:border-white/10";

function markInvalid(message?: string) {
    invalidMessage.value = message || INVALID_LINK_MESSAGE;
    state.value = "invalid";
}

onMounted(async () => {
    if (!token || !userUuid) {
        markInvalid();
        return;
    }

    try {
        await authService.checkResetLink({ token, user: userUuid });
        state.value = "ready";
    } catch (err: any) {
        markInvalid(err?.message);
    }
});

async function resetPassword() {
    errors.value = { password: "", confirmPassword: "" };

    if (!password.value) {
        errors.value.password = "Password is required.";
    } else if (password.value.length < 6) {
        errors.value.password = "Password must be at least 6 characters.";
    }

    if (!confirmPassword.value) {
        errors.value.confirmPassword = "Please confirm your password.";
    } else if (password.value && password.value !== confirmPassword.value) {
        errors.value.confirmPassword = "Passwords do not match.";
    }

    if (errors.value.password || errors.value.confirmPassword) return;

    loading.value = true;

    try {
        const res = await authService.resetPassword({
            token,
            user: userUuid,
            password: password.value,
            password_confirmation: confirmPassword.value,
        });

        success(res?.message || "Your password has been reset.");
        await navigateTo("/auth/signin");
    } catch (err: any) {
        const passwordError = err?.errors?.password?.[0];

        if (passwordError) {
            errors.value.password = passwordError;
        } else if (err?.status === 422) {
            markInvalid(err?.message);
        } else {
            error(
                err?.message ||
                    "We couldn't reset your password. Please try again.",
            );
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div
        v-if="state === 'checking'"
        class="flex flex-col items-center gap-3 py-10 text-center"
    >
        <LoaderCircle class="h-8 w-8 animate-spin text-primary" />
        <p class="text-sm font-medium text-slate-500 dark:text-gray-400">
            Checking your link…
        </p>
    </div>

    <div v-else-if="state === 'invalid'" class="text-center">
        <div
            class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
        >
            <TriangleAlert class="h-7 w-7" />
        </div>

        <h2
            class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white"
        >
            Link can't be used
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-gray-400">
            {{ invalidMessage }} Request a new link to reset your password.
        </p>

        <NuxtLink to="/auth/forgot-password" :class="[primaryButtonClass, 'mt-7']">
            Request a new link
        </NuxtLink>

        <NuxtLink
            to="/auth/signin"
            class="mt-4 inline-block text-sm text-slate-500 transition-colors hover:text-slate-800 dark:text-gray-400 dark:hover:text-white"
        >
            Back to sign in
        </NuxtLink>
    </div>

    <div v-else>
        <div class="mb-7 text-center">
            <div
                class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary-50 text-primary dark:bg-primary-500/10"
            >
                <KeyRound class="h-7 w-7" />
            </div>

            <h2
                class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white"
            >
                Set a new password
            </h2>

            <p class="mt-1.5 text-sm text-slate-500 dark:text-gray-400">
                Choose a new password for your AMUMA account.
            </p>
        </div>

        <form novalidate @submit.prevent="resetPassword">
            <label for="reset-password" :class="labelClass">New Password</label>

            <div class="relative">
                <span :class="affixClass">
                    <Lock class="h-[1.05rem] w-[1.05rem]" />
                </span>

                <input
                    id="reset-password"
                    v-model="password"
                    :type="showPassword ? 'text' : 'password'"
                    autocomplete="new-password"
                    placeholder="Enter your new password"
                    :class="[fieldClass, borderClass(errors.password)]"
                    @input="errors.password = ''"
                />

                <button
                    type="button"
                    tabindex="-1"
                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-slate-400 outline-none transition-colors hover:text-blue-500 dark:text-gray-500"
                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    @click="showPassword = !showPassword"
                >
                    <EyeOff v-if="showPassword" class="h-[1.05rem] w-[1.05rem]" />
                    <Eye v-else class="h-[1.05rem] w-[1.05rem]" />
                </button>
            </div>

            <p v-if="errors.password" class="mt-1.5 text-xs text-danger">
                {{ errors.password }}
            </p>

            <label
                for="reset-password-confirm"
                :class="labelClass"
                class="mt-[22px]"
            >
                Confirm New Password
            </label>

            <div class="relative">
                <span :class="affixClass">
                    <Lock class="h-[1.05rem] w-[1.05rem]" />
                </span>

                <input
                    id="reset-password-confirm"
                    v-model="confirmPassword"
                    :type="showPassword ? 'text' : 'password'"
                    autocomplete="new-password"
                    placeholder="Confirm your new password"
                    :class="[fieldClass, borderClass(errors.confirmPassword)]"
                    @input="errors.confirmPassword = ''"
                />

                <button
                    type="button"
                    tabindex="-1"
                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-slate-400 outline-none transition-colors hover:text-blue-500 dark:text-gray-500"
                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    @click="showPassword = !showPassword"
                >
                    <EyeOff v-if="showPassword" class="h-[1.05rem] w-[1.05rem]" />
                    <Eye v-else class="h-[1.05rem] w-[1.05rem]" />
                </button>
            </div>

            <p v-if="errors.confirmPassword" class="mt-1.5 text-xs text-danger">
                {{ errors.confirmPassword }}
            </p>

            <button
                type="submit"
                :disabled="loading"
                :class="[primaryButtonClass, 'mt-7']"
            >
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                {{ loading ? "Resetting…" : "Reset password" }}
            </button>
        </form>
    </div>
</template>
