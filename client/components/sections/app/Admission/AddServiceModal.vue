<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="close"
            />

            <div
                class="relative z-50 flex max-h-[90dvh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-4 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p
                            class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500"
                        >
                            Add service · In-house facility
                        </p>

                        <h2
                            class="mt-1 truncate text-lg font-semibold text-slate-800 dark:text-white"
                        >
                            {{ patientName || "Resident" }}
                        </h2>

                        <p class="mt-1 text-xs text-muted dark:text-gray-400">
                            Schedule one medical service for this resident.
                            Choosing another replaces the current pick.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                        @click="close"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div
                    class="min-h-0 flex-1 overflow-y-auto md:grid md:grid-cols-[minmax(0,3fr)_2fr] md:overflow-hidden"
                >
                    <div class="space-y-6 p-6 md:overflow-y-auto">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <BaseInput
                                :model-value="form.date"
                                label="Select Schedule Date"
                                mode="date"
                                :min="todayStr"
                                required
                                @update:model-value="form.date = $event"
                            />

                            <Combobox
                                :model-value="form.preferred_time"
                                :placeholder="displayTime"
                                label="Preferred Time"
                                :items="availableTimeSlots"
                                required
                                @update:model-value="
                                    form.preferred_time = $event
                                "
                            />
                        </div>

                        <BaseInput
                            :model-value="form.note"
                            label="Note"
                            mode="textarea"
                            placeholder="Add any additional notes..."
                            @update:model-value="form.note = $event"
                        />

                        <div>
                            <div class="mb-3 flex items-center justify-between">
                                <label
                                    class="text-sm font-semibold text-slate-700 dark:text-gray-400"
                                >
                                    Select one service
                                    <span class="text-danger">*</span>
                                </label>

                                <span
                                    v-if="selectedService"
                                    class="max-w-[55%] truncate rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                                >
                                    {{ selectedService.service_name }}
                                </span>
                            </div>

                            <div class="relative mb-4">
                                <Search
                                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500"
                                />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search services or categories..."
                                    class="w-full rounded-lg border border-slate-200 bg-transparent py-2 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-white/10 dark:text-gray-400 dark:placeholder:text-gray-500"
                                />
                            </div>

                            <div
                                v-if="loading"
                                class="animate-pulse space-y-3"
                            >
                                <div
                                    v-for="i in 3"
                                    :key="i"
                                    class="h-14 rounded-xl bg-slate-100 dark:bg-white/5"
                                />
                            </div>

                            <div
                                v-else-if="loadError"
                                class="rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500 dark:border-white/10 dark:text-gray-400"
                            >
                                {{ loadError }}
                            </div>

                            <div
                                v-else-if="!groupedServices.length"
                                class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-slate-200 py-12 text-center dark:border-white/10"
                            >
                                <PackageSearch
                                    class="h-6 w-6 text-slate-300 dark:text-gray-500"
                                />
                                <p
                                    class="text-sm font-medium text-slate-500 dark:text-gray-400"
                                >
                                    No services found
                                </p>
                                <p
                                    class="text-xs text-slate-400 dark:text-gray-500"
                                >
                                    Try a different search term.
                                </p>
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="group in groupedServices"
                                    :key="group.category"
                                    class="overflow-hidden rounded-xl border border-slate-100 dark:border-white/10"
                                >
                                    <button
                                        type="button"
                                        class="flex w-full items-center justify-between gap-3 bg-slate-50 px-4 py-3 text-left transition hover:bg-slate-100 dark:bg-white/5 dark:hover:bg-white/10"
                                        @click="toggleCategory(group.category)"
                                    >
                                        <div class="flex items-center gap-2.5">
                                            <span
                                                class="text-sm font-semibold text-slate-700 dark:text-gray-400"
                                            >
                                                {{ group.category }}
                                            </span>
                                            <span
                                                class="rounded-full bg-white px-2 py-0.5 text-[11px] font-medium text-slate-400 ring-1 ring-slate-200 dark:bg-secondary dark:text-gray-500 dark:ring-white/10"
                                            >
                                                {{ group.items.length }}
                                            </span>
                                            <span
                                                v-if="group.selectedCount"
                                                class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary"
                                            >
                                                Selected
                                            </span>
                                        </div>

                                        <ChevronDown
                                            class="h-4 w-4 shrink-0 text-slate-400 transition-transform dark:text-gray-500"
                                            :class="{
                                                'rotate-180': isCollapsed(
                                                    group.category,
                                                ),
                                            }"
                                        />
                                    </button>

                                    <div
                                        v-show="!isCollapsed(group.category)"
                                        class="space-y-2 p-3"
                                    >
                                        <button
                                            v-for="service in group.items"
                                            :key="service.service_uuid"
                                            type="button"
                                            :aria-pressed="isSelected(service)"
                                            :disabled="!service.is_available"
                                            class="flex w-full items-center justify-between gap-3 rounded-lg border p-3 text-left transition disabled:cursor-not-allowed disabled:opacity-50"
                                            :class="
                                                isSelected(service)
                                                    ? 'border-primary bg-primary/5 ring-1 ring-primary/20'
                                                    : 'border-slate-200 hover:border-primary/40 hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5'
                                            "
                                            @click="toggleService(service)"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="truncate text-sm font-medium text-slate-800 dark:text-white"
                                                >
                                                    {{ service.service_name }}
                                                </p>
                                                <p
                                                    v-if="!service.is_available"
                                                    class="mt-0.5 flex items-center gap-1 text-[11px] text-red-400"
                                                >
                                                    <CircleAlert
                                                        class="h-3 w-3"
                                                    />
                                                    Currently unavailable
                                                </p>
                                                <p
                                                    v-else
                                                    class="mt-0.5 text-[11px] capitalize text-slate-400 dark:text-gray-500"
                                                >
                                                    {{ service.type_formatted }}
                                                </p>
                                            </div>

                                            <span
                                                class="shrink-0 text-sm font-semibold text-primary"
                                            >
                                                {{
                                                    formatCurrency(
                                                        service.price,
                                                    )
                                                }}
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <aside
                        class="flex flex-col border-t border-slate-100 bg-slate-50 md:min-h-0 md:border-l md:border-t-0 dark:border-white/10 dark:bg-white/5"
                    >
                        <div class="flex-1 overflow-y-auto p-6">
                            <div class="flex items-center gap-2">
                                <ClipboardList class="h-4 w-4 text-primary" />
                                <h3
                                    class="font-semibold text-slate-800 dark:text-white"
                                >
                                    Service Summary
                                </h3>
                            </div>
                            <p
                                class="mt-1 text-xs text-muted dark:text-gray-400"
                            >
                                Review the details before confirming.
                            </p>

                            <div class="mt-5 space-y-2.5">
                                <div
                                    class="flex items-center gap-3 rounded-lg bg-white p-3 shadow-sm ring-1 ring-slate-100 dark:bg-secondary dark:ring-white/10"
                                >
                                    <CalendarDays
                                        class="h-4 w-4 shrink-0 text-primary"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-[11px] text-muted dark:text-gray-400"
                                        >
                                            Date
                                        </p>
                                        <p
                                            class="truncate text-sm font-medium text-slate-800 dark:text-white"
                                        >
                                            {{
                                                form.date
                                                    ? formatDate(form.date)
                                                    : "Not selected"
                                            }}
                                        </p>
                                    </div>
                                    <CircleAlert
                                        v-if="!form.date"
                                        class="h-3.5 w-3.5 shrink-0 text-amber-500 dark:text-amber-300"
                                    />
                                </div>

                                <div
                                    class="flex items-center gap-3 rounded-lg bg-white p-3 shadow-sm ring-1 ring-slate-100 dark:bg-secondary dark:ring-white/10"
                                >
                                    <Clock
                                        class="h-4 w-4 shrink-0 text-primary"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-[11px] text-muted dark:text-gray-400"
                                        >
                                            Time
                                        </p>
                                        <p
                                            class="truncate text-sm font-medium text-slate-800 dark:text-white"
                                        >
                                            {{
                                                format24To12(
                                                    form.preferred_time,
                                                ) || "Not selected"
                                            }}
                                        </p>
                                    </div>
                                    <CircleAlert
                                        v-if="!form.preferred_time"
                                        class="h-3.5 w-3.5 shrink-0 text-amber-500 dark:text-amber-300"
                                    />
                                </div>
                            </div>

                            <div class="mt-6">
                                <div
                                    v-if="!selectedService"
                                    class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-slate-200 bg-white py-8 text-center dark:border-white/10 dark:bg-secondary"
                                >
                                    <ClipboardX
                                        class="h-5 w-5 text-slate-300 dark:text-gray-500"
                                    />
                                    <p
                                        class="text-xs text-muted dark:text-gray-400"
                                    >
                                        No service selected yet.
                                    </p>
                                </div>

                                <div
                                    v-else
                                    class="flex items-center justify-between gap-2 rounded-lg bg-white p-3 shadow-sm ring-1 ring-slate-100 dark:bg-secondary dark:ring-white/10"
                                >
                                    <p
                                        class="min-w-0 truncate text-sm font-medium text-slate-800 dark:text-white"
                                    >
                                        {{ selectedService.service_name }}
                                    </p>

                                    <div
                                        class="flex shrink-0 items-center gap-3"
                                    >
                                        <span
                                            class="text-sm font-semibold tabular-nums text-slate-800 dark:text-white"
                                        >
                                            {{
                                                formatCurrency(
                                                    selectedService.price,
                                                )
                                            }}
                                        </span>
                                        <button
                                            type="button"
                                            class="text-slate-400 transition hover:text-red-500 dark:text-gray-500"
                                            aria-label="Remove service"
                                            @click="selectedService = null"
                                        >
                                            <X class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="shrink-0 border-t border-slate-100 bg-white p-6 dark:border-white/10 dark:bg-secondary"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-sm font-semibold text-slate-800 dark:text-white"
                                >
                                    Total
                                </span>
                                <span
                                    class="text-xl font-bold tabular-nums text-primary"
                                >
                                    {{ formatCurrency(totalPrice) }}
                                </span>
                            </div>

                            <div class="mt-4 flex items-center gap-3">
                                <button
                                    type="button"
                                    class="rounded-xl px-4 py-3 text-sm font-medium text-slate-500 transition hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-white/10"
                                    @click="close"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="button"
                                    :disabled="!canSchedule || submitting"
                                    class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-primary py-3 text-sm font-semibold text-white transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:disabled:bg-white/10 dark:disabled:text-gray-500"
                                    @click="submit"
                                >
                                    <LoaderCircle
                                        v-if="submitting"
                                        class="h-4 w-4 animate-spin"
                                    />
                                    <CalendarCheck2 v-else class="h-4 w-4" />
                                    {{
                                        submitting
                                            ? "Scheduling..."
                                            : "Schedule Service"
                                    }}
                                </button>
                            </div>

                            <p
                                v-if="!canSchedule"
                                class="mt-2.5 text-center text-[11px] text-muted dark:text-gray-400"
                            >
                                {{ missingRequirementLabel }}
                            </p>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import {
    CalendarCheck2,
    CalendarDays,
    ChevronDown,
    CircleAlert,
    ClipboardList,
    ClipboardX,
    Clock,
    LoaderCircle,
    PackageSearch,
    Search,
    X,
} from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import { scheduleService } from "~/api/schedule/ScheduleService";
import { serviceService } from "~/api/service/ServiceService";
import { useToast } from "~/composables/useToast";
import {
    format24To12,
    formatDate,
    generateAvailableAmPmTimes,
    getLocalDateStr,
} from "~/utils/time";
import { formatCurrency } from "~/utils/currency";
import type { Service } from "~/types/service";
import type { BookedService } from "~/types/booking";

const props = defineProps<{
    open: boolean;
    patientUuid: string;
    patientName?: string | null;
    branchUuid: string;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "scheduled"): void;
}>();

const { success, error } = useToast();

const services = ref<Service[]>([]);
const loading = ref(false);
const loadError = ref("");
const submitting = ref(false);
const searchQuery = ref("");
const selectedService = ref<BookedService | null>(null);
const collapsedCategories = reactive<Record<string, boolean>>({});

const todayStr = getLocalDateStr(new Date());

const form = reactive({
    date: todayStr,
    preferred_time: "",
    note: "",
});

const availableTimeSlots = computed(() =>
    generateAvailableAmPmTimes(form.date),
);

const displayTime = computed(() =>
    availableTimeSlots.value.length ? "Select time" : "No available time slots",
);

const filteredServices = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) return services.value;

    return services.value.filter(
        (s) =>
            s.service_name.toLowerCase().includes(query) ||
            s.category_name?.toLowerCase().includes(query),
    );
});

const groupedServices = computed(() => {
    const groups = new Map<string, Service[]>();

    for (const service of filteredServices.value) {
        const key = service.category_name || "Other";
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key)!.push(service);
    }

    return Array.from(groups.entries())
        .sort((a, b) => a[0].localeCompare(b[0]))
        .map(([category, items]) => ({
            category,
            items,
            selectedCount: items.filter((s) => isSelected(s)).length,
        }));
});

const totalPrice = computed(() => Number(selectedService.value?.price ?? 0));

const canSchedule = computed(
    () => !!form.date && !!form.preferred_time && !!selectedService.value,
);

const missingRequirementLabel = computed(() => {
    const missing: string[] = [];
    if (!form.date) missing.push("a date");
    if (!form.preferred_time) missing.push("a time");
    if (!selectedService.value) missing.push("a service");
    if (!missing.length) return "";
    return `Select ${missing.join(", ")} to continue.`;
});

function isCollapsed(category: string) {
    return !!collapsedCategories[category];
}

function toggleCategory(category: string) {
    collapsedCategories[category] = !collapsedCategories[category];
}

function isSelected(service: Service) {
    return selectedService.value?.service_id === service.service_id;
}

function toggleService(service: Service) {
    if (!service.is_available || service.service_id == null) return;

    if (isSelected(service)) {
        selectedService.value = null;
        return;
    }

    selectedService.value = {
        service_id: service.service_id,
        service_name: service.service_name,
        price: Number(service.price),
    };
}

function resetForm() {
    form.date = todayStr;
    form.preferred_time = "";
    form.note = "";
    searchQuery.value = "";
    selectedService.value = null;

    for (const key of Object.keys(collapsedCategories)) {
        delete collapsedCategories[key];
    }
}

async function load() {
    loading.value = true;
    loadError.value = "";
    services.value = [];

    try {
        const res: any = await serviceService.list({
            branch_uuid: props.branchUuid,
            type: "facility",
        });

        services.value = res.services ?? res.data ?? [];
    } catch (err: any) {
        loadError.value =
            err?.message ?? "Unable to load the facility services.";
    } finally {
        loading.value = false;
    }
}

async function submit() {
    if (!canSchedule.value || !selectedService.value || submitting.value) {
        return;
    }

    submitting.value = true;

    try {
        const res: any = await scheduleService.create({
            branch_uuid: props.branchUuid,
            patient_uuid: props.patientUuid,
            ...form,
            services: [selectedService.value],
        });

        success(res?.message ?? "Service scheduled successfully.");
        resetForm();
        emit("scheduled");
        close();
    } catch (err: any) {
        error(err?.message ?? "Unable to schedule this service.");
    } finally {
        submitting.value = false;
    }
}

function close() {
    emit("close");
}

watch(
    () => props.open,
    (open) => {
        if (!open) return;

        resetForm();
        load();
    },
);
</script>
