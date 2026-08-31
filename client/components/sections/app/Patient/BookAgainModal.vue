<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="close"
            />

            <div
                class="relative z-10 flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-secondary"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-gray-400"
                        >
                            Book Again
                        </p>

                        <h2
                            class="mt-1 truncate text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            {{ patientName }}
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-white/80"
                        @click="close"
                    >
                        <X class="h-4.5 w-4.5" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto">
                    <div
                        v-if="loadingBranch"
                        class="space-y-4 p-8"
                    >
                        <div class="h-5 w-40 animate-pulse rounded bg-slate-200 dark:bg-white/10" />
                        <div class="h-28 animate-pulse rounded-2xl bg-slate-100 dark:bg-white/5" />
                        <div class="h-28 animate-pulse rounded-2xl bg-slate-100 dark:bg-white/5" />
                    </div>

                    <div
                        v-else-if="loadError"
                        class="flex flex-col items-center gap-2 p-10 text-center"
                    >
                        <p class="text-sm font-medium text-slate-700 dark:text-gray-300">
                            Couldn't load booking options
                        </p>
                        <p class="text-xs text-muted dark:text-gray-400">
                            {{ loadError }}
                        </p>
                    </div>

                    <HomecareBooking
                        v-else
                        :model="model"
                        :services="services"
                        :homecare="branch?.homecare"
                        :settings="branch?.settings"
                        :errors="errors"
                        @update:model="(value) => Object.assign(model, value)"
                        @update:errors="(value) => (errors = value)"
                    />
                </div>

                <div
                    class="flex shrink-0 items-center justify-end gap-3 border-t border-gray-100 px-6 py-4 dark:border-white/10"
                >
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-white/10"
                        @click="close"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="submitting || loadingBranch || !!loadError"
                        @click="submit"
                    >
                        <Loader2
                            v-if="submitting"
                            class="h-4 w-4 animate-spin"
                        />
                        {{ submitting ? "Submitting..." : "Submit Booking Request" }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { X, Loader2 } from "lucide-vue-next";
import HomecareBooking from "~/components/sections/booking/provider/HomecareBooking.vue";
import { useBranch } from "~/composables/useBranchProvider";
import { serviceService } from "~/api/service/ServiceService";
import { patientAccessService } from "~/api/patient-access/PatientAccessService";
import { useToast } from "~/composables/useToast";
import { useSchemaValidation } from "~/composables/useSchemaValidation";
import { createHomecareBookingSchema } from "~/schema/booking-schema";
import type { HomecareBooking as HomecareBookingModel } from "~/types/booking";
import type { Service } from "~/types/service";

const props = defineProps<{
    open: boolean;
    patientId: number;
    patientName: string;
    branchUuid: string | null;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "booked"): void;
}>();

const { success, error: toastError } = useToast();

const { branch, fetchBranch } = useBranch();
const services = ref<Service[]>([]);
const loadingBranch = ref(false);
const loadError = ref<string | null>(null);

function emptyModel(): HomecareBookingModel {
    return {
        type: "ADL",
        date: "",
        prefered_time: "",
        time_span: "",
        address: "",
        latitude: null,
        longitude: null,
        services: [],
    };
}

const model = reactive<HomecareBookingModel>(emptyModel());
const errors = ref<Record<string, string>>({});

const homecareSchema = computed(() =>
    createHomecareBookingSchema(branch.value?.homecare?.adl_min_hour ?? 0),
);

const { validate } = useSchemaValidation(homecareSchema, model);

async function loadBranchOptions(uuid: string) {
    loadingBranch.value = true;
    loadError.value = null;

    try {
        await fetchBranch(uuid);

        const res: any = await serviceService.getBranchService(uuid);
        services.value = res?.services ?? res?.data?.services ?? [];
    } catch (err: any) {
        loadError.value =
            err?.response?.data?.message ??
            err?.message ??
            "Something went wrong loading this branch's services.";
    } finally {
        loadingBranch.value = false;
    }
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return;

        Object.assign(model, emptyModel());
        errors.value = {};

        if (props.branchUuid) {
            loadBranchOptions(props.branchUuid);
        } else {
            loadError.value = "This loved one has no branch on file.";
        }
    },
    { immediate: true },
);

const submitting = ref(false);

function priceFor(booking: HomecareBookingModel): number {
    if (booking.type === "Medical") {
        return (booking.services ?? []).reduce(
            (total, item) => total + Number(item.price || 0),
            0,
        );
    }

    const hours = Number(booking.time_span) || 0;
    const rate = Number(branch.value?.homecare?.adl_hourly_rate ?? 0);

    return hours * rate;
}

async function submit() {
    if (submitting.value) return;

    if (!validate()) return;

    submitting.value = true;

    try {
        const res: any = await patientAccessService.executeAction({
            action: "book_again",
            patient_id: props.patientId,
            category: "Homecare",
            type: model.type,
            date: model.date,
            prefered_time: model.prefered_time,
            time_span: model.time_span,
            address: model.address,
            latitude: model.latitude,
            longitude: model.longitude,
            services: model.services,
            price: priceFor(model),
        });

        success(
            res?.message ??
                "Booking request submitted. The branch will review and confirm it shortly.",
        );

        emit("booked");
        close();
    } catch (err: any) {
        toastError(
            err?.response?.data?.message ??
                err?.message ??
                "Failed to submit the booking request.",
        );
    } finally {
        submitting.value = false;
    }
}

function close() {
    emit("close");
}
</script>
