<template>
    <div class="w-full max-w-8xl mx-auto p-4 md:p-6 space-y-5">
        <ServiceSearch
            v-model="searchData"
            v-model:activeTab="activeTab"
            @addService="addServiceClicked"
        />

        <ServiceList
            :loading="loading"
            :services="filteredServices"
            @edit="editServiceClicked"
        />

        <ServiceModal
            v-if="modalOpen"
            :form="serviceForm"
            @close="closeModal"
            :errors="errors"
            @submit="submitService"
            :title="title"
            :subtitle="subtitle"
            :button-title="buttonTitle"
            :submitLoading="submitLoading"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { serviceService } from "~/api/service/ServiceService";
import { createServiceForm, type Service } from "~/types/service";
import ServiceSearch from "~/components/sections/app/Service/ServiceSearch.vue";
import ServiceList from "~/components/sections/app/Service/ServiceList.vue";
import ServiceModal from "~/components/sections/app/Service/ServiceModal.vue";
import { useToast } from "~/composables/useToast";

const { success, error } = useToast();

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Services" });

const route = useRoute();
const uuid = route.params.uuid as string;

const searchData = ref("");
const activeTab = ref("All Services");

const loading = ref(true);
const services = ref<Service[]>([]);

const filteredServices = computed(() => {
    let list = services.value;

    if (activeTab.value !== "All Services") {
        list = list.filter((s) => s.type === "both");
    }

    if (searchData.value.trim()) {
        const keyword = searchData.value.toLowerCase();
        list = list.filter((s) =>
            s.service_name.toLowerCase().includes(keyword),
        );
    }

    return list;
});

const serviceForm = reactive(createServiceForm());
const modalOpen = ref(false);
const submitLoading = ref(false);
const title = ref("");
const subtitle = ref("");
const buttonTitle = ref("");
const errors = ref<Record<string, string>>({});
const editingServiceId = ref<number | null>(null);

const fetchService = async () => {
    loading.value = true;
    try {
        const res: any = await serviceService.getBranchService(uuid);
        services.value = res.services ?? [];
    } catch (err: any) {
        console.error(err);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchService);

const addServiceClicked = () => {
    editingServiceId.value = null;
    Object.assign(serviceForm, createServiceForm());
    errors.value = {};
    modalOpen.value = true;
    title.value = "Add Service";
    subtitle.value = "Fill in the details below to create a new service.";
    buttonTitle.value = "Add Service";
};

const editServiceClicked = (service: Service) => {
    editingServiceId.value = service.service_id ?? null;
    errors.value = {};
    Object.assign(serviceForm, {
        service_name: service.service_name,
        category_id: service.category_id,
        category_name: service.category_name,
        price: service.price,
        maximum_duration: service.maximum_duration,
        is_available: service.is_available,
        type: service.type,
        branch_uuid: uuid,
    });
    modalOpen.value = true;
    title.value = "Update Service";
    subtitle.value = "Edit the details below to update this service.";
    buttonTitle.value = "Update Service";
};

const submitService = async () => {
    submitLoading.value = true;
    serviceForm.branch_uuid = uuid;
    try {
        const res = editingServiceId.value
            ? await serviceService.update(editingServiceId.value, serviceForm)
            : await serviceService.create(serviceForm);

        success(
            res.message ??
                (editingServiceId.value
                    ? "Service updated successfully!"
                    : "Service added successfully!"),
        );
        closeModal();
        fetchService();
    } catch (err: any) {
        const apiErrors = err?.data?.errors;
        console.error(err);

        if (apiErrors && Object.keys(apiErrors).length > 0) {
            errors.value = Object.fromEntries(
                Object.entries(apiErrors).map(([key, value]: any) => [
                    key,
                    Array.isArray(value) ? value[0] : value,
                ]),
            );
        } else {
            error(err?.data?.message ?? "Something went wrong.");
        }
    } finally {
        submitLoading.value = false;
    }
};

const closeModal = () => {
    modalOpen.value = false;
    editingServiceId.value = null;
    errors.value = {};
    submitLoading.value = false;
    Object.assign(serviceForm, createServiceForm());
};
</script>
