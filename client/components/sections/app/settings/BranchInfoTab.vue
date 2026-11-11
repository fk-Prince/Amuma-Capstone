<template>
    <div v-if="localValue" class="space-y-6 w-full">
        <BranchCoverCard
            ref="coverRef"
            :uuid="branch?.uuid"
            :profile="localValue.image"
            :pending-cover="pendingCover"
            @select-profile="(file) => (localValue!.image = file)"
            @select-cover="(file) => (pendingCover = file)"
        />

        <ClientOnly>
            <BranchForm
                v-model:branch="localValue"
                v-model:errors="errors"
                lock-verification
                hide-image
            />
        </ClientOnly>

    </div>

    <div
        v-else
        class="py-12 text-center text-sm text-gray-400 dark:text-gray-500"
    >
        Loading branch information...
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, toRaw } from "vue";
import { formSnapshot } from "~/utils/formSnapshot";
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

// Only the fields this form saves, so Save can stay off until one changes.
const editable = (value: Branch | null | undefined) =>
    value
        ? formSnapshot({
              name: value.name,
              description: value.description,
              contact_number: value.contact_number,
              image: value.image,
              email: value.email,
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
const pendingCover = ref<File | null>(null);
const coverRef = ref<{ saveCover: (file: File) => Promise<boolean> } | null>(null);
const fieldsDirty = computed(() => Boolean(localValue.value) && editable(localValue.value) !== savedSnapshot.value);
const dirty = computed(() => fieldsDirty.value || Boolean(pendingCover.value));

function copyFrom(value: Branch) {
    const copy = structuredClone(toRaw(value));
    copy.tin = copy.settings?.tin ?? "";
    localValue.value = copy;
    savedSnapshot.value = editable(copy);
}

watch(
    branch,
    (value) => {
        if (value) {
            copyFrom(value);
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

    if (!fieldsDirty.value) {
        return saveCoverOnly();
    }

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

        if (pendingCover.value && !(await coverRef.value?.saveCover(pendingCover.value))) {
            return false;
        }

        pendingCover.value = null;
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
async function saveCoverOnly(): Promise<boolean> {
    if (!pendingCover.value) return true;

    saving.value = true;

    try {
        if (!(await coverRef.value?.saveCover(pendingCover.value))) return false;

        pendingCover.value = null;
        success("Cover photo updated.");

        return true;
    } finally {
        saving.value = false;
    }
}

// Puts back what was last saved.
function reset() {
    if (!branch.value) return;
    copyFrom(branch.value);
    pendingCover.value = null;
    errors.value = {};
}

// The Save button lives in the Branch Settings header (like My Profile).
defineExpose({ save: handleSave, saving, dirty, reset });
</script>