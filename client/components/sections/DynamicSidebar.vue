<template>
    <ClientOnly>
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-300 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="open"
                    class="fixed inset-0 z-[60] lg:hidden bg-black/50 backdrop-blur-sm"
                    @click="$emit('close')"
                />
            </Transition>

            <Transition
                enter-active-class="transition-transform duration-300 ease-out"
                enter-from-class="-translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition-transform duration-300 ease-in"
                leave-from-class="translate-x-0"
                leave-to-class="-translate-x-full"
            >
                <div
                    v-if="open"
                    class="fixed left-0 top-0 h-full w-72 bg-white shadow-2xl z-[70] flex flex-col lg:hidden"
                >
                    <div
                        class="flex items-center justify-between px-5 h-[90px] border-b"
                    >
                        <NuxtLink to="/" @click="$emit('close')">
                            <img :src="logo" class="w-[150px] object-contain" />
                        </NuxtLink>
                        <button
                            @click="$emit('close')"
                            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100"
                        >
                            ✕
                        </button>
                    </div>

                    <nav
                        class="flex flex-col px-4 py-6 gap-1 flex-1 overflow-y-auto"
                    >
                        <NuxtLink
                            v-for="item in navItems"
                            :key="item.to"
                            :to="item.to"
                            class="px-3 py-2.5 rounded-lg hover:bg-gray-50"
                            @click="$emit('close')"
                        >
                            {{ item.label }}
                        </NuxtLink>
                    </nav>

                    <div class="border-t px-4 pb-6 pt-3">
                        <template v-if="user">
                            <div class="flex items-center gap-3 mb-3">
                                <img
                                    :src="avatarSrc"
                                    class="w-9 h-9 rounded-full"
                                />
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-sm">
                                        {{ user.first_name }}
                                        {{ user.last_name }}
                                    </p>
                                    <p class="truncate text-xs text-gray-400">
                                        {{ user.email }}
                                    </p>
                                </div>
                            </div>
                            <button
                                @click="logout"
                                class="w-full text-left text-red-500 hover:bg-red-50 px-3 py-2 rounded-lg"
                            >
                                Log out
                            </button>
                        </template>
                        <template v-else>
                            <div class="flex flex-col gap-2">
                                <NuxtLink
                                    to="/auth/signin"
                                    class="btn"
                                    @click="$emit('close')"
                                    >Sign in</NuxtLink
                                >
                                <NuxtLink
                                    to="/auth/signup"
                                    class="btn-secondary"
                                    @click="$emit('close')"
                                    >Sign up</NuxtLink
                                >
                            </div>
                        </template>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <div
            v-if="variant === 2"
            class="hidden lg:flex h-full w-72 bg-white shadow-2xl flex-col shrink-0"
        >
            <nav class="flex flex-col px-4 py-6 gap-1 flex-1 overflow-y-auto">
                <NuxtLink
                    v-for="item in navItems"
                    :key="item.to"
                    :to="item.to"
                    class="px-3 py-2.5 rounded-lg hover:bg-gray-50"
                >
                    {{ item.label }}
                </NuxtLink>
            </nav>

            <div class="border-t px-4 pb-6 pt-3">
                <template v-if="user">
                    <div class="flex items-center gap-3 mb-3">
                        <img :src="avatarSrc" class="w-9 h-9 rounded-full" />
                        <div class="min-w-0">
                            <p class="truncate font-medium text-sm">
                                {{ user.first_name }} {{ user.last_name }}
                            </p>
                            <p class="truncate text-xs text-gray-400">
                                {{ user.email }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="logout"
                        class="w-full text-left text-red-500 hover:bg-red-50 px-3 py-2 rounded-lg"
                    >
                        Log out
                    </button>
                </template>
                <template v-else>
                    <div class="flex flex-col gap-2">
                        <NuxtLink to="/auth/signin" class="btn"
                            >Sign in</NuxtLink
                        >
                        <NuxtLink to="/auth/signup" class="btn-secondary"
                            >Sign up</NuxtLink
                        >
                    </div>
                </template>
            </div>
        </div>
    </ClientOnly>
</template>

<script setup lang="ts">
import { authService } from "~/api/auth/AuthService";
import { useToast } from "~/composables/useToast";
import { resetAuth } from "~/composables/useAuthUser";
import { navigateTo } from "#imports";

const { success, error } = useToast();

const props = withDefaults(
    defineProps<{
        open: boolean;
        logo: string;
        navItems: { label: string; to: string }[];
        user?: { first_name: string; last_name: string; email: string } | null;
        avatarSrc: string;
        variant?: 1 | 2;
    }>(),
    {
        variant: 1,
    },
);

const emit = defineEmits<{
    close: [];
    logout: [];
}>();

const logout = async () => {
    try {
        const res = await authService.logout();
        success(res.message);
        resetAuth();
        emit("logout");
        await navigateTo("/auth/signin");
    } catch (err: any) {
        console.error(err);
        error(err);
    }
};
</script>
