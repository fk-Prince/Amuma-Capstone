<script setup lang="ts">
import SigninForm from "~/components/forms/SigninForm.vue";
import signinLogo from "~/assets/logo/signinLogo.png";
</script>

<template>
    <div
        class="grid grid-cols-1 lg:grid-cols-[55%_40%] bg-slate-100 font-primary min-h-screen"
    >
        <img
            :src="signinLogo"
            alt="Background"
            class="absolute inset-0 w-full h-full object-cover lg:hidden"
        />
        <div
            class="absolute inset-0 lg:hidden bg-gradient-to-br from-slate-900/75 via-blue-950/60 to-blue-900/50"
        />

        <section
            class="relative hidden lg:flex overflow-hidden rounded-r-[280px]"
        >
            <img
                :src="signinLogo"
                alt="Background"
                class="absolute inset-0 w-full h-full object-cover"
            />
            <div
                class="absolute inset-0 bg-gradient-to-br from-slate-900/75 via-blue-950/60 to-blue-900/50"
            />
            <div
                class="relative z-10 flex flex-col justify-center gap-[10rem] w-full px-10 md:px-20 py-10 text-white"
            >
                <div class="space-y-4">
                    <p
                        class="inline-block border-b-2 border-blue-500 pb-1.5 text-[0.65rem] uppercase tracking-[0.22em] text-blue-300"
                    >
                        Welcome Back
                    </p>
                    <h1
                        class="text-4xl xl:text-5xl font-extrabold leading-[1.12] tracking-tight"
                    >
                        We're here,<br />
                        whenever
                        <span class="text-blue-400">you need us.</span>
                    </h1>
                    <p
                        class="max-w-[38ch] text-sm leading-relaxed text-white/70"
                    >
                        Sign in to your AMUMA account to continue accessing your
                        dashboard.
                    </p>
                </div>

                <ul class="space-y-4">
                    <li class="flex items-start gap-3.5">
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border border-white/20 bg-white/10"
                        >
                            <svg
                                class="h-4 w-4 text-blue-300"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"
                                />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold">Designed for You</p>
                            <p
                                class="mt-0.5 text-xs leading-snug text-white/60"
                            >
                                Simple, intuitive, and built to make things
                                easier.
                            </p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3.5">
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border border-white/20 bg-white/10"
                        >
                            <svg
                                class="h-4 w-4 text-blue-300"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.62 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.87a16 16 0 0 0 5.36 5.36l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold">Always Here</p>
                            <p
                                class="mt-0.5 text-xs leading-snug text-white/60"
                            >
                                Our support team is ready to help you anytime.
                            </p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3.5">
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border border-white/20 bg-white/10"
                        >
                            <svg
                                class="h-4 w-4 text-blue-300"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold">
                                Trusted &amp; Secure
                            </p>
                            <p
                                class="mt-0.5 text-xs leading-snug text-white/60"
                            >
                                Your data is protected with enterprise-grade
                                security.
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <section
            class="relative z-10 flex flex-col items-center justify-center px-6 py-12"
        >
            <div
                class="bg-white rounded-2xl shadow-xl px-10 py-11 w-full max-w-[460px]"
            >
                <div class="text-center mb-12">
                    <h2
                        class="text-[1.85rem] font-extrabold text-slate-900 tracking-tight"
                    >
                        Welcome back
                    </h2>

                    <p class="text-sm text-slate-500 mt-1">
                        Sign in to your
                        <span class="text-blue-600 font-semibold">AMUMA</span>
                        account
                    </p>
                </div>
                <SigninForm />
            </div>
        </section>
    </div>
</template>
