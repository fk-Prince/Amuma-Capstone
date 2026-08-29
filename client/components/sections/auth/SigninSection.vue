<script setup lang="ts">
import SigninForm from "~/components/forms/SigninForm.vue";
import signinLogo from "~/assets/logo/signinLogo.png";
</script>
<template>
    <div
        class="relative grid grid-cols-1 md:grid-cols-[55%_40%] bg-slate-100 dark:bg-secondary font-primary h-screen overflow-hidden"
    >
        <img
            :src="signinLogo"
            alt="Background"
            class="absolute inset-0 w-full h-full object-cover md:hidden"
        />
        <div
            class="absolute inset-0 md:hidden bg-gradient-to-br from-slate-900/75 via-blue-950/60 to-blue-900/50"
        />

        <!--  rounded-r-[120px] lg:rounded-r-[280px] -->
        <section class="relative hidden md:flex overflow-hidden">
            <img
                :src="signinLogo"
                alt="Background"
                class="absolute inset-0 w-full h-full object-cover"
            />
            <div
                class="absolute inset-0 bg-gradient-to-br from-slate-900/75 via-blue-950/60 to-blue-900/50"
            />
            <div
                class="relative z-10 flex flex-col justify-center gap-8 lg:gap-16 xl:gap-[10rem] w-full px-8 md:px-10 lg:px-20 py-6 text-white overflow-y-auto"
            >
                <div class="space-y-3">
                    <p
                        class="inline-block border-b-2 border-blue-500 pb-1.5 text-[0.65rem] uppercase tracking-[0.22em] text-blue-300"
                    >
                        Welcome Back
                    </p>
                    <h1
                        class="text-2xl md:text-3xl xl:text-5xl font-extrabold leading-[1.12] tracking-tight"
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
            </div>
        </section>

        <section
            class="relative z-10 flex flex-col items-center justify-center px-4 sm:px-6 pt-[106px] pb-4 h-full overflow-y-auto"
        >
            <div
                class="bg-white dark:bg-secondary rounded-2xl shadow-xl px-6 sm:px-10 py-6 sm:py-8 w-full max-w-[460px] my-auto"
            >
                <div class="text-center mb-6 sm:mb-8">
                    <h2
                        class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight"
                    >
                        Welcome back
                    </h2>

                    <p class="text-sm text-slate-500 dark:text-gray-400 mt-1">
                        Sign in to your
                        <span
                            class="text-blue-600 dark:text-blue-400 font-semibold"
                            >AMUMA</span
                        >
                        account
                    </p>
                </div>
                <SigninForm />
            </div>
        </section>
    </div>
</template>
