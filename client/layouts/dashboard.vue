﻿<template>
    <div
        class="h-screen flex flex-col bg-[#EEF3FB] print:bg-white print:h-auto print:overflow-visible"
    >
        <div class="print:hidden">
            <DashboardHeader @open="isOpen = true" />
        </div>

        <div class="flex flex-1 min-h-0">
            <div class="print:hidden">
                <DynamicSidebar
                    :open="isOpen"
                    :logo="logoAmuma"
                    :authMenu="menus"
                    :user="user"
                    @close="isOpen = false"
                    :variant="2"
                />
            </div>

            <main class="flex-1 overflow-auto p-0 m-0">
                <div
                    class="pointer-events-none absolute inset-0 overflow-hidden print:hidden"
                    aria-hidden="true"
                >
                    <div
                        class="lg:flex hidden absolute -bottom-0 left-42 h-[520px] w-[520px] rounded-full bg-sky-300/25 blur-[140px]"
                    ></div>

                    <div
                        class="lg:flex hidden absolute -top-40 -left-42 h-[520px] w-[520px] rounded-full bg-sky-200/25 blur-[140px]"
                    ></div>
                    <div
                        class="lg:flex hidden absolute -top-40 left-1/2 -translate-x-1/2 h-[520px] w-[520px] rounded-full bg-sky-200/25 blur-[140px]"
                    ></div>
                    <div
                        class="lg:flex hidden absolute -top-90 -right-32 h-[520px] w-[520px] rounded-full bg-sky-200/25 blur-[150px]"
                    ></div>
                    <!-- <div
                        class="lg:flex hidden absolute top-1/3 left-1/2 h-[280px] w-[280px] -translate-x-1/2 rounded-full bg-cyan-200/20 blur-[100px]"
                    ></div> -->
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
<script setup lang="ts">
import logoAmuma from "~/assets/logo/logoAmuma.png";
import DynamicSidebar from "~/components/sections/DynamicSidebar.vue";
import DashboardHeader from "~/components/sections/DashboardHeader.vue";

import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";

import { authMenuList } from "~/config/authMenu";

const route = useRoute();
const user = useAuthUser();
const isOpen = ref(false);

const branchStore = useBranchStore();

onMounted(async () => {
    if (!branchStore.branches.length) {
        await branchStore.fetchBranches();
    }
    let uuid = branchStore.activeBranch?.uuid ?? branchStore.branches[0]?.uuid;

    if (!uuid) {
        const branch = branchStore.branches.find((branch) => branch?.uuid);
        uuid = branch?.uuid;
    }

    if (uuid && !route.path.startsWith("/app/branches/")) {
        await navigateTo(`/app/branches/${uuid}/dashboard`);
    }
});

const activeModules = computed(() => {
    const branch = branchStore.activeBranch;

    return (
        branch?.permissions
            ?.filter((p) => p.can_read)
            .map((p) => p.module_name) ?? []
    );
});

const menus = computed(() => {
    const modules = activeModules.value ?? [];
    const uuid = branchStore.routeUuid;
    const branch = branchStore.activeBranch;

    if (!branch?.agency?.is_verified || !branch?.is_verified) {
        return authMenuList
            .filter((item) => item.label === "Dashboard")
            .map((item) => ({
                label: item.label,
                to: uuid ? item.to.replace("[uuid]", uuid) : item.to,
                icon: item.icon,
            }));
    }

    // const branch = branchStore.activeBranch;
    //   const branchPlans =
    //         branch?.plan?.map((p) => p.plan_code) ?? [];
    return authMenuList
        .filter((item) => {
            if (!item.modules) return true;
            return item.modules.some((m) => modules.includes(m));

            // if (item.modules) {
            //     const hasModule = item.modules.some((m) => modules.includes(m));

            //     if (!hasModule) return false;
            // }

            // if (item.plan?.length) {
            //     const hasPlan = item.plan.some((plan) =>
            //         branchPlans.includes(plan)
            //     );

            //     if (!hasPlan) return false;
            // }
            // return true;
        })
        .map((item) => ({
            label: item.label,
            to: uuid ? item.to.replace("[uuid]", uuid) : item.to,
            icon: item.icon,
        }));
});
</script>
