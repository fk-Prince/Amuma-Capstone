<template>
    <div class="min-h-screen bg-slate-50">
        <div class="mx-auto max-w-[1700px] space-y-6 p-4 md:p-6">
            <PageHeader
                title="Medical Services"
                subtitle="Healthcare"
                description="View and manage available medical services."
            />

            <div
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="border-b border-slate-100 p-5">
                    <ServiceSearch
                        v-model="searchData"
                        v-model:activeTab="activeTab"
                        @addService="addServiceClicked"
                    />
                </div>

                <div class="p-5">
                    <div class="mb-5 flex justify-end">
                        <span
                            class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600"
                        >
                            {{ filteredServices.length }}
                            {{
                                filteredServices.length === 1
                                    ? "Service"
                                    : "Services"
                            }}
                        </span>
                    </div>

                    <ServiceList
                        :loading="loading"
                        :services="filteredServices"
                        @edit="editServiceClicked"
                        @assign="assignService"
                    />
                </div>
            </div>

            <ServiceModal
                v-if="modalOpen"
                :form="serviceForm"
                :action="editingServiceId ? 'update' : 'create'"
                :errors="errors"
                :title="title"
                :subtitle="subtitle"
                :button-title="buttonTitle"
                :submitLoading="submitLoading"
                @close="closeModal"
                @submit="submitService"
            />

            <ServiceEmployeeModal
                v-if="selectedService"
                :open="showAssignModal"
                :service="selectedService"
                @close="showAssignModal = false"
            />
        </div>
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
import ServiceEmployeeModal from "~/components/sections/app/Service/ServiceEmployeeModal.vue";

import PageHeader from "~/components/ui/PageHeader.vue";
import { useToast } from "~/composables/useToast";

const { success, error } = useToast();

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Services",
});

const route = useRoute();
const uuid = route.params.uuid as string;

const searchData = ref("");
const activeTab = ref("All Services");

const loading = ref(true);
const submitLoading = ref(false);

const services = ref<Service[]>([]);

const modalOpen = ref(false);
const showAssignModal = ref(false);

const serviceForm = reactive(createServiceForm());

const selectedService = ref<Service | null>(null);

const title = ref("");
const subtitle = ref("");
const buttonTitle = ref("");

const errors = ref<Record<string, string>>({});

const editingServiceId = ref<number | null>(null);

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

const fetchService = async () => {
    loading.value = true;

    try {
        const res: any = await serviceService.getBranchService(uuid);

        services.value = res.services ?? [];
    } catch (err) {
        console.error(err);
    } finally {
        loading.value = false;
    }
};

onMounted(fetchService);

const addServiceClicked = () => {
    editingServiceId.value = null;

    Object.assign(serviceForm, createServiceForm(), {
        branch_uuid: uuid,
        is_available: true,
    });

    errors.value = {};

    title.value = "Add Service";
    subtitle.value = "Fill in the details below to create a new service.";
    buttonTitle.value = "Add Service";

    modalOpen.value = true;
};

const editServiceClicked = (service: Service) => {
    editingServiceId.value = service.service_id ?? null;

    Object.assign(serviceForm, {
        service_name: service.service_name,
        category_id: service.category_id,
        category_name: service.category_name,
        price: service.price,
        maximum_duration: service.maximum_duration,
        is_available: service.is_available ?? true,
        type: service.type,
        branch_uuid: uuid,
    });

    errors.value = {};

    title.value = "Update Service";
    subtitle.value = "Edit the details below to update this service.";
    buttonTitle.value = "Update Service";

    modalOpen.value = true;
};

const assignService = (service: Service) => {
    selectedService.value = service;
    showAssignModal.value = true;
};

const submitService = async () => {
    submitLoading.value = true;

    try {
        serviceForm.branch_uuid = uuid;

        let res: any;

        if (editingServiceId.value) {
            res = await serviceService.update(
                editingServiceId.value,
                serviceForm,
            );
            const targetService = services.value.find(
                (item) => item.service_id === editingServiceId.value,
            );
            if (targetService) {
                Object.assign(targetService, res.data);
            }
            success(res.message ?? "Service updated successfully!");
        } else {
            res = await serviceService.create(serviceForm);
            services.value.unshift(res.data);
            success(res.message ?? "Service created successfully!");
        }
        closeModal();
    } catch (err: any) {
        console.error(err);
        const validationErrors = err?.data?.errors;
        if (validationErrors) {
            errors.value = Object.fromEntries(
                Object.entries(validationErrors).map(([key, value]: any) => [
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
    submitLoading.value = false;
    errors.value = {};
    Object.assign(serviceForm, createServiceForm());
};
</script>
