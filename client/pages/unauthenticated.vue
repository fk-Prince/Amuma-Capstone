<template>
    <div
        class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 via-white to-gray-100 px-6"
    >
        <div class="text-center max-w-lg">
            <div
                class="mx-auto mb-8 flex h-24 w-24 items-center justify-center rounded-full bg-amber-100 text-amber-600"
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

            <h1 class="text-4xl font-black tracking-tight text-gray-900">
                {{ heading }}
            </h1>

            <p class="mt-3 text-gray-500 leading-relaxed">
                {{ message }}
            </p>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <NuxtLink
                    :to="signInTarget"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 font-medium text-white shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 hover:shadow-xl"
                >
                    Sign in
                </NuxtLink>

                <NuxtLink
                    to="/"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-6 py-3 font-medium text-gray-600 transition hover:bg-gray-50"
                >
                    Back to home
                </NuxtLink>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAuthUser } from "~/composables/useAuthUser";

definePageMeta({
    layout: false,
});

const route = useRoute();
const user = useAuthUser();

const isSignedIn = computed(() => !!user.value);

const heading = computed(() =>
    isSignedIn.value ? "This area is for families" : "Please sign in",
);

const message = computed(() =>
    isSignedIn.value
        ? "The portal is for a patient's family and guardians. Your account is signed in to the staff side, so there is nothing here for it."
        : "You need to be signed in to a family account to open the portal.",
);

const signInTarget = computed(() => {
    const redirect = (route.query.redirect as string) || "/portal/overview";

    return { path: "/auth/signin", query: { redirect } };
});

useHead({
    title: "Access denied",
});
</script>
