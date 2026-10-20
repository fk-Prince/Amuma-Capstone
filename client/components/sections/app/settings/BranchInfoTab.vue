<template>
    <div v-if="localValue" class="space-y-6 w-full">
        <BranchCoverCard
            :uuid="branch?.uuid"
            :profile="localValue.image"
            @select-profile="(file) => (localValue!.image = file)"
        />

        <ClientOnly>
            <BranchForm
                v-model:branch="localValue"
                v-model:errors="errors"
                lock-verification
                hide-image
            />
        </ClientOnly>

        <StickyActionBar hint="Remember to save before leaving this tab.">
            <button
                type="button"
                :disabled="saving"
                @click="handleSave"
                class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm shadow-primary/30 transition hover:bg-primary-600 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
            >
                <span
                    v-if="saving"
                    class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/30 border-t-white"
                />
                {{ saving ? "Saving..." : "Save Changes" }}
            </button>
        </StickyActionBar>
    </div>

    <div
        v-else
        class="py-12 text-center text-sm text-gray-400 dark:text-gray-500"
    >
        Loading branch information...
    </div>
</template>

<script setup lang="ts">
import StickyActionBar from "~/components/ui/StickyActionBar.vue";
import { ref, watch, toRaw } from "vue";
import BranchForm from "~/components/forms/BranchForm.vue";
import BranchCoverCard from "~/components/sections/app/settings/BranchCoverCard.vue";
import { branchService } from "~/api/branch/BranchService";
import { branchSchema } from "~/schema/branch-schema";
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
            const copy = structuredClone(toRaw(value));
            copy.tin = copy.settings?.tin ?? "";
            localValue.value = copy;
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
    email: "branch_email",
    tin: "branch_tin",
};

const handleSave = async (): Promise<boolean> => {
    if (!localValue.value) return false;
    // Verification is read-only here and never sent, so requiring it would
    // block every other edit — including for branches predating the TIN.
    const result = branchSchema
        .omit({ tin: true, document: true })
        .safeParse(localValue.value);

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
            branch_uuid: branch?.value?.uuid,
            name: localValue.value.name,
            description: localValue.value.description,
            contact_number: localValue.value.contact_number,
            image: localValue.value.image,
            email: localValue.value.email,
            location: {
                street: localValue.value.location.street,
                city: localValue.value.location.city,
                province: localValue.value.location.province,
                country: localValue.value.location.country,
                longitude: localValue.value.location.longitude,
                latitude: localValue.value.location.latitude,
            },
            // settings: localValue.value.settings,
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