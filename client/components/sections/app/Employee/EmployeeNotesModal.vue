<template>
    <Teleport to="body">
        <div
            v-if="open && employee"
            class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 backdrop-blur-sm sm:items-center sm:p-6"
            @click.self="emit('close')"
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-label="Employee notes"
                class="flex h-[90vh] max-h-[820px] w-full max-w-4xl flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl ring-1 ring-black/5 sm:rounded-3xl dark:bg-secondary"
            >
                <header
                    class="flex shrink-0 items-center gap-4 border-b border-slate-100 px-6 py-5 dark:border-white/10"
                >
                    <img
                        :src="avatar"
                        :alt="name"
                        class="h-12 w-12 shrink-0 rounded-full object-cover ring-2 ring-primary/20"
                    />

                    <div class="min-w-0 flex-1">
                        <p
                            class="text-[10px] font-semibold uppercase tracking-[0.16em] text-primary"
                        >
                            Notes
                        </p>

                        <h3
                            class="truncate text-lg font-semibold text-slate-800 dark:text-white"
                        >
                            {{ name }}
                        </h3>

                        <p
                            class="truncate text-xs capitalize text-slate-400 dark:text-gray-500"
                        >
                            {{ formatRole(employee.role_name) }}
                        </p>
                    </div>

                    <span
                        v-if="meta"
                        class="hidden shrink-0 rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary sm:inline-block"
                    >
                        {{ meta.total }}
                        {{ meta.total === 1 ? "note" : "notes" }}
                    </span>

                    <button
                        type="button"
                        class="shrink-0 rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                        aria-label="Close"
                        @click="emit('close')"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </header>

                <div
                    class="min-h-0 flex-1 overflow-y-auto bg-slate-50/70 px-6 py-5 dark:bg-white/[0.03]"
                >
                    <p
                        class="mb-4 flex items-start gap-2 rounded-xl bg-white px-3.5 py-2.5 text-xs leading-5 text-slate-500 ring-1 ring-slate-100 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10"
                    >
                        <Lock class="mt-0.5 h-3.5 w-3.5 shrink-0 text-primary" />

                        <span v-if="isSelf">
                            Notes you write here are private. Only you can see
                            them. Notes you add to a schedule or a patient are
                            shared with the branch staff.
                        </span>

                        <span v-else>
                            Private notes are only visible to
                            {{ employee.first_name }}. You can see the notes
                            {{ employee.first_name }} added to schedules and
                            patients.
                        </span>
                    </p>

                    <div
                        v-if="loading"
                        class="grid gap-3 sm:grid-cols-2"
                    >
                        <div
                            v-for="n in 4"
                            :key="n"
                            class="h-28 animate-pulse rounded-2xl bg-slate-100 dark:bg-white/10"
                        />
                    </div>

                    <div
                        v-else-if="!notes.length"
                        class="flex flex-col items-center justify-center gap-2 py-14 text-center"
                    >
                        <span
                            class="flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary"
                        >
                            <NotebookPen class="h-6 w-6" />
                        </span>

                        <p
                            class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >
                            No notes yet
                        </p>

                        <p
                            class="max-w-xs text-xs text-slate-400 dark:text-gray-500"
                        >
                            <template v-if="isSelf">
                                Write your first private note below.
                            </template>
                            <template v-else>
                                Nothing shared by {{ employee.first_name }}
                                yet.
                            </template>
                        </p>
                    </div>

                    <template v-else>
                        <EmployeeNoteList
                            :notes="notes"
                            :branch-uuid="branchUuid"
                            grid
                            show-patient
                            show-schedule
                            @updated="onUpdated"
                            @removed="onRemoved"
                        />

                        <button
                            v-if="meta && meta.current_page < meta.last_page"
                            type="button"
                            :disabled="loadingMore"
                            class="mx-auto mt-4 block rounded-xl border border-slate-200 bg-white px-5 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:opacity-50 dark:border-white/10 dark:bg-transparent dark:text-gray-300 dark:hover:bg-white/5"
                            @click="loadMore"
                        >
                            {{ loadingMore ? "Loading..." : "Load more" }}
                        </button>
                    </template>
                </div>

                <footer
                    v-if="isSelf"
                    class="shrink-0 border-t border-slate-100 bg-white px-6 py-4 dark:border-white/10 dark:bg-secondary"
                >
                    <p
                        class="mb-2 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                    >
                        <Lock class="h-3 w-3" />
                        Private note
                    </p>

                    <EmployeeNoteForm
                        ref="form"
                        :saving="saving"
                        placeholder="Write a private note for yourself..."
                        @submit="add"
                    />
                </footer>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Lock, NotebookPen, X } from "lucide-vue-next";
import EmployeeNoteForm from "~/components/sections/app/Employee/EmployeeNoteForm.vue";
import EmployeeNoteList from "~/components/sections/app/Employee/EmployeeNoteList.vue";
import { employeeNoteService } from "~/api/employee-note/EmployeeNoteService";
import { useAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";
import { formatRole } from "~/utils/user";
import type { Employee } from "~/types/employee";
import type { EmployeeNote, EmployeeNoteMeta } from "~/types/employee-note";

const props = defineProps<{
    open: boolean;
    employee: Employee | null;
    branchUuid: string;
}>();

const emit = defineEmits<{
    (e: "close"): void;
}>();

const { success, error } = useToast();
const authUser = useAuthUser();

const notes = ref<EmployeeNote[]>([]);
const meta = ref<EmployeeNoteMeta | null>(null);
const loading = ref(false);
const loadingMore = ref(false);
const saving = ref(false);
const form = ref<{ clear: () => void } | null>(null);

const isSelf = computed(
    () => !!props.employee && props.employee.uuid === authUser.value?.uuid,
);

const name = computed(() =>
    props.employee
        ? `${props.employee.first_name} ${props.employee.last_name}`
        : "",
);

const avatar = computed(
    () =>
        props.employee?.avatar ||
        `https://ui-avatars.com/api/?name=${encodeURIComponent(name.value)}`,
);

watch(
    () => [props.open, props.employee?.uuid] as const,
    async ([isOpen, employeeUuid]) => {
        if (!isOpen || !employeeUuid) return;

        notes.value = [];
        meta.value = null;
        loading.value = true;

        try {
            const res = await employeeNoteService.list({
                branch_uuid: props.branchUuid,
                employee_uuid: employeeUuid,
            });

            notes.value = res.data ?? [];
            meta.value = res.meta ?? null;
        } catch (err: any) {
            error(err?.message ?? "Couldn't load the notes.");
        } finally {
            loading.value = false;
        }
    },
    { immediate: true },
);

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
    saving.value = true;

    try {
        const res = await employeeNoteService.create({
            branch_uuid: props.branchUuid,
            note: text,
        });

        notes.value = [res.data, ...notes.value];

        if (meta.value) {
            meta.value = { ...meta.value, total: meta.value.total + 1 };
        }

        form.value?.clear();
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
        meta.value = {
            ...meta.value,
            total: Math.max(0, meta.value.total - 1),
        };
    }
}
</script>
