<template>
    <ClientOnly>
        <BaseDropdownMenu align="right" width="w-56">
            <template #trigger="{ toggle, open }">
                <button
                    @click="toggle"
                    class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl transition-colors focus:outline-none"
                    :class="
                        scrolled || navTheme !== 'dark'
                            ? 'hover:bg-primary-50'
                            : 'hover:bg-light/10'
                    "
                >
                    <div class="relative">
                        <img
                            :src="user.avatar"
                            class="w-9 h-9 rounded-full border-2 border-white shadow-sm object-cover"
                            alt="Profile"
                        />
                        <span
                            class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-400 border-2 border-white rounded-full"
                        />
                    </div>

                    <div
                        class="hidden md:flex flex-col items-start leading-tight"
                    >
                        <span
                            class="text-sm font-medium transition-colors duration-300"
                            :class="
                                scrolled || navTheme !== 'dark'
                                    ? 'text-secondary/80'
                                    : 'text-light'
                            "
                        >
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                        <span
                            class="text-xs transition-colors duration-300"
                            :class="
                                scrolled || navTheme !== 'dark'
                                    ? 'text-muted'
                                    : 'text-light/70'
                            "
                        >
                            View profile
                        </span>
                    </div>

                    <ChevronIcon
                        :isOpen="open"
                        class="block w-4 h-4 transition-colors duration-300"
                        :class="
                            scrolled || navTheme !== 'dark'
                                ? 'text-muted'
                                : 'text-light/80'
                        "
                    />
                </button>
            </template>

            <template #default="{ close }">
                <div class="bg-secondary text-white rounded-xl overflow-hidden">
                    <div
                        class="px-4 py-3 border-b border-white/10 flex items-center gap-3"
                    >
                        <div class="relative">
                            <img
                                :src="user.avatar"
                                class="w-9 h-9 rounded-full border-2 border-white shadow-sm object-cover"
                                alt="Profile"
                            />
                            <span
                                class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-400 border border-white rounded-full"
                            />
                        </div>
                        <div class="flex flex-col min-w-0">
                            <p class="text-sm font-medium truncate">
                                {{ user.first_name }} {{ user.last_name }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">
                                {{ user.email }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <DropdownItem
                            v-for="item in visibleMenuItems"
                            :key="item.label"
                            :icon="item.icon"
                            :label="item.label"
                            @click="
                                async () => {
                                    await handleMenuClick(item);
                                    close();
                                }
                            "
                        />
                    </div>

                    <div class="py-1 border-t border-white/10">
                        <ActionButton
                            variant="danger"
                            :loading="loggingOut"
                            extraClass="w-full justify-start bg-transparent hover:bg-white/10 text-white border-none ml-1.5"
                            @click="
                                () => {
                                    logout();
                                    close();
                                }
                            "
                        >
                            <span class="flex items-center gap-2">
                                Log out
                            </span>
                        </ActionButton>
                    </div>
                </div>
            </template>
        </BaseDropdownMenu>
    </ClientOnly>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { navigateTo } from "#imports";
import BaseDropdownMenu from "../ui/BaseDropdownMenu.vue";
import DropdownItem from "../ui/DropdownItem.vue";
import ChevronIcon from "../icons/dropdown.vue";
import { authService } from "~/api/auth/AuthService.js";
import { resetAuth } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";
import ActionButton from "./ActionButton.vue";
import {
    handleMenuClick,
    profileMenuDropDownList,
} from "~/config/profileMenu.js";
import type { User } from "~/types/auth.js";

const props = withDefaults(
    defineProps<{
        user: User;
        scrolled?: boolean;
        navTheme?: any;
    }>(),
    {
        scrolled: true,
        navTheme: "light",
    },
);

const { success, error } = useToast();
const loggingOut = ref(false);

const visibleMenuItems = computed(() =>
    profileMenuDropDownList.filter((item) => {
        if (!item.types) return true;
        return item.types.some((type) => props.user[type as keyof User]);
    }),
);

const logout = async () => {
    loggingOut.value = true;

    try {
        const res = await authService.logout();
        resetAuth();
        success(res.message);
        await navigateTo("/auth/signin");
    } catch (err: any) {
        console.error(err);
        error(err);
    } finally {
        loggingOut.value = false;
    }
};
</script>
