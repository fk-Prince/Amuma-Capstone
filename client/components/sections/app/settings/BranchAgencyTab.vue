<template>
    <div v-if="localValue" class="space-y-6 w-full">
        <ClientOnly>
            <AgencyForm
                v-model:agency="localValue"
                v-model:errors="errors"
                lock-verification
            />
        </ClientOnly>

    </div>

    <div
        v-else
        class="py-12 text-center text-sm text-gray-400 dark:text-gray-500"
    >
        Loading agency information...
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, toRaw } from "vue";
import { formSnapshot } from "~/utils/formSnapshot";
import { agencyService } from "~/api/agency/AgencyService";
import AgencyForm from "~/components/forms/AgencyForm.vue";
import type { Agency } from "~/types/agency";
import { agencySchema } from "~/schema/agency-schema";
import { useToast } from "~/composables/useToast";
import { useBranchStore } from "~/stores/branch";
import { useRoute } from "vue-router";

const branchStore = useBranchStore();
const { success, error } = useToast();
const route = useRoute();
const props = defineProps<{
    uuid?: string;
}>();

const agency = defineModel<Agency | null | undefined>("agency", {
    required: true,
});

const errors = defineModel<Record<string, string>>("errors", {
    default: () => ({}),
});

const localValue = ref<Agency | null>(null);

// Only the fields this form saves, so Save can stay off until one changes.
const editable = (value: Agency | null | undefined) =>
    value
        ? formSnapshot({
              name: value.name,
              description: value.description,
              email: value.email,
              image: value.image,
              location: value.location
                  ? {
                        street: value.location.street,
                        city: value.location.city,
                        province: value.location.province,
                        country: value.location.country,
                        longitude: value.location.longitude,
                        latitude: value.location.latitude,
                    }
                  : null,
          })
        : "";

const savedSnapshot = ref("");
const dirty = computed(() => Boolean(localValue.value) && editable(localValue.value) !== savedSnapshot.value);

watch(
    agency,
    (value) => {
        if (value) {
            localValue.value = structuredClone(toRaw(value));
            savedSnapshot.value = editable(localValue.value);
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
    agency_email: "agency_email",
};

const handleSave = async (): Promise<boolean> => {
    if (!localValue.value) return false;

    // Verification files are read-only here and never sent, so requiring them
    // would block every other edit.
    const result = agencySchema
        .omit({ id_front: true, id_back: true, id_selfie: true, document: true })
        .safeParse(localValue.value);

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
            branch_uuid: route.params.uuid as string,
            agency_name: localValue.value.name,
            agency_description: localValue.value.description,
            agency_email: localValue.value.email,
            agency_image: localValue.value.image,
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
        await branchStore.refreshBranch();
        agency.value = branchStore.activeBranch?.agency ?? null;
        localValue.value = agency.value ?? null;

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
// Puts back what was last saved.
function reset() {
    if (!agency.value) return;
    localValue.value = structuredClone(toRaw(agency.value));
    savedSnapshot.value = editable(localValue.value);
    errors.value = {};
}

// The Save button lives in the Branch Settings header (like My Profile).
defineExpose({ save: handleSave, saving, dirty, reset });
</script>
