<script setup lang="ts">
import { ref, reactive, computed, watch } from "vue";
import { FileText, Plus, Stethoscope, UploadCloud } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import { patientService } from "~/api/patient/PatientService";
import { useToast } from "~/composables/useToast";
import type { PatientRetrieve } from "~/types/patient";
import { formatDate } from "~/utils/time";
import {
    LIFE_SYSTEM_ACTIVITIES,
    activityLabel,
    assessmentLabel,
    lifeSystemLabel,
} from "~/utils/assessment";

const props = defineProps<{
    patient: PatientRetrieve;
}>();

const assessments = computed(() => {
    const value = props.patient.assessment;

    if (!value) return [];

    if (Array.isArray(value)) return value;

    if (typeof value === "object") return [value];

    return [];
});

// Diagnoses are their own records now; bookings made before the split still
// carry them inside the assessment, so those are read back here too.
const diagnoses = computed<any[]>(() => {
    const provided = (props.patient as any)?.diagnoses;

    const existing =
        Array.isArray(provided) && provided.length
            ? provided
            : assessments.value.filter(
                (entry: any) => entry?.diagnosis || entry?.diagnosis_notes,
            );

    return [...existing, ...added.value];
});

const route = useRoute();
const { success, error } = useToast();

const todayStr = new Date().toISOString().split("T")[0];

const adding = ref(false);
const saving = ref(false);
const added = ref<any[]>([]);

const draft = reactive<{
    diagnosis: string;
    diagnosis_date: string;
    diagnosis_notes: string;
    diagnosis_file: File | null;
}>({
    diagnosis: "",
    diagnosis_date: "",
    diagnosis_notes: "",
    diagnosis_file: null,
});

const draftErrors = reactive<Record<string, string>>({});

const ALLOWED_TYPES = ["application/pdf", "image/png", "image/jpeg"];

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    delete draftErrors.diagnosis_file;

    if (!file) return;

    if (file.size > 10 * 1024 * 1024) {
        draftErrors.diagnosis_file = "File must be 10MB or smaller.";
        input.value = "";
        return;
    }

    if (!ALLOWED_TYPES.includes(file.type)) {
        draftErrors.diagnosis_file = "Only PDF, PNG, and JPG files are allowed.";
        input.value = "";
        return;
    }

    draft.diagnosis_file = file;
}

function cancelAdd() {
    adding.value = false;
    draft.diagnosis = "";
    draft.diagnosis_date = "";
    draft.diagnosis_notes = "";
    draft.diagnosis_file = null;
    delete draftErrors.diagnosis;
    delete draftErrors.diagnosis_date;
    delete draftErrors.diagnosis_file;
}

async function submitDiagnosis() {
    delete draftErrors.diagnosis;

    if (!draft.diagnosis.trim()) {
        draftErrors.diagnosis = "Primary diagnosis is required.";
        return;
    }

    saving.value = true;

    try {
        const res = await patientService.addDiagnosis(props.patient.uuid, {
            branch_uuid: route.params.uuid,
            diagnosis: draft.diagnosis.trim(),
            diagnosis_date: draft.diagnosis_date || undefined,
            diagnosis_notes: draft.diagnosis_notes || undefined,
            diagnosis_file: draft.diagnosis_file ?? undefined,
        });

        // Appended locally so the card updates without refetching the patient.
        if (res?.diagnosis) added.value.push(res.diagnosis);

        success(res?.message ?? "Diagnosis added.");
        cancelAdd();
    } catch (err: any) {
        error(err?.message ?? "Could not add the diagnosis.");
    } finally {
        saving.value = false;
    }
}

const activeAssessmentIndex = ref(0);

watch(assessments, (list) => {
    if (!list.length) {
        activeAssessmentIndex.value = 0;
        return;
    }

    if (activeAssessmentIndex.value > list.length - 1) {
        activeAssessmentIndex.value = list.length - 1;
    }
});

const activeAssessment = computed(
    () => assessments.value[activeAssessmentIndex.value] ?? null,
);
</script>

<template>
    <section class="mb-5 rounded-2xl bg-white p-6 shadow-sm dark:bg-secondary">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <FileText class="h-4 w-4 text-primary" />
                <h3 class="font-semibold text-secondary dark:text-white">Diagnosis</h3>
            </div>

            <button
                v-if="!adding"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-gray-400"
                @click="adding = true"
            >
                <Plus class="h-3.5 w-3.5" />
                Add diagnosis
            </button>
        </div>

        <form
            v-if="adding"
            class="mt-4 space-y-4 rounded-xl border border-slate-200 p-4 dark:border-white/10"
            @submit.prevent="submitDiagnosis"
        >
            <div class="grid gap-4 sm:grid-cols-2">
                <BaseInput
                    v-model="draft.diagnosis"
                    label="Primary Diagnosis"
                    placeholder="e.g. Type 2 Diabetes"
                    :error="draftErrors.diagnosis"
                />

                <BaseInput
                    v-model="draft.diagnosis_date"
                    label="Date Diagnosed"
                    mode="date"
                    :max="todayStr"
                    :error="draftErrors.diagnosis_date"
                />
            </div>

            <BaseInput
                v-model="draft.diagnosis_notes"
                label="Diagnosis Notes"
                placeholder="Additional details"
            />

            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-semibold text-slate-700 dark:text-gray-400">
                    Supporting Document
                </label>

                <label
                    class="group flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 border-dashed p-4 text-center text-sm transition"
                    :class="
                        draftErrors.diagnosis_file
                            ? 'border-red-300 bg-red-50/70'
                            : draft.diagnosis_file
                              ? 'border-primary/40 bg-primary/5'
                              : 'border-slate-200 bg-slate-50 hover:bg-primary/5 dark:border-white/10 dark:bg-white/5'
                    "
                >
                    <input
                        type="file"
                        class="hidden"
                        accept=".pdf,.png,.jpg,.jpeg"
                        @change="onFileChange"
                    />

                    <template v-if="draft.diagnosis_file">
                        <FileText class="h-4 w-4 text-primary" />
                        <span class="font-medium text-slate-700 dark:text-gray-400">
                            {{ draft.diagnosis_file.name }}
                        </span>
                        <button
                            type="button"
                            class="text-xs text-slate-400 underline hover:text-red-500 dark:text-gray-500"
                            @click.prevent="draft.diagnosis_file = null"
                        >
                            Remove
                        </button>
                    </template>

                    <template v-else>
                        <UploadCloud
                            class="h-4 w-4 text-slate-400 group-hover:text-primary dark:text-gray-500"
                        />
                        <span class="text-muted dark:text-gray-400">
                            Click to upload a PDF, PNG or JPG (up to 10MB)
                        </span>
                    </template>
                </label>

                <p
                    v-if="draftErrors.diagnosis_file"
                    class="text-xs text-red-500"
                >
                    {{ draftErrors.diagnosis_file }}
                </p>
            </div>

            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    :disabled="saving"
                    class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-50 dark:text-gray-400 dark:hover:bg-white/10"
                    @click="cancelAdd"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    :disabled="saving"
                    class="rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-white transition hover:opacity-90 disabled:opacity-50"
                >
                    {{ saving ? "Saving…" : "Save diagnosis" }}
                </button>
            </div>
        </form>

        <p v-if="!diagnoses.length && !adding" class="mt-4 text-sm text-muted dark:text-gray-400">
            No diagnosis recorded.
        </p>

        <div v-else class="mt-5 space-y-5">
            <div
                v-for="(entry, index) in diagnoses"
                :key="index"
                class="border-b border-slate-100 pb-5 last:border-b-0 last:pb-0 dark:border-white/10"
            >
                <p
                    v-if="diagnoses.length > 1"
                    class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted dark:text-gray-400"
                >
                    Diagnosis {{ index + 1 }}
                </p>

                <div class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div>
                        <p class="text-xs text-muted dark:text-gray-400">Diagnosis</p>
                        <p class="mt-0.5 font-medium text-secondary dark:text-white">
                            {{ entry.diagnosis || "—" }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-muted dark:text-gray-400">Diagnosis Date</p>
                        <p class="mt-0.5 font-medium text-secondary dark:text-white">
                            {{ formatDate(entry.diagnosis_date) }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <p class="text-xs text-muted dark:text-gray-400">Diagnosis Notes</p>
                        <p class="mt-0.5 font-medium text-secondary dark:text-white">
                            {{ entry.diagnosis_notes || "—" }}
                        </p>
                    </div>

                    <div v-if="entry.diagnosis_file" class="sm:col-span-2">
                        <p class="text-xs text-muted dark:text-gray-400">Supporting Document</p>
                        <a
                            :href="entry.diagnosis_file"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-0.5 inline-block font-medium text-primary underline hover:text-primary/70"
                        >
                            View file
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-2xl bg-white p-6 shadow-sm dark:bg-secondary">
        <div class="flex items-center gap-2">
            <Stethoscope class="h-4 w-4 text-primary" />
            <h3 class="font-semibold text-secondary dark:text-white">Assessment</h3>
        </div>

        <p
            v-if="!assessments.length"
            class="mt-4 text-sm text-muted dark:text-gray-400"
        >
            No assessment recorded.
        </p>

        <template v-else>
            <div
                v-if="assessments.length > 1"
                class="mt-4 flex flex-wrap items-center gap-2"
            >
                <button
                    v-for="(assessment, index) in assessments"
                    :key="index"
                    type="button"
                    class="rounded-full px-3 py-1.5 text-xs font-medium transition-colors"
                    :class="
                        activeAssessmentIndex === index
                            ? 'bg-primary text-white'
                            : 'bg-muted-light/60 text-secondary hover:bg-muted-light dark:text-white dark:hover:bg-white/10'
                    "
                    @click="activeAssessmentIndex = index"
                >
                    Assessment {{ index + 1 }}
                </button>
            </div>

            <div v-if="activeAssessment" class="mt-5 space-y-6">
                <div>
                    <h5
                        class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted dark:text-gray-400"
                    >
                        Condition &amp; Mental / Cognitive State
                    </h5>

                    <div class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                        <div>
                            <p class="text-xs text-muted dark:text-gray-400">Mobility</p>
                            <p class="mt-0.5 font-medium text-secondary dark:text-white">
                                {{
                                    assessmentLabel(
                                        activeAssessment.condition,
                                    ) || "—"
                                }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted dark:text-gray-400">
                                Level of Consciousness
                            </p>
                            <p class="mt-0.5 font-medium text-secondary dark:text-white">
                                {{
                                    assessmentLabel(
                                        activeAssessment.mental_state,
                                    ) || "—"
                                }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted dark:text-gray-400">Affect</p>
                            <p class="mt-0.5 font-medium text-secondary dark:text-white">
                                {{
                                    assessmentLabel(activeAssessment.affect) ||
                                    "—"
                                }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted dark:text-gray-400">Behavior</p>
                            <p class="mt-0.5 font-medium text-secondary dark:text-white">
                                {{
                                    assessmentLabel(
                                        activeAssessment.behavior,
                                    ) || "—"
                                }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted dark:text-gray-400">Communication</p>
                            <p class="mt-0.5 font-medium text-secondary dark:text-white">
                                {{ activeAssessment.communication || "—" }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-muted dark:text-gray-400">Speech</p>
                            <p class="mt-0.5 font-medium text-secondary dark:text-white">
                                {{ assessmentLabel(activeAssessment.speech) || "—" }}
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="activeAssessment.life_system_profile">
                    <h5
                        class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted dark:text-gray-400"
                    >
                        Life System Profile
                    </h5>

                    <div class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                        <div v-for="activity in LIFE_SYSTEM_ACTIVITIES" :key="activity">
                            <p class="text-xs text-muted dark:text-gray-400">{{ activityLabel(activity) }}</p>
                            <p class="mt-0.5 font-medium text-secondary dark:text-white">
                                {{ lifeSystemLabel(activeAssessment.life_system_profile?.[activity]) || "—" }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </section>
</template>
