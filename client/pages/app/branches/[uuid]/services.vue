<template>
    <div class="w-full max-w-8xl mx-auto p-4 md:p-6 space-y-5">
        <ServiceSearch v-model="searchData" v-model:activeTab="activeTab" />

        <ServiceList :loading="loading" :services="services" />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import { serviceService } from "~/api/service/ServiceService";
import type { Service } from "~/types/service";
import ServiceSearch from "~/components/sections/app/Service/ServiceSearch.vue";
import ServiceList from "~/components/sections/app/Service/ServiceList.vue";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Branches" });

const route = useRoute();
const searchData = ref("");
const activeTab = ref("All Services");

const loading = ref(true);
const services = ref<Service[]>([]);

const loadServices = async () => {
    loading.value = true;
    try {
        const uuid = route.params.uuid as string;
        const res: any = await serviceService.getBranchService(uuid);
        services.value = res.services ?? [];
    } finally {
        loading.value = false;
    }
};

onMounted(loadServices);
</script>
