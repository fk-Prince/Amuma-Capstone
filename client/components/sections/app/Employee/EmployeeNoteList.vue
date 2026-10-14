<template>
    <div>
        <ul
            v-if="notes.length"
            :class="grid ? 'grid items-start gap-3 sm:grid-cols-2' : 'space-y-2'"
        >
            <li
                v-for="note in notes"
                :key="note.uuid"
                class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-secondary"
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
                    <div
                        v-if="hasChips(note) || note.is_private"
                        class="mb-3 flex flex-wrap items-center gap-1.5"
                    >
                        <span
                            v-if="showEmployee && note.employee_name"
                            class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 py-0.5 pl-0.5 pr-2.5 text-[11px] font-semibold text-slate-600 dark:bg-white/10 dark:text-gray-300"
                        >
                            <span
                                class="flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[9px] font-bold text-white"
                            >
                                {{ initials(note.employee_name) }}
                            </span>
                            {{ note.employee_name }}
                        </span>

                        <span
                            v-if="showPatient && note.patient_name"
                            class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                        >
                            {{ note.patient_name }}
                        </span>

                        <span
                            v-if="showSchedule && note.schedule_code"
                            class="rounded-full bg-primary/10 px-2.5 py-0.5 text-[11px] font-medium text-primary"
                        >
                            {{ note.schedule_code }}
                        </span>

                        <span
                            v-if="note.is_private"
                            class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
                        >
                            <Lock class="h-3 w-3" />
                            Private
                        </span>
                    </div>

                    <p
                        class="whitespace-pre-wrap text-sm leading-6 text-slate-700 dark:text-gray-300"
                    >
                        {{ note.note }}
                    </p>

                    <div
                        class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-2.5 dark:border-white/10"
                    >
                        <p
                            class="flex min-w-0 items-center gap-1.5 text-[11px] text-slate-400 dark:text-gray-500"
                        >
                            <Clock class="h-3 w-3 shrink-0" />
                            {{ stringToDateTime(note.created_at) }}
                            <template v-if="isEdited(note)"> · edited </template>
                        </p>

                        <div
                            v-if="isMine(note)"
                            class="flex shrink-0 items-center gap-1 opacity-60 transition group-hover:opacity-100"
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
import { Clock, Lock, Pencil, Trash2 } from "lucide-vue-next";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import EmployeeNoteForm from "~/components/sections/app/Employee/EmployeeNoteForm.vue";
import { employeeNoteService } from "~/api/employee-note/EmployeeNoteService";
import { useAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";
import { initials } from "~/utils/user";
import { stringToDateTime } from "~/utils/time";
import type { EmployeeNote } from "~/types/employee-note";

const props = withDefaults(
    defineProps<{
        notes: EmployeeNote[];
        branchUuid: string;
        showEmployee?: boolean;
        showPatient?: boolean;
        showSchedule?: boolean;
        grid?: boolean;
        emptyText?: string;
    }>(),
    { emptyText: "No notes yet." },
);

const emit = defineEmits<{
    (e: "updated", note: EmployeeNote): void;
    (e: "removed", uuid: string): void;
}>();

const { success, error } = useToast();
const authUser = useAuthUser();

const editingUuid = ref<string | null>(null);
const deleting = ref<EmployeeNote | null>(null);
const saving = ref(false);

function isMine(note: EmployeeNote) {
    return !!note.employee_uuid && note.employee_uuid === authUser.value?.uuid;
}

function isEdited(note: EmployeeNote) {
    return note.updated_at !== note.created_at;
}

function hasChips(note: EmployeeNote) {
    return !!(
        (props.showEmployee && note.employee_name) ||
        (props.showPatient && note.patient_name) ||
        (props.showSchedule && note.schedule_code)
    );
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
