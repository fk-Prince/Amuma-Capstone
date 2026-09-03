<template>
    <div
        v-if="open"
        class="fixed inset-0 z-30 bg-gray-900/40 backdrop-blur-sm lg:hidden"
        aria-hidden="true"
        @click="emit('close')"
    />

    <aside
        class="group fixed inset-y-0 left-0 z-40 flex h-full w-64 shrink-0 flex-col overflow-hidden border-r border-gray-100 dark:border-white/10 bg-white dark:bg-secondary transition-transform duration-200 ease-in-out lg:static lg:z-20 lg:w-[76px] lg:translate-x-0 lg:transition-[width] lg:hover:w-64"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
    >
        <div
            class="flex shrink-0 items-center justify-between px-[19px] pt-4 pb-3"
        >
            <NuxtLink
                :to="homeLink"
                class="flex items-center gap-2.5"
                @click="emit('close')"
            >
                <img
                    :src="logo"
                    alt="AMUMA"
                    class="w-9 h-9 rounded-lg object-contain shrink-0"
                />
                <div
                    class="whitespace-nowrap leading-tight transition-opacity duration-150 delay-75 lg:opacity-0 lg:group-hover:opacity-100"
                >
                    <p
                        class="font-extrabold text-primary-500 text-2xl tracking-wide [text-shadow:0_4px_8px_rgb(49_130_237_/_35%)] dark:text-primary-300"
                    >
                        AMUMA
                    </p>
                </div>
            </NuxtLink>

            <button
                type="button"
                class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-50 hover:text-gray-600 dark:text-white/40 dark:hover:bg-white/10 dark:hover:text-white/80 lg:hidden"
                aria-label="Close navigation"
                @click="emit('close')"
            >
                <X class="h-5 w-5" />
            </button>
        </div>

        <nav
            class="sidebar-scroll flex-1 px-3.5 space-y-1.5 mt-5 overflow-y-auto overflow-x-hidden"
        >
            <NuxtLink
                v-for="item in menus"
                :key="item.to"
                :to="item.to"
                class="w-full flex items-center gap-3 lg:justify-center lg:gap-0 lg:px-0 lg:group-hover:justify-start lg:group-hover:gap-3 lg:group-hover:px-[13px] px-[13px] py-3 rounded-xl text-sm font-medium transition-colors"
                :class="
                    isActive(item.to)
                        ? 'bg-primary-500 text-white shadow-sm'
                        : 'text-gray-400 hover:bg-gray-50 hover:text-gray-600 dark:text-white/40 dark:hover:bg-white/5 dark:hover:text-white/80'
                "
                @click="emit('close')"
            >
                <component
                    :is="item.icon"
                    v-if="item.icon"
                    class="w-[18px] h-[18px] shrink-0"
                />
                <span
                    class="flex-1 lg:flex-none lg:w-0 lg:group-hover:flex-1 lg:group-hover:w-auto text-left whitespace-nowrap overflow-hidden transition-opacity duration-150 delay-75 lg:opacity-0 lg:group-hover:opacity-100"
                    >{{ item.label }}</span
                >
            </NuxtLink>
        </nav>

        <div
            class="px-3.5 pb-6 pt-3 shrink-0 border-t border-gray-50 dark:border-white/10"
        >
            <button
                type="button"
                :disabled="loadingLogout"
                class="w-full flex items-center gap-3 px-[13px] py-3 rounded-xl text-sm font-medium text-gray-400 hover:bg-gray-50 hover:text-gray-600 dark:text-white/40 dark:hover:bg-white/5 dark:hover:text-white/80 disabled:opacity-50"
                @click="logout"
            >
                <LogOut class="w-[18px] h-[18px] shrink-0" />
                <span
                    class="whitespace-nowrap transition-opacity duration-150 delay-75 lg:opacity-0 lg:group-hover:opacity-100"
                >
                    {{ loadingLogout ? "Logging out..." : "Logout" }}
                </span>
            </button>
        </div>
    </aside>
</template>

<script setup lang="ts">
import logo from "assets/logo/logo.png";
import { ref, watch } from "vue";
import { useRoute } from "vue-router";
import { LogOut, X } from "lucide-vue-next";
import { authService } from "~/api/auth/AuthService";
import { useToast } from "~/composables/useToast";
import { resetAuth } from "~/composables/useAuthUser";

interface MenuItem {
    label: string;
    to: string;
    icon?: any;
}

withDefaults(
    defineProps<{
        open?: boolean;
        menus?: MenuItem[];
        homeLink?: string;
    }>(),
    {
        menus: () => [],
        homeLink: "/",
    },
);

const emit = defineEmits<{
    close: [];
}>();

const route = useRoute();
const { success } = useToast();

watch(
    () => route.path,
    () => emit("close"),
);

function isActive(to: string) {
    return route.path === to || route.path.startsWith(`${to}/`);
}

const loadingLogout = ref(false);

async function logout() {
    loadingLogout.value = true;

    // The server call is best-effort — if it fails (expired session, network
    // blip, stale CSRF token) the user must still be logged out locally and
    // sent to sign-in, never left stranded on the page they clicked
    // "Logout" from.
    try {
        const res = await authService.logout();
        success(res.message ?? "Logged out successfully.");
    } catch (err) {
        console.error(err);
    } finally {
        resetAuth();
        loadingLogout.value = false;
        window.location.href = "/auth/signin";
    }
}
</script>

<style scoped>
.sidebar-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.6) transparent;
}
.sidebar-scroll::-webkit-scrollbar {
    width: 5px;
}
.sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.sidebar-scroll::-webkit-scrollbar-thumb {
    background-color: rgba(148, 163, 184, 0.6);
    border-radius: 999px;
}
</style>
