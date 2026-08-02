<template>
    <!-- class="h-[90px] w-full bg-white border-b border-gray-200 flex items-center justify-between px-6 shrink-0" -->
    <header
        class="lg:h-[90px] h-[150px] lg:flex-none flex-col w-full bg-white border-b border-gray-200 flex items-center justify-between px-6 shrink-0"
    >
        <div class="flex justify-between items-center h-full w-full">
            <div class="flex justify-start items-center gap-4">
                <NuxtLink
                    to="/"
                    class="flex items-center pr-5 lg:border-r lg:border-muted"
                >
                    <img
                        :src="logo"
                        alt="AMUMA logo"
                        class="w-[50px] md:w-[50px] object-contain"
                    />
                </NuxtLink>

                <!-- class="items-center flex gap-2.5 cursor-pointer select-none" -->
                <div
                    v-if="isMounted"
                    class="items-center lg:flex hidden gap-2.5 cursor-pointer select-none"
                    @click="branchStore.openModal"
                >
                    <div
                        class="w-9 h-9 rounded-lg overflow-hidden bg-gray-100 shrink-0 flex items-center justify-center"
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
                            class="w-5 h-5 text-gray-400"
                        >
                            <path
                                d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01"
                            />
                        </svg>
                    </div>

                    <div class="flex flex-col leading-tight">
                        <span
                            class="text-[30px] font-bold text-gray-900 tracking-tight"
                        >
                            {{
                                branchStore.activeBranch?.name ||
                                "Select a branch"
                            }}
                        </span>
                        <span class="text-[13px] text-gray-500">
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
                        class="w-4 h-4 text-gray-400 ml-5"
                    >
                        <polyline points="6 9 12 15 18 9" />
                    </svg>
                </div>
            </div>
            <div class="flex gap-2 items-center">
                <div class="flex items-center gap-3">
                    <div v-if="!isMounted" class="flex items-center gap-3">
                        <div
                            class="w-9 h-9 bg-gray-200 rounded-full animate-pulse"
                        />
                        <div class="flex flex-col gap-2">
                            <div
                                class="w-24 h-3 bg-gray-200 rounded animate-pulse"
                            />
                            <div
                                class="w-14 h-3 bg-gray-200 rounded animate-pulse"
                            />
                        </div>
                    </div>

                    <div v-else class="flex items-center gap-3">
                        <Notification />
                        <NavbarProfileDropdown v-if="user" :user="user" />
                    </div>
                </div>
                <button
                    @click="$emit('open')"
                    class="flex lg:hidden items-center justify-center w-10 h-10 rounded-lg hover:bg-gray-100"
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

        <div
            v-if="isMounted"
            class="flex lg:hidden w-full h-full gap-2.5 mt-3 cursor-pointer"
            @click="branchStore.openModal"
        >
            <div
                class="w-9 h-9 rounded-lg bg-gray-100 flex items-center mt-2 justify-center overflow-hidden"
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
                    class="w-5 h-5 text-gray-400"
                >
                    <path
                        d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01"
                    />
                </svg>
            </div>

            <div class="flex flex-col leading-tight">
                <span class="text-lg font-bold text-gray-900">
                    {{ branchStore.activeBranch?.name || "Select a branch" }}
                </span>
                <span class="text-xs text-gray-500">
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
                class="w-4 h-4 text-gray-400 ml-5 mb-2 self-center"
            >
                <polyline points="6 9 12 15 18 9" />
            </svg>
        </div>
    </header>
    <Teleport to="body">
        <div
            v-if="branchStore.showModal"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
            @click.self="branchStore.closeModal"
        >
            <div
                class="bg-white rounded-2xl shadow-2xl w-1/3 overflow-hidden animate-fade-in"
            >
                <div
                    class="flex items-start justify-between px-5 py-4 border-b border-gray-100"
                >
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Select a branch
                        </h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Choose which branch you want to manage
                        </p>
                    </div>

                    <button
                        v-if="branchStore.activeBranch"
                        class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-gray-100 transition"
                        @click="branchStore.showModal = false"
                    >
                        ✕
                    </button>
                </div>

                <div class="max-h-96 overflow-y-auto p-2 space-y-2">
                    <button
                        v-for="branch in branchStore.branches"
                        :key="branch.uuid"
                        class="w-full flex items-center gap-3 p-3 rounded-xl text-left border transition hover:shadow-sm"
                        :class="
                            branch.uuid === branchStore.activeBranch?.uuid
                                ? 'border-blue-300 bg-blue-50'
                                : 'border-gray-100 hover:bg-gray-50'
                        "
                        @click="branchStore.selectBranch(branch)"
                    >
                        <div
                            class="w-12 h-12 rounded-xl overflow-hidden bg-gray-100 flex items-center justify-center shrink-0"
                        >
                            <img
                                v-if="branch.image"
                                :src="getBranchImage(branch.image)"
                                class="w-full h-full object-cover"
                            />
                            <span v-else class="text-xs text-gray-400"
                                >No Img</span
                            >
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="text-lg font-semibold truncate">
                                    {{ branch.name }}
                                </p>

                                <span
                                    v-if="
                                        branch.uuid ===
                                        branchStore.activeBranch?.uuid
                                    "
                                    class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-600"
                                >
                                    Active
                                </span>
                            </div>

                            <p
                                class="text-xs text-gray-500 truncate mt-0.5 flex gap-1"
                            >
                                <Location /> {{ branch.location?.address }}
                            </p>

                            <div class="flex justify-between items-center">
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <span
                                        v-for="plan in branch.plan"
                                        :key="plan.plan_code"
                                        class="text-[12px] px-2 py-0.5 rounded-full font-medium border"
                                        :class="{
                                            'bg-blue-50 text-primary border-blue-200':
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
                                <div class="flex flex-wrap gap-1 mt-2">
                                    <span
                                        class="text-[12px] px-2 py-0.5 rounded-full font-medium border"
                                        :class="
                                            roleMeta[branch?.role_name ?? '']
                                                ?.class ||
                                            'bg-gray-50 text-gray-600 border-gray-200'
                                        "
                                    >
                                        {{
                                            formatRole(branch?.role_name ?? "")
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>
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
