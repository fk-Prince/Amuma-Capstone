<template>
    <section ref="diagnosisSection" class="rounded-2xl p-8 md:p-10">
        <div class="flex items-baseline gap-3 mb-8">
            <span class="text-2xl text-primary">04</span>

            <div>
                <h2 class="text-xl text-primary">
                    Diagnosis
                    <span
                        class="ml-1 align-middle text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500"
                    >
                        (Optional)
                    </span>
                </h2>

                <p class="text-[13px] text-muted dark:text-gray-400">
                    Any conditions the patient has already been diagnosed with,
                    and the documents that support them
                </p>
            </div>
        </div>

        <div
            v-if="diagnoses.length > 1"
            class="mb-8 flex flex-wrap items-center gap-2"
        >
            <button
                v-for="(entry, index) in diagnoses"
                :key="index"
                type="button"
                class="group flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition-colors"
                :class="
                    index === activeIndex
                        ? 'bg-primary text-white'
                        : 'bg-slate-50 text-slate-500 hover:bg-slate-100 dark:bg-secondary dark:text-gray-400 dark:border dark:border-white/10 dark:hover:bg-white/5'
                "
                @click="activeIndex = index"
            >
                <span
                    class="flex h-5 w-5 items-center justify-center rounded-full text-[11px] font-semibold"
                    :class="
                        index === activeIndex
                            ? 'bg-white/20 text-white'
                            : 'bg-white text-slate-400 dark:bg-white/10 dark:text-gray-500'
                    "
                >
                    {{ index + 1 }}
                </span>

                Diagnosis {{ index + 1 }}

                <XIcon
                    class="h-3.5 w-3.5 opacity-0 transition-opacity group-hover:opacity-100"
                    :class="
                        index === activeIndex
                            ? 'text-white/80 hover:text-white'
                            : 'text-slate-400 hover:text-red-500 dark:text-gray-500'
                    "
                    @click.stop="removeDiagnosis(index)"
                />
            </button>

            <button
                type="button"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition hover:bg-primary/10 hover:text-primary dark:bg-secondary dark:text-gray-500"
                @click="addDiagnosis"
            >
                <PlusIcon class="h-4 w-4" />
            </button>
        </div>

        <div class="space-y-10">
            <div class="space-y-6">
                <div
                    v-if="diagnoses.length > 1"
                    class="flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary"
                        >
                            {{ activeIndex + 1 }}
                        </span>

                        <h3
                            class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                        >
                            Diagnosis {{ activeIndex + 1 }}
                        </h3>
                    </div>

                    <button
                        type="button"
                        class="text-sm font-medium text-slate-400 transition hover:text-red-500 dark:text-gray-500"
                        @click="removeDiagnosis(activeIndex)"
                    >
                        Remove
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <BaseInput
                        label="Primary Diagnosis"
                        :model-value="activeDiagnosis.diagnosis"
                        @update:model-value="
                            update(activeIndex, 'diagnosis', $event)
                        "
                        :error="errors?.[`diagnosis.${activeIndex}`]"
                        placeholder="e.g. Type 2 Diabetes"
                    />

                    <BaseInput
                        label="Date Diagnosed"
                        :model-value="activeDiagnosis.diagnosis_date"
                        @update:model-value="
                            update(activeIndex, 'diagnosis_date', $event)
                        "
                        mode="date"
                        :max="todayStr"
                        :error="errors?.[`diagnosis_date.${activeIndex}`]"
                    />
                </div>

                <BaseInput
                    label="Diagnosis Notes"
                    :model-value="activeDiagnosis.diagnosis_notes"
                    @update:model-value="
                        update(activeIndex, 'diagnosis_notes', $event)
                    "
                    :error="errors?.[`diagnosis_notes.${activeIndex}`]"
                    placeholder="Additional details about the diagnosis"
                />

                <div class="flex flex-col gap-1.5">
                    <label
                        class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                    >
                        Supporting Document
                    </label>

                    <p class="text-[13px] text-muted dark:text-gray-400">
                        Upload a lab result, medical certificate, or report
                        (PDF, PNG, or JPG, up to 10MB)
                    </p>

                    <label
                        class="group flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl p-6 text-center transition-colors border-2 border-dashed border-slate-200 dark:border-white/10"
                        :class="
                            errors?.[`diagnosis_file.${activeIndex}`]
                                ? 'bg-red-50/70 dark:bg-red-500/10'
                                : activeDiagnosis.diagnosis_file_name
                                  ? 'bg-primary/5 dark:bg-primary-500/10'
                                  : 'bg-slate-50 hover:bg-primary/5 dark:bg-white/5 dark:hover:bg-primary-500/10'
                        "
                    >
                        <input
                            type="file"
                            class="hidden"
                            accept=".pdf,.png,.jpg,.jpeg"
                            @change="onFileChange($event, activeIndex)"
                        />

                        <template v-if="activeDiagnosis.diagnosis_file_name">
                            <FileText class="h-6 w-6 text-primary" />

                            <span
                                class="text-sm font-medium text-slate-700 dark:text-gray-300"
                            >
                                {{ activeDiagnosis.diagnosis_file_name }}
                            </span>

                            <button
                                type="button"
                                class="text-xs font-medium text-slate-400 underline transition hover:text-red-500"
                                @click.prevent="clearFile(activeIndex)"
                            >
                                Remove file
                            </button>
                        </template>

                        <template v-else>
                            <UploadCloud
                                class="h-6 w-6 text-slate-400 transition group-hover:text-primary"
                            />

                            <span class="text-sm text-muted dark:text-gray-400">
                                Click to upload
                            </span>
                        </template>
                    </label>

                    <p
                        v-if="errors?.[`diagnosis_file.${activeIndex}`]"
                        class="text-xs text-red-500"
                    >
                        {{ errors[`diagnosis_file.${activeIndex}`] }}
                    </p>
                </div>
            </div>

            <button
                type="button"
                class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-200 py-4 text-sm font-semibold text-primary transition hover:border-primary/40 hover:bg-primary/5 dark:border-white/10"
                @click="
                    addDiagnosis();
                    scrollToTop();
                "
            >
                <span class="text-xl leading-none">+</span>
                Add Another Diagnosis
            </button>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from "vue";
import {
    FileText,
    UploadCloud,
    Plus as PlusIcon,
    X as XIcon,
} from "lucide-vue-next";
import BaseInput from "../ui/BaseInput.vue";
import type { Diagnosis } from "~/types/patient";

const props = defineProps<{
    model: Diagnosis[];
    errors?: Record<string, string> | null;
}>();

const emit = defineEmits<{
    (e: "update:model", value: Diagnosis[]): void;
    (e: "update:errors", value: Record<string, string>): void;
}>();

const todayStr = new Date().toISOString().split("T")[0];

function createDiagnosis(): Diagnosis {
    return {
        diagnosis: "",
        diagnosis_date: "",
        diagnosis_notes: "",
        diagnosis_file: undefined,
        diagnosis_file_name: undefined,
    };
}

const diagnoses = ref<Diagnosis[]>(
    props.model?.length
        ? props.model.map((entry) => ({ ...createDiagnosis(), ...entry }))
        : [createDiagnosis()],
);

const activeIndex = ref(0);

const activeDiagnosis = computed(
    () => diagnoses.value[activeIndex.value] ?? createDiagnosis(),
);

watch(
    () => props.model,
    (val) => {
        if (!val || val.length === 0) return;
        if (val === diagnoses.value) return;

        diagnoses.value = val.map((entry) => ({
            ...createDiagnosis(),
            ...entry,
        }));

        if (activeIndex.value > diagnoses.value.length - 1) {
            activeIndex.value = diagnoses.value.length - 1;
        }
    },
);

function update<K extends keyof Diagnosis>(
    index: number,
    key: K,
    value: Diagnosis[K],
) {
    diagnoses.value[index] = { ...diagnoses.value[index], [key]: value };

    emit("update:model", [...diagnoses.value]);
    clearError(`${String(key)}.${index}`);
}

function addDiagnosis() {
    diagnoses.value.push(createDiagnosis());
    activeIndex.value = diagnoses.value.length - 1;

    emit("update:model", [...diagnoses.value]);
}

function removeDiagnosis(index: number) {
    if (diagnoses.value.length <= 1) return;

    diagnoses.value.splice(index, 1);

    if (activeIndex.value >= diagnoses.value.length) {
        activeIndex.value = diagnoses.value.length - 1;
    } else if (activeIndex.value > index) {
        activeIndex.value -= 1;
    }

    emit("update:model", [...diagnoses.value]);
}

function clearError(field: string) {
    if (!props.errors) return;

    const updated = { ...props.errors };

    delete updated[field];

    emit("update:errors", updated);
}

function onFileChange(event: Event, index: number) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) return;

    if (file.size > 10 * 1024 * 1024) {
        emit("update:errors", {
            ...(props.errors ?? {}),
            [`diagnosis_file.${index}`]: "File size must not exceed 10MB.",
        });

        input.value = "";
        return;
    }

    const allowed = ["application/pdf", "image/png", "image/jpeg"];

    if (!allowed.includes(file.type)) {
        emit("update:errors", {
            ...(props.errors ?? {}),
            [`diagnosis_file.${index}`]:
                "Only PDF, PNG, and JPG files are allowed.",
        });

        input.value = "";
        return;
    }

    update(index, "diagnosis_file", file);
    update(index, "diagnosis_file_name", file.name);
}

function clearFile(index: number) {
    update(index, "diagnosis_file", undefined);
    update(index, "diagnosis_file_name", undefined);
}

const diagnosisSection = ref<HTMLElement | null>(null);

async function scrollToTop() {
    await nextTick();

    diagnosisSection.value?.scrollIntoView({
        behavior: "smooth",
        block: "start",
    });
}
</script>
