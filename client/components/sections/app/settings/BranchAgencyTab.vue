<template>
    <div v-if="localValue" class="space-y-6 w-full">
        <ClientOnly>
            <AgencyForm v-model:agency="localValue" v-model:errors="errors" />
        </ClientOnly>

        <div class="flex justify-end">
            <button
                type="button"
                :disabled="saving"
                @click="handleSave"
                class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium disabled:opacity-50"
            >
                {{ saving ? "Saving..." : "Save Changes" }}
            </button>
        </div>
    </div>

    <div v-else class="py-12 text-center text-sm text-gray-400">
        Loading agency information...
    </div>
</template>

<script setup lang="ts">
import { ref, watch, toRaw } from "vue";
import { agencyService } from "~/api/agency/AgencyService";
import AgencyForm from "~/components/forms/AgencyForm.vue";
import type { Agency } from "~/types/agency";
import { agencySchema2 } from "~/types/agency";
import { useToast } from "~/composables/useToast";
import { useBranchStore } from "~/stores/branch";

const branchStore = useBranchStore();
const { success, error } = useToast();

const props = defineProps<{
    uuid?: string;
}>();

const agency = defineModel<Agency | null>("agency", {
    required: true,
});

const errors = defineModel<Record<string, string>>("errors", {
    default: () => ({}),
});

const localValue = ref<Agency | null>(null);

watch(
    agency,
    (value) => {
        if (value) {
            localValue.value = structuredClone(toRaw(value));
        }
    },
    {
        immediate: true,
        deep: true,
    },
);

const saving = ref(false);

const fieldKeyMap: Record<string, string> = {
    agency_name: "agency_name",
    agency_description: "agency_description",
};

const handleSave = async (): Promise<boolean> => {
    if (!localValue.value) return false;

    const result = agencySchema2.safeParse(localValue.value);

    if (!result.success) {
        const validationErrors: Record<string, string> = {};

        result.error.issues.forEach((issue: any) => {
            const path = issue.path.join(".");

            validationErrors[fieldKeyMap[path] ?? path] = issue.message;
        });

        errors.value = validationErrors;

        return false;
    }

    errors.value = {};
    saving.value = true;

    try {
        const payload = {
            agency_name: localValue.value.agency_name,
            agency_description: localValue.value.agency_description,
            location: {
                street: localValue.value.location.street,
                city: localValue.value.location.city,
                province: localValue.value.location.province,
                country: localValue.value.location.country,
                longitude: localValue.value.location.longitude,
                latitude: localValue.value.location.latitude,
            },
        };

        const res = await agencyService.update(props.uuid as string, payload);

        agency.value = structuredClone(toRaw(localValue.value));

        await branchStore.refreshBranch();

        success(res.message);

        return true;
    } catch (err: any) {
        const serverErrors = err?.errors || err?.response?.data?.errors;

        if (serverErrors) {
            errors.value = Object.fromEntries(
                Object.entries(serverErrors).map(([key, value]) => [
                    fieldKeyMap[key] ?? key,
                    Array.isArray(value) ? value[0] : value,
                ]),
            ) as Record<string, string>;
        } else {
            error(err?.message || "Something went wrong. Please try again.");
        }
        return false;
    } finally {
        saving.value = false;
    }
};
</script>
