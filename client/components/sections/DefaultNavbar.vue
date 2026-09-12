<script setup lang="ts">
import {
    computed,
    onMounted,
    onUnmounted,
    ref,
} from "vue";
import { useRoute } from "vue-router";

import logoIcon from "~/assets/logo/logo.png";
import BaseButton from "../ui/BaseButton.vue";
import { useAuthUser } from "~/composables/useAuthUser";
import NavbarProfileDropdown from "../ui/NavbarProfileDropdown.vue";
import DynamicSidebar from "./DynamicSidebar.vue";

const user = useAuthUser();
const route = useRoute();

const hydrated = ref(false);
const mobileMenuOpen = ref(false);
const scrolled = ref(false);
 
const onScroll = () => {
    scrolled.value = window.scrollY > 8;
};

onMounted(() => {
    hydrated.value = true;

    window.addEventListener("scroll", onScroll, {
        passive: true,
    });

    onScroll();
});

onUnmounted(() => {
    window.removeEventListener("scroll", onScroll);
});

 

const props = defineProps<{
    navList?: {
        label: string;
        to: string;
    }[];
}>();

 

const variant = computed(() => route.meta.navVariant ?? 3);

const navTheme = computed(() => route.meta.navTheme ?? "light");

const isChromeSolid = computed(
    () =>
        variant.value === 3 ||
        scrolled.value ||
        navTheme.value !== "dark",
);


const authNavLabel = computed(() =>
    route.path.includes("signup") ? "Sign in" : "Sign up",
);

const authNavTo = computed(() =>
    route.path.includes("signup") ? "/auth/signin" : "/auth/signup",
);

const header = computed(() => {
    switch (variant.value) {


        case 3: //sa landing page
            return [
                "fixed",
                "top-4",
                "left-1/2",
                "-translate-x-1/2",
                "z-50",

                "w-[89%]",
                "max-w-[1300px]",
                "h-[72px]",

                "rounded-[24px]",

            
                "bg-white/35",
                "dark:bg-secondary-900/60",
                "backdrop-blur-2xl",
                "backdrop-saturate-150",

       
                "border",
                "border-white/60",
                "dark:border-white/10",

                
                "shadow-[0_8px_32px_0_rgba(31,38,135,0.12)]",

                "transition-all",
                "duration-300",
            ].join(" ");

     

        case 4:
            return [
                "relative",
                "w-full",
                "h-[70px]",
                "flex",
                "items-center",
                "md:px-[5%]",
                "lg:px-[8%]",
                "bg-secondary",
            ].join(" ");

    

        case 5: //sa signin/up form 
            return [
                "fixed",
                "top-4",
                "left-1/2",
                "-translate-x-1/2",
                "z-50",

                "w-[89%]",
                "max-w-[1300px]",
                "h-[72px]",

              

                "rounded-[24px]",
                "px-6",
                "sm:px-10",

                "bg-white/70",
                "dark:bg-secondary-900/70",
                "backdrop-blur-xl",
                "backdrop-saturate-150",

                "border",
                "border-white/60",
                "dark:border-white/10",

                "shadow-[0_8px_30px_-8px_rgba(15,23,42,0.25)]",

                "transition-all",
                "duration-300",
            ].join(" ");
        

        default:
            return [
                "fixed",
                "top-0",
                "left-0",
                "z-50",
                "w-full",
                "h-[84px]",
                "md:px-[5%]",
                "lg:px-[10%]",
                "transition-all",
                "duration-300",
                "ease-out",

                scrolled.value || navTheme.value !== "dark"
                    ? "bg-white/80 dark:bg-secondary-900/80 backdrop-blur-xl border-b border-white/50 dark:border-white/10 shadow-sm"
                    : "bg-transparent border-b border-transparent",
            ].join(" ");
    }
});
 

const navLinkClass = computed(() =>
    isChromeSolid.value
        ? "text-slate-600 dark:text-gray-300 hover:text-primary"
        : "text-light/90 hover:text-light",
);

 

const menuIconClass = computed(() =>
    isChromeSolid.value
        ? "text-slate-700 dark:text-gray-200 hover:bg-blue-50 dark:hover:bg-white/10 hover:text-primary"
        : "text-light hover:bg-light/10",
);
</script>


<template>

    <header :class="header">


        <nav
            class="
                relative
                flex
                h-full
                w-full
                items-center
                justify-between
            "
            :class="
                variant === 5
                    ? ''
                    : 'px-6 sm:px-8 lg:px-10'
            "
        >

            <template v-if="variant === 5">

                <NuxtLink
                    to="/"
                    class="
                        group
                        flex
                        shrink-0
                        items-center
                        gap-2.5
                    "
                >

                    <img
                        :src="logoIcon"
                        alt="AMUMA logo"
                        class="
                            h-10
                            w-10
                            object-contain
                            transition-transform
                            duration-300
                            group-hover:scale-105
                        "
                    />

                    <span
                        class="
                            text-2xl
                            font-black
                            tracking-tight
                            text-primary
                        "
                    >
                        AMUMA
                    </span>

                </NuxtLink>

                <NuxtLink
                    :to="authNavTo"
                    class="
                        shrink-0
                        text-sm
                        font-semibold
                        text-primary
                        transition-colors
                        duration-200
                        hover:text-primary-600
                    "
                >
                    {{ authNavLabel }}
                </NuxtLink>

            </template>


            <template v-else>


                <div
                    class="
                        flex
                        min-w-0
                        items-center
                    "
                >

                    <NuxtLink
                        to="/"
                        class="
                            group
                            flex
                            shrink-0
                            items-center
                            gap-2.5
                        "
                    >

                        <img
                            :src="logoIcon"
                            alt="AMUMA logo"
                            class="
                                h-10
                                w-10
                                object-contain
                                transition-transform
                                duration-300
                                group-hover:scale-105
                            "
                        />

                        <span
                            class="
                                text-2xl
                                font-black
                                tracking-tight
                                text-primary
                            "
                        >
                            AMUMA
                        </span>

                    </NuxtLink>

                </div>



                <div
                    v-if="
                        variant === 1 ||
                        variant === 3
                    "
                    class="
                        absolute
                        left-1/2
                        top-1/2
                        hidden
                        -translate-x-1/2
                        -translate-y-1/2
                        items-center
                        gap-8
                        lg:flex
                    "
                >

          

                    <NuxtLink
                        v-for="i in navList"
                        :key="i.to"
                        :to="i.to"
                        class="
                            group
                            relative
                            whitespace-nowrap
                            py-2
                            text-sm
                            font-semibold
                            transition-colors
                            duration-200
                        "
                        :class="navLinkClass"
                    >

                        {{ i.label }}


                        <span
                            class="
                                absolute
                                bottom-0
                                left-1/2
                                h-[2px]
                                w-0
                                -translate-x-1/2
                                rounded-full
                                bg-primary

                                transition-all
                                duration-300
                                ease-out

                                group-hover:w-full
                            "
                        />

                    </NuxtLink>

                </div>

                <div
                    class="
                        flex
                        shrink-0
                        items-center
                        gap-7
                    "
                >


                    <template
                        v-if="
                            !hydrated ||
                            !user
                        "
                    >

                        <div
                            class="
                                hidden
                                items-center
                                gap-7
                                sm:flex
                            "
                        >

                   

                            <NuxtLink
                                :to="
                                    hydrated
                                        ? '/auth/signin'
                                        : undefined
                                "
                                class="
                                    shrink-0
                                    text-sm
                                    font-semibold
                                    text-primary
                                    transition-colors
                                    duration-200
                                    hover:text-primary-600
                                "
                            >
                                Sign in
                            </NuxtLink>

                            <span
                                class="
                                    h-6
                                    w-px
                                    shrink-0
                                    bg-blue-200/80
                                    dark:bg-white/15
                                "
                            />


            

                            <NuxtLink
                                :to="
                                    hydrated
                                        ? '/auth/signup'
                                        : undefined
                                "
                                class="
                                    group
                                    shrink-0
                                "
                            >

                                <BaseButton
                                    buttonClass="
                                        h-12
                                        min-w-[126px]
                                        rounded-full
                                        px-7
                                        whitespace-nowrap

                                        shadow-[0_6px_18px_-6px_rgba(37,99,235,0.45)]

                                        transition-all
                                        duration-200

                                        group-hover:-translate-y-0.5

                                        group-hover:shadow-[0_8px_22px_-6px_rgba(37,99,235,0.55)]

                                        active:scale-[0.97]
                                    "
                                    class="
                                        border
                                        border-primary
                                        bg-primary
                                        text-white
                                        font-semibold
                                        hover:bg-primary-600
                                    "
                                >
                                    Sign up
                                </BaseButton>

                            </NuxtLink>

                        </div>

                    </template>



                    <NavbarProfileDropdown
                        v-else
                        :user="user"
                        :scrolled="scrolled"
                        :navTheme="navTheme"
                        :theme-aware="isChromeSolid"
                    />



                    <button
                        v-if="
                            variant === 1 ||
                            variant === 3
                        "
                        class="
                            flex
                            h-10
                            w-10
                            items-center
                            justify-center
                            rounded-xl
                            transition-all
                            duration-300
                            lg:hidden
                        "
                        :class="menuIconClass"
                        aria-label="Open menu"
                        @click="
                            mobileMenuOpen = true
                        "
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="h-6 w-6"
                        >

                            <line
                                x1="3"
                                y1="6"
                                x2="21"
                                y2="6"
                            />

                            <line
                                x1="3"
                                y1="12"
                                x2="21"
                                y2="12"
                            />

                            <line
                                x1="3"
                                y1="18"
                                x2="21"
                                y2="18"
                            />

                        </svg>

                    </button>

                </div>

            </template>

        </nav>


 

        <ClientOnly
            v-if="
                variant === 1 ||
                variant === 3
            "
        >

            <DynamicSidebar
                :open="mobileMenuOpen"
                :logo="logoIcon"
                :authMenu="navList"
                :user="user"
                :desktop-breakpoint="1024"
                @close="
                    mobileMenuOpen = false
                "
            />

        </ClientOnly>

    </header>

</template>