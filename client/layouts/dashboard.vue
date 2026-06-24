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
import { useAuthUser } from "~/composables/useAuthUser";
import { useBranchStore } from "~/stores/branch";

import {
    authMenuList,
    branchOwnerMenuLists,
    ownerMenuList,
} from "~/config/authMenu";

const user = useAuthUser();
const isOpen = ref(false);

const branchStore = useBranchStore();

onMounted(async () => {
    if (!branchStore.branches.length) {
        await branchStore.fetchBranches();
    }
});

const initials = userInitials(user);
const avatar = avatarSrc(initials);

const activeRoles = computed(() => {
    const branch = branchStore.activeBranch;

    return (
        branch?.roles?.filter((r) => r.is_active).map((r) => r.role_type) ?? []
    );
});

const menus = computed(() => {
    const roles = activeRoles.value ?? [];
    const uuid = branchStore.routeUuid;

    return authMenuList
        .filter((item) => {
            if (!item.roles) return true;
            return item.roles.some((r) => roles.includes(r));
        })
        .map((item) => {
            return {
                label: item.label,
                to: uuid ? item.to.replace("[uuid]", uuid) : item.to,
                icon: item.icon,
            };
        });
});

// const menus = computed(() => {
//     const roles = activeRoles.value ?? [];
//     const uuid = branchStore.routeUuid;

//     const allMenus = [
//         ...ownerMenuList.map((item) => ({
//             ...item,
//             needsUuid: false,
//             group: "owner",
//         })),
//         ...branchOwnerMenuLists.map((item) => ({
//             ...item,
//             needsUuid: false,
//             group: "branchOwner",
//         })),
//         ...authMenuList.map((item) => ({
//             ...item,
//             needsUuid: true,
//             group: "auth",
//         })),
//     ];

//     const filtered = allMenus
//         .filter((item) => {
//             if (!item.roles) return true;
//             return item.roles.some((r) => roles.includes(r));
//         })
//         .map((item) => ({
//             label: item.label,
//             to:
//                 item.needsUuid && uuid
//                     ? item.to.replace("[uuid]", uuid)
//                     : item.to,
//             icon: item.icon,
//             group: item.group,
//         }));

//     const result: typeof filtered = [];
//     filtered.forEach((item, index) => {
//         if (index > 0 && item.group !== filtered[index - 1]?.group) {
//             result.push({ divider: true, id: `divider-${item.group}` } as any);
//         }
//         result.push(item);
//     });

//     return result;
// });
</script>
