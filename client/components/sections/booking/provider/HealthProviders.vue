<script setup lang="ts">
import { ref, watch } from "vue";
import { Award, Stethoscope } from "lucide-vue-next";
import { branchService } from "~/api/branch/BranchService";

interface HealthProvider {
    name: string;
    avatar: string | null;
    role_name: string;
    achievements: { label: string }[];
}

const props = defineProps<{
    branchUuid: string;
}>();

const VISIBLE_ACHIEVEMENTS = 3;

const providers = ref<HealthProvider[]>([]);
const loading = ref(false);
const expanded = ref<Record<number, boolean>>({});

const visibleAchievements = (provider: HealthProvider, index: number) =>
    expanded.value[index]
        ? provider.achievements
        : provider.achievements.slice(0, VISIBLE_ACHIEVEMENTS);

async function load(uuid: string) {
    if (!uuid) return;

    loading.value = true;
    expanded.value = {};

    try {
        const res: any = await branchService.healthProviders(uuid);
        providers.value = res.data ?? res;
    } catch (err) {
        console.error(err);
        providers.value = [];
    } finally {
        loading.value = false;
    }
}

const initials = (name: string) =>
    name
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join("")
        .toUpperCase();

watch(() => props.branchUuid, load, { immediate: true });
</script>

<template>
    <section
        class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-secondary"
    >
        <div class="flex items-start gap-3">
            <Stethoscope class="mt-1 h-6 w-6 shrink-0 text-primary" />
            <div>
                <h2 class="text-xl font-bold text-secondary dark:text-white">
                    Health Providers
                </h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    Meet the nurses and caregivers of this provider, along with
                    their certificates and achievements.
                </p>
            </div>
        </div>

        <div
            v-if="loading"
            class="mt-5 grid animate-pulse grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <div
                v-for="n in 3"
                :key="n"
                class="h-48 w-full rounded-2xl bg-gray-200 dark:bg-white/10"
            />
        </div>

        <p
            v-else-if="!providers.length"
            class="mt-5 rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-gray-400 dark:border-white/10 dark:text-gray-500"
        >
            No health providers listed yet.
        </p>

        <div
            v-else
            class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <div
                v-for="(provider, index) in providers"
                :key="index"
                class="flex flex-col rounded-2xl border border-gray-100 p-5 dark:border-white/10 dark:bg-white/5"
            >
                <div class="flex flex-col items-center text-center">
                    <img
                        v-if="provider.avatar"
                        :src="provider.avatar"
                        :alt="provider.name"
                        class="h-20 w-20 rounded-full object-cover ring-4 ring-primary-50 dark:ring-primary/15"
                    />
                    <span
                        v-else
                        class="flex h-20 w-20 items-center justify-center rounded-full bg-primary-50 text-xl font-semibold text-primary ring-4 ring-primary-50 dark:bg-primary/15 dark:ring-primary/15"
                    >
                        {{ initials(provider.name) }}
                    </span>

                    <p
                        class="mt-3 w-full truncate text-base font-semibold text-secondary dark:text-white"
                    >
                        {{ provider.name }}
                    </p>
                </div>

                <ul
                    v-if="provider.achievements.length"
                    class="mt-4 divide-y divide-gray-100 border-t border-gray-100 dark:divide-white/10 dark:border-white/10"
                >
                    <li
                        v-for="(item, itemIndex) in visibleAchievements(
                            provider,
                            index,
                        )"
                        :key="itemIndex"
                        class="flex items-center justify-between gap-3 py-2.5"
                    >
                        <div class="flex min-w-0 items-start gap-2">
                            <Award
                                class="mt-0.5 h-4 w-4 shrink-0 text-amber-500"
                            />
                            <p
                                :title="item.label"
                                class="line-clamp-2 text-sm font-medium text-gray-800 dark:text-gray-100"
                            >
                                {{ item.label }}
                            </p>
                        </div>
                    </li>
                </ul>

                <button
                    v-if="provider.achievements.length > VISIBLE_ACHIEVEMENTS"
                    type="button"
                    class="mt-1 self-start text-xs font-semibold text-primary hover:underline"
                    @click="expanded[index] = !expanded[index]"
                >
                    {{
                        expanded[index]
                            ? "Show less"
                            : `Show ${provider.achievements.length - VISIBLE_ACHIEVEMENTS} more`
                    }}
                </button>

                <div class="mt-auto pt-4">
                    <div
                        class="flex justify-center border-t border-gray-100 pt-3 dark:border-white/10"
                    >
                        <span
                            class="inline-flex rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary dark:bg-primary/15"
                        >
                            {{ provider.role_name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
