<template>
    <div class="flex flex-col gap-3">
        <div>
            <h3
                class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white"
            >
                Request a {{ roleTitle }}
                <span
                    class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500 dark:bg-white/10 dark:text-gray-400"
                >
                    Optional
                </span>
            </h3>

            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                Please choose the {{ roleLabel }} you would like
                {{ patientName ? `for ${patientName}` : "for this visit" }}.
            </p>
        </div>

        <div
            class="flex items-start gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-3 text-xs leading-5 text-blue-900 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-200"
        >
            <Info class="mt-0.5 h-4 w-4 shrink-0" />
            <p>
                Your choice isn't guaranteed. It still depends on whether
                they're available on your date and time. The branch confirms
                the final assignment.
            </p>
        </div>

        <div class="relative">
            <Search
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500"
            />

            <input
                v-model="search"
                type="text"
                maxlength="100"
                :placeholder="`Search ${roleLabel} name`"
                :aria-label="`Search ${roleLabel} name`"
                class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-9 text-sm text-slate-700 transition placeholder:text-slate-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-white/10 dark:bg-secondary dark:text-gray-200 dark:placeholder:text-gray-500"
            />

            <button
                v-if="search"
                type="button"
                aria-label="Clear search"
                class="absolute right-1.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10"
                @click="search = ''"
            >
                <X class="h-4 w-4" />
            </button>
        </div>

        <div
            class="rounded-xl border border-slate-200 dark:border-white/10"
        >
            <div
                class="grid grid-cols-[48px_minmax(0,1fr)] items-center rounded-t-xl border-b border-slate-200 bg-slate-50 py-2.5 pr-4 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
            >
                <span />
                <span>{{ columnTitle }}</span>
            </div>

            <template v-if="loading && !options.length">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="flex items-center gap-3 border-b border-slate-200 px-4 py-3 dark:border-white/10"
                >
                    <div
                        class="h-9 w-9 animate-pulse rounded-full bg-slate-200 dark:bg-white/15"
                    />
                    <div class="flex-1 space-y-1.5">
                        <div
                            class="h-3.5 w-32 animate-pulse rounded bg-slate-200 dark:bg-white/15"
                        />
                        <div
                            class="h-3 w-20 animate-pulse rounded bg-slate-200 dark:bg-white/15"
                        />
                    </div>
                </div>
            </template>

            <p
                v-else-if="!options.length"
                class="border-b border-slate-200 px-4 py-5 text-center text-xs text-slate-500 dark:border-white/10 dark:text-gray-400"
            >
                {{
                    loadError ||
                    (activeSearch
                        ? `No ${rolePlural} match "${activeSearch}".`
                        : `No ${rolePlural} are available for this service yet. The branch will choose for you.`)
                }}
            </p>

            <label
                v-for="option in visibleOptions"
                :key="option.uuid"
                class="grid min-h-[64px] grid-cols-[48px_minmax(0,1fr)] items-center border-b border-slate-200 pr-4 transition dark:border-white/10"
                :class="rowClass(option)"
            >
                <span class="flex justify-center">
                    <input
                        type="checkbox"
                        class="h-[18px] w-[18px] rounded accent-primary"
                        :checked="isSelected(option.uuid)"
                        :disabled="!option.is_eligible"
                        :aria-label="
                            option.is_eligible
                                ? `Choose ${option.full_name}`
                                : `${option.full_name}, not specialized for this service`
                        "
                        @change="toggle(option)"
                    />
                </span>

                <span class="flex min-w-0 items-center gap-3 py-2">
                    <AchievementPeek :titles="option.achievement_titles ?? []">
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full text-xs font-semibold"
                            :class="avatarClass(option)"
                        >
                            <img
                                v-if="option.avatar"
                                :src="option.avatar"
                                :alt="option.full_name"
                                class="h-full w-full object-cover"
                            />
                            <template v-else>
                                {{ initials(option.full_name) }}
                            </template>
                        </span>
                    </AchievementPeek>

                    <span
                        class="min-w-0"
                        :class="option.is_eligible ? '' : 'opacity-60'"
                    >
                        <span
                            class="block truncate text-sm font-semibold text-slate-800 dark:text-white"
                        >
                            {{ option.full_name }}
                        </span>
                        <span
                            class="block truncate text-xs capitalize text-slate-500 dark:text-gray-400"
                        >
                            {{ option.role }}
                            <template v-if="!option.is_eligible">
                                · Not specialized
                            </template>
                        </span>
                        <span
                            v-if="option.cared_before && patientName"
                            class="mt-1 inline-block rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-semibold text-violet-700 dark:bg-violet-500/15 dark:text-violet-300"
                        >
                            Cared for {{ patientName }} before
                        </span>
                    </span>
                </span>
            </label>

            <div
                v-if="options.length"
                class="flex flex-wrap items-center justify-between gap-2.5 rounded-b-xl bg-slate-50 px-4 py-2.5 dark:bg-white/5"
            >
                <span class="text-xs text-slate-500 dark:text-gray-400">
                    Showing {{ visibleOptions.length }} of {{ total }}
                    {{ rolePlural }}
                </span>

                <div class="flex gap-2">
                    <button
                        v-if="shown > PAGE_SIZE"
                        type="button"
                        class="h-9 rounded-lg border border-slate-200 bg-white px-3.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-white/10 dark:bg-transparent dark:text-gray-300 dark:hover:bg-white/10"
                        @click="showLess"
                    >
                        Show less
                    </button>

                    <button
                        v-if="remaining > 0"
                        type="button"
                        :disabled="loading"
                        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-primary bg-primary/5 px-3.5 text-xs font-semibold text-primary transition hover:bg-primary/10 disabled:cursor-not-allowed disabled:opacity-60 dark:text-primary-300"
                        @click="loadMore"
                    >
                        <Loader2 v-if="loading" class="h-3.5 w-3.5 animate-spin" />
                        Load {{ Math.min(PAGE_SIZE, remaining) }} more
                    </button>
                </div>
            </div>
        </div>

        <p
            v-if="hasIneligible"
            class="text-xs text-slate-500 dark:text-gray-400"
        >
            Greyed-out {{ rolePlural }} aren't assigned to this service, so
            they can't be chosen.
        </p>

        <p v-if="stayIn" class="text-xs text-slate-500 dark:text-gray-400">
            A stay-in booking can request one caregiver.
        </p>

        <div
            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-white/10 dark:bg-white/5"
        >
            <span class="text-xs text-slate-500 dark:text-gray-400">
                Your choice
            </span>
            <span class="text-[13px] font-semibold text-slate-800 dark:text-white">
                {{ selectionSummary }}
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { Info, Loader2, Search, X } from "lucide-vue-next";
import AchievementPeek from "~/components/ui/AchievementPeek.vue";
import { bookingService } from "~/api/booking/BookingService";
import { initials } from "~/utils/user";
import type { BookingStaffOption } from "~/types/booking";

const PAGE_SIZE = 5;

const props = defineProps<{
    modelValue: string[];
    branchUuid: string;
    type: "Medical" | "ADL";
    serviceId?: number | null;
    patientId?: number | null;
    patientName?: string | null;
    stayIn?: boolean;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: string[]): void;
}>();

const options = ref<BookingStaffOption[]>([]);
const total = ref(0);
const page = ref(0);
const hasMore = ref(false);
const shown = ref(PAGE_SIZE);
const loading = ref(false);
const loadError = ref<string | null>(null);
const role = ref<string>(props.type === "ADL" ? "caregiver" : "staff");
const names = ref<Record<string, string>>({});
const search = ref("");
const activeSearch = ref("");

let requestId = 0;
let searchTimer: ReturnType<typeof setTimeout> | undefined;

const singleRole = computed(() =>
    role.value === "nurse" || role.value === "caregiver" ? role.value : null,
);

const roleLabel = computed(() => singleRole.value ?? "nurse or caregiver");

const rolePlural = computed(() =>
    singleRole.value ? `${singleRole.value}s` : "staff",
);

const roleTitle = computed(() =>
    singleRole.value
        ? singleRole.value.charAt(0).toUpperCase() + singleRole.value.slice(1)
        : "Nurse or Caregiver",
);

const columnTitle = computed(() =>
    singleRole.value ? roleTitle.value : "Staff",
);

const visibleOptions = computed(() => options.value.slice(0, shown.value));

const remaining = computed(() => total.value - visibleOptions.value.length);

const hasIneligible = computed(() =>
    visibleOptions.value.some((option) => !option.is_eligible),
);

const selectionSummary = computed(() => {
    const chosen = props.modelValue.map((uuid) => names.value[uuid] ?? "");
    const count = chosen.length;

    if (!count) return "None yet · the branch will choose";

    const label = count === 1 ? roleLabel.value : rolePlural.value;

    return `${count} ${label} · ${chosen.filter(Boolean).join(", ")}`;
});

function isSelected(uuid: string) {
    return props.modelValue.includes(uuid);
}

function toggle(option: BookingStaffOption) {
    if (!option.is_eligible) return;

    names.value[option.uuid] = option.full_name;

    if (isSelected(option.uuid)) {
        emit(
            "update:modelValue",
            props.modelValue.filter((uuid) => uuid !== option.uuid),
        );
        return;
    }

    emit(
        "update:modelValue",
        props.stayIn ? [option.uuid] : [...props.modelValue, option.uuid],
    );
}

function rowClass(option: BookingStaffOption) {
    if (!option.is_eligible) {
        return "cursor-not-allowed bg-slate-50/60 dark:bg-transparent";
    }

    return isSelected(option.uuid)
        ? "cursor-pointer bg-primary/5 dark:bg-primary-500/10"
        : "cursor-pointer bg-white hover:bg-slate-50 dark:bg-transparent dark:hover:bg-white/5";
}

function avatarClass(option: BookingStaffOption) {
    if (isSelected(option.uuid)) return "bg-primary text-white";

    return option.is_eligible
        ? "bg-primary/10 text-primary dark:text-primary-300"
        : "bg-slate-200 text-slate-600 dark:bg-white/10 dark:text-gray-300";
}

async function fetchPage(next: number) {
    const id = ++requestId;

    loading.value = true;
    loadError.value = null;

    try {
        const res: any = await bookingService.actionBooking({
            action: "staff_options",
            branch_uuid: props.branchUuid,
            type: props.type,
            service_id: props.type === "Medical" ? props.serviceId : undefined,
            patient_id: props.patientId ?? undefined,
            search: activeSearch.value || undefined,
            page: next,
        });

        if (id !== requestId) return;

        const rows: BookingStaffOption[] = res?.data ?? [];

        options.value = next === 1 ? rows : [...options.value, ...rows];
        total.value = Number(res?.meta?.total ?? options.value.length);
        hasMore.value = Boolean(res?.meta?.has_more);
        role.value = res?.meta?.role ?? role.value;
        page.value = next;

        rows.forEach((option) => {
            names.value[option.uuid] = option.full_name;
        });
    } catch (err: any) {
        if (id !== requestId) return;

        loadError.value =
            err?.response?.data?.message ??
            err?.message ??
            "Couldn't load the staff list. Try again later.";
    } finally {
        if (id === requestId) loading.value = false;
    }
}

async function loadMore() {
    if (loading.value) return;

    if (options.value.length < shown.value + PAGE_SIZE && hasMore.value) {
        await fetchPage(page.value + 1);
    }

    shown.value += PAGE_SIZE;
}

function showLess() {
    shown.value = PAGE_SIZE;
}

function reset() {
    options.value = [];
    total.value = 0;
    page.value = 0;
    hasMore.value = false;
    shown.value = PAGE_SIZE;
    role.value = props.type === "ADL" ? "caregiver" : "staff";

    if (props.type === "Medical" && !props.serviceId) return;

    fetchPage(1);
}

watch(
    () => [props.branchUuid, props.type, props.serviceId, props.patientId],
    () => {
        clearTimeout(searchTimer);
        search.value = "";
        activeSearch.value = "";
        reset();
    },
    { immediate: true },
);

watch(search, (value) => {
    clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        const term = value.trim();

        if (term === activeSearch.value) return;

        activeSearch.value = term;
        reset();
    }, 300);
});

onBeforeUnmount(() => clearTimeout(searchTimer));

watch(
    () => props.stayIn,
    (stayIn) => {
        if (stayIn && props.modelValue.length > 1) {
            emit("update:modelValue", props.modelValue.slice(0, 1));
        }
    },
);
</script>
