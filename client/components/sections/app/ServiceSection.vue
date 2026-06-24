<template>
    <div class="p-6 w-full">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold mb-6">Services</h1>
            <BaseButton @click="addService"> ADD SERVICE </BaseButton>
        </div>

        <div v-if="loading" class="flex justify-center py-12">
            <div
                class="h-10 w-10 animate-spin rounded-full border-4 border-gray-300 border-t-blue-600"
            ></div>
        </div>

        <DynamicTable
            v-else
            :columns="tableColumns"
            :data="services"
            :items-per-page="5"
        >
            <template #cell-price="{ value }">
                ₱{{ Number(value).toFixed(2) }}
            </template>

            <template #cell-is_available="{ value }">
                <span
                    :class="[
                        'px-3 py-1 rounded-full text-sm font-medium',
                        value
                            ? 'bg-green-100 text-green-800'
                            : 'bg-red-100 text-red-800',
                    ]"
                >
                    {{ value ? "Available" : "Unavailable" }}
                </span>
            </template>

            <template #cell-actions="{ row }">
                <button
                    @click="editService(row)"
                    class="text-blue-600 hover:underline mr-3"
                >
                    Edit
                </button>

                <button
                    @click="deleteService(row)"
                    class="text-red-600 hover:underline"
                >
                    Delete
                </button>
            </template>
        </DynamicTable>

        <DynamicModal
            v-if="showModal"
            title="Add Service"
            :inputs="inputs"
            :errors="errors"
            className="max-w-2xl px-10"
            @close="showModal = false"
            @save="saveService"
        />
    </div>
</template>

<script setup lang="ts">
import BaseButton from "~/components/ui/BaseButton.vue";
import DynamicTable from "../DynamicTable.vue";
import {
    createServiceForm,
    serviceSchema,
    type Service,
} from "~/types/service";
import DynamicModal from "../DynamicModal.vue";
import { useRoute } from "vue-router";
import { serviceService } from "~/api/service/ServiceService";

const errors = ref<Record<string, string[]>>({});
const route = useRoute();
const showModal = ref(false);
const form = ref<Service>(createServiceForm());
defineProps<{
    services: Service[];
    loading: boolean;
}>();
const emit = defineEmits(["refresh"]);

const tableColumns = [
    { key: "service_name", label: "Service Name" },
    { key: "category_name", label: "Category" },
    { key: "price", label: "Price" },
    { key: "type", label: "Type" },
    { key: "maximum_duration", label: "Duration" },
    { key: "is_available", label: "Status" },
    { key: "actions", label: "Actions" },
];

const inputs: any = [
    {
        type: "input",
        name: "service_name",
        label: "Service Name",
        placeholder: "Enter service name",
    },
    {
        type: "input",
        name: "duration",
        label: "Duration",
        placeholder: "Enter estimated duration HH:MM:SS",
    },
    {
        type: "input",
        name: "price",
        label: "Price",
        placeholder: "Enter price",
    },
    {
        type: "combobox",
        name: "category",
        label: "Category",
        placeholder: "Select category",
        allowCustom: true,
        items: [
            { label: "Haircut", value: "haircut" },
            { label: "Massage", value: "massage" },
            { label: "Nails", value: "nails" },
        ],
    },
    {
        type: "combobox",
        name: "type",
        label: "Service Applicable",
        placeholder: "Select type",
        items: [
            { label: "Online", value: "online" },
            { label: "Facility", value: "facility" },
            { label: "Both", value: "both" },
        ],
    },
];

const editService = (service: any) => {
    alert("EDIT:");
};

const deleteService = (service: any) => {
    alert("Delete:");
};

const addService = () => {
    showModal.value = true;
};
const saveService = async (data: any) => {
    console.log(data);
    const result = serviceSchema.safeParse(data);

    if (!result.success) {
        const fieldErrors = result.error.flatten().fieldErrors;

        errors.value = Object.fromEntries(
            Object.entries(fieldErrors).map(([key, value]) => [
                key,
                value ?? [],
            ]),
        ) as Record<string, string[]>;

        return;
    }
    errors.value = {};

    form.value.category_name = data.category;
    form.value.maximum_duration = data.duration;
    form.value.price = data.price;
    form.value.service_name = data.service_name;
    form.value.type = data.type;
    try {
        const res = await serviceService.create(form.value);
        form.value = createServiceForm();
        form.value.branch_uuid = route.params.uuid as string;
        alert(res.message);
        emit("refresh");
        showModal.value = false;
    } catch (err: any) {
        alert(err.message || err);
    }
};

onMounted(async () => {
    form.value.branch_uuid = route.params.uuid as string;
});
</script>
