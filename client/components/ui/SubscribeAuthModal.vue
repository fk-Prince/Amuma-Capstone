<script setup lang="ts">
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from "vue";
import { Check, LoaderCircle, X } from "lucide-vue-next";
import logo from "~/assets/logo/logo.png";
import panelPhoto from "~/assets/logo/signinLogo2.png";
import { authService } from "~/api/auth/AuthService";
import { useSubscribeAuthOpen } from "~/composables/useSubscribeFlow";
import { useSubscriptionCheckout } from "~/stores/subscription";
import { formatCurrency } from "~/utils/currency";
import { planPrice, planTypeLabel } from "~/utils/planType";

const open = useSubscribeAuthOpen();
const checkout = useSubscriptionCheckout();

const primaryRef = ref<HTMLButtonElement | null>(null);
const googleLoading = ref(false);
const errorMessage = ref("");

const chips = ["Bookings", "Billing", "eMAR"];

const planName = computed(() => {
    const plan: any = checkout.selectedPlan;
    return plan?.title ?? plan?.name ?? "";
});

// Plans are priced per year, so this is exactly what the plan card showed.
const planAmount = computed(() => {
    const plan: any = checkout.selectedPlan;
    const amount = plan ? planPrice(plan) : 0;

    return amount > 0 ? formatCurrency(amount) : "";
});

const planTypeText = computed(() =>
    planTypeLabel((checkout.selectedPlan as any)?.type),
);

function close() {
    open.value = false;
}

async function go(path: string) {
    saveAuthRedirect(CHECKOUT_PATH);
    close();
    await navigateTo(withRedirect(path, CHECKOUT_PATH));
}

async function continueWithGoogle() {
    googleLoading.value = true;
    errorMessage.value = "";

    // success.vue reads this after Google sends the person back.
    saveAuthRedirect(CHECKOUT_PATH);

    try {
        const res = await authService.googleUrl();
        window.location.href = res.url;
    } catch (err: any) {
        errorMessage.value =
            err?.message || "We couldn't reach Google. Please try again.";
        googleLoading.value = false;
    }
}

function onKeydown(event: KeyboardEvent) {
    if (open.value && event.key === "Escape") close();
}

watch(open, async (isOpen) => {
    if (!import.meta.client) return;

    document.body.style.overflow = isOpen ? "hidden" : "";

    if (isOpen) {
        errorMessage.value = "";
        googleLoading.value = false;
        await nextTick();
        primaryRef.value?.focus({ preventScroll: true });
    }
});

onMounted(() => window.addEventListener("keydown", onKeydown));

onBeforeUnmount(() => {
    window.removeEventListener("keydown", onKeydown);
    document.body.style.overflow = "";
});
</script>

<template>
    <ClientOnly>
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="open"
                    class="fixed inset-0 z-[90] flex items-center justify-center overflow-y-auto bg-secondary-950/60 px-4 py-6 backdrop-blur-sm"
                    @click.self="close"
                >
                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="subscribe-auth-title"
                        aria-describedby="subscribe-auth-desc"
                        class="sam-panel relative my-auto flex w-full max-w-[460px] flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_40px_90px_-20px_rgba(15,23,42,0.6)] md:max-w-[800px] md:flex-row dark:border-white/10 dark:bg-secondary"
                    >
                        <button
                            type="button"
                            class="absolute right-3 top-3 z-20 flex h-8 w-8 items-center justify-center rounded-full text-white/80 outline-none transition-colors hover:bg-white/15 hover:text-white focus-visible:ring-2 focus-visible:ring-white/60 md:right-4 md:top-4 md:text-slate-400 md:hover:bg-slate-900/5 md:hover:text-slate-600 md:focus-visible:ring-primary-500/40 dark:md:text-gray-500 dark:md:hover:bg-white/10 dark:md:hover:text-gray-200"
                            aria-label="Close"
                            @click="close"
                        >
                            <X class="h-4 w-4" />
                        </button>

                        <!-- Brand panel -->
                        <div
                            class="relative flex flex-col items-center justify-start overflow-hidden bg-gradient-to-br from-primary-500 via-primary-600 to-primary-800 px-8 pb-16 pt-9 text-center text-white md:w-[46%] md:pb-24 md:pl-9 md:pr-14 md:pt-12"
                        >
                            <div
                                class="pointer-events-none absolute inset-0"
                                aria-hidden="true"
                            >
                                <div
                                    class="absolute -left-20 -top-24 h-72 w-72 rounded-full bg-white/15 blur-3xl"
                                />

                                <!-- Your caregiver photo, blended into the blue -->
                                <div
                                    class="sam-photo absolute inset-x-0 bottom-0 top-[40%] hidden md:block"
                                >
                                    <img
                                        :src="panelPhoto"
                                        alt=""
                                        class="h-full w-full object-cover object-[18%_30%] opacity-50 mix-blend-luminosity"
                                    />
                                </div>

                                <!-- Drifting waves -->
                                <svg
                                    class="sam-wave sam-wave-back absolute bottom-0 left-0 h-24 w-[200%] text-white/10 md:h-28"
                                    viewBox="0 0 1200 120"
                                    preserveAspectRatio="none"
                                    fill="currentColor"
                                >
                                    <path
                                        d="M0 60 Q150 0 300 60 T600 60 T900 60 T1200 60 V120 H0 Z"
                                    />
                                </svg>
                                <svg
                                    class="sam-wave sam-wave-front absolute bottom-0 left-0 h-16 w-[200%] text-white/[0.14] md:h-20"
                                    viewBox="0 0 1200 120"
                                    preserveAspectRatio="none"
                                    fill="currentColor"
                                >
                                    <path
                                        d="M0 70 Q150 20 300 70 T600 70 T900 70 T1200 70 V120 H0 Z"
                                    />
                                </svg>

                                <!-- Wavy edge: bottom on phones -->
                                <svg
                                    class="absolute -bottom-px left-0 h-6 w-full fill-white md:hidden dark:fill-secondary"
                                    viewBox="0 0 400 40"
                                    preserveAspectRatio="none"
                                >
                                    <path
                                        d="M0 22 Q50 0 100 22 T200 22 T300 22 T400 22 V40 H0 Z"
                                    />
                                </svg>

                                <!-- Wavy edge: right side on desktop -->
                                <svg
                                    class="absolute -right-px top-0 hidden h-full w-12 fill-white md:block dark:fill-secondary"
                                    viewBox="0 0 60 400"
                                    preserveAspectRatio="none"
                                >
                                    <path
                                        d="M30 0 Q12 50 30 100 T30 200 T30 300 T30 400 H60 V0 Z"
                                    />
                                </svg>
                            </div>

                            <div class="relative flex flex-col items-center">
                                <div
                                    class="relative flex h-[76px] w-[76px] items-center justify-center"
                                >
                                    <span
                                        class="absolute -inset-4 rounded-full bg-[radial-gradient(circle,rgba(255,255,255,0.5)_0%,rgba(255,255,255,0.16)_48%,transparent_72%)]"
                                        aria-hidden="true"
                                    />
                                    <span class="sam-ring" aria-hidden="true" />
                                    <span
                                        class="sam-ring sam-ring-late"
                                        aria-hidden="true"
                                    />

                                    <img
                                        :src="logo"
                                        alt="AMUMA"
                                        class="sam-beat relative h-[60px] w-[60px] object-contain"
                                    />
                                </div>

                                <p
                                    class="sam-brand mt-5 text-2xl font-extrabold leading-none tracking-wide"
                                >
                                    AMUMA
                                </p>

                                <p
                                    class="mt-3 hidden whitespace-nowrap text-[13px] leading-relaxed text-white/85 md:block"
                                >
                                    Run your agency from one place.
                                </p>

                                <ul
                                    class="mt-4 hidden flex-wrap justify-center gap-2 md:flex"
                                >
                                    <li
                                        v-for="chip in chips"
                                        :key="chip"
                                        class="rounded-full border border-white/30 bg-white/15 px-3 py-1 text-[11px] font-semibold text-white backdrop-blur-sm"
                                    >
                                        {{ chip }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Actions panel -->
                        <div class="flex-1 px-7 pb-7 pt-5 md:py-11 md:pl-5 md:pr-9">
                            <h2
                                id="subscribe-auth-title"
                                class="text-2xl font-extrabold leading-tight tracking-tight text-slate-900 dark:text-white"
                            >
                                Create your agency account
                            </h2>

                            <p
                                id="subscribe-auth-desc"
                                class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-gray-400"
                            >
                                Set up your agency on AMUMA. It only takes a
                                minute, and your plan stays selected.
                            </p>

                            <!-- Selected plan -->
                            <div
                                v-if="planName"
                                class="mt-5 flex items-center justify-between gap-3 rounded-2xl border border-primary-100 bg-primary-50/70 px-4 py-3 dark:border-primary-500/20 dark:bg-primary-500/10"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-white"
                                    >
                                        <Check class="h-4 w-4" stroke-width="3" />
                                    </span>

                                    <div class="min-w-0 text-left">
                                        <p
                                            class="text-[11px] font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-300"
                                        >
                                            Selected plan
                                        </p>
                                        <p
                                            class="truncate text-sm font-bold text-slate-900 dark:text-white"
                                        >
                                            {{ planName }}
                                        </p>
                                        <p
                                            v-if="planTypeText"
                                            class="truncate text-[11px] text-slate-500 dark:text-gray-400"
                                        >
                                            {{ planTypeText }}
                                        </p>
                                    </div>
                                </div>

                                <div class="shrink-0 text-right">
                                    <p
                                        v-if="planAmount"
                                        class="text-sm font-extrabold text-slate-900 dark:text-white"
                                    >
                                        {{ planAmount }}
                                    </p>
                                    <p
                                        class="text-[11px] font-medium text-slate-500 dark:text-gray-400"
                                    >
                                        per year
                                    </p>
                                    <button
                                        type="button"
                                        class="mt-0.5 rounded text-[11px] font-semibold text-blue-600 outline-none hover:underline focus-visible:ring-2 focus-visible:ring-primary-500/40 dark:text-blue-400"
                                        @click="close"
                                    >
                                        Change
                                    </button>
                                </div>
                            </div>

                            <div class="mt-5 space-y-3">
                                <button
                                    ref="primaryRef"
                                    type="button"
                                    class="h-[50px] w-full rounded-xl bg-primary text-[15px] font-semibold text-white shadow-[0_10px_24px_-10px_rgba(49,130,237,0.8)] outline-none transition-all hover:-translate-y-0.5 hover:bg-primary-600 focus-visible:ring-2 focus-visible:ring-primary-300/60"
                                    @click="go('/auth/signup')"
                                >
                                    Create account
                                </button>

                                <div class="flex items-center gap-3">
                                    <span
                                        class="h-px flex-1 bg-slate-200 dark:bg-white/10"
                                    />
                                    <span
                                        class="text-[11px] font-semibold tracking-wider text-slate-400 dark:text-gray-500"
                                    >
                                        OR
                                    </span>
                                    <span
                                        class="h-px flex-1 bg-slate-200 dark:bg-white/10"
                                    />
                                </div>

                                <button
                                    type="button"
                                    class="flex h-[50px] w-full items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 outline-none transition-colors hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-primary-500/40 disabled:cursor-not-allowed disabled:opacity-60 dark:border-white/10 dark:bg-white/[0.04] dark:text-white dark:hover:bg-white/10"
                                    :disabled="googleLoading"
                                    @click="continueWithGoogle"
                                >
                                    <LoaderCircle
                                        v-if="googleLoading"
                                        class="h-5 w-5 animate-spin"
                                    />
                                    <img
                                        v-else
                                        src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                                        alt=""
                                        class="h-5 w-5"
                                    />
                                    Continue with Google
                                </button>

                                <p
                                    v-if="errorMessage"
                                    class="text-center text-xs text-danger"
                                    role="alert"
                                >
                                    {{ errorMessage }}
                                </p>
                            </div>

                            <p
                                class="mt-6 border-t border-slate-100 pt-5 text-center text-sm text-slate-500 dark:border-white/10 dark:text-gray-400"
                            >
                                Already have an account?
                                <button
                                    type="button"
                                    class="rounded font-semibold text-blue-600 outline-none hover:underline focus-visible:ring-2 focus-visible:ring-primary-500/40 dark:text-blue-400"
                                    @click="go('/auth/family/signin')"
                                >
                                    Sign in
                                </button>
                            </p>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </ClientOnly>
</template>

<style scoped>
.sam-panel {
    animation: sam-pop 0.32s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes sam-pop {
    from {
        opacity: 0;
        transform: translateY(14px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* The photo fades in from the bottom up, so it never fights the text. */
.sam-photo {
    -webkit-mask-image: linear-gradient(to bottom, transparent 0%, #000 45%);
    mask-image: linear-gradient(to bottom, transparent 0%, #000 45%);
}

/* Heartbeat: a quick "lub-dub", then a long rest. */
.sam-beat {
    transform-origin: center;
    filter: brightness(1.12) saturate(1.1)
        drop-shadow(0 0 8px rgba(255, 255, 255, 0.55))
        drop-shadow(0 3px 8px rgba(10, 40, 87, 0.4));
    animation: sam-beat 2.8s ease-in-out infinite;
}

@keyframes sam-beat {
    0%,
    100% {
        transform: scale(1);
    }
    14% {
        transform: scale(1.09);
    }
    28% {
        transform: scale(1);
    }
    42% {
        transform: scale(1.06);
    }
    70% {
        transform: scale(1);
    }
}

/* Soft rings that ripple out on each beat. */
.sam-ring {
    position: absolute;
    inset: 8px;
    border-radius: 9999px;
    border: 2px solid rgba(255, 255, 255, 0.55);
    opacity: 0;
    animation: sam-ring 2.8s ease-out infinite;
}

.sam-ring-late {
    animation-delay: 0.4s;
}

@keyframes sam-ring {
    0% {
        opacity: 0;
        transform: scale(0.9);
    }
    14% {
        opacity: 0.6;
        transform: scale(1);
    }
    70%,
    100% {
        opacity: 0;
        transform: scale(1.8);
    }
}

/* Same bold wordmark and moving shine as the default brand text, in light
   colors so it reads on the blue panel. */
.sam-brand {
    background-image: linear-gradient(
        100deg,
        #cfe2fb 0%,
        #cfe2fb 38%,
        #ffffff 50%,
        #cfe2fb 62%,
        #cfe2fb 100%
    );
    background-size: 250% 100%;
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    animation: sam-shine 4.5s linear infinite;
}

@keyframes sam-shine {
    from {
        background-position: 150% 0;
    }
    to {
        background-position: -150% 0;
    }
}

/* Waves drift sideways. The svg is twice the panel width and holds exactly
   two wave periods, so moving it by half its width loops without a jump. */
.sam-wave {
    animation: sam-drift 16s linear infinite;
}

.sam-wave-back {
    animation-duration: 24s;
    animation-direction: reverse;
}

@keyframes sam-drift {
    from {
        transform: translateX(0);
    }
    to {
        transform: translateX(-50%);
    }
}

@media (prefers-reduced-motion: reduce) {
    .sam-panel,
    .sam-beat,
    .sam-ring,
    .sam-wave {
        animation: none;
    }

    .sam-ring {
        display: none;
    }

    .sam-brand {
        animation: none;
        background-image: none;
        color: #ffffff;
    }
}
</style>