<script setup lang="ts">
import { ref, watch } from "vue";
import { Award, ExternalLink } from "lucide-vue-next";
import { branchService } from "~/api/branch/BranchService";
import { formatDate } from "~/utils/time";

interface StaffAchievement {
    name: string;
    avatar: string | null;
    role_name: string;
    achievements: { label: string; url: string; date: string | null }[];
}

const props = defineProps<{
    branchUuid: string;
}>();

const staff = ref<StaffAchievement[]>([]);
const loading = ref(false);

async function load(uuid: string) {
    if (!uuid) return;

    loading.value = true;

    try {
        const res: any = await branchService.staffAchievements(uuid);
        staff.value = res.data ?? res;
    } catch (err) {
        console.error(err);
        staff.value = [];
    } finally {
        loading.value = false;
    }
}

watch(() => props.branchUuid, load, { immediate: true });
</script>

<template>
    <section
        class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-secondary"
    >
        <div class="flex items-start gap-3">
            <Award class="mt-1 h-6 w-6 shrink-0 text-primary" />
            <div>
                <h2 class="text-xl font-bold text-secondary dark:text-white">
                    Certificates & Achievements
                </h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    Credentials and recognitions earned by this provider's
                    team.
                </p>
            </div>
        </div>

        <div v-if="loading" class="mt-5 flex animate-pulse flex-col gap-4">
            <div
                v-for="n in 2"
                :key="n"
                class="h-24 w-full rounded-2xl bg-gray-200 dark:bg-white/10"
            />
        </div>

        <p
            v-else-if="!staff.length"
            class="mt-5 rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-400 dark:border-white/10 dark:text-gray-500"
        >
            No certificates or achievements listed yet.
        </p>

        <div v-else class="mt-5 flex flex-col gap-4">
            <div
                v-for="member in staff"
                :key="member.name"
                class="rounded-2xl border border-gray-100 p-4 dark:border-white/10 dark:bg-white/5"
            >
                <div class="flex items-center gap-3">
                    <img
                        v-if="member.avatar"
                        :src="member.avatar"
                        :alt="member.name"
                        class="h-10 w-10 shrink-0 rounded-full object-cover"
                    />

                    <div class="min-w-0">
                        <p
                            class="truncate text-sm font-semibold text-secondary dark:text-white"
                        >
                            {{ member.name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ member.role_name }}
                        </p>
                    </div>
                </div>

                <ul
                    class="mt-3 divide-y divide-gray-100 border-t border-gray-100 dark:divide-white/10 dark:border-white/10"
                >
                    <li
                        v-for="(item, index) in member.achievements"
                        :key="index"
                        class="flex items-center justify-between gap-3 py-2.5"
                    >
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-medium text-gray-800 dark:text-gray-100"
                            >
                                {{ item.label }}
                            </p>
                            <p
                                v-if="item.date"
                                class="text-xs text-gray-500 dark:text-gray-400"
                            >
                                {{ formatDate(item.date) }}
                            </p>
                        </div>

                        <a
                            :href="item.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-primary hover:underline"
                        >
                            View
                            <ExternalLink class="h-3.5 w-3.5" />
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>
