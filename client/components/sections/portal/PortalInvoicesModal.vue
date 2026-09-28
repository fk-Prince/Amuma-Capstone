<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import AppIcon from "~/components/ui/AppIcon.vue";
import BalanceInvoiceList from "~/components/sections/portal/BalanceInvoiceList.vue";
import { patientAccessService } from "~/api/patient-access/PatientAccessService";
import { toPortalInvoice } from "~/utils/portal-billing";
import type { PortalInvoice } from "~/types/portal-billing";

const PER_PAGE = 5;

const props = defineProps<{
    open: boolean;
    patientId: number | null;
    residentName?: string | null;
}>();

const emit = defineEmits<{
    (event: "close"): void;
    (event: "open-invoice", invoice: PortalInvoice): void;
    (event: "adjustments", invoice: PortalInvoice): void;
}>();

const invoices = ref<PortalInvoice[]>([]);
const loading = ref(false);
const loadError = ref("");
const meta = reactive({
    current_page: 1,
    last_page: 1,
    per_page: PER_PAGE,
    total: 0,
});

async function load(page = 1) {
    if (!props.patientId) return;

    loading.value = true;
    loadError.value = "";

    try {
        const res = await patientAccessService.retrieveAction({
            action: "invoices",
            patient_id: props.patientId,
            page,
            per_page: PER_PAGE,
        });

        invoices.value = (res?.data ?? []).map(toPortalInvoice);
        Object.assign(meta, res?.meta ?? {});
    } catch (err: any) {
        invoices.value = [];
        loadError.value = err?.message || "Unable to load invoices.";
    } finally {
        loading.value = false;
    }
}

function go(step: number) {
    const next = Math.min(
        meta.last_page,
        Math.max(1, meta.current_page + step),
    );

    if (next !== meta.current_page) load(next);
}

watch(
    () => props.open,
    (open) => {
        if (open) load(1);
    },
);
</script>

<template>
    <Transition name="modal">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 backdrop-blur-sm"
            @click.self="emit('close')"
        >
            <div
                class="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-secondary"
            >
                <div
                    class="flex items-center justify-between gap-3 border-b border-gray-100 px-6 py-4 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p
                            class="text-sm font-bold text-gray-900 dark:text-white"
                        >
                            All invoices
                        </p>

                        <p
                            class="mt-0.5 truncate text-xs text-gray-400 dark:text-gray-500"
                        >
                            Every bill raised for
                            {{ residentName || "this resident" }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10"
                        @click="emit('close')"
                    >
                        <AppIcon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="min-h-[400px] flex-1 overflow-y-auto px-6 py-4">
                    <div
                        v-if="loading"
                        class="animate-pulse divide-y divide-gray-100 dark:divide-white/10"
                    >
                        <div
                            v-for="i in PER_PAGE"
                            :key="i"
                            class="flex items-start justify-between gap-3 py-3 first:pt-0"
                        >
                            <div class="flex-1 space-y-2">
                                <div
                                    class="h-3 w-28 rounded bg-gray-200 dark:bg-white/10"
                                />
                                <div
                                    class="h-3 w-48 rounded bg-gray-100 dark:bg-white/5"
                                />
                                <div
                                    class="h-2.5 w-32 rounded bg-gray-100 dark:bg-white/5"
                                />
                            </div>
                            <div
                                class="h-3.5 w-20 rounded bg-gray-200 dark:bg-white/10"
                            />
                        </div>
                    </div>

                    <p
                        v-else-if="loadError"
                        class="py-14 text-center text-xs text-rose-500 dark:text-rose-300"
                    >
                        {{ loadError }}
                    </p>

                    <BalanceInvoiceList
                        v-else
                        :invoices="invoices"
                        @open="emit('open-invoice', $event)"
                        @adjustments="emit('adjustments', $event)"
                    />
                </div>

                <div
                    v-if="meta.last_page > 1"
                    class="flex items-center justify-between gap-3 border-t border-gray-100 px-6 py-3 dark:border-white/10"
                >
                    <p class="text-[11px] text-gray-400 dark:text-gray-500">
                        {{ meta.total }} invoices
                    </p>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            :disabled="loading || meta.current_page === 1"
                            class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:text-gray-400 dark:hover:bg-white/10"
                            @click="go(-1)"
                        >
                            <AppIcon name="chevron-left" class="h-4 w-4" />
                        </button>

                        <span
                            class="px-2 text-[11px] font-semibold text-gray-600 dark:text-gray-300"
                        >
                            {{ meta.current_page }} / {{ meta.last_page }}
                        </span>

                        <button
                            type="button"
                            :disabled="
                                loading || meta.current_page === meta.last_page
                            "
                            class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:text-gray-400 dark:hover:bg-white/10"
                            @click="go(1)"
                        >
                            <AppIcon name="chevron-right" class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
    transform: translateY(12px) scale(0.98);
}
</style>
