<template>
    <div>
        <ul v-if="notes.length" class="space-y-2">
            <li
                v-for="note in notes"
                :key="note.uuid"
                class="rounded-xl border border-slate-200 bg-white p-3 dark:border-white/10 dark:bg-secondary"
            >
                <EmployeeNoteForm
                    v-if="editingUuid === note.uuid"
                    :initial="note.note"
                    :saving="saving"
                    submit-label="Save"
                    show-cancel
                    @submit="save(note, $event)"
                    @cancel="editingUuid = null"
                />

                <template v-else>
                    <p
                        class="whitespace-pre-wrap text-sm text-slate-700 dark:text-gray-300"
                    >
                        {{ note.note }}
                    </p>

                    <div class="mt-2 flex items-start justify-between gap-3">
                        <p
                            class="flex min-w-0 flex-wrap items-center gap-x-1.5 gap-y-1 text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            <span
                                v-if="showEmployee && note.employee_name"
                                class="font-medium text-slate-500 dark:text-gray-400"
                            >
                                {{ note.employee_name }}
                            </span>

                            <span
                                v-if="showPatient && note.patient_name"
                                class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-500 dark:bg-white/10 dark:text-gray-400"
                            >
                                {{ note.patient_name }}
                            </span>

                            <span
                                v-if="showSchedule && note.schedule_code"
                                class="rounded-full bg-primary/10 px-2 py-0.5 font-medium text-primary"
                            >
                                {{ note.schedule_code }}
                            </span>

                            <span>
                                <template v-if="note.recorded_by">
                                    by {{ note.recorded_by }} ·
                                </template>
                                {{ stringToDateTime(note.created_at) }}
                                <template v-if="isEdited(note)">
                                    (edited)
                                </template>
                            </span>
                        </p>

                        <div
                            v-if="canWrite"
                            class="flex shrink-0 items-center gap-1"
                        >
                            <button
                                type="button"
                                title="Edit note"
                                aria-label="Edit note"
                                class="rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                                @click="editingUuid = note.uuid"
                            >
                                <Pencil class="h-3.5 w-3.5" />
                            </button>

                            <button
                                type="button"
                                title="Delete note"
                                aria-label="Delete note"
                                class="rounded-md p-1 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:text-gray-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
                                @click="deleting = note"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </template>
            </li>
        </ul>

        <p
            v-else
            class="rounded-xl border border-dashed border-slate-200 px-3 py-4 text-center text-xs text-slate-400 dark:border-white/10 dark:text-gray-500"
        >
            {{ emptyText }}
        </p>

        <ConfirmDialog
            :open="!!deleting"
            title="Delete this note?"
            message="This action cannot be undone."
            confirm-label="Delete"
            variant="danger"
            :loading="saving"
            @confirm="remove"
            @cancel="deleting = null"
        />
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { Pencil, Trash2 } from "lucide-vue-next";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import EmployeeNoteForm from "~/components/sections/app/Employee/EmployeeNoteForm.vue";
import { employeeNoteService } from "~/api/employee-note/EmployeeNoteService";
import { useToast } from "~/composables/useToast";
import { stringToDateTime } from "~/utils/time";
import type { EmployeeNote } from "~/types/employee-note";

const props = withDefaults(
    defineProps<{
        notes: EmployeeNote[];
        branchUuid: string;
        canWrite?: boolean;
        showEmployee?: boolean;
        showPatient?: boolean;
        showSchedule?: boolean;
        emptyText?: string;
    }>(),
    { emptyText: "No notes yet." },
);

const emit = defineEmits<{
    (e: "updated", note: EmployeeNote): void;
    (e: "removed", uuid: string): void;
}>();

const { success, error } = useToast();

const editingUuid = ref<string | null>(null);
const deleting = ref<EmployeeNote | null>(null);
const saving = ref(false);

function isEdited(note: EmployeeNote) {
    return note.updated_at !== note.created_at;
}

async function save(note: EmployeeNote, text: string) {
    saving.value = true;

    try {
        const res = await employeeNoteService.update(note.uuid, {
            branch_uuid: props.branchUuid,
            note: text,
        });

        emit("updated", res.data);
        editingUuid.value = null;
        success(res.message ?? "Note updated.");
    } catch (err: any) {
        error(err?.message ?? "Couldn't update the note.");
    } finally {
        saving.value = false;
    }
}

async function remove() {
    if (!deleting.value) return;

    saving.value = true;

    try {
        const res = await employeeNoteService.remove(deleting.value.uuid, {
            branch_uuid: props.branchUuid,
        });

        emit("removed", deleting.value.uuid);
        deleting.value = null;
        success(res.message ?? "Note deleted.");
    } catch (err: any) {
        error(err?.message ?? "Couldn't delete the note.");
    } finally {
        saving.value = false;
    }
}
</script>
