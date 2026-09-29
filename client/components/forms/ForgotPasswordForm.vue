<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from "vue";
import { ArrowLeft, LoaderCircle, Mail, MailCheck } from "lucide-vue-next";
import { authService } from "~/api/auth/AuthService";
import { useToast } from "~/composables/useToast";

defineOptions({ name: "ForgotPasswordForm" });

const { success, error } = useToast();

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const RESEND_COOLDOWN_SECONDS = 60;

const email = ref("");
const emailError = ref("");
const loading = ref(false);
const sent = ref(false);
const expiresInMinutes = ref(60);
const cooldown = ref(0);
let cooldownTimer: ReturnType<typeof setInterval> | null = null;

const cooldownLabel = computed(
    () => `0:${String(cooldown.value).padStart(2, "0")}`,
);

const fieldClass =
    "h-[47px] w-full rounded-xl border bg-slate-50 pl-11 pr-4 text-sm text-slate-900 outline-none transition-colors placeholder:text-slate-400 focus:border-primary focus:ring-2 focus:ring-primary-500/30 dark:bg-white/[0.04] dark:text-white dark:placeholder:text-gray-500";

const primaryButtonClass =
    "flex h-[46px] w-full items-center justify-center gap-2 rounded-xl bg-primary text-[15px] font-semibold text-white outline-none transition-colors hover:bg-primary-600 focus-visible:ring-2 focus-visible:ring-primary-500/40 disabled:cursor-not-allowed disabled:opacity-60";

function stopCooldown() {
    if (cooldownTimer) clearInterval(cooldownTimer);
    cooldownTimer = null;
}

function startCooldown() {
    stopCooldown();
    cooldown.value = RESEND_COOLDOWN_SECONDS;

    cooldownTimer = setInterval(() => {
        cooldown.value -= 1;

        if (cooldown.value <= 0) stopCooldown();
    }, 1000);
}

async function sendLink(resend = false) {
    emailError.value = "";

    if (!email.value) {
        emailError.value = "Email is required.";
    } else if (!EMAIL_PATTERN.test(email.value)) {
        emailError.value = "Please enter a valid email address.";
    }

    if (emailError.value) return;

    loading.value = true;

    try {
        const res = await authService.forgotPassword({ email: email.value });

        expiresInMinutes.value = Math.round((res?.expires_in ?? 3600) / 60);
        sent.value = true;
        startCooldown();

        if (resend) success("A new link was sent to your email.");
    } catch (err: any) {
        const firstError = Object.values(err?.errors ?? {}).flat()[0];
        const message =
            (typeof firstError === "string" && firstError) ||
            err?.message ||
            "We couldn't send the link. Please try again.";

        if (sent.value) {
            error(message);
        } else {
            emailError.value = message;
        }
    } finally {
        loading.value = false;
    }
}

function useDifferentEmail() {
    sent.value = false;
    cooldown.value = 0;
    stopCooldown();
}

onBeforeUnmount(stopCooldown);
</script>

<template>
    <div v-if="!sent">
        <div class="mb-7 text-center">
            <h2
                class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white"
            >
                Forgot your password?
            </h2>

            <p class="mt-1.5 text-sm text-slate-500 dark:text-gray-400">
                Enter the email you use for AMUMA and we'll send you a link to
                reset your password.
            </p>
        </div>

        <form novalidate @submit.prevent="sendLink()">
            <label
                for="forgot-email"
                class="mb-2 block text-[13px] font-semibold text-slate-700 dark:text-white"
            >
                Email
            </label>

            <div class="relative">
                <span
                    class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-slate-400 dark:text-gray-500"
                >
                    <Mail class="h-[1.05rem] w-[1.05rem]" />
                </span>

                <input
                    id="forgot-email"
                    v-model="email"
                    type="email"
                    autocomplete="email"
                    placeholder="Enter your email address"
                    :class="[
                        fieldClass,
                        emailError
                            ? 'border-danger focus:border-danger focus:ring-danger/30'
                            : 'border-slate-200 dark:border-white/10',
                    ]"
                    @input="emailError = ''"
                />
            </div>

            <p v-if="emailError" class="mt-1.5 text-xs text-danger">
                {{ emailError }}
            </p>

            <button
                type="submit"
                :disabled="loading"
                :class="[primaryButtonClass, 'mt-6']"
            >
                <LoaderCircle v-if="loading" class="h-4 w-4 animate-spin" />
                {{ loading ? "Verifying…" : "Verify Email" }}
            </button>
        </form>

        <NuxtLink
            to="/auth/signin"
            class="mt-6 flex items-center justify-center gap-1.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-800 dark:text-gray-400 dark:hover:text-white"
        >
            <ArrowLeft class="h-4 w-4" />
            Back to sign in
        </NuxtLink>
    </div>

    <div v-else class="text-center">
        <div
            class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary-50 text-primary dark:bg-primary-500/10"
        >
            <MailCheck class="h-7 w-7" />
        </div>

        <h2
            class="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white"
        >
            Check your email
        </h2>

        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-gray-400">
            We sent you a link to reset your password at
            <span class="font-semibold text-slate-800 dark:text-white">{{
                email
            }}</span
            >. The link expires in {{ expiresInMinutes }} minutes.
        </p>

        <NuxtLink to="/auth/signin" :class="[primaryButtonClass, 'mt-7']">
            Back to sign in
        </NuxtLink>

        <div class="mt-5 flex items-center justify-center gap-2 text-sm">
            <span class="text-slate-500 dark:text-gray-400">
                Didn't get the email?
            </span>

            <button
                type="button"
                class="font-semibold transition"
                :class="
                    cooldown > 0 || loading
                        ? 'cursor-not-allowed text-slate-400 dark:text-gray-500'
                        : 'text-primary hover:underline'
                "
                :disabled="cooldown > 0 || loading"
                @click="sendLink(true)"
            >
                {{ loading ? "Sending…" : "Resend link" }}
            </button>

            <span
                v-if="cooldown > 0 && !loading"
                class="rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold tabular-nums text-primary dark:bg-primary-500/10"
            >
                {{ cooldownLabel }}
            </span>
        </div>

        <button
            type="button"
            class="mt-3 text-sm text-slate-500 transition-colors hover:text-slate-800 dark:text-gray-400 dark:hover:text-white"
            @click="useDifferentEmail"
        >
            Use a different email
        </button>
    </div>
</template>
