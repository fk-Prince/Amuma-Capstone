<template>
    <ClientOnly>
        <template #fallback>
            <div />
        </template>

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
                <aside
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
                            :key="item.id || item.to"
                            :to="item.to"
                            :class="navClass(item.to)"
                            @click="$emit('close')"
                        >
                            <component
                                v-if="item.icon"
                                :is="item.icon"
                                class="w-5 h-5 shrink-0"
                            />
                            <span v-if="!collapsed" class="truncate">{{
                                item.label
                            }}</span>
                        </NuxtLink>
                    </nav>

                    <div class="border-t px-4 pb-6 pt-3">
                        <ClientOnly>
                            <template #fallback>
                                <div class="flex items-center gap-3 mb-3">
                                    <div
                                        class="w-9 h-9 rounded-full bg-gray-200 animate-pulse"
                                    />
                                    <div class="flex-1 space-y-2">
                                        <div
                                            class="h-3 w-24 bg-gray-200 rounded animate-pulse"
                                        />
                                        <div
                                            class="h-3 w-32 bg-gray-200 rounded animate-pulse"
                                        />
                                    </div>
                                </div>

                                <div
                                    class="h-9 w-full bg-gray-200 rounded animate-pulse"
                                />
                            </template>

                            <template v-if="user">
                                <div class="flex items-center gap-3 mb-3">
                                    <img
                                        :src="user.avatar"
                                        class="w-9 h-9 rounded-full"
                                    />

                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-sm">
                                            {{ user.first_name }}
                                            {{ user.last_name }}
                                        </p>

                                        <p
                                            class="truncate text-xs text-gray-400"
                                        >
                                            {{ user.email }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="flex flex-wrap gap-1 my-2"
                                    v-for="branches in branchStore.branches"
                                >
                                    <span
                                        v-for="role in branches.roles"
                                        :key="role.role_type"
                                        class="text-[12px] px-2 py-0.5 rounded-full font-medium border"
                                        :class="
                                            roleMeta[role.role_type]?.class ||
                                            'bg-gray-50 text-gray-600 border-gray-200'
                                        "
                                    >
                                        {{ formatRole(role.role_type) }}
                                    </span>
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
                                    <NuxtLink to="/auth/signin" class="btn">
                                        Sign in
                                    </NuxtLink>

                                    <NuxtLink
                                        to="/auth/signup"
                                        class="btn-secondary"
                                    >
                                        Sign up
                                    </NuxtLink>
                                </div>
                            </template>
                        </ClientOnly>
                    </div>
                </aside>
            </Transition>
        </Teleport>
    </ClientOnly>

    <!-- <aside
        v-if="variant === 2"
        class="hidden lg:flex h-full w-72 bg-white shadow-2xl flex-col shrink-0"
    >
        <div class="flex flex-col px-4 py-6 gap-1 flex-1 overflow-y-auto">
            <ClientOnly>
                <template #fallback>
                    <div
                        v-for="n in 10"
                        :key="n"
                        class="h-10 rounded-lg bg-gray-200 animate-pulse mb-1"
                    />
                </template>

                <template v-for="item in navItems" :key="item.id || item.to">
                    <DropdownDivider v-if="item.divider" />

                    <NuxtLink v-else :to="item.to" :class="navClass(item.to)">
                        <component
                            v-if="item.icon"
                            :is="item.icon"
                            class="w-5 h-5"
                        />
                        {{ item.label }}
                    </NuxtLink>
                </template>
            </ClientOnly>
        </div>

        <div class="border-t px-4 pb-6 pt-3">
            <ClientOnly>
                <template #fallback>
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-9 h-9 rounded-full bg-gray-200 animate-pulse"
                        />
                        <div class="flex-1 space-y-2">
                            <div
                                class="h-3 w-24 bg-gray-200 rounded animate-pulse"
                            />
                            <div
                                class="h-3 w-32 bg-gray-200 rounded animate-pulse"
                            />
                        </div>
                    </div>

                    <div class="h-9 w-full bg-gray-200 rounded animate-pulse" />
                </template>

                <template v-if="user">
                    <div class="flex items-center gap-3 mb-3">
                        <img :src="avatarSrc" class="w-9 h-9 rounded-full" />

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
                        <NuxtLink to="/auth/signin" class="btn">
                            Sign in
                        </NuxtLink>

                        <NuxtLink to="/auth/signup" class="btn-secondary">
                            Sign up
                        </NuxtLink>
                    </div>
                </template>
            </ClientOnly>
        </div>
    </aside> -->
    <aside
        v-if="variant === 2"
        :class="[
            'hidden lg:flex h-full relative bg-white shadow-2xl flex-col shrink-0 transition-all duration-300',
            collapsed ? 'w-20' : 'w-72',
        ]"
    >
        <button
            @click="collapsed = !collapsed"
            class="absolute bg-primary w-8 h-8 flex items-center justify-center rounded-full mx-auto"
            :class="
                collapsed
                    ? '-right-[21%] top-[2%] pl-0.5'
                    : '-right-[6%] top-[2%] pr-0.5'
            "
        >
            <component
                :is="collapsed ? ChevronRight : ChevronLeft"
                class="text-white"
            />
        </button>
        <div class="flex flex-col px-4 py-6 gap-1 flex-1 overflow-y-auto">
            <ClientOnly>
                <template #fallback>
                    <div
                        v-for="n in 10"
                        :key="n"
                        class="h-10 rounded-lg bg-gray-200 animate-pulse mb-1"
                    />
                </template>

                <template v-for="item in navItems" :key="item.id || item.to">
                    <DropdownDivider v-if="item.divider" />

                    <NuxtLink
                        v-else
                        :to="item.to"
                        :class="navClass(item.to)"
                        :title="collapsed ? item.label : undefined"
                    >
                        <component
                            v-if="item.icon"
                            :is="item.icon"
                            class="w-5 h-5 shrink-0"
                        />
                        <span v-if="!collapsed" class="truncate">{{
                            item.label
                        }}</span>
                    </NuxtLink>
                </template>
            </ClientOnly>
        </div>

        <div class="border-t px-4 pb-6 pt-3">
            <ClientOnly>
                <template #fallback>
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-9 h-9 rounded-full bg-gray-200 animate-pulse"
                        />
                        <div v-if="!collapsed" class="flex-1 space-y-2">
                            <div
                                class="h-3 w-24 bg-gray-200 rounded animate-pulse"
                            />
                            <div
                                class="h-3 w-32 bg-gray-200 rounded animate-pulse"
                            />
                        </div>
                    </div>
                    <div
                        v-if="!collapsed"
                        class="h-9 w-full bg-gray-200 rounded animate-pulse"
                    />
                </template>

                <template v-if="user">
                    <div
                        class="flex items-center gap-3 mb-3"
                        :class="collapsed ? 'justify-center' : ''"
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
                        <div v-if="!collapsed" class="min-w-0">
                            <p class="truncate font-medium text-sm">
                                {{ user.first_name }} {{ user.last_name }}
                            </p>
                            <p class="truncate text-xs text-gray-400">
                                {{ user.email }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex flex-wrap gap-1 my-2"
                        v-for="branches in branchStore.branches"
                    >
                        <span
                            v-for="role in branches.roles"
                            :key="role.role_type"
                            class="text-[12px] px-2 py-0.5 rounded-full font-medium border"
                            :class="
                                roleMeta[role.role_type]?.class ||
                                'bg-gray-50 text-gray-600 border-gray-200'
                            "
                        >
                            {{ formatRole(role.role_type) }}
                        </span>
                    </div>

                    <button
                        @click="logout"
                        class="w-full flex items-center gap-2 text-red-500 hover:bg-red-50 px-3 py-2 rounded-lg"
                        :title="collapsed ? 'Log out' : undefined"
                    >
                        <LogOut class="w-5 h-5 shrink-0" />
                        <span v-if="!collapsed" class="truncate">Log out</span>
                    </button>
                </template>

                <template v-else>
                    <div class="flex flex-col gap-2">
                        <NuxtLink to="/auth/signin" class="btn">
                            <span v-if="!collapsed">Sign in</span>
                            <span v-else>→</span>
                        </NuxtLink>
                        <NuxtLink
                            to="/auth/signup"
                            class="btn-secondary"
                            v-if="!collapsed"
                        >
                            Sign up
                        </NuxtLink>
                    </div>
                </template>
            </ClientOnly>
        </div>
    </aside>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import { authService } from "~/api/auth/AuthService";
import { useToast } from "~/composables/useToast";
import { resetAuth } from "~/composables/useAuthUser";
import { navigateTo } from "#imports";
import DropdownDivider from "../ui/DropdownDivider.vue";
import { ChevronLeft, ChevronRight, LogOut } from "lucide-vue-next";
import { useBranchStore } from "~/stores/branch";
const branchStore = useBranchStore();

const route = useRoute();
const { success, error } = useToast();
const collapsed = ref(false);
const props = withDefaults(
    defineProps<{
        open: boolean;
        logo: string;
        authMenu?: Array<{
            id?: string;
            label: string;
            to: string;
            icon?: any;
            divider?: boolean;
        }>;
        user?: {
            first_name: string;
            last_name: string;
            email: string;
            avatar: any;
        } | null;
        variant?: 1 | 2;
    }>(),
    {
        variant: 1,
    },
);

defineEmits<{
    close: [];
    logout: [];
}>();

const navItems = computed(() => props.authMenu ?? []);

function isActive(to: string) {
    return route.path === to || route.path.startsWith(to + "/");
}

function navClass(to: string) {
    return [
        "px-3 py-2.5 rounded-lg transition flex gap-2 items-center",
        collapsed.value ? "lg:justify-center" : "",
        isActive(to)
            ? "bg-primary hover:bg-primary/70 font-medium text-white hover:text-black stroke-white"
            : "text-gray-700 hover:bg-gray-200 stroke-black",
    ];
}

const logout = async () => {
    try {
        const res = await authService.logout();
        success(res.message);
        resetAuth();
        await navigateTo("/auth/signin");
    } catch (err: any) {
        error(err);
    }
};
</script>
