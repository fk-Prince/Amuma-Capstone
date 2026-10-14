<template>
    <div v-if="notes.length || canAdd" class="mt-2 border-t border-slate-200 pt-2 dark:border-white/10">
        <button
            type="button"
            class="inline-flex items-center gap-1.5 text-xs font-medium text-primary hover:underline"
            @click="open = !open"
        >
            <NotebookPen class="h-3.5 w-3.5" />
            {{ notes.length ? `Notes (${notes.length})` : "Add note" }}
            <ChevronDown
                class="h-3.5 w-3.5 transition"
                :class="{ 'rotate-180': open }"
            />
        </button>

        <div v-if="open" class="mt-2 space-y-3">
            <EmployeeNoteList
                v-if="notes.length"
                :notes="notes"
                :branch-uuid="branchUuid"
                :can-write="canWrite"
                @updated="emit('updated', $event)"
                @removed="emit('removed', $event)"
            />

            <EmployeeNoteForm
                v-if="canAdd"
                ref="form"
                :saving="saving"
                @submit="add"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { ChevronDown, NotebookPen } from "lucide-vue-next";
import EmployeeNoteForm from "~/components/sections/app/Employee/EmployeeNoteForm.vue";
import EmployeeNoteList from "~/components/sections/app/Employee/EmployeeNoteList.vue";
import { employeeNoteService } from "~/api/employee-note/EmployeeNoteService";
import { useToast } from "~/composables/useToast";
import type { EmployeeNote } from "~/types/employee-note";

const props = defineProps<{
    notes: EmployeeNote[];
    branchUuid: string;
    employeeUuid: string | null;
    scheduleCode: string;
    canWrite: boolean;
}>();

const emit = defineEmits<{
    (e: "created", note: EmployeeNote): void;
    (e: "updated", note: EmployeeNote): void;
    (e: "removed", uuid: string): void;
}>();

const { success, error } = useToast();

const open = ref(false);
const saving = ref(false);
const form = ref<{ clear: () => void } | null>(null);

const canAdd = computed(() => props.canWrite && !!props.employeeUuid);

async function add(text: string) {
    saving.value = true;

    try {
        const res = await employeeNoteService.create({
            branch_uuid: props.branchUuid,
            employee_uuid: props.employeeUuid,
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
