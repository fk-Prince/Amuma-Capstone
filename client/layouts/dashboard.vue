<template>
    <div class="h-screen flex flex-col bg-[#EEF3FB]">
        <DashboardHeader @open="isOpen = true" />

        <div class="flex flex-1 min-h-0">
            <DynamicSidebar
                :open="isOpen"
                :logo="logoAmuma"
                :authMenu="menus"
                :user="user"
                @close="isOpen = false"
                :variant="2"
            />

            <main class="flex-1 overflow-auto p-0 m-0">
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
