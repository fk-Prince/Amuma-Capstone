<template>
    <section
        class="h-full overflow-hidden rounded-2xl bg-white p-6 shadow-sm border border-slate-100 md:p-8"
        :class="{ 'animate-pulse': loading }"
    >
        <div class="mb-8">
            <template v-if="loading">
                <div class="h-6 w-40 rounded bg-slate-200 mb-2" />
                <div class="h-3 w-64 rounded bg-slate-200" />
            </template>

            <template v-else>
                <h2 class="text-xl font-semibold text-primary">
                    In-house Facility Services
                </h2>

                <p class="mt-1 text-sm text-muted">
                    Assist the patient by selecting and scheduling the
                    appropriate medical service.
                </p>
            </template>
        </div>

        <div
            class="grid h-[calc(100vh-260px)] items-start gap-8 overflow-hidden md:grid-cols-[minmax(0,3fr)_2fr]"
        >
            <div class="h-full overflow-y-auto pr-2">
                <div class="space-y-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <BaseInput
                            :model-value="form.date"
                            label="Select Schedule Date"
                            mode="date"
                            :min="todayStr"
                            required
                            @update:model-value="update('date', $event)"
                        />

                        <Combobox
                            :model-value="form.preferred_time"
                            :placeholder="displayTime"
                            label="Preferred Time"
                            :items="availableTimeSlots"
                            :error="errors.preferred_time"
                            required
                            @update:model-value="
                                update('preferred_time', $event)
                            "
                        />
                    </div>
                    <div class="mt-4">
                        <BaseInput
                            :model-value="form.note"
                            label="Note"
                            mode="textarea"
                            placeholder="Add any additional notes..."
                            @update:model-value="update('note', $event)"
                        />
                    </div>

                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <label class="text-sm font-semibold text-slate-700">
                                Select Service
                                <span class="text-danger">*</span>
                            </label>

                            <span
                                v-if="selectedServices.length"
                                class="rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                            >
                                {{ selectedServices.length }} selected
                            </span>
                        </div>

                        <div class="relative mb-4">
                            <Search
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search services or categories..."
                                class="w-full rounded-lg border border-slate-200 py-2 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                            />
                        </div>

                        <p
                            v-if="errors.services"
                            class="mb-3 text-xs text-red-500"
                        >
                            {{ errors.services }}
                        </p>

                        <div
                            v-if="!groupedServices.length"
                            class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-slate-200 py-12 text-center"
                        >
                            <PackageSearch class="h-6 w-6 text-slate-300" />
                            <p class="text-sm font-medium text-slate-500">
                                No services found
                            </p>
                            <p class="text-xs text-slate-400">
                                Try a different search term.
                            </p>
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="group in groupedServices"
                                :key="group.category"
                                class="overflow-hidden rounded-xl border border-slate-100"
                            >
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between gap-3 bg-slate-50 px-4 py-3 text-left transition hover:bg-slate-100"
                                    @click="toggleCategory(group.category)"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <span
                                            class="text-sm font-semibold text-slate-700"
                                        >
                                            {{ group.category }}
                                        </span>
                                        <span
                                            class="rounded-full bg-white px-2 py-0.5 text-[11px] font-medium text-slate-400 ring-1 ring-slate-200"
                                        >
                                            {{ group.items.length }}
                                        </span>
                                        <span
                                            v-if="group.selectedCount"
                                            class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary"
                                        >
                                            {{ group.selectedCount }} chosen
                                        </span>
                                    </div>

                                    <ChevronDown
                                        class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
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
                                        :disabled="!service.is_available"
                                        class="flex w-full items-center justify-between gap-3 rounded-lg border p-3 text-left transition disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="
                                            isSelected(service)
                                                ? 'border-primary bg-primary/5 ring-1 ring-primary/20'
                                                : 'border-slate-200 hover:border-primary/40 hover:bg-slate-50'
                                        "
                                        @click="toggleService(service)"
                                    >
                                        <div
                                            class="flex min-w-0 items-center gap-3"
                                        >
                                            <span
                                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition"
                                                :class="
                                                    isSelected(service)
                                                        ? 'border-primary bg-primary text-white'
                                                        : 'border-slate-300 bg-white'
                                                "
                                            >
                                                <Check
                                                    v-if="isSelected(service)"
                                                    class="h-3 w-3"
                                                />
                                            </span>

                                            <div class="min-w-0">
                                                <p
                                                    class="truncate text-sm font-medium text-slate-800"
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
                                                    class="mt-0.5 text-[11px] capitalize text-slate-400"
                                                >
                                                    {{ service.type_formatted }}
                                                </p>
                                            </div>
                                        </div>

                                        <span
                                            class="shrink-0 text-sm font-semibold text-primary"
                                        >
                                            ₱{{
                                                Number(
                                                    service.price,
                                                ).toLocaleString()
                                            }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <aside
                class="flex h-full flex-col overflow-hidden rounded-2xl border border-slate-100 bg-slate-50"
            >
                <div class="border-b border-slate-100 bg-white p-6">
                    <div class="flex items-center gap-2">
                        <ClipboardList class="h-4 w-4 text-primary" />
                        <h3 class="font-semibold text-slate-800">
                            Service Summary
                        </h3>
                    </div>
                    <p class="mt-1 text-xs text-muted">
                        Review the details before confirming.
                    </p>
                </div>

                <div class="flex-1 overflow-y-auto p-6">
                    <div class="space-y-2.5">
                        <div
                            class="flex items-center gap-3 rounded-lg bg-white p-3 shadow-sm ring-1 ring-slate-100"
                        >
                            <CalendarDays
                                class="h-4 w-4 shrink-0 text-primary"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] text-muted">Date</p>
                                <p
                                    class="truncate text-sm font-medium text-slate-800"
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
                                class="h-3.5 w-3.5 shrink-0 text-amber-500"
                            />
                        </div>

                        <div
                            class="flex items-center gap-3 rounded-lg bg-white p-3 shadow-sm ring-1 ring-slate-100"
                        >
                            <Clock class="h-4 w-4 shrink-0 text-primary" />
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] text-muted">Time</p>
                                <p
                                    class="truncate text-sm font-medium text-slate-800"
                                >
                                    {{ form.preferred_time || "Not selected" }}
                                </p>
                            </div>
                            <CircleAlert
                                v-if="!form.preferred_time"
                                class="h-3.5 w-3.5 shrink-0 text-amber-500"
                            />
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="mb-2.5 flex items-center justify-between">
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-muted"
                            >
                                Service
                            </p>
                            <span
                                v-if="selectedServices.length"
                                class="text-xs text-muted"
                            >
                                {{ selectedServices.length }} item{{
                                    selectedServices.length === 1 ? "" : "s"
                                }}
                            </span>
                        </div>

                        <div
                            v-if="!selectedServices.length"
                            class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-slate-200 bg-white py-8 text-center"
                        >
                            <ClipboardX class="h-5 w-5 text-slate-300" />
                            <p class="text-xs text-muted">
                                No service selected yet.
                            </p>
                        </div>

                        <TransitionGroup
                            v-else
                            tag="div"
                            class="space-y-2"
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="opacity-0 -translate-x-1"
                            leave-active-class="transition duration-150 ease-in absolute"
                            leave-to-class="opacity-0 translate-x-1"
                        >
                            <div
                                v-for="service in selectedServices"
                                :key="service.service_id"
                                class="group flex items-center justify-between gap-2 rounded-lg bg-white p-3 shadow-sm ring-1 ring-slate-100 transition hover:ring-primary/30"
                            >
                                <p
                                    class="min-w-0 truncate text-sm font-medium text-slate-800"
                                >
                                    {{ service.service_name }}
                                </p>

                                <div class="flex shrink-0 items-center gap-3">
                                    <span
                                        class="text-sm font-semibold tabular-nums text-slate-800"
                                    >
                                        ₱{{
                                            Number(
                                                service.price,
                                            ).toLocaleString()
                                        }}
                                    </span>
                                    <button
                                        type="button"
                                        class="text-slate-400 transition hover:text-red-500"
                                        aria-label="Remove service"
                                        @click="
                                            removeService(service.service_id)
                                        "
                                    >
                                        <X class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </TransitionGroup>
                    </div>
                </div>

                <div class="border-t border-slate-100 bg-white p-6">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted">
                            Subtotal ({{ selectedServices.length }} item{{
                                selectedServices.length === 1 ? "" : "s"
                            }})
                        </span>
                        <span class="font-medium tabular-nums text-slate-700">
                            ₱{{ totalPrice.toLocaleString() }}
                        </span>
                    </div>

                    <div class="mt-1.5 flex items-center justify-between">
                        <span class="text-sm font-semibold text-slate-800"
                            >Total</span
                        >
                        <span
                            class="text-xl font-bold tabular-nums text-primary"
                        >
                            ₱{{ totalPrice.toLocaleString() }}
                        </span>
                    </div>

                    <button
                        type="button"
                        :disabled="!canSchedule || submitLoading"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3 text-sm font-semibold text-white transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                        @click="
                            $emit('schedule', {
                                form,
                                services: selectedServices,
                            })
                        "
                    >
                        <LoaderCircle
                            v-if="submitLoading"
                            class="h-4 w-4 animate-spin"
                        />

                        <CalendarCheck2 v-else class="h-4 w-4" />

                        {{
                            submitLoading ? "Scheduling..." : "Schedule Service"
                        }}
                    </button>

                    <p
                        v-if="!canSchedule"
                        class="mt-2.5 text-center text-[11px] text-muted"
                    >
                        {{ missingRequirementLabel }}
                    </p>
                </div>
            </aside>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import {
    Check,
    ChevronDown,
    CircleAlert,
    ClipboardList,
    ClipboardX,
    PackageSearch,
    Search,
    CalendarDays,
    Clock,
    CalendarCheck2,
    X,
    LoaderCircle,
} from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import { formatDate, getLocalDateStr, getTimeSlots } from "~/utils/time";

import { generateAvailableAmPmTimes } from "~/utils/time-slot";

import type { PatientRetrieve } from "~/types/patient";
import type { Service } from "~/types/service";
import type { BookedService } from "~/types/booking";

const props = defineProps<{
    patient: PatientRetrieve;
    services: Service[];
    submitLoading?: boolean;
}>();

const emit = defineEmits<{
    (
        e: "schedule",
        payload: { form: typeof form.value; services: BookedService[] },
    ): void;
}>();

const loading = ref(false);
const selectedServices = ref<BookedService[]>([]);
const searchQuery = ref("");
const todayStr = getLocalDateStr(new Date());
const collapsedCategories = reactive<Record<string, boolean>>({});

const form = ref({
    date: getLocalDateStr(new Date()),
    preferred_time: "",
    note: "",
});

const availableTimeSlots = computed(() =>
    generateAvailableAmPmTimes(form.value.date),
);

const displayTime = computed(() =>
    availableTimeSlots.value.length ? "Select time" : "No available time slots",
);
const errors = ref<Record<string, string>>({});

const allTimeSlots = computed(() => getTimeSlots("00:00", "00:00", 1));

function isCollapsed(category: string) {
    return !!collapsedCategories[category];
}

function toggleCategory(category: string) {
    collapsedCategories[category] = !collapsedCategories[category];
}

const filteredServices = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) return props.services;

    return props.services.filter(
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

function isSelected(service: Service) {
    return selectedServices.value.some(
        (s) => s.service_id === service.service_id,
    );
}

function toggleService(service: Service) {
    if (!service.is_available || service.service_id == null) return;

    if (isSelected(service)) {
        removeService(service.service_id);
        return;
    }

    // Only one service may be booked at a time — selecting a new one
    // replaces whatever was previously selected instead of adding to it.
    selectedServices.value = [
        {
            service_id: service.service_id,
            service_name: service.service_name,
            price: Number(service.price),
        },
    ];

    clearError("services");
}

function removeService(serviceId: number) {
    selectedServices.value = selectedServices.value.filter(
        (s) => s.service_id !== serviceId,
    );
}

const totalPrice = computed(() =>
    selectedServices.value.reduce((sum, s) => sum + Number(s.price || 0), 0),
);

const canSchedule = computed(
    () =>
        !!form.value.date &&
        !!form.value.preferred_time &&
        selectedServices.value.length > 0,
);

const missingRequirementLabel = computed(() => {
    const missing: string[] = [];
    if (!form.value.date) missing.push("a date");
    if (!form.value.preferred_time) missing.push("a time");
    if (!selectedServices.value.length) missing.push("at least one service");
    if (!missing.length) return "";
    return `Select ${missing.join(", ")} to continue.`;
});

function update(key: keyof typeof form.value, value: string) {
    form.value[key] = value;
    clearError(key);
}

function clearError(field: string) {
    delete errors.value[field];
}
</script>
