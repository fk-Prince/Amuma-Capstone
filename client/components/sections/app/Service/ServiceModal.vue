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
}>();

const durationType = ref<"time" | "minutes">(
    (props.form as any).durationType ??
        (props.form.maximum_duration?.includes(":") ? "time" : "minutes"),
);

const { errors, validate, clearError, reset } = useSchemaValidation(
    computed(() => createServiceSchema(durationType.value)),
    props.form,
);

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
                @click.self="emit('close')"
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
                        class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
                        role="dialog"
                        aria-modal="true"
                        :aria-label="title"
                    >
                        <div
                            class="sticky top-0 z-10 flex items-center justify-between gap-4 rounded-t-2xl border-b border-gray-100 bg-white/95 px-6 py-5 backdrop-blur"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                >
                                    <Stethoscope class="h-5 w-5" />
                                </div>

                                <div>
                                    <h2
                                        class="text-lg font-semibold leading-tight text-gray-900"
                                    >
                                        {{ title }}
                                    </h2>

                                    <p
                                        v-if="subtitle"
                                        class="mt-0.5 text-sm text-gray-500"
                                    >
                                        {{ subtitle }}
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="emit('close')"
                                aria-label="Close dialog"
                                class="shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
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
                                        class="mt-1.5 text-xs text-amber-600"
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
                                                class="mb-1 flex items-center text-end gap-2 text-sm font-semibold text-slate-700"
                                            >
                                                Maximum Duration
                                            </label>

                                            <div
                                                class="inline-flex shrink-0 rounded-lg border border-slate-200 bg-slate-50 p-0.5 mb-0.5"
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
                                                            ? 'bg-white text-slate-900 shadow-sm'
                                                            : 'text-slate-500 hover:text-slate-700'
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
                                                            ? 'bg-white text-slate-900 shadow-sm'
                                                            : 'text-slate-500 hover:text-slate-700'
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
                                                        class="border-l border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-500"
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
                            </div>

                            <div
                                class="mt-8 flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end"
                            >
                                <button
                                    type="button"
                                    @click="emit('close')"
                                    class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-300"
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
