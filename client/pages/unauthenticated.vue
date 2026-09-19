<template>
    <div
        class="relative min-h-screen flex items-center justify-center overflow-hidden bg-secondary-950 px-6"
    >
        <div
            class="pointer-events-none absolute inset-0 overflow-hidden"
            aria-hidden="true"
        >
            <div
                class="absolute -top-40 left-1/2 h-[520px] w-[520px] -translate-x-1/2 rounded-full bg-primary-500/15 blur-[140px]"
            />
            <div
                class="absolute -bottom-40 -right-40 h-[420px] w-[420px] rounded-full bg-accent-500/10 blur-[140px]"
            />
        </div>

        <NuxtLink
            to="/"
            class="absolute left-6 top-6 z-10 inline-flex"
            aria-label="AMUMA home"
        >
            <BrandLogo />
        </NuxtLink>

        <div class="relative z-10 text-center max-w-lg">
            <div
                class="mx-auto mb-8 flex h-24 w-24 items-center justify-center rounded-full border border-white/10 bg-amber-500/10 text-amber-400"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-12 w-12"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                    />
                </svg>
            </div>

            <h1 class="text-4xl font-black tracking-tight text-white">
                {{ heading }}
            </h1>

            <p class="mt-3 text-gray-400 leading-relaxed">
                {{ message }}
            </p>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <NuxtLink
                    v-if="!isNoBooking"
                    :to="signInTarget"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 font-medium text-white shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 hover:shadow-xl"
                >
                    Sign in
                </NuxtLink>

                <NuxtLink
                    v-else
                    to="/booking"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 font-medium text-white shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 hover:shadow-xl"
                >
                    Book now
                </NuxtLink>

                <NuxtLink
                    v-if="!isNoBooking"
                    to="/"
                    class="inline-flex items-center gap-2 rounded-xl border border-white/10 px-6 py-3 font-medium text-gray-300 transition hover:bg-white/10"
                >
                    Back to home
                </NuxtLink>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAuthUser } from "~/composables/useAuthUser";
import BrandLogo from "~/components/ui/BrandLogo.vue";

definePageMeta({
    layout: false,
});

const route = useRoute();
const user = useAuthUser();

const isSignedIn = computed(() => !!user.value);

const isNoBooking = computed(
    () => route.query.reason === "no-booking" && isSignedIn.value,
);

const isNotAdmin = computed(
    () => route.query.reason === "not-admin" && isSignedIn.value,
);

const heading = computed(() => {
    if (isNoBooking.value) return "No booking yet";
    if (isNotAdmin.value) return "Platform admins only";

    return isSignedIn.value ? "This area is for families" : "Please sign in";
});

const message = computed(() => {
    if (isNoBooking.value) {
        return "The family portal opens once there is a booking on this account. Book a stay or a home visit to get started.";
    }

    if (isNotAdmin.value) {
        return "This area is for AMUMA's platform administrators. Your account doesn't have that access.";
    }

    return isSignedIn.value
        ? "The portal is for a patient's family and guardians. Your account is signed in to the staff side, so there is nothing here for it."
        : "You need to be signed in to a family account to open the portal.";
});

const signInTarget = computed(() => {
    const redirect = (route.query.redirect as string) || "/portal/overview";

    return { path: "/auth/signin", query: { redirect } };
});

useHead({
    title: "Access denied",
});
</script>
