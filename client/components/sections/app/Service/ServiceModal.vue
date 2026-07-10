<script setup lang="ts">
import { categoryService } from "~/api/category/CategoryService";
import { serviceService } from "~/api/service/ServiceService";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import type { Service } from "~/types/service";
import { useRoute } from "vue-router";
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
    errors: any;
}>();

const onSubmit = () => {
    emit("submit");
};

const categoryData = ref<any>([]);

onMounted(async () => {
    const uuid = route.params.uuid as string;
    const res = await categoryService.list({ branch_uuid: uuid });
    categoryData.value = res.data.map((item: any) => ({
        label: item.category_name,
        value: item.category_name,
    }));
});
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
            @click.self="emit('close')"
        >
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                <div
                    class="flex items-center justify-between border-b border-gray-100 px-6 py-5"
                >
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ title }}
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ subtitle }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="emit('close')"
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                d="M18 6L6 18M6 6l12 12"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="space-y-5 p-6">
                    <Combobox
                        :items="categoryData"
                        v-model="form.category_name"
                        :error="errors?.category_name"
                        label="Service Category"
                        placeholder="e.g. Consultation, Laboratory, Imaging"
                        :allowCustom="true"
                        searchBar
                    />

                    <BaseInput
                        v-model="form.service_name"
                        :error="errors?.service_name"
                        placeholder="General Consultation"
                        label="Service Name"
                    />

                    <div class="grid grid-cols-2 gap-4">
                        <BaseInput
                            v-model="form.price"
                            :error="errors?.price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            label="Price"
                        />

                        <BaseInput
                            v-model="form.maximum_duration"
                            :error="errors?.maximum_duration"
                            type="text"
                            placeholder="30 mins"
                            label="Max Duration"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <Combobox
                            v-model="form.type"
                            :error="errors?.type"
                            label="Service Type"
                            :items="[
                                {
                                    label: 'Online',
                                    value: 'online',
                                },
                                {
                                    label: 'In-house Facility',
                                    value: 'facility',
                                },
                                {
                                    label: 'Online and Inhouse',
                                    value: 'both',
                                },
                            ]"
                        />
                    </div>

                    <div
                        class="flex justify-end gap-3 border-t border-gray-100 pt-5"
                    >
                        <button
                            type="button"
                            @click="emit('close')"
                            class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="submitLoading"
                            class="inline-flex min-w-[140px] items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-white transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <svg
                                v-if="submitLoading"
                                class="mr-2 h-4 w-4 animate-spin"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />
                                <path
                                    class="opacity-90"
                                    fill="currentColor"
                                    d="M22 12a10 10 0 0 0-10-10v4a6 6 0 0 1 6 6h4z"
                                />
                            </svg>

                            <span>
                                {{ submitLoading ? "Saving..." : buttonTitle }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
