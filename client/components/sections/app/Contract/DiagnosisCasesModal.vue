<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/40 backdrop-blur-sm p-4 font-sans dark:bg-white/10"
        >
            <div
                class="w-full max-w-3xl h-[80vh] bg-white rounded-3xl shadow-xl border border-muted-light overflow-hidden flex flex-col dark:bg-secondary dark:border-white/10"
            >
                <div
                    class="flex items-start justify-between gap-3 px-6 pt-5 pb-4 border-b border-muted-light dark:border-white/10"
                >
                    <div>
                        <h2
                            class="text-lg font-semibold text-secondary dark:text-white"
                        >
                            Diagnosis Cases
                        </h2>

                        <p class="text-sm text-muted mt-0.5 dark:text-gray-400">
                            Set the price range for each case. When a charge is
                            added for a diagnosis case, the price entered must
                            fall within its range.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-muted hover:text-secondary hover:bg-light shrink-0 dark:text-gray-400 dark:hover:text-white dark:hover:bg-white/5"
                        :disabled="saving"
                        @click="$emit('close')"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="flex-1 overflow-auto">
                    <form
                        v-if="formOpen"
                        class="space-y-4 px-6 py-5 border-b border-muted-light dark:border-white/10"
                        @submit.prevent="save"
                    >
                        <p
                            class="text-sm font-semibold text-secondary dark:text-white"
                        >
                            {{
                                editingUuid
                                    ? "Edit diagnosis case"
                                    : "New diagnosis case"
                            }}
                        </p>

                        <BaseInput
                            v-model="form.title"
                            label="Case name"
                            placeholder="e.g. Pneumonia"
                            :error="errors.title"
                            required
                            @update:modelValue="delete errors.title"
                        />

                        <BaseInput
                            v-model="form.description"
                            label="Description (optional)"
                            mode="textarea"
                            :rows="10"
                            :allowResize="true"
                            :textMax="1000"
                            placeholder="What this case covers"
                            :error="errors.description"
                        />

                        <BaseInput
                            v-model="form.price"
                            label="Price"
                            mode="number"
                            step="0.01"
                            min="0.01"
                            placeholder="0.00"
                            :error="errors.price"
                            required
                            @update:modelValue="delete errors.price"
                        />

                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                class="rounded-xl border px-4 py-2 text-sm font-medium transition hover:bg-light dark:border-white/10 dark:hover:bg-white/5"
                                :disabled="saving"
                                @click="closeForm"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="saving"
                            >
                                <LoaderCircle
                                    v-if="saving"
                                    class="h-4 w-4 animate-spin"
                                />
                                {{
                                    editingUuid
                                        ? "Save changes"
                                        : "Add diagnosis case"
                                }}
                            </button>
                        </div>
                    </form>

                    <div
                        v-if="loading"
                        class="divide-y divide-muted-light dark:divide-white/10"
                    >
                        <div v-for="n in 4" :key="n" class="px-6 py-4">
                            <div
                                class="h-14 bg-light/60 rounded-2xl animate-pulse dark:bg-white/5"
                            />
                        </div>
                    </div>

                    <div
                        v-else-if="!cases.length"
                        class="py-20 text-center text-sm text-muted dark:text-gray-400"
                    >
                        No diagnosis cases yet.
                    </div>

                    <ul
                        v-else
                        class="divide-y divide-muted-light dark:divide-white/10"
                    >
                        <li
                            v-for="item in cases"
                            :key="item.uuid"
                            class="flex items-start justify-between gap-4 px-6 py-4"
                        >
                            <div class="min-w-0">
                                <p
                                    class="text-sm font-semibold text-secondary dark:text-white"
                                >
                                    {{ item.title }}
                                </p>
                                <p
                                    v-if="item.description"
                                    class="mt-0.5 text-xs text-muted break-words dark:text-gray-400"
                                >
                                    {{ item.description }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-3">
                                <div class="text-right">
                                    <p
                                        class="text-[10px] font-semibold uppercase tracking-wide text-muted dark:text-gray-500"
                                    >
                                        Price
                                    </p>
                                    <p
                                        class="text-sm font-semibold text-secondary dark:text-white"
                                    >
                                        {{ formatCurrency(item.price) }}
                                    </p>
                                </div>

                                <button
                                    v-if="canUpdate(Modules.Contracts)"
                                    type="button"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-muted transition hover:bg-light hover:text-primary dark:text-gray-400 dark:hover:bg-white/5"
                                    aria-label="Edit diagnosis case"
                                    @click="openForm(item)"
                                >
                                    <Pencil class="h-4 w-4" />
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>

                <div
                    v-if="canCreate(Modules.Contracts) && !formOpen"
                    class="border-t border-muted-light px-6 py-4 dark:border-white/10"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-medium text-white transition hover:opacity-90"
                        @click="openForm()"
                    >
                        <Plus class="h-4 w-4" />
                        Add diagnosis case
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import { LoaderCircle, Pencil, Plus, X } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import { diagnosisCaseService } from "~/api/diagnosis-case/DiagnosisCaseService";
import { useToast } from "~/composables/useToast";
import { formatCurrency } from "~/utils/currency";
import { Modules } from "~/types/module";
import type { DiagnosisCase } from "~/types/additional-charge";

const props = defineProps<{
    open: boolean;
    branchUuid: string;
}>();

defineEmits<{ (e: "close"): void }>();

const { success, error } = useToast();
const { canCreate, canUpdate } = usePermissions();

const cases = ref<DiagnosisCase[]>([]);
const loading = ref(false);
const saving = ref(false);
const formOpen = ref(false);
const editingUuid = ref<string | null>(null);
const errors = reactive<Record<string, string>>({});

const form = reactive({
    title: "",
    description: "",
    price: "" as string | number,
});

function resetForm() {
    form.title = "";
    form.description = "";
    form.price = "";
    Object.keys(errors).forEach((key) => delete errors[key]);
}

function openForm(item?: DiagnosisCase) {
    resetForm();
    editingUuid.value = item?.uuid ?? null;

    if (item) {
        form.title = item.title;
        form.description = item.description ?? "";
        form.price = item.price;
    }

    formOpen.value = true;
}

function closeForm() {
    formOpen.value = false;
    editingUuid.value = null;
    resetForm();
}

async function fetchCases() {
    loading.value = true;

    try {
        const res = await diagnosisCaseService.list({
            branch_uuid: props.branchUuid,
        });

        cases.value = res.data ?? [];
    } catch (err: any) {
        cases.value = [];
        error(err?.message ?? "Couldn't load diagnosis cases.");
    } finally {
        loading.value = false;
    }
}

function validate() {
    Object.keys(errors).forEach((key) => delete errors[key]);

    const price = Number(form.price);

    if (!form.title.trim()) errors.title = "Enter a case name.";
    if (form.price === "" || Number.isNaN(price) || price <= 0) {
        errors.price = "Enter a price greater than zero.";
    }

    return Object.keys(errors).length === 0;
}

async function save() {
    if (saving.value || !validate()) return;

    saving.value = true;

    const payload = {
        branch_uuid: props.branchUuid,
        title: form.title.trim(),
        description: form.description.trim() || null,
        price: Math.round(Number(form.price) * 100) / 100,
    };

    try {
        const res = editingUuid.value
            ? await diagnosisCaseService.update(editingUuid.value, payload)
            : await diagnosisCaseService.create(payload);

        const saved: DiagnosisCase = res.data;

        cases.value = editingUuid.value
            ? cases.value.map((item) =>
                  item.uuid === saved.uuid ? saved : item,
              )
            : [...cases.value, saved].sort((a, b) =>
                  a.title.localeCompare(b.title),
              );

        success(editingUuid.value ? "Diagnosis case updated." : "Diagnosis case added.");
        closeForm();
    } catch (err: any) {
        const raw = err?.errors ?? {};

        Object.entries(raw).forEach(([key, value]: any) => {
            errors[key] = Array.isArray(value) ? value[0] : value;
        });

        if (!Object.keys(raw).length) {
            error(err?.message ?? "Couldn't save the diagnosis case.");
        }
    } finally {
        saving.value = false;
    }
}

watch(
    () => props.open,
    (open) => {
        if (!open) return;

        closeForm();
        fetchCases();
    },
);
</script>
