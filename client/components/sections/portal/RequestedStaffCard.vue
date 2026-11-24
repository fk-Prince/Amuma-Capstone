<script setup lang="ts">
import { computed } from "vue";
import { Users } from "lucide-vue-next";
import AchievementPeek from "~/components/ui/AchievementPeek.vue";
import { initials } from "~/utils/user";
import type { RequestedStaff } from "~/types/booking";

const props = defineProps<{
    staff: RequestedStaff[];
    type?: string | null;
}>();

const isAdl = computed(() => (props.type ?? "").toLowerCase() === "adl");

const title = computed(() => {
    const roles = new Set(
        props.staff.map((person) => (person.role ?? "").toLowerCase()),
    );
    const plural = props.staff.length > 1;

    if (!props.staff.length) {
        return isAdl.value ? "Requested Caregiver" : "Requested Staff";
    }

    if (roles.size === 1 && roles.has("nurse")) {
        return plural ? "Requested Nurses" : "Requested Nurse";
    }

    if (roles.size === 1 && roles.has("caregiver")) {
        return plural ? "Requested Caregivers" : "Requested Caregiver";
    }

    return "Requested Staff";
});

const emptyText = computed(() =>
    isAdl.value ? "No caregiver requested" : "No staff requested",
);
</script>

<template>
    <div
        class="rounded-2xl border border-gray-100 bg-white p-4 sm:p-5 dark:border-white/10 dark:bg-secondary"
    >
        <div class="mb-3 flex items-center justify-between gap-3">
            <p
                class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500"
            >
                <Users class="h-3.5 w-3.5" />
                {{ title }}
            </p>

            <p
                v-if="staff.length"
                class="text-[11px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500"
            >
                {{ staff.length }}
                {{ staff.length === 1 ? "person" : "people" }}
            </p>
        </div>

        <p
            v-if="!staff.length"
            class="text-sm text-gray-500 dark:text-gray-400"
        >
            {{ emptyText }}
        </p>

        <ul v-else class="divide-y divide-gray-100 dark:divide-white/10">
            <li
                v-for="person in staff"
                :key="person.uuid ?? person.full_name"
                class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0"
            >
                <AchievementPeek :titles="person.achievement_titles ?? []">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary-50 text-xs font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300"
                    >
                        <img
                            v-if="person.avatar"
                            :src="person.avatar"
                            :alt="person.full_name"
                            class="h-full w-full object-cover"
                        />
                        <template v-else>
                            {{ initials(person.full_name) }}
                        </template>
                    </span>
                </AchievementPeek>

                <div class="min-w-0">
                    <p
                        class="truncate text-sm font-semibold text-gray-800 dark:text-white"
                    >
                        {{ person.full_name }}
                    </p>
                    <p
                        v-if="person.role"
                        class="mt-0.5 truncate text-xs capitalize text-gray-500 dark:text-gray-400"
                    >
                        {{ person.role }}
                    </p>
                </div>
            </li>
        </ul>
    </div>
</template>
