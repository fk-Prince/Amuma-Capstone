<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import {
    ArrowRight,
    Award,
    ExternalLink,
    HeartHandshake,
    Stethoscope,
    User,
    X,
} from "lucide-vue-next";
import { branchService } from "~/api/branch/BranchService";

interface Certificate {
    label: string;
    url?: string | null;
    date?: string | null;
}

interface HealthProvider {
    name: string;
    avatar: string | null;
    role_name: string;
    achievements: Certificate[];
}

const props = defineProps<{
    branchUuid: string;
}>();

const VISIBLE_CHIPS = 2;

const providers = ref<HealthProvider[]>([]);
const loading = ref(false);

const activeRole = ref("all");
const selected = ref<HealthProvider | null>(null);
const previewing = ref<Certificate | null>(null);

async function load(uuid: string) {
    if (!uuid) return;

    loading.value = true;
    selected.value = null;
    previewing.value = null;
    activeRole.value = "all";

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

const roleStyle = (role: string) =>
    isCaregiver(role)
        ? {
              tint: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300",
              icon: HeartHandshake,
          }
        : {
              tint: "bg-primary-50 text-primary dark:bg-primary/15 dark:text-sky-300",
              icon: Stethoscope,
          };

// Role filters (All / Nurses / Caregivers ...), only shown when there is more
// than one role among the providers.
const roleOptions = computed(() => {
    const counts = new Map<string, number>();

    for (const provider of providers.value) {
        counts.set(
            provider.role_name,
            (counts.get(provider.role_name) ?? 0) + 1,
        );
    }

    return [...counts.entries()].map(([role, count]) => ({
        role,
        count,
        label: `${role}s`,
    }));
});

const showFilters = computed(() => roleOptions.value.length > 1);

const visibleProviders = computed(() =>
    activeRole.value === "all"
        ? providers.value
        : providers.value.filter(
              (provider) => provider.role_name === activeRole.value,
          ),
);

const countLabel = (count: number) =>
    `${count} ${count === 1 ? "certificate" : "certificates"}`;

const isImage = (url?: string | null) =>
    !!url && /\.(png|jpe?g|webp|gif)$/i.test(url.split("?")[0] ?? "");

const formatDate = (date?: string | null) => {
    if (!date) return "";
    const parsed = new Date(date);
    if (Number.isNaN(parsed.getTime())) return "";
    return parsed.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const selectedCertificates = computed(() => selected.value?.achievements ?? []);

function openProfile(provider: HealthProvider) {
    selected.value = provider;
}

function closeProfile() {
    previewing.value = null;
    selected.value = null;
}

function openCertificate(item: Certificate) {
    if (!item.url) return;

    if (isImage(item.url)) {
        previewing.value = item;
        return;
    }

    window.open(item.url, "_blank", "noopener");
}

function onKeydown(event: KeyboardEvent) {
    if (event.key !== "Escape") return;

    if (previewing.value) previewing.value = null;
    else if (selected.value) closeProfile();
}

watch(
    () => !!selected.value,
    (open) => {
        if (typeof document === "undefined") return;
        document.body.style.overflow = open ? "hidden" : "";
    },
);

onMounted(() => window.addEventListener("keydown", onKeydown));

onBeforeUnmount(() => {
    window.removeEventListener("keydown", onKeydown);
    if (typeof document !== "undefined") document.body.style.overflow = "";
});

watch(() => props.branchUuid, load, { immediate: true });
</script>

<template>
    <section
        class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-6 dark:border-white/10 dark:bg-secondary"
    >
        <!-- Header -->
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="flex items-center gap-3">
                <span
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary dark:bg-primary/15 dark:text-sky-300"
                >
                    <Stethoscope class="h-5 w-5" />
                </span>

                <div>
                    <h2
                        class="text-lg font-bold text-secondary sm:text-xl dark:text-white"
                    >
                        Health Providers
                    </h2>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        Meet the nurses and caregivers who will look after your
                        loved one.
                    </p>
                </div>
            </div>

            <div
                v-if="!loading && showFilters"
                class="flex flex-wrap gap-1.5"
                role="group"
                aria-label="Filter providers by role"
            >
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                    :class="
                        activeRole === 'all'
                            ? 'border-primary/30 bg-primary-50 text-primary dark:bg-primary/15 dark:text-sky-300'
                            : 'border-gray-200 text-gray-600 hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-gray-300'
                    "
                    @click="activeRole = 'all'"
                >
                    All {{ providers.length }}
                </button>

                <button
                    v-for="option in roleOptions"
                    :key="option.role"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                    :class="
                        activeRole === option.role
                            ? 'border-primary/30 bg-primary-50 text-primary dark:bg-primary/15 dark:text-sky-300'
                            : 'border-gray-200 text-gray-600 hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-gray-300'
                    "
                    @click="activeRole = option.role"
                >
                    <component
                        :is="roleStyle(option.role).icon"
                        class="h-3.5 w-3.5"
                    />
                    {{ option.label }} {{ option.count }}
                </button>
            </div>

            <span
                v-else-if="!loading && providers.length"
                class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary dark:bg-primary/15 dark:text-sky-300"
            >
                <User class="h-3.5 w-3.5" />
                {{ providers.length }}
                {{ providers.length === 1 ? "provider" : "providers" }}
            </span>
        </div>

        <!-- Loading -->
        <div
            v-if="loading"
            class="mt-5 grid animate-pulse grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3"
        >
            <div
                v-for="n in 3"
                :key="n"
                class="rounded-xl border border-gray-100 p-4 dark:border-white/10"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="h-[52px] w-[52px] rounded-full bg-gray-200 dark:bg-white/10"
                    />
                    <div class="flex-1 space-y-2">
                        <div
                            class="h-4 w-32 rounded bg-gray-200 dark:bg-white/10"
                        />
                        <div
                            class="h-4 w-16 rounded bg-gray-100 dark:bg-white/5"
                        />
                    </div>
                </div>
                <div class="mt-4 h-12 rounded-lg bg-gray-100 dark:bg-white/5" />
                <div class="mt-4 h-10 rounded-xl bg-gray-100 dark:bg-white/5" />
            </div>
        </div>

        <!-- Empty -->
        <div
            v-else-if="!providers.length"
            class="mt-5 flex flex-col items-center rounded-2xl border border-dashed border-gray-200 px-6 py-12 text-center dark:border-white/10"
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

        <!-- Cards -->
        <div
            v-else
            class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="(provider, index) in visibleProviders"
                :key="`${provider.name}-${index}`"
                class="provider-card flex flex-col gap-4 rounded-xl border border-gray-100 bg-white p-4 transition duration-200 hover:border-primary/40 dark:border-white/10 dark:bg-white/5 dark:hover:border-primary/50"
                :style="{ animationDelay: `${Math.min(index, 8) * 50}ms` }"
            >
                <!-- Identity -->
                <div class="flex items-center gap-3">
                    <img
                        v-if="hasPhoto(provider.avatar)"
                        :src="provider.avatar!"
                        :alt="provider.name"
                        class="h-[52px] w-[52px] shrink-0 rounded-full object-cover"
                    />
                    <span
                        v-else
                        class="flex h-[52px] w-[52px] shrink-0 items-center justify-center rounded-full text-base font-bold"
                        :class="roleStyle(provider.role_name).tint"
                    >
                        {{ initials(provider.name) }}
                    </span>

                    <div class="min-w-0">
                        <h3
                            class="truncate text-[15px] font-bold text-secondary dark:text-white"
                            :title="provider.name"
                        >
                            {{ provider.name }}
                        </h3>

                        <span
                            class="mt-1 inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-semibold"
                            :class="roleStyle(provider.role_name).tint"
                        >
                            <component
                                :is="roleStyle(provider.role_name).icon"
                                class="h-3 w-3"
                            />
                            {{ provider.role_name }}
                        </span>
                    </div>
                </div>

                <!-- Certificates -->
                <div>
                    <div
                        class="mb-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400"
                    >
                        <span>Certificates</span>
                        <span class="font-semibold">
                            {{ provider.achievements.length }}
                        </span>
                    </div>

                    <div
                        v-if="provider.achievements.length"
                        class="flex min-h-[3.25rem] flex-wrap content-start gap-1.5"
                    >
                        <span
                            v-for="(item, itemIndex) in provider.achievements.slice(
                                0,
                                VISIBLE_CHIPS,
                            )"
                            :key="itemIndex"
                            :title="item.label"
                            class="inline-flex max-w-full items-center gap-1 rounded-md bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800 dark:bg-amber-500/10 dark:text-amber-200"
                        >
                            <Award class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{ item.label }}</span>
                        </span>

                        <span
                            v-if="provider.achievements.length > VISIBLE_CHIPS"
                            class="px-1 py-1 text-xs font-semibold text-primary dark:text-sky-300"
                        >
                            +{{ provider.achievements.length - VISIBLE_CHIPS }}
                            more
                        </span>
                    </div>

                    <p
                        v-else
                        class="min-h-[3.25rem] text-sm text-gray-400 dark:text-gray-500"
                    >
                        No certificates added yet.
                    </p>
                </div>

                <!-- Action -->
                <button
                    type="button"
                    class="mt-auto inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-secondary transition hover:border-primary hover:text-primary active:scale-[0.99] dark:border-white/10 dark:text-white dark:hover:border-primary dark:hover:text-sky-300"
                    @click="openProfile(provider)"
                >
                    View Profile
                    <ArrowRight class="h-4 w-4" />
                </button>
            </article>
        </div>

        <!-- Profile modal -->
        <ClientOnly>
            <Teleport to="body">
                <Transition name="fade">
                    <div
                        v-if="selected"
                        class="fixed inset-0 z-[100] flex items-end justify-center bg-secondary/60 p-0 backdrop-blur-sm sm:items-center sm:p-6"
                        role="dialog"
                        aria-modal="true"
                        :aria-label="`${selected.name} profile`"
                        @click.self="closeProfile"
                    >
                        <div
                            class="modal-panel relative flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:max-w-lg sm:rounded-2xl dark:bg-secondary"
                        >
                            <!-- Identity -->
                            <div
                                class="flex shrink-0 items-start justify-between gap-3 p-5 sm:p-6"
                            >
                                <div class="flex min-w-0 items-center gap-4">
                                    <img
                                        v-if="hasPhoto(selected.avatar)"
                                        :src="selected.avatar!"
                                        :alt="selected.name"
                                        class="h-16 w-16 shrink-0 rounded-full object-cover"
                                    />
                                    <span
                                        v-else
                                        class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full text-xl font-bold"
                                        :class="roleStyle(selected.role_name).tint"
                                    >
                                        {{ initials(selected.name) }}
                                    </span>

                                    <div class="min-w-0">
                                        <h3
                                            class="truncate text-lg font-bold text-secondary dark:text-white"
                                        >
                                            {{ selected.name }}
                                        </h3>

                                        <div
                                            class="mt-1 flex flex-wrap items-center gap-2"
                                        >
                                            <span
                                                class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-semibold"
                                                :class="roleStyle(selected.role_name).tint"
                                            >
                                                <component
                                                    :is="roleStyle(selected.role_name).icon"
                                                    class="h-3 w-3"
                                                />
                                                {{ selected.role_name }}
                                            </span>

                                            <span
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {{
                                                    countLabel(
                                                        selectedCertificates.length,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10"
                                    aria-label="Close"
                                    @click="closeProfile"
                                >
                                    <X class="h-5 w-5" />
                                </button>
                            </div>

                            <!-- Certificates -->
                            <div
                                class="min-h-0 flex-1 overflow-y-auto border-t border-gray-100 p-5 sm:p-6 dark:border-white/10"
                            >
                                <h4
                                    class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                                >
                                    Certificates and credentials
                                </h4>

                                <div
                                    v-if="!selectedCertificates.length"
                                    class="mt-3 flex flex-col items-center rounded-xl border border-dashed border-gray-200 px-6 py-10 text-center dark:border-white/10"
                                >
                                    <span
                                        class="flex h-11 w-11 items-center justify-center rounded-full bg-amber-50 text-amber-500 dark:bg-amber-500/10"
                                    >
                                        <Award class="h-5 w-5" />
                                    </span>
                                    <p
                                        class="mt-3 text-sm font-semibold text-gray-700 dark:text-gray-200"
                                    >
                                        No certificates added yet
                                    </p>
                                </div>

                                <ul v-else class="mt-3 space-y-2">
                                    <li
                                        v-for="(item, i) in selectedCertificates"
                                        :key="i"
                                    >
                                        <button
                                            type="button"
                                            :disabled="!item.url"
                                            class="cert-row flex w-full items-center gap-3 rounded-xl border border-gray-100 p-2.5 text-left transition hover:border-primary/40 hover:bg-primary-50/40 disabled:cursor-default disabled:hover:border-gray-100 disabled:hover:bg-transparent dark:border-white/10 dark:hover:bg-white/5 dark:disabled:hover:border-white/10"
                                            :style="{
                                                animationDelay: `${Math.min(i, 8) * 40}ms`,
                                            }"
                                            @click="openCertificate(item)"
                                        >
                                            <span
                                                class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
                                            >
                                                <img
                                                    v-if="isImage(item.url)"
                                                    :src="item.url!"
                                                    :alt="item.label"
                                                    loading="lazy"
                                                    class="h-full w-full object-cover"
                                                />
                                                <Award v-else class="h-5 w-5" />
                                            </span>

                                            <span class="min-w-0 flex-1">
                                                <span
                                                    class="block truncate text-sm font-semibold text-secondary dark:text-white"
                                                >
                                                    {{ item.label }}
                                                </span>
                                                <span
                                                    v-if="formatDate(item.date)"
                                                    class="block text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    Issued {{ formatDate(item.date) }}
                                                </span>
                                            </span>

                                            <ExternalLink
                                                v-if="item.url"
                                                class="h-4 w-4 shrink-0 text-gray-400"
                                            />
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Certificate image viewer -->
                <Transition name="fade">
                    <div
                        v-if="previewing"
                        class="fixed inset-0 z-[110] flex flex-col items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
                        @click.self="previewing = null"
                    >
                        <button
                            type="button"
                            class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-white transition hover:bg-white/30"
                            aria-label="Close preview"
                            @click="previewing = null"
                        >
                            <X class="h-5 w-5" />
                        </button>

                        <img
                            :src="previewing.url!"
                            :alt="previewing.label"
                            class="max-h-[80vh] max-w-full rounded-xl bg-white object-contain shadow-2xl"
                        />

                        <div class="mt-4 flex flex-col items-center gap-2 text-center">
                            <p class="text-sm font-semibold text-white">
                                {{ previewing.label }}
                                <span
                                    v-if="formatDate(previewing.date)"
                                    class="font-normal text-white/70"
                                >
                                    · {{ formatDate(previewing.date) }}
                                </span>
                            </p>
                            <a
                                :href="previewing.url!"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-white/30"
                            >
                                <ExternalLink class="h-3.5 w-3.5" />
                                Open original
                            </a>
                        </div>
                    </div>
                </Transition>
            </Teleport>
        </ClientOnly>
    </section>
</template>

<style scoped>
.provider-card,
.cert-row {
    animation: provider-in 0.35s ease-out backwards;
}

.modal-panel {
    animation: panel-in 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@keyframes provider-in {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes panel-in {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>