<script setup lang="ts">
import heroImage from "~/assets/logo/signinLogo.png";
import SigninForm from "~/components/forms/SigninForm.vue";

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

     
        <div
            class="relative z-10 mx-auto flex min-h-screen w-[94%] max-w-[1400px] items-center justify-center px-6 pt-32 pb-12 sm:px-15 lg:justify-start"
        >
            <div
                class="grid w-full grid-cols-2 items-center gap-17 md:grid-cols-[1fr_560px]"
            >
            
                <div class="hidden max-w-[440px] flex-col gap-8 text-white lg:flex">
                    <div>
                        <span
                            class="inline-block border-b-2 border-primary pb-1 text-xs font-semibold tracking-wide text-blue-200"
                        >
                            Welcome back
                        </span>
                        <h1
                            class="mt-4 text-4xl font-black leading-[1.1] xl:text-[2.5rem]"
                        >
                            We're here, whenever
                            <span class="text-blue-300">you need us.</span>
                        </h1>
                        <p class="mt-4 max-w-[400px] text-sm leading-7 text-blue-100/90">
                            Sign in to your AMUMA account to continue
                            accessing your dashboard.
                        </p>
                    </div>

                    <div class="flex items-center gap-5 border-t border-white/20 pt-6">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/15 backdrop-blur-md">
                                <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                </svg>
                            </span>
                            <p class="text-sm font-semibold leading-tight">Better<br />Healthcare Access</p>
                        </div>

                        <span class="h-9 w-px shrink-0 bg-white/25"></span>

                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/15 backdrop-blur-md">
                                <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </span>
                            <p class="text-sm font-semibold leading-tight">Stronger<br />Communities</p>
                        </div>

                        <span class="h-9 w-px shrink-0 bg-white/25"></span>

                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/15 backdrop-blur-md">
                                <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.6z" />
                                </svg>
                            </span>
                            <p class="text-sm font-semibold leading-tight">Healthier<br />Tomorrows</p>
                        </div>
                    </div>
                </div>

                <!-- Sign-in card -->
                <div class="flex w-full justify-center md:justify-end">
                    <SigninForm />
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