<template>
    <div
        class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 via-white to-gray-100 px-6"
    >
        <div class="text-center max-w-lg">
            <div
                class="mx-auto mb-8 flex h-24 w-24 items-center justify-center rounded-full bg-primary/10 text-primary"
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
                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
            </div>

            <h1 class="text-8xl font-black tracking-tight text-gray-900">
                404
            </h1>

            <h2 class="mt-4 text-2xl font-semibold text-gray-800">
                Page not found
            </h2>

            <p class="mt-3 text-gray-500 leading-relaxed">
                Sorry, the page you are looking for doesn't exist or has been
                moved somewhere else.
            </p>

            <div class="mt-8 flex justify-center">
                <NuxtLink
                    to="/"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 font-medium text-white shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 hover:shadow-xl"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6"
                        />
                    </svg>

                    Back to home
                </NuxtLink>
            </div>
        </div>
    </div>
</template>
<script setup lang="ts">
definePageMeta({
    layout: false,
});

const event = useRequestEvent();

if (event) {
    setResponseStatus(event, 404);
}

useHead({
    title: "Not Found",
});
</script>
