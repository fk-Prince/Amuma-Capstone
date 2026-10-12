<script setup lang="ts">
import { ref, watch } from "vue";
import { Award, HeartHandshake, Stethoscope } from "lucide-vue-next";
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

const hasPhoto = (avatar: string | null) =>
    !!avatar && !avatar.includes("ui-avatars.com");

const isCaregiver = (role: string) => role.toLowerCase() === "caregiver";

const theme = (role: string) =>
    isCaregiver(role)
        ? {
              band: "from-emerald-400 via-teal-400 to-cyan-500",
              avatar: "from-emerald-400 to-teal-500",
              badge: "bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30",
              icon: HeartHandshake,
          }
        : {
              band: "from-sky-400 via-blue-500 to-indigo-500",
              avatar: "from-sky-400 to-blue-600",
              badge: "bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/30",
              icon: Stethoscope,
          };

watch(() => props.branchUuid, load, { immediate: true });
</script>

<template>
    <section
        class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-secondary"
    >
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <span
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-sky-400 to-blue-600 text-white shadow-md shadow-blue-500/20"
                >
                    <Stethoscope class="h-5 w-5" />
                </span>
                <div>
                    <h2
                        class="text-xl font-bold text-secondary dark:text-white"
                    >
                        Health Providers
                    </h2>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        Meet the nurses and caregivers who will look after your
                        loved one.
                    </p>
                </div>
            </div>

            <span
                v-if="!loading && providers.length"
                class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary dark:bg-primary/15"
            >
                {{ providers.length }}
                {{ providers.length === 1 ? "provider" : "providers" }}
            </span>
        </div>

        <div
            v-if="loading"
            class="mt-6 grid animate-pulse grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3"
        >
            <div
                v-for="n in 3"
                :key="n"
                class="overflow-hidden rounded-2xl border border-gray-100 dark:border-white/10"
            >
                <div class="h-20 bg-gray-200 dark:bg-white/10" />
                <div class="flex flex-col items-center gap-3 px-5 pb-6">
                    <div
                        class="-mt-10 h-20 w-20 rounded-full border-4 border-white bg-gray-200 dark:border-secondary dark:bg-white/10"
                    />
                    <div class="h-4 w-32 rounded bg-gray-200 dark:bg-white/10" />
                    <div class="h-6 w-40 rounded-full bg-gray-100 dark:bg-white/5" />
                </div>
            </div>
        </div>

        <div
            v-else-if="!providers.length"
            class="mt-6 flex flex-col items-center rounded-2xl border border-dashed border-gray-200 px-6 py-12 text-center dark:border-white/10"
        >
            <span
                class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary dark:bg-primary/15"
            >
                <Stethoscope class="h-6 w-6" />
            </span>
            <p class="mt-3 text-sm font-semibold text-gray-700 dark:text-gray-200">
                No health providers listed yet
            </p>
        </div>

        <div
            v-else
            class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="(provider, index) in providers"
                :key="index"
                class="provider-card group flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/10 dark:border-white/10 dark:bg-white/5"
                :style="{ animationDelay: `${Math.min(index, 8) * 60}ms` }"
            >
                <div
                    class="relative h-20 overflow-hidden bg-gradient-to-r"
                    :class="theme(provider.role_name).band"
                >
                    <span
                        class="absolute -right-6 -top-8 h-24 w-24 rounded-full bg-white/15"
                    />
                    <span
                        class="absolute -left-4 top-6 h-16 w-16 rounded-full bg-white/10"
                    />
                </div>

                <div class="flex flex-1 flex-col items-center px-5 pb-5 text-center">
                    <div class="relative -mt-10">
                        <img
                            v-if="hasPhoto(provider.avatar)"
                            :src="provider.avatar!"
                            :alt="provider.name"
                            class="h-20 w-20 rounded-full border-4 border-white object-cover shadow-md transition duration-300 group-hover:scale-105 dark:border-secondary"
                        />
                        <span
                            v-else
                            class="flex h-20 w-20 items-center justify-center rounded-full border-4 border-white bg-gradient-to-br text-xl font-bold text-white shadow-md transition duration-300 group-hover:scale-105 dark:border-secondary"
                            :class="theme(provider.role_name).avatar"
                        >
                            {{ initials(provider.name) }}
                        </span>

                        <span
                            v-if="provider.achievements.length"
                            class="absolute -bottom-0.5 -right-0.5 flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-amber-400 text-white shadow dark:border-secondary"
                            :title="`${provider.achievements.length} certificate(s) & achievement(s)`"
                        >
                            <Award class="h-3.5 w-3.5" />
                        </span>
                    </div>

                    <h3
                        class="mt-3 w-full truncate text-base font-bold text-secondary dark:text-white"
                    >
                        {{ provider.name }}
                    </h3>

                    <div
                        v-if="provider.achievements.length"
                        class="mt-4 flex w-full flex-wrap justify-center gap-1.5"
                    >
                        <span
                            v-for="(item, itemIndex) in visibleAchievements(
                                provider,
                                index,
                            )"
                            :key="itemIndex"
                            :title="item.label"
                            class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 ring-1 ring-amber-200/70 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/20"
                        >
                            <Award class="h-3.5 w-3.5 shrink-0 text-amber-500" />
                            <span class="truncate">{{ item.label }}</span>
                        </span>

                        <button
                            v-if="provider.achievements.length > VISIBLE_ACHIEVEMENTS"
                            type="button"
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold text-primary ring-1 ring-primary/20 transition hover:bg-primary-50 dark:hover:bg-primary/10"
                            @click="expanded[index] = !expanded[index]"
                        >
                            {{
                                expanded[index]
                                    ? "Show less"
                                    : `+${provider.achievements.length - VISIBLE_ACHIEVEMENTS} more`
                            }}
                        </button>
                    </div>

                    <div class="mt-auto pt-5">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1"
                            :class="theme(provider.role_name).badge"
                        >
                            <component
                                :is="theme(provider.role_name).icon"
                                class="h-3.5 w-3.5"
                            />
                            {{ provider.role_name }}
                        </span>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>

<style scoped>
.provider-card {
    animation: provider-in 0.4s ease-out backwards;
}

@keyframes provider-in {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
