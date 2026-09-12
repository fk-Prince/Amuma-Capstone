<script setup lang="ts">
import heroImage from "~/assets/logo/signinLogo.png";
import SignupForm from "~/components/forms/SignupForm.vue";

// navVariant/navTheme are already set by pages/auth/signup.vue's
// definePageMeta — DefaultNavbar renders the pill nav for us, so this
// section only handles the hero + form.
</script>

<template>
    <div
        class="no-scrollbar relative h-screen w-full overflow-y-auto overflow-x-hidden bg-[#eaf2fd] dark:bg-secondary-950"
    >
        <div class="absolute inset-0 hidden md:block">
            <img :src="heroImage" alt="" class="h-full w-full object-cover" />
            <div class="absolute inset-0 bg-blue-950/45 mix-blend-multiply"></div>
            <div
                class="absolute inset-0 bg-gradient-to-r from-blue-950/55 via-blue-900/25 to-[#eaf2fd] dark:to-secondary-950"
            ></div>
            <div
                class="absolute inset-y-0 right-0 w-2/5 bg-gradient-to-r from-transparent to-[#eaf2fd] dark:to-secondary-950"
            ></div>
            <div
                class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-[#eaf2fd]/80 dark:from-secondary-950/80 to-transparent"
            ></div>
        </div>

        <!-- Content -->
        <div
            class="relative z-10 flex min-h-screen w-full items-center justify-center px-6 pt-32 pb-12 sm:px-15 lg:justify-start lg:pl-[7%] lg:pr-[4%]"
        >
            <div
                class="grid w-full max-w-[1400px] grid-cols-2 items-center gap-15 lg:grid-cols-[1fr_auto]"
            >
                <!-- Welcome column -->
                <div
                    class="hidden max-w-[440px] flex-col gap-8 text-white lg:flex"
                >
                    <div>
                        <span
                            class="inline-block border-b-2 border-primary pb-1 text-xs font-semibold tracking-wide text-blue-200"
                        >
                            Create account
                        </span>
                        <h1
                            class="mt-4 text-4xl font-black leading-[1.1] xl:text-[2.5rem]"
                        >
                            Join <span class="text-blue-300">AMUMA</span>
                            today.
                        </h1>
                        <p
                            class="mt-4 max-w-[400px] text-sm leading-7 text-blue-100/90"
                        >
                            Create your account and start accessing all
                            features designed to help you manage and monitor
                            your services with ease.
                        </p>
                    </div>

                    <div
                        class="flex flex-col gap-5 border-t border-white/20 pt-6"
                    >
                        <div class="flex items-center gap-4">
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/15 backdrop-blur-md"
                            >
                                <svg
                                    class="h-4.5 w-4.5 text-white"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"
                                    />
                                    <circle cx="9" cy="7" r="4" />
                                    <path
                                        d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"
                                    />
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold leading-tight">
                                    Quick & Easy Registration
                                </p>
                                <p class="mt-0.5 text-xs text-blue-100/70">
                                    Set up your account in just a few simple
                                    steps.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/15 backdrop-blur-md"
                            >
                                <svg
                                    class="h-4.5 w-4.5 text-white"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"
                                    />
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold leading-tight">
                                    Dedicated Support
                                </p>
                                <p class="mt-0.5 text-xs text-blue-100/70">
                                    Get assistance whenever you need help.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sign-up card -->
                <div class="flex w-full max-w-[500px] justify-center lg:justify-end">
                    <SignupForm />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>