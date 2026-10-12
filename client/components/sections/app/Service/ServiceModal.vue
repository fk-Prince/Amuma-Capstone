<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed, watch } from "vue";
import { X, Check, LoaderCircle, Stethoscope } from "lucide-vue-next";
import { categoryService } from "~/api/category/CategoryService";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import { useRoute } from "vue-router";
import type { Service } from "~/types/service";
import { createServiceSchema } from "~/types/service";
import { useSchemaValidation } from "~/composables/useSchemaValidation";
import ToggleSwitch from "~/components/ui/ToggleSwitch.vue";
import { timeConverter } from "~/utils/time";

const route = useRoute();

const emit = defineEmits<{
    close: [];
    submit: [];
}>();

const props = defineProps<{
    form: Service;
    title?: string;
    subtitle?: string;
    buttonTitle?: string;
    submitLoading?: boolean;
    action?: "create" | "update";
    errors?: Record<string, string>;
}>();

const durationType = ref<"time" | "minutes">(
    (props.form as any).durationType ??
        (props.form.maximum_duration?.includes(":") ? "time" : "minutes"),
);

const {
    errors: schemaErrors,
    validate,
    clearError: clearSchemaError,
    reset,
} = useSchemaValidation(
    computed(() => createServiceSchema(durationType.value)),
    props.form,
);

// Errors the server sent back, kept until the field is edited.
const serverErrors = ref<Record<string, string>>({});

watch(
    () => props.errors,
    (value) => {
        serverErrors.value = { ...(value ?? {}) };
    },
    { immediate: true, deep: true },
);

const errors = computed(() => ({
    ...serverErrors.value,
    ...schemaErrors.value,
}));

const clearError = (field: string) => {
    clearSchemaError(field);
    delete serverErrors.value[field];
};

watch(
    durationType,
    (value) => {
        (props.form as any).durationType = value;
        clearError("durationType");
    },
    { immediate: true },
);

const changeDurationType = (type: "minutes" | "time") => {
    props.form.maximum_duration = String(
        timeConverter(props.form.maximum_duration, type),
    );
    durationType.value = type;
    clearError("maximum_duration");
};

const categoryData = ref<any[]>([]);
const categoryLoadError = ref(false);

const onKeydown = (e: KeyboardEvent) => {
    if (e.key === "Escape") {
        e.preventDefault();
        emit("close");
    }
};

onMounted(async () => {
    document.addEventListener("keydown", onKeydown, true);

    try {
        const uuid = route.params.uuid as string;

        const res = await categoryService.list({
            branch_uuid: uuid,
        });

        categoryData.value = res.data.map((item: any) => ({
            label: item.category_name,
            value: item.category_name,
        }));
    } catch (err) {
        categoryLoadError.value = true;
        console.error("Failed to load service categories", err);
    }
});

onUnmounted(() => {
    document.removeEventListener("keydown", onKeydown, true);
});

const staffRoleError = ref("");

const allowsNurse = computed(
    () => (props.form.staff_role ?? "both") !== "caregiver",
);
const allowsCaregiver = computed(
    () => (props.form.staff_role ?? "both") !== "nurse",
);

const toggleStaffRole = (role: "nurse" | "caregiver") => {
    const nurse = role === "nurse" ? !allowsNurse.value : allowsNurse.value;
    const caregiver =
        role === "caregiver" ? !allowsCaregiver.value : allowsCaregiver.value;

    if (!nurse && !caregiver) {
        staffRoleError.value = "Select at least one role.";
        return;
    }

    staffRoleError.value = "";
    props.form.staff_role =
        nurse && caregiver ? "both" : nurse ? "nurse" : "caregiver";
};

const staffRoleOptions = computed(() => [
    { value: "nurse" as const, label: "Nurse", checked: allowsNurse.value },
    {
        value: "caregiver" as const,
        label: "Caregiver",
        checked: allowsCaregiver.value,
    },
]);

const onStaffRoleChange = (role: "nurse" | "caregiver", event: Event) => {
    toggleStaffRole(role);
    (event.target as HTMLInputElement).checked =
        role === "nurse" ? allowsNurse.value : allowsCaregiver.value;
};

const onSubmit = () => {
    if (!validate()) return;
    emit("submit");
};

watch(
    () => props.form.maximum_duration,
    (value) => {
        durationType.value = value?.includes(":") ? "time" : "minutes";
    },
    { immediate: true },
);
</script>
<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
            >
                <Transition
                    appear
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary"
                        role="dialog"
                        aria-modal="true"
                        :aria-label="title"
                    >
                        <div
                            class="sticky top-0 z-10 flex items-center justify-between gap-4 rounded-t-2xl border-b border-gray-100 bg-white/95 px-6 py-5 backdrop-blur dark:bg-secondary/95 dark:border-white/10"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                >
                                    <Stethoscope class="h-5 w-5" />
                                </div>

                                <div>
                                    <h2
                                        class="text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                                    >
                                        {{ title }}
                                    </h2>

                                    <p
                                        v-if="subtitle"
                                        class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        {{ subtitle }}
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="emit('close')"
                                aria-label="Close dialog"
                                class="shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <form @submit.prevent="onSubmit" class="p-6">
                            <div class="space-y-6">
                                <ToggleSwitch
                                    v-if="action === 'update'"
                                    v-model="form.is_available"
                                    label="Status"
                                    :description="
                                        form.is_available
                                            ? 'Service is currently active'
                                            : 'Service is currently inactive'
                                    "
                                />

                                <div>
                                    <Combobox
                                        :items="categoryData"
                                        v-model="form.category_name"
                                        label="Service Category"
                                        placeholder="Search service category..."
                                        searchBar
                                        searchBarPlaceHolder="Select category or Add Service"
                                        :allowCustom="true"
                                        :error="errors?.category_name"
                                        @update:modelValue="
                                            clearError('category_name')
                                        "
                                    />
                                    <p
                                        v-if="categoryLoadError"
                                        class="mt-1.5 text-xs text-amber-600 dark:text-amber-300"
                                    >
                                        Couldn't load existing categories — you
                                        can still type a new one.
                                    </p>
                                </div>

                                <BaseInput
                                    v-model="form.service_name"
                                    label="Service Name"
                                    placeholder="e.g. General Consultation"
                                    :error="errors?.service_name"
                                    @update:modelValue="
                                        clearError('service_name')
                                    "
                                />

                                <div>
                                    <label
                                        for="service-description"
                                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Description
                                        <span
                                            class="font-normal text-slate-400 dark:text-gray-500"
                                        >
                                            (optional)
                                        </span>
                                    </label>
                                    <textarea
                                        id="service-description"
                                        v-model="form.description"
                                        rows="3"
                                        maxlength="1000"
                                        placeholder="What does this service include?"
                                        class="w-full resize-none rounded-xl border bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/10 dark:bg-secondary dark:text-white dark:placeholder:text-gray-500"
                                        :class="
                                            errors?.description
                                                ? 'border-red-400'
                                                : 'border-slate-200 dark:border-white/10'
                                        "
                                        @input="clearError('description')"
                                    />
                                    <p
                                        v-if="errors?.description"
                                        class="mt-1 text-xs text-red-500"
                                    >
                                        {{ errors.description }}
                                    </p>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 items-center"
                                >
                                    <BaseInput
                                        v-model="form.price"
                                        type="number"
                                        class="mt-2"
                                        step="0.01"
                                        min="0"
                                        label="Price"
                                        placeholder="e.g. 500"
                                        :error="errors?.price"
                                        @update:modelValue="clearError('price')"
                                    />
                                    <div>
                                        <div
                                            class="flex items-end justify-between"
                                        >
                                            <label
                                                class="mb-1 flex items-center text-end gap-2 text-sm font-semibold text-slate-700 dark:text-gray-400"
                                            >
                                                Maximum Duration
                                            </label>

                                            <div
                                                class="inline-flex shrink-0 rounded-lg border border-slate-200 bg-slate-50 p-0.5 mb-0.5 dark:border-white/10 dark:bg-white/5"
                                            >
                                                <button
                                                    type="button"
                                                    @click="
                                                        changeDurationType(
                                                            'minutes',
                                                        )
                                                    "
                                                    class="rounded-md px-2.5 py-1 text-xs font-medium transition"
                                                    :class="
                                                        durationType ===
                                                        'minutes'
                                                            ? 'bg-white text-slate-900 shadow-sm dark:bg-secondary dark:text-white'
                                                            : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-400'
                                                    "
                                                >
                                                    Minutes
                                                </button>

                                                <button
                                                    type="button"
                                                    @click="
                                                        changeDurationType(
                                                            'time',
                                                        )
                                                    "
                                                    class="rounded-md px-2.5 py-1 text-xs font-medium transition"
                                                    :class="
                                                        durationType === 'time'
                                                            ? 'bg-white text-slate-900 shadow-sm dark:bg-secondary dark:text-white'
                                                            : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-400'
                                                    "
                                                >
                                                    HH:mm:ss
                                                </button>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <BaseInput
                                                v-if="durationType === 'time'"
                                                v-model="form.maximum_duration"
                                                class="flex-1"
                                                placeholder="e.g. 00:30:00"
                                                :error="
                                                    errors?.maximum_duration
                                                "
                                                @update:modelValue="
                                                    clearError(
                                                        'maximum_duration',
                                                    )
                                                "
                                            />

                                            <BaseInput
                                                v-if="
                                                    durationType === 'minutes'
                                                "
                                                v-model="form.maximum_duration"
                                                class="flex-1"
                                                placeholder="e.g. 30"
                                                :error="
                                                    errors?.maximum_duration
                                                "
                                                @update:modelValue="
                                                    clearError(
                                                        'maximum_duration',
                                                    )
                                                "
                                            >
                                                <template #suffix>
                                                    <span
                                                        class="border-l border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
                                                    >
                                                        Minutes
                                                    </span>
                                                </template>
                                            </BaseInput>
                                        </div>
                                    </div>
                                </div>

                                <Combobox
                                    v-model="form.type"
                                    label="Service Type"
                                    position="top"
                                    placeholder="Select service type"
                                    :error="errors?.type"
                                    @update:modelValue="clearError('type')"
                                    :items="[
                                        {
                                            label: 'Homecare',
                                            value: 'online',
                                        },
                                        {
                                            label: 'In-house Facility',
                                            value: 'facility',
                                        },
                                        {
                                            label: 'Homecare and Inhouse',
                                            value: 'both',
                                        },
                                    ]"
                                />

                                <div>
                                    <p
                                        class="mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        Performed by
                                    </p>
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <label
                                            v-for="option in staffRoleOptions"
                                            :key="option.value"
                                            class="flex cursor-pointer items-center gap-3 rounded-xl border px-4 py-3 text-sm transition"
                                            :class="
                                                option.checked
                                                    ? 'border-primary bg-primary/5 text-gray-900 dark:bg-primary-500/10 dark:text-white'
                                                    : 'border-gray-200 text-gray-600 hover:border-primary/40 dark:border-white/10 dark:text-gray-400'
                                            "
                                        >
                                            <input
                                                type="checkbox"
                                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary dark:border-white/20 dark:bg-white/5"
                                                :checked="option.checked"
                                                @change="onStaffRoleChange(option.value, $event)"
                                            />
                                            <span class="font-medium">{{
                                                option.label
                                            }}</span>
                                        </label>
                                    </div>
                                    <p
                                        v-if="staffRoleError"
                                        class="mt-1.5 text-xs text-red-500"
                                    >
                                        {{ staffRoleError }}
                                    </p>
                                    <p
                                        v-else
                                        class="mt-1.5 text-xs text-gray-400 dark:text-gray-500"
                                    >
                                        Only staff with a checked role can be
                                        assigned to this service.
                                    </p>
                                </div>
                            </div>

                            <div
                                class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end dark:border-white/10"
                            >
                                <button
                                    type="button"
                                    @click="emit('close')"
                                    class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-300 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5 dark:focus-visible:ring-white/10"
                                >
                                    Cancel
                                </button>

                                <button
                                    type="submit"
                                    :disabled="submitLoading"
                                    class="inline-flex min-w-[150px] items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    <LoaderCircle
                                        v-if="submitLoading"
                                        class="h-4 w-4 animate-spin"
                                    />

                                    <Check v-else class="h-4 w-4" />

                                    {{
                                        submitLoading
                                            ? "Saving Service..."
                                            : buttonTitle
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
