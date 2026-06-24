<template>
    <div>
        <div v-if="loading" class="flex justify-center py-12">
            <div
                class="h-10 w-10 animate-spin rounded-full border-4 border-gray-300 border-t-blue-600"
            />
        </div>

        <ServiceSection
            v-else
            :services="services"
            :loading="loading"
            @refresh="loadServices"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import { serviceService } from "~/api/service/ServiceService";
import ServiceSection from "~/components/sections/app/ServiceSection.vue";
import type { Service } from "~/types/service";
import { useAuthReady } from "~/composables/useAuthUser";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Branches" });

const route = useRoute();

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
