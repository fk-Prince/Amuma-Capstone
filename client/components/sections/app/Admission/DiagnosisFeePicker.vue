<template>
    <section v-if="rows.length || (loaded && emptyText)" :class="sectionClass">
        <h4
            class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
        >
            {{ step }}Diagnosis fees
            <span class="font-normal normal-case tracking-normal">
                (optional)
            </span>
        </h4>

        <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
            {{ hint }}
        </p>

        <p
            v-if="!rows.length"
            class="mt-2.5 rounded-xl border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-400 dark:border-white/10 dark:text-gray-500"
        >
            {{ emptyText }}
        </p>

        <div class="mt-2.5 space-y-3">
            <div
                v-for="row in rows"
                :key="row.uuid"
                class="rounded-xl border p-4 dark:border-white/10"
            >
                <p class="font-semibold break-words">
                    {{ row.diagnosis }}
                </p>

                <div class="mt-2.5">
                    <Combobox
                        v-model="row.case_uuid"
                        placeholder="No diagnosis fee"
                        search-bar
                        search-placeholder="Search diagnosis cases"
                        :items="caseItems"
                    />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { useRoute } from "vue-router";

import Combobox from "~/components/ui/Combobox.vue";
import { diagnosisCaseService } from "~/api/diagnosis-case/DiagnosisCaseService";
import type {
    DiagnosisCase,
    DiagnosisFeeSelection,
} from "~/types/additional-charge";
import { formatCurrency } from "~/utils/currency";

const route = useRoute();

const props = withDefaults(
    defineProps<{
        // The patient whose recorded diagnoses can be billed. Loads on open.
        patientUuid?: string;
        open: boolean;
        step?: string;
        hint?: string;
        sectionClass?: string;
        // Starts each diagnosis from the case it already has on this admission.
        admissionId?: number;
        // Shown when there is nothing to pick. Leave out to hide the section.
        emptyText?: string;
    }>(),
    {
        step: "",
        hint: "Billed together with this stay.",
        sectionClass: "mt-6",
    },
);

const emit = defineEmits<{
    (e: "update:selection", value: DiagnosisFeeSelection): void;
}>();

const cases = ref<DiagnosisCase[]>([]);
const loaded = ref(false);
const rows = ref<{ uuid: string; diagnosis: string; case_uuid: string }[]>([]);

const caseItems = computed(() => [
    { label: "No diagnosis fee", value: "" },
    ...cases.value.map((item) => ({
        label: `${item.title} — ${formatCurrency(item.price)} / month`,
        value: item.uuid,
    })),
]);

const selection = computed<DiagnosisFeeSelection>(() => ({
    diagnoses: rows.value
        .filter((row) => row.case_uuid)
        .map((row) => ({
            patient_diagnosis_uuid: row.uuid,
            diagnosis_case_uuid: row.case_uuid,
        })),
    fee: rows.value.reduce(
        (sum, row) =>
            sum +
            Number(
                cases.value.find((item) => item.uuid === row.case_uuid)
                    ?.price ?? 0,
            ),
        0,
    ),
}));

watch(selection, (value) => emit("update:selection", value), {
    immediate: true,
});

async function load() {
    cases.value = [];
    rows.value = [];
    loaded.value = false;

    if (!props.patientUuid) return;

    try {
        const branch_uuid = route.params.uuid as string;

        const [caseList, diagnosisList] = await Promise.all([
            diagnosisCaseService.list({ branch_uuid, for: "charges" }),
            diagnosisCaseService.patientDiagnoses({
                branch_uuid,
                patient_uuid: props.patientUuid,
                ...(props.admissionId
                    ? { patient_admission_id: props.admissionId }
                    : {}),
            }),
        ]);

        cases.value = caseList.data ?? [];

        if (!cases.value.length) return;

        rows.value = (diagnosisList.data ?? []).map(
            (item: {
                uuid: string;
                diagnosis: string;
                last_case_uuid?: string | null;
                admission_case_uuid?: string | null;
            }) => ({
                uuid: item.uuid,
                diagnosis: item.diagnosis,
                case_uuid: props.admissionId
                    ? (item.admission_case_uuid ?? "")
                    : (item.last_case_uuid ?? ""),
            }),
        );
    } catch {
        cases.value = [];
        rows.value = [];
    } finally {
        loaded.value = true;
    }
}

watch(
    [() => props.open, () => props.patientUuid],
    ([open]) => {
        if (open) load();
    },
    { immediate: true },
);
</script>
