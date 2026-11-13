<script setup lang="ts">
import { computed, ref } from "vue";
import { Loader2 } from "lucide-vue-next";
import LocationSelector from "~/components/ui/LocationSelector.vue";
import { scheduleService } from "~/api/schedule/ScheduleService";
import { useToast } from "~/composables/useToast";
import type { ScheduleItem } from "~/types/schedule";

type PickedLocation = {
    lat: number | null;
    lng: number | null;
    label: string;
    street: string;
    city: string;
    province: string;
    country: string;
};

const props = defineProps<{
    schedule: ScheduleItem;
    branchUuid: string;
}>();

const emit = defineEmits<{
    cancel: [];
    saved: [location: { address: string; latitude: number | null; longitude: number | null }];
}>();

const { success, error: toastError } = useToast();

const selectorRef = ref<InstanceType<typeof LocationSelector> | null>(null);
const picked = ref<PickedLocation | null>(null);
const saving = ref(false);

const changed = computed(() => {
    const next = picked.value;
    if (!next) return false;

    return (
        next.label !== props.schedule.address ||
        next.lat !== (props.schedule.latitude ?? null) ||
        next.lng !== (props.schedule.longitude ?? null)
    );
});

async function save() {
    const next = picked.value;
    if (!next || saving.value) return;

    saving.value = true;

    try {
        const res = await scheduleService.action({
            type: "update_location",
            branch_uuid: props.branchUuid,
            schedule_id: props.schedule.schedule_id,
            street: next.street,
            city: next.city,
            province: next.province,
            country: next.country,
            full_address: next.label,
            latitude: next.lat,
            longitude: next.lng,
        });

        success(res?.message ?? "Location updated.");
        emit("saved", res?.data ?? { address: next.label, latitude: next.lat, longitude: next.lng });
    } catch (err: any) {
        toastError(err?.message ?? "Unable to update the location.");
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                    Homecare Service Address
                    <span class="text-danger">*</span>
                </h3>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                    Where the caregiver or nurse should visit. This can differ
                    from the patient's home address.
                </p>
            </div>

            <button
                v-if="picked"
                type="button"
                class="whitespace-nowrap text-xs font-medium text-red-500 hover:text-red-600"
                @click="selectorRef?.clearSelection()"
            >
                Reset
            </button>
        </div>

        <ClientOnly>
            <LocationSelector
                ref="selectorRef"
                :initial-lat="schedule.latitude ?? undefined"
                :initial-lng="schedule.longitude ?? undefined"
                :initial-street="
                    schedule.address && schedule.address !== 'No address on file'
                        ? schedule.address
                        : undefined
                "
                @location-selected="picked = $event"
                @location-cleared="picked = null"
            />

            <template #fallback>
                <div
                    class="h-[400px] w-full animate-pulse rounded-xl border border-gray-200 bg-slate-50 dark:border-white/10 dark:bg-secondary"
                />
            </template>
        </ClientOnly>

        <div class="flex justify-end gap-2">
            <button
                type="button"
                class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                :disabled="saving"
                @click="emit('cancel')"
            >
                Cancel
            </button>

            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90 disabled:opacity-40"
                :disabled="saving || !changed"
                @click="save"
            >
                <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
                Save location
            </button>
        </div>
    </div>
</template>
