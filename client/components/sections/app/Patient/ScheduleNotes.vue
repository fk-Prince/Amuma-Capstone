<template>
    <div
        class="rounded-xl border border-slate-100 p-3 dark:border-white/10"
    >
        <p class="text-xs text-slate-400 dark:text-gray-500">
            Notes{{ notes.length ? ` (${notes.length})` : "" }}
        </p>

        <div class="mt-2 space-y-3">
            <EmployeeNoteList
                v-if="notes.length"
                :notes="notes"
                :branch-uuid="branchUuid"
                show-employee
                @updated="emit('updated', $event)"
                @removed="emit('removed', $event)"
            />

            <EmployeeNoteForm
                ref="form"
                :saving="saving"
                placeholder="Write what you did for this schedule..."
                @submit="add"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import EmployeeNoteForm from "~/components/sections/app/Employee/EmployeeNoteForm.vue";
import EmployeeNoteList from "~/components/sections/app/Employee/EmployeeNoteList.vue";
import { employeeNoteService } from "~/api/employee-note/EmployeeNoteService";
import { useToast } from "~/composables/useToast";
import type { EmployeeNote } from "~/types/employee-note";

const props = defineProps<{
    notes: EmployeeNote[];
    branchUuid: string;
    scheduleCode: string;
}>();

const emit = defineEmits<{
    (e: "created", note: EmployeeNote): void;
    (e: "updated", note: EmployeeNote): void;
    (e: "removed", uuid: string): void;
}>();

const { success, error } = useToast();

const saving = ref(false);
const form = ref<{ clear: () => void } | null>(null);

async function add(text: string) {
    saving.value = true;

    try {
        const res = await employeeNoteService.create({
            branch_uuid: props.branchUuid,
            schedule_code: props.scheduleCode,
            note: text,
        });

        emit("created", res.data);
        form.value?.clear();
        success(res.message ?? "Note added.");
    } catch (err: any) {
        error(err?.message ?? "Couldn't add the note.");
    } finally {
        saving.value = false;
    }
}
</script>
