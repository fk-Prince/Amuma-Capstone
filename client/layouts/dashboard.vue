﻿<template>
    <div
        class="h-[100dvh] flex bg-[#EEF3FB] dark:bg-surface overflow-hidden print:h-auto print:overflow-visible"
    >
        <div class="print:hidden">
            <DashboardSidebar
                :open="isOpen"
                :menus="menus"
                :home-link="homeLink"
                @close="isOpen = false"
            />
        </div>

        <div class="flex-1 flex flex-col min-w-0 h-full">
            <div class="print:hidden">
                <DashboardHeader @open="isOpen = true" />
            </div>

            <main
                class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden relative"
            >
                <div
                    class="pointer-events-none absolute inset-0 overflow-hidden print:hidden"
                    aria-hidden="true"
                >
                    <div
                        class="lg:flex hidden absolute -bottom-0 left-42 h-[520px] w-[520px] rounded-full bg-sky-300/25 dark:bg-primary-500/15 blur-[140px]"
                    ></div>

                    <div
                        class="lg:flex hidden absolute -top-40 -left-42 h-[520px] w-[520px] rounded-full bg-sky-200/25 dark:bg-primary-500/10 blur-[140px]"
                    ></div>
                    <div
                        class="lg:flex hidden absolute -top-40 left-1/2 -translate-x-1/2 h-[520px] w-[520px] rounded-full bg-sky-200/25 dark:bg-primary-500/10 blur-[140px]"
                    ></div>
                    <div
                        class="lg:flex hidden absolute -top-90 -right-32 h-[520px] w-[520px] rounded-full bg-sky-200/25 dark:bg-primary-500/10 blur-[150px]"
                    ></div>
                </div>

                <div class="relative min-h-full flex flex-col">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
<script setup lang="ts">
import DashboardSidebar from "~/components/sections/DashboardSidebar.vue";
import DashboardHeader from "~/components/sections/DashboardHeader.vue";

import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useBranchStore } from "~/stores/branch";

import { authMenuList } from "~/config/authMenu";

const route = useRoute();
const isOpen = ref(false);

const branchStore = useBranchStore();

const homeLink = computed(() => {
    const uuid = branchStore.routeUuid;
    return uuid ? `/app/branches/${uuid}/dashboard` : "/";
});

onMounted(async () => {
    const uuidParam = route.params.uuid;
    await branchStore.fetchBranches(
        Array.isArray(uuidParam) ? uuidParam[0] : uuidParam,
    );

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
