<template>
    <div class="space-y-3 pb-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3
                    class="text-sm font-semibold text-slate-800 dark:text-white"
                >
                    Care notes
                </h3>

                <p class="mt-0.5 text-xs text-slate-400 dark:text-gray-500">
                    What staff did for this patient. Notes written here are
                    about the patient only; notes from a schedule also show
                    here with their schedule code.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary/90"
                @click="addOpen = true"
            >
                <Plus class="h-3.5 w-3.5" />
                Add note
            </button>
        </div>

        <AddCareNoteModal
            ref="form"
            :open="addOpen"
            :saving="saving"
            @close="addOpen = false"
            @submit="add"
        />

        <div
            v-if="loading"
            class="h-24 animate-pulse rounded-xl bg-slate-100 dark:bg-white/10"
        />

        <template v-else>
            <EmployeeNoteList
                :notes="notes"
                :branch-uuid="branchUuid"
                show-employee
                show-schedule
                empty-text="No care notes yet."
                @updated="onUpdated"
                @removed="onRemoved"
            />

            <button
                v-if="meta && meta.current_page < meta.last_page"
                type="button"
                :disabled="loadingMore"
                class="w-full rounded-lg border border-slate-200 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-50 disabled:opacity-50 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
                @click="loadMore"
            >
                {{ loadingMore ? "Loading..." : "Load more" }}
            </button>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { Plus } from "lucide-vue-next";
import AddCareNoteModal from "~/components/sections/app/Patient/AddCareNoteModal.vue";
import EmployeeNoteList from "~/components/sections/app/Employee/EmployeeNoteList.vue";
import { employeeNoteService } from "~/api/employee-note/EmployeeNoteService";
import { useToast } from "~/composables/useToast";
import type { EmployeeNote, EmployeeNoteMeta } from "~/types/employee-note";

const props = defineProps<{
    patientUuid: string;
    branchUuid: string;
}>();

const { success, error } = useToast();

const notes = ref<EmployeeNote[]>([]);
const meta = ref<EmployeeNoteMeta | null>(null);
const loading = ref(false);
const loadingMore = ref(false);
const saving = ref(false);
const form = ref<{ clear: () => void } | null>(null);
const addOpen = ref(false);

async function add(text: string) {
    saving.value = true;

    try {
        const res = await employeeNoteService.create({
            branch_uuid: props.branchUuid,
            patient_uuid: props.patientUuid,
            note: text,
        });

        notes.value = [res.data, ...notes.value];

        if (meta.value) {
            meta.value = { ...meta.value, total: meta.value.total + 1 };
        }

        form.value?.clear();
        addOpen.value = false;
        success(res.message ?? "Note added.");
    } catch (err: any) {
        error(err?.message ?? "Couldn't add the note.");
    } finally {
        saving.value = false;
    }
}

async function fetchPage(page: number) {
    return await employeeNoteService.list({
        branch_uuid: props.branchUuid,
        patient_uuid: props.patientUuid,
        page,
    });
}

watch(
    () => props.patientUuid,
    async () => {
        loading.value = true;

        try {
            const res = await fetchPage(1);

            notes.value = res.data ?? [];
            meta.value = res.meta ?? null;
        } catch (err: any) {
            error(err?.message ?? "Couldn't load the care notes.");
        } finally {
            loading.value = false;
        }
    },
    { immediate: true },
);

async function loadMore() {
    if (!meta.value) return;

    loadingMore.value = true;

    try {
        const res = await fetchPage(meta.value.current_page + 1);

        notes.value = [...notes.value, ...(res.data ?? [])];
        meta.value = res.meta ?? meta.value;
    } catch (err: any) {
        error(err?.message ?? "Couldn't load more notes.");
    } finally {
        loadingMore.value = false;
    }
}

function onUpdated(note: EmployeeNote) {
    notes.value = notes.value.map((existing) =>
        existing.uuid === note.uuid ? note : existing,
    );
}

function onRemoved(uuid: string) {
    notes.value = notes.value.filter((note) => note.uuid !== uuid);
}
</script>
