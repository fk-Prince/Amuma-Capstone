<template>
    <Teleport to="body">
        <div
            v-if="open && employee"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
            @click.self="emit('close')"
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-label="Employee notes"
                class="flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary"
            >
                <div
                    class="flex items-start justify-between gap-3 border-b border-slate-100 px-6 py-4 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p
                            class="text-[10px] font-semibold uppercase tracking-[0.14em] text-primary"
                        >
                            Notes
                        </p>

                        <h3
                            class="mt-1 truncate text-lg font-semibold text-slate-800 dark:text-white"
                        >
                            {{ employee.first_name }} {{ employee.last_name }}
                        </h3>
                    </div>

                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                        aria-label="Close"
                        @click="emit('close')"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-6 py-5">
                    <div
                        class="space-y-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4 dark:border-white/10 dark:bg-white/5"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                        >
                            Add a note
                        </p>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <Combobox
                                v-model="scheduleCode"
                                label="Homecare schedule (optional)"
                                placeholder="No schedule"
                                search-placeholder="Search schedule"
                                :items="scheduleItems"
                            />

                            <Combobox
                                v-model="patientUuid"
                                label="Admitted patient (optional)"
                                placeholder="No patient"
                                search-placeholder="Search patient"
                                :items="patientItems"
                                :disabled="!!scheduleCode"
                            />
                        </div>

                        <button
                            v-if="scheduleCode || patientUuid"
                            type="button"
                            class="text-xs font-medium text-primary hover:underline"
                            @click="clearLinks"
                        >
                            Clear selection
                        </button>

                        <p
                            v-if="scheduleCode"
                            class="text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            The note is also added to that schedule's patient.
                        </p>

                        <EmployeeNoteForm
                            ref="form"
                            :saving="saving"
                            @submit="add"
                        />
                    </div>

                    <div>
                        <p
                            class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                        >
                            Notes{{ meta ? ` (${meta.total})` : "" }}
                        </p>

                        <div
                            v-if="loading"
                            class="h-24 animate-pulse rounded-xl bg-slate-100 dark:bg-white/10"
                        />

                        <template v-else>
                            <EmployeeNoteList
                                :notes="notes"
                                :branch-uuid="branchUuid"
                                can-write
                                show-patient
                                show-schedule
                                empty-text="No notes for this employee yet."
                                @updated="onUpdated"
                                @removed="onRemoved"
                            />

                            <button
                                v-if="meta && meta.current_page < meta.last_page"
                                type="button"
                                :disabled="loadingMore"
                                class="mt-3 w-full rounded-lg border border-slate-200 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-50 disabled:opacity-50 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
                                @click="loadMore"
                            >
                                {{ loadingMore ? "Loading..." : "Load more" }}
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { X } from "lucide-vue-next";
import Combobox from "~/components/ui/Combobox.vue";
import EmployeeNoteForm from "~/components/sections/app/Employee/EmployeeNoteForm.vue";
import EmployeeNoteList from "~/components/sections/app/Employee/EmployeeNoteList.vue";
import { employeeNoteService } from "~/api/employee-note/EmployeeNoteService";
import { useToast } from "~/composables/useToast";
import { formatDate } from "~/utils/time";
import type { Employee } from "~/types/employee";
import type {
    EmployeeNote,
    EmployeeNoteMeta,
    NotePatientOption,
    NoteScheduleOption,
} from "~/types/employee-note";

const props = defineProps<{
    open: boolean;
    employee: Employee | null;
    branchUuid: string;
}>();

const emit = defineEmits<{
    (e: "close"): void;
}>();

const { success, error } = useToast();

const notes = ref<EmployeeNote[]>([]);
const meta = ref<EmployeeNoteMeta | null>(null);
const schedules = ref<NoteScheduleOption[]>([]);
const patients = ref<NotePatientOption[]>([]);

const loading = ref(false);
const loadingMore = ref(false);
const saving = ref(false);

const scheduleCode = ref<string | null>(null);
const patientUuid = ref<string | null>(null);
const form = ref<{ clear: () => void } | null>(null);

const scheduleItems = computed(() =>
    schedules.value.map((schedule) => ({
        label: [
            schedule.schedule_code,
            formatDate(schedule.scheduled_at),
            schedule.patient_name,
        ]
            .filter(Boolean)
            .join(" · "),
        value: schedule.schedule_code,
    })),
);

const patientItems = computed(() =>
    patients.value.map((patient) => ({
        label: patient.patient_name,
        value: patient.patient_uuid,
    })),
);

watch(scheduleCode, (code) => {
    if (code) patientUuid.value = null;
});

watch(
    () => [props.open, props.employee?.uuid] as const,
    async ([isOpen, employeeUuid]) => {
        if (!isOpen || !employeeUuid) return;

        notes.value = [];
        meta.value = null;
        schedules.value = [];
        patients.value = [];
        scheduleCode.value = null;
        patientUuid.value = null;
        loading.value = true;

        try {
            const res = await employeeNoteService.list({
                branch_uuid: props.branchUuid,
                employee_uuid: employeeUuid,
            });

            notes.value = res.data ?? [];
            meta.value = res.meta ?? null;
            schedules.value = res.schedules ?? [];
            patients.value = res.patients ?? [];
        } catch (err: any) {
            error(err?.message ?? "Couldn't load the notes.");
        } finally {
            loading.value = false;
        }
    },
    { immediate: true },
);

function clearLinks() {
    scheduleCode.value = null;
    patientUuid.value = null;
}

async function loadMore() {
    if (!props.employee || !meta.value) return;

    loadingMore.value = true;

    try {
        const res = await employeeNoteService.list({
            branch_uuid: props.branchUuid,
            employee_uuid: props.employee.uuid,
            page: meta.value.current_page + 1,
        });

        notes.value = [...notes.value, ...(res.data ?? [])];
        meta.value = res.meta ?? meta.value;
    } catch (err: any) {
        error(err?.message ?? "Couldn't load more notes.");
    } finally {
        loadingMore.value = false;
    }
}

async function add(text: string) {
    if (!props.employee) return;

    saving.value = true;

    try {
        const res = await employeeNoteService.create({
            branch_uuid: props.branchUuid,
            employee_uuid: props.employee.uuid,
            note: text,
            ...(scheduleCode.value && { schedule_code: scheduleCode.value }),
            ...(patientUuid.value && { patient_uuid: patientUuid.value }),
        });

        notes.value = [res.data, ...notes.value];

        if (meta.value) meta.value = { ...meta.value, total: meta.value.total + 1 };

        form.value?.clear();
        clearLinks();
        success(res.message ?? "Note added.");
    } catch (err: any) {
        error(err?.message ?? "Couldn't add the note.");
    } finally {
        saving.value = false;
    }
}

function onUpdated(note: EmployeeNote) {
    notes.value = notes.value.map((existing) =>
        existing.uuid === note.uuid ? note : existing,
    );
}

function onRemoved(uuid: string) {
    notes.value = notes.value.filter((note) => note.uuid !== uuid);

    if (meta.value) {
        meta.value = { ...meta.value, total: Math.max(0, meta.value.total - 1) };
    }
}
</script>
