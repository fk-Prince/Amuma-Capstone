<template>
    <div v-if="localValue" class="space-y-6 w-full">
        <ClientOnly>
            <BranchForm v-model:branch="localValue" v-model:errors="errors" />

            <BranchHoursTab
                v-if="localValue.settings"
                v-model:setting="localValue.settings"
            />
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
        Loading branch information...
    </div>
</template>

<script setup lang="ts">
import { ref, watch, toRaw } from "vue";
import BranchHoursTab from "~/components/sections/app/settings/BranchHoursTab.vue";
import BranchForm from "~/components/forms/BranchForm.vue";
import { branchService } from "~/api/branch/BranchService";
import { branchSchema } from "~/types/branch";
import type { Branch } from "~/types/branch";
import { useToast } from "~/composables/useToast";
import { useBranchStore } from "~/stores/branch";

const branchStore = useBranchStore();
const { success, error } = useToast();

const branch = defineModel<Branch | null>("branch", {
    required: true,
});

const errors = defineModel<Record<string, string>>("errors", {
    default: () => ({}),
});

const localValue = ref<Branch | null>(null);

watch(
    branch,
    (value) => {
        if (value) {
            localValue.value = structuredClone(toRaw(value));
        }
    },
    {
        deep: true,
        immediate: true,
    },
);

const saving = ref(false);

const fieldKeyMap: Record<string, string> = {
    name: "branch_name",
    description: "branch_description",
    contact_number: "branch_contact_number",
    image: "branch_image",
};

const handleSave = async (): Promise<boolean> => {
    if (!localValue.value) return false;
    const result = branchSchema.safeParse(localValue.value);

    if (!result.success) {
        const validationErrors: Record<string, string> = {};

        result.error.issues.forEach((issue) => {
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
            name: localValue.value.name,
            description: localValue.value.description,
            contact_number: localValue.value.contact_number,
            image: localValue.value.image,
            location: {
                street: localValue.value.location.street,
                city: localValue.value.location.city,
                province: localValue.value.location.province,
                country: localValue.value.location.country,
                longitude: localValue.value.location.longitude,
                latitude: localValue.value.location.latitude,
            },
            settings: localValue.value.settings,
        };

        const res = await branchService.update(
            branch?.value?.uuid as string,
            payload,
        );

        branch.value = {
            ...toRaw(localValue.value),
            image: localValue.value.image,
        };

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
