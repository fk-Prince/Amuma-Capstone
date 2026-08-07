<template>
    <header
        class="lg:h-[90px] h-[150px] lg:flex-none flex-col w-full bg-white border-b border-primary-100/80 shadow-[0_0_40px_rgba(10,40,87,0.06)] flex items-center justify-between px-6 shrink-0"
    >
        <div class="flex justify-between items-center h-full w-full">
            <div class="flex justify-start items-center gap-4">
                <NuxtLink
                    to="/"
                    class="flex items-center pr-5 lg:border-r lg:border-primary-100/80"
                >
                    <img
                        :src="logo"
                        alt="AMUMA logo"
                        class="w-[50px] md:w-[50px] object-contain"
                    />
                </NuxtLink>

                <div
                    v-if="isMounted"
                    class="items-center lg:flex hidden gap-2.5 cursor-pointer select-none rounded-lg px-2 py-1.5 -ml-2 transition-colors duration-200 hover:bg-primary-50"
                    @click="branchStore.openModal"
                >
                    <div
                        class="w-9 h-9 rounded-lg overflow-hidden bg-primary-50 shrink-0 flex items-center justify-center ring-2 ring-primary-100"
                    >
                        <img
                            v-if="branchStore.activeBranch?.image"
                            :src="
                                getBranchImage(branchStore.activeBranch.image)
                            "
                            :alt="branchStore.activeBranch.name"
                            class="w-full h-full object-cover"
                        />
                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            class="w-5 h-5 text-primary-400"
                        >
                            <path
                                d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01"
                            />
                        </svg>
                    </div>

                    <div class="flex flex-col leading-tight">
                        <span
                            class="text-[30px] font-bold text-primary-900 tracking-tight"
                        >
                            {{
                                branchStore.activeBranch?.name ||
                                "Select a branch"
                            }}
                        </span>
                        <span class="text-[13px] text-muted">
                            {{
                                branchStore.activeBranch?.location?.address ||
                                "No branch selected"
                            }}
                        </span>
                    </div>

                    <svg
                        v-if="branchStore.hasMultipleBranches"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        class="w-4 h-4 text-primary-400 ml-5"
                    >
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </div>
            </div>
            <div class="flex gap-2 items-center">
                <div class="flex items-center gap-3">
                    <div v-if="!isMounted" class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full skeleton-shimmer" />
                        <div class="flex flex-col gap-2">
                            <div class="w-24 h-3 rounded-md skeleton-shimmer" />
                            <div class="w-14 h-3 rounded-md skeleton-shimmer" />
                        </div>
                    </div>

                    <div v-else class="flex items-center gap-3">
                        <Notification />
                        <NavbarProfileDropdown v-if="user" :user="user" />
                    </div>
                </div>
                <button
                    @click="$emit('open')"
                    class="flex lg:hidden items-center justify-center w-10 h-10 rounded-lg text-primary-600 transition-colors duration-200 hover:bg-primary-50"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        class="w-6 h-6"
                    >
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
            </div>
        </div>

        <header class="flex items-center justify-between w-full">
            <div
                v-if="isMounted"
                class="flex lg:hidden w-fit h-full gap-2.5 mb-3 cursor-pointer rounded-lg px-1 transition-colors duration-200 hover:bg-primary-50"
                @click="branchStore.openModal"
            >
                <div
                    class="w-9 h-9 rounded-lg bg-primary-50 flex items-center mt-2 justify-center overflow-hidden ring-2 ring-primary-100"
                >
                    <img
                        v-if="branchStore.activeBranch?.image"
                        :src="getBranchImage(branchStore.activeBranch.image)"
                        :alt="branchStore.activeBranch.name"
                        class="w-full h-full object-cover"
                    />
                    <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                        class="w-5 h-5 text-primary-400"
                    >
                        <path
                            d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01"
                        />
                    </svg>
                </div>

                <div class="flex flex-col leading-tight">
                    <span class="text-lg font-bold text-primary-900">
                        {{
                            branchStore.activeBranch?.name || "Select a branch"
                        }}
                    </span>
                    <span class="text-xs text-muted">
                        {{
                            branchStore.activeBranch?.location?.address ||
                            "No branch selected"
                        }}
                    </span>
                </div>

                <svg
                    v-if="branchStore.hasMultipleBranches"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="w-4 h-4 text-primary-400 ml-5 mb-2 self-center"
                >
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </div>
        </header>
    </header>

    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="branchStore.showModal"
                class="fixed inset-0 bg-primary-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
                @click.self="branchStore.closeModal"
            >
                <Transition
                    appear
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="branchStore.showModal"
                        class="bg-white rounded-2xl shadow-[0_0_40px_rgba(10,40,87,0.15)] ring-1 ring-primary-100/60 w-full max-w-md overflow-hidden"
                        role="dialog"
                        aria-modal="true"
                        aria-label="Select a branch"
                    >
                        <div
                            class="flex items-start justify-between px-5 py-4 border-b border-primary-100/80 bg-primary-50/40"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        class="w-5 h-5"
                                    >
                                        <path
                                            d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h2
                                        class="text-base font-semibold leading-tight text-primary-900"
                                    >
                                        Select a branch
                                    </h2>
                                    <p class="text-xs text-muted mt-0.5">
                                        Choose which branch you want to manage
                                    </p>
                                </div>
                            </div>

                            <button
                                v-if="branchStore.activeBranch"
                                aria-label="Close dialog"
                                class="shrink-0 w-8 h-8 flex items-center justify-center rounded-full text-primary-400 transition-colors duration-200 hover:bg-primary-100 hover:text-primary-700"
                                @click="branchStore.showModal = false"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="w-4 h-4"
                                >
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                        </div>

                        <div
                            v-if="branchStore.branches?.length"
                            class="max-h-[26rem] overflow-y-auto p-2.5 space-y-1.5 branch-scroll"
                        >
                            <button
                                v-for="branch in branchStore.branches"
                                :key="branch.uuid"
                                class="group w-full flex items-center gap-3 p-2.5 rounded-xl text-left border transition-all duration-200"
                                :class="
                                    branch.uuid ===
                                    branchStore.activeBranch?.uuid
                                        ? 'border-primary-300 bg-primary-50 ring-1 ring-primary-200'
                                        : 'border-transparent hover:border-primary-100 hover:bg-primary-50/70'
                                "
                                @click="branchStore.selectBranch(branch)"
                            >
                                <div
                                    class="relative w-11 h-11 rounded-xl overflow-hidden bg-primary-50 flex items-center justify-center shrink-0 ring-2 ring-primary-100 transition-transform duration-200 group-hover:scale-[1.03]"
                                >
                                    <img
                                        v-if="branch.image"
                                        :src="getBranchImage(branch.image)"
                                        class="w-full h-full object-cover"
                                    />
                                    <svg
                                        v-else
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        class="w-5 h-5 text-primary-400"
                                    >
                                        <path
                                            d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01"
                                        />
                                    </svg>

                                    <span
                                        v-if="
                                            branch.uuid ===
                                            branchStore.activeBranch?.uuid
                                        "
                                        class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-400 border-2 border-white rounded-full"
                                    />
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <p
                                            class="text-[14px] font-semibold text-primary-900 truncate"
                                        >
                                            {{ branch.name }}
                                        </p>

                                        <span
                                            v-if="
                                                branch.uuid ===
                                                branchStore.activeBranch?.uuid
                                            "
                                            class="shrink-0 text-[10px] px-2 py-0.5 rounded-full bg-primary-100 text-primary-600 font-semibold"
                                        >
                                            Active
                                        </span>
                                    </div>

                                    <p
                                        class="text-[12px] text-muted truncate mt-0.5 flex items-center gap-1"
                                    >
                                        <Location class="shrink-0 w-3 h-3" />
                                        <span class="truncate">{{
                                            branch.location?.address
                                        }}</span>
                                    </p>

                                    <div
                                        class="flex justify-between items-center mt-1.5"
                                    >
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-for="plan in branch.plan"
                                                :key="plan.plan_code"
                                                class="text-[11px] px-2 py-0.5 rounded-full font-medium border"
                                                :class="{
                                                    'bg-primary-50 text-primary border-primary-200':
                                                        plan.plan_code === 'A',
                                                    'bg-green-50 text-accent border-green-200':
                                                        plan.plan_code === 'B',
                                                    'bg-orange-50 text-secondary border-orange-200':
                                                        plan.plan_code === 'C',
                                                }"
                                            >
                                                {{ plan.name }}
                                            </span>
                                        </div>

                                        <span
                                            class="text-[11px] px-2 py-0.5 rounded-full font-medium border"
                                            :class="
                                                roleMeta[
                                                    branch?.role_name ?? ''
                                                ]?.class ||
                                                'bg-primary-50 text-primary-600 border-primary-200'
                                            "
                                        >
                                            {{
                                                formatRole(
                                                    branch?.role_name ?? "",
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="w-4 h-4 shrink-0 text-primary-300 opacity-0 -translate-x-1 transition-all duration-200 group-hover:opacity-100 group-hover:translate-x-0"
                                >
                                    <polyline points="9 18 15 12 9 6" />
                                </svg>
                            </button>
                        </div>

                        <div
                            v-else
                            class="flex flex-col items-center justify-center gap-2 py-10 px-6 text-center"
                        >
                            <div
                                class="w-11 h-11 rounded-xl bg-primary-50 flex items-center justify-center text-primary-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    class="w-5 h-5"
                                >
                                    <path
                                        d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01"
                                    />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-primary-900">
                                No branches yet
                            </p>
                            <p class="text-xs text-muted max-w-[220px]">
                                You don't have access to any branches at the
                                moment.
                            </p>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import logo from "assets/logo/logo.png";
import { computed, onMounted, ref } from "vue";
import Notification from "../ui/Notification.vue";
import NavbarProfileDropdown from "../ui/NavbarProfileDropdown.vue";
import Location from "../icons/location.vue";

import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";
import { formatRole, roleMeta } from "~/utils/user";
import { getBranchImage } from "~/types/branch.js";

const user = useAuthUser();

defineEmits<{ open: [] }>();

const branchStore = useBranchStore();

const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
});
</script>

<style scoped>
.skeleton-shimmer {
    background: linear-gradient(
        90deg,
        theme("colors.primary.50") 25%,
        theme("colors.primary.100") 50%,
        theme("colors.primary.50") 75%
    );
    background-size: 200% 100%;
    animation: shimmer 1.4s ease-in-out infinite;
}

@keyframes shimmer {
    0% {
        background-position: 200% 0;
    }
    100% {
        background-position: -200% 0;
    }
}

.branch-scroll {
    scrollbar-width: thin;
    scrollbar-color: theme("colors.primary.300") transparent;
}

.branch-scroll::-webkit-scrollbar {
    width: 5px;
}

.branch-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.branch-scroll::-webkit-scrollbar-thumb {
    background-color: theme("colors.primary.300");
    border-radius: 999px;
}

.branch-scroll::-webkit-scrollbar-thumb:hover {
    background-color: theme("colors.primary.500");
}
</style>
