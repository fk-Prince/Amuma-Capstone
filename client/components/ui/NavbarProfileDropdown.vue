<template>
    <ClientOnly>
        <BaseDropdownMenu align="right" width="w-56">
            <template #trigger="{ toggle, open }">
                <button
                    @click="toggle"
                    class="flex items-center gap-2.5 px-2 py-1.5 rounded-xl hover:bg-gray-100 transition-colors focus:outline-none"
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
                        <span class="text-sm font-medium text-gray-800">
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                        <span class="text-xs text-gray-400">View profile</span>
                    </div>

                    <ChevronIcon
                        :isOpen="open"
                        class="hidden md:block w-4 h-4 text-gray-400"
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
                            v-for="item in profileMenuDropDownList"
                            v-show="!item.roles || hasRole(...item.roles)"
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
                        <DropdownItem
                            icon="logout"
                            label="Log out"
                            @click="
                                () => {
                                    logout();
                                    close();
                                }
                            "
                        />
                    </div>
                </div>
            </template>
        </BaseDropdownMenu>
    </ClientOnly>
</template>

<script setup lang="ts">
import { navigateTo } from "#imports";
import BaseDropdownMenu from "../ui/BaseDropdownMenu.vue";
import DropdownDivider from "../ui/DropdownDivider.vue";
import DropdownItem from "../ui/DropdownItem.vue";
import ChevronIcon from "../icons/dropdown.vue";
import { authService } from "~/api/auth/AuthService.js";
import { resetAuth } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";
import { usePermissions } from "~/composables/usePermission";
import {
    handleMenuClick,
    profileMenuDropDownList,
} from "~/config/profileMenu.js";
import type { User } from "~/types/auth.js";

const { hasRole } = usePermissions();

const props = defineProps<{
    user: User;
}>();

const { success, error } = useToast();

const logout = async () => {
    try {
        const res = await authService.logout();
        await navigateTo("/auth/signin");
        resetAuth();
        success(res.message);
    } catch (err: any) {
        console.error(err);
        error(err);
    }
};
</script>
