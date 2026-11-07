<template>
    <div
        v-if="patients.length > 1"
        ref="rootEl"
        :class="compact ? '' : 'relative w-full max-w-xs'"
        @keydown.esc="open = false"
    >
        <button
            v-if="compact"
            type="button"
            aria-label="Search your loved ones"
            class="flex items-center justify-center text-gray-500 dark:text-gray-300"
            @click="openCompact"
        >
            <Search class="h-[18px] w-[18px]" />
        </button>

        <div
            v-show="!compact || open"
            :class="
                compact
                    ? 'fixed inset-x-3 top-[84px] z-50 rounded-2xl border border-gray-100 bg-white p-3 shadow-2xl dark:border-white/10 dark:bg-secondary'
                    : ''
            "
        >
            <div
                class="flex h-11 items-center gap-2 rounded-xl border border-gray-200 bg-gray-50/80 px-3 transition focus-within:border-primary/50 focus-within:bg-white focus-within:ring-2 focus-within:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:focus-within:bg-white/10"
            >
                <Search class="h-4 w-4 shrink-0 text-gray-400" />
                <input
                    :id="compact ? 'portal-patient-search-mobile' : 'portal-patient-search'"
                    ref="inputEl"
                    v-model="query"
                    type="search"
                    autocomplete="off"
                    placeholder="Search your loved ones"
                    class="min-w-0 flex-1 bg-transparent text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none dark:text-white"
                    @focus="open = true"
                    @keydown.down.prevent="move(1)"
                    @keydown.up.prevent="move(-1)"
                    @keydown.enter.prevent="pick(results[highlighted])"
                />
                <button
                    v-if="compact"
                    type="button"
                    aria-label="Close search"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10"
                    @click="open = false"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div
                v-if="open"
                :class="
                    compact
                        ? 'mt-2'
                        : 'absolute left-0 right-0 top-full z-40 mt-2 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xl dark:border-white/10 dark:bg-secondary'
                "
                role="listbox"
            >
                <p
                    class="px-4 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-wide text-gray-400"
                >
                    Your loved ones
                </p>

                <ul v-if="results.length" class="max-h-72 overflow-y-auto pb-2">
                    <li v-for="(patient, index) in results" :key="patient.uuid">
                        <button
                            type="button"
                            role="option"
                            :aria-selected="patient.uuid === currentUuid"
                            class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-left transition"
                            :class="
                                index === highlighted
                                    ? 'bg-primary/5 dark:bg-white/5'
                                    : 'hover:bg-gray-50 dark:hover:bg-white/5'
                            "
                            @mouseenter="highlighted = index"
                            @click="pick(patient)"
                        >
                            <span
                                class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary/10 text-xs font-semibold text-primary"
                            >
                                <img
                                    v-if="patient.avatar"
                                    :src="patient.avatar"
                                    :alt="patient.full_name"
                                    class="h-full w-full object-cover"
                                />
                                <template v-else>
                                    {{ initials(patient.full_name) }}
                                </template>
                            </span>

                            <span
                                class="min-w-0 flex-1 truncate text-sm font-medium text-gray-800 dark:text-white"
                            >
                                {{ patient.full_name }}
                            </span>

                            <span
                                v-if="patient.uuid === currentUuid"
                                class="shrink-0 rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary"
                            >
                                Viewing
                            </span>
                        </button>
                    </li>
                </ul>

                <p
                    v-else
                    class="px-4 pb-4 pt-1 text-sm text-gray-500 dark:text-gray-400"
                >
                    No loved one matches "{{ query }}".
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Search, X } from "lucide-vue-next";
import { patientAccessService } from "~/api/patient-access/PatientAccessService";
import { initials } from "~/utils/user";

interface PortalPatient {
    uuid: string;
    full_name: string;
    avatar?: string | null;
}

defineProps<{
    compact?: boolean;
}>();

const route = useRoute();
const router = useRouter();

const patients = useState<PortalPatient[]>("portal_patient_directory", () => []);
const loaded = useState<boolean>("portal_patient_directory_loaded", () => false);
const remembered = useState<string | null>("portal_selected_patient", () => null);

const rootEl = ref<HTMLElement | null>(null);
const inputEl = ref<HTMLInputElement | null>(null);
const open = ref(false);
const query = ref("");
const highlighted = ref(0);

const currentUuid = computed(
    () =>
        String(route.query.patient ?? "") ||
        remembered.value ||
        patients.value[0]?.uuid,
);

const results = computed(() => {
    const term = query.value.trim().toLowerCase();

    return term
        ? patients.value.filter((p) => p.full_name.toLowerCase().includes(term))
        : patients.value;
});

watch(results, () => (highlighted.value = 0));

async function openCompact() {
    open.value = true;
    await nextTick();
    inputEl.value?.focus();
}

function move(step: number) {
    if (!results.value.length) return;

    open.value = true;
    highlighted.value =
        (highlighted.value + step + results.value.length) % results.value.length;
}

function pick(patient?: PortalPatient) {
    if (!patient) return;

    open.value = false;
    query.value = "";
    remembered.value = patient.uuid;

    router.replace({ query: { ...route.query, patient: patient.uuid } });
}

function onDocumentClick(event: MouseEvent) {
    if (!rootEl.value?.contains(event.target as Node)) open.value = false;
}

async function loadPatients() {
    if (loaded.value) return;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "overview",
            section: "directory",
        });

        patients.value = (Array.isArray(res?.data) ? res.data : [])
            .map((item: any) => item?.patient)
            .filter((p: any) => p?.uuid)
            .map((p: any) => ({
                uuid: p.uuid,
                full_name: p.full_name || "Unnamed Resident",
                avatar: p.avatar ?? null,
            }));

        loaded.value = true;
    } catch {}
}

onMounted(() => {
    document.addEventListener("click", onDocumentClick);
    loadPatients();
});

onBeforeUnmount(() => {
    document.removeEventListener("click", onDocumentClick);
});
</script>
