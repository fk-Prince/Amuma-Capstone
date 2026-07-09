<script setup lang="ts">
import { roomService } from "~/api/room/RoomService";
import RoomList from "~/components/sections/app/Room/RoomList.vue";
import RoomSearch from "~/components/sections/app/Room/RoomSearch.vue";
import type { Room } from "~/types/room";
import { useRoute } from "vue-router";
import RoomDashboard from "~/components/sections/app/Room/RoomDashboard.vue";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Room & Beds" });
const searchData = ref("");
const activeTab = ref("All Rooms");
const expandedRooms = ref<number[]>([]);

const toggleRoom = (id: number) => {
    expandedRooms.value.includes(id)
        ? (expandedRooms.value = expandedRooms.value.filter((x) => x !== id))
        : expandedRooms.value.push(id);
};

const route = useRoute();
const uuid = route.params.uuid as string;
const loading = ref(true);
const roomData = ref<Room[]>([]);
onMounted(async () => {
    loading.value = true;
    try {
        const res = await roomService.list({
            per_page: 10,
            branch_uuid: uuid,
        });
        roomData.value = res.data;
    } catch (err: any) {
        console.error(err);
    } finally {
        loading.value = false;
    }
});

const filteredRooms = computed(() => {
    let rooms = roomData.value;

    if (activeTab.value === "VIP Rooms") {
        rooms = rooms.filter((room: Room) => room.room_type === "VIP");
    }

    if (activeTab.value === "Common Rooms") {
        rooms = rooms.filter((room: Room) => room.room_type === "Common");
    }

    if (searchData.value.trim()) {
        const keyword = searchData.value.toLowerCase();

        rooms = rooms.filter((room: Room) => {
            const roomMatch = room.room_no.toLowerCase().includes(keyword);

            const patientMatch = room.beds.some((bed) => {
                const patientName = `${bed.patient?.first_name ?? ""} ${
                    bed.patient?.last_name ?? ""
                }`.toLowerCase();

                return patientName.includes(keyword);
            });

            return roomMatch || patientMatch;
        });
    }

    return rooms;
});
</script>

<template>
    <div class="w-full max-w-8xl mx-auto p-4 md:p-6 space-y-5">
        <RoomDashboard />

        <RoomSearch v-model="searchData" v-model:activeTab="activeTab" />

        <RoomList
            :loading="loading"
            :rooms="filteredRooms"
            :expandedRooms="expandedRooms"
            @toggle="toggleRoom"
        />
    </div>
</template>
